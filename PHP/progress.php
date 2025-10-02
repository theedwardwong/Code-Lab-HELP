<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];

// Get overall statistics
$stats_query = "
    SELECT 
        COUNT(DISTINCT sp.lesson_id) as lessons_started,
        COUNT(DISTINCT CASE WHEN sp.status = 'completed' THEN sp.lesson_id END) as lessons_completed,
        COALESCE(SUM(sp.time_spent_minutes), 0) as total_time,
        COALESCE(SUM(CASE WHEN es.status = 'passed' THEN es.score ELSE 0 END), 0) as total_points
    FROM student_progress sp
    LEFT JOIN exercise_submissions es ON es.student_id = sp.student_id
    WHERE sp.student_id = ?
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $student_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get total available lessons
$total_lessons_result = $conn->query("SELECT COUNT(*) as total FROM lessons WHERE is_published = 1");
$total_lessons = $total_lessons_result->fetch_assoc()['total'];

// Get progress by lesson
$progress_query = "
    SELECT 
        l.id, l.title, l.category, l.difficulty, l.duration_minutes,
        sp.status, sp.completion_percentage, sp.started_at, sp.completed_at,
        sp.time_spent_minutes,
        COUNT(DISTINCT e.id) as total_exercises,
        COUNT(DISTINCT es.id) as completed_exercises,
        COALESCE(SUM(CASE WHEN es.status = 'passed' THEN es.score ELSE 0 END), 0) as earned_points
    FROM lessons l
    LEFT JOIN student_progress sp ON sp.lesson_id = l.id AND sp.student_id = ?
    LEFT JOIN exercises e ON e.lesson_id = l.id
    LEFT JOIN exercise_submissions es ON es.exercise_id = e.id AND es.student_id = ?
    WHERE l.is_published = 1
    GROUP BY l.id
    ORDER BY l.order_index
";
$progress_stmt = $conn->prepare($progress_query);
$progress_stmt->bind_param("ii", $student_id, $student_id);
$progress_stmt->execute();
$lessons = $progress_stmt->get_result();

// Get recent submissions
$recent_query = "
    SELECT 
        es.*, e.title as exercise_title, l.title as lesson_title
    FROM exercise_submissions es
    JOIN exercises e ON e.id = es.exercise_id
    JOIN lessons l ON l.id = e.lesson_id
    WHERE es.student_id = ?
    ORDER BY es.submitted_at DESC
    LIMIT 5
";
$recent_stmt = $conn->prepare($recent_query);
$recent_stmt->bind_param("i", $student_id);
$recent_stmt->execute();
$recent_submissions = $recent_stmt->get_result();

// Calculate overall completion percentage
$completion_percentage = $total_lessons > 0 ? 
    round(($stats['lessons_completed'] / $total_lessons) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Progress - Code Lab @ HELP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #2e3f54;
            color: white;
        }

        /* Navbar */
        .navbar {
            background-color: #111;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 1.5rem;
        }

        .nav-links li a {
            color: white;
            text-decoration: none;
        }

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background-color: #2e3f54;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Main Content */
        .container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #358efb;
        }

        .stat-card h3 {
            font-size: 0.9rem;
            color: #aaa;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #358efb;
        }

        .stat-card .subtitle {
            font-size: 0.85rem;
            color: #888;
            margin-top: 0.3rem;
        }

        /* Progress Overview */
        .progress-overview {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .progress-overview h2 {
            margin-bottom: 1rem;
        }

        .overall-progress-bar {
            background-color: #2e3f54;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            position: relative;
        }

        .overall-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #358efb, #4caf50);
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }

        /* Lessons Progress */
        .lessons-section {
            margin-bottom: 2rem;
        }

        .lessons-section h2 {
            margin-bottom: 1rem;
        }

        .lesson-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            transition: all 0.2s;
        }

        .lesson-card:hover {
            background-color: #1f2a3a;
            transform: translateY(-2px);
        }

        .lesson-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .lesson-title {
            font-size: 1.2rem;
            font-weight: bold;
        }

        .lesson-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-completed {
            background-color: #4caf50;
            color: white;
        }

        .status-in-progress {
            background-color: #ff9800;
            color: white;
        }

        .status-not-started {
            background-color: #666;
            color: white;
        }

        .lesson-meta {
            display: flex;
            gap: 2rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #aaa;
        }

        .progress-bar {
            background-color: #2e3f54;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            background-color: #358efb;
            transition: width 0.3s ease;
        }

        .progress-text {
            font-size: 0.85rem;
            color: #aaa;
        }

        /* Recent Activity */
        .recent-activity {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
        }

        .recent-activity h2 {
            margin-bottom: 1rem;
        }

        .activity-item {
            padding: 1rem;
            border-left: 3px solid #358efb;
            margin-bottom: 1rem;
            background-color: #2e3f54;
            border-radius: 0 8px 8px 0;
        }

        .activity-title {
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .activity-meta {
            font-size: 0.85rem;
            color: #aaa;
        }

        .activity-status {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 0.5rem;
        }

        .activity-status.passed {
            background-color: #4caf50;
        }

        .activity-status.failed {
            background-color: #f44336;
        }

        /* Empty States */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }

        .cta-button {
            display: inline-block;
            margin-top: 1rem;
            padding: 0.8rem 1.5rem;
            background-color: #358efb;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
        }

        .cta-button:hover {
            background-color: #2a72c9;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">Code Lab @ HELP</div>
        <ul class="nav-links">
            <li><a href="studentDashboard.php">Dashboard</a></li>
            <li><a href="browser.php">Browse</a></li>
            <li><a href="exercises.php">Exercises</a></li>
            <li><a href="progress.php">Progress</a></li>
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="page-header">
            <h1>My Learning Progress</h1>
            <p style="color: #aaa;">Track your coding journey and achievements</p>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Lessons Completed</h3>
                <div class="value"><?php echo $stats['lessons_completed']; ?></div>
                <div class="subtitle">out of <?php echo $total_lessons; ?> available</div>
            </div>

            <div class="stat-card">
                <h3>Total Points Earned</h3>
                <div class="value"><?php echo $stats['total_points']; ?></div>
                <div class="subtitle">from completed exercises</div>
            </div>

            <div class="stat-card">
                <h3>Time Spent Learning</h3>
                <div class="value"><?php echo $stats['total_time']; ?></div>
                <div class="subtitle">minutes of coding practice</div>
            </div>

            <div class="stat-card">
                <h3>Overall Progress</h3>
                <div class="value"><?php echo $completion_percentage; ?>%</div>
                <div class="subtitle">course completion</div>
            </div>
        </div>

        <!-- Overall Progress Bar -->
        <div class="progress-overview">
            <h2>Overall Course Progress</h2>
            <div class="overall-progress-bar">
                <div class="overall-progress-fill" style="width: <?php echo $completion_percentage; ?>%">
                    <?php echo $completion_percentage; ?>%
                </div>
            </div>
        </div>

        <!-- Lessons Progress -->
        <div class="lessons-section">
            <h2>Lessons Progress</h2>
            
            <?php if ($lessons->num_rows > 0): ?>
                <?php while ($lesson = $lessons->fetch_assoc()): ?>
                    <?php
                    $status = $lesson['status'] ?? 'not_started';
                    $percentage = $lesson['completion_percentage'] ?? 0;
                    $status_class = 'status-' . str_replace('_', '-', $status);
                    $status_text = ucwords(str_replace('_', ' ', $status));
                    ?>
                    <div class="lesson-card">
                        <div class="lesson-header">
                            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                            <span class="lesson-status <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </div>

                        <div class="lesson-meta">
                            <span>Category: <?php echo ucfirst($lesson['category']); ?></span>
                            <span>Difficulty: <?php echo ucfirst($lesson['difficulty']); ?></span>
                            <span>Exercises: <?php echo $lesson['completed_exercises']; ?>/<?php echo $lesson['total_exercises']; ?></span>
                            <span>Points: <?php echo $lesson['earned_points']; ?></span>
                        </div>

                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="progress-text"><?php echo $percentage; ?>% Complete</div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No lessons available yet</h3>
                    <p>Check back soon for new coding lessons!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Recent Activity -->
        <div class="recent-activity">
            <h2>Recent Submissions</h2>
            
            <?php if ($recent_submissions->num_rows > 0): ?>
                <?php while ($submission = $recent_submissions->fetch_assoc()): ?>
                    <div class="activity-item">
                        <div class="activity-title">
                            <?php echo htmlspecialchars($submission['exercise_title']); ?>
                            <span class="activity-status <?php echo $submission['status']; ?>">
                                <?php echo strtoupper($submission['status']); ?>
                            </span>
                        </div>
                        <div class="activity-meta">
                            <?php echo htmlspecialchars($submission['lesson_title']); ?> • 
                            Score: <?php echo $submission['score']; ?> points • 
                            <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No submissions yet</h3>
                    <p>Start completing exercises to track your progress!</p>
                    <a href="exercises.php" class="cta-button">Go to Exercises</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'login.php';
            }
        }
    </script>
</body>
</html>