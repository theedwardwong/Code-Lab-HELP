<?php
session_start();
include 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

// Get search query
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$search_term = '%' . $search_query . '%';

// Initialize results arrays
$lessons_results = [];
$exercises_results = [];
$assignments_results = [];

if (!empty($search_query)) {
    // Search Lessons
    $lessons_stmt = $conn->prepare("
        SELECT id, title, description, category, difficulty, duration_minutes
        FROM lessons 
        WHERE is_published = 1 
        AND (title LIKE ? OR description LIKE ? OR category LIKE ?)
        ORDER BY 
            CASE 
                WHEN title LIKE ? THEN 1
                WHEN description LIKE ? THEN 2
                ELSE 3
            END,
            order_index
        LIMIT 10
    ");
    $lessons_stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $search_term);
    $lessons_stmt->execute();
    $lessons_results = $lessons_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Search Exercises
    $exercises_stmt = $conn->prepare("
        SELECT e.id, e.title, e.description, e.difficulty, e.points,
               l.title as lesson_title, l.category
        FROM exercises e
        JOIN lessons l ON l.id = e.lesson_id
        WHERE l.is_published = 1
        AND (e.title LIKE ? OR e.description LIKE ? OR e.instructions LIKE ?)
        ORDER BY 
            CASE 
                WHEN e.title LIKE ? THEN 1
                WHEN e.description LIKE ? THEN 2
                ELSE 3
            END
        LIMIT 10
    ");
    $exercises_stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $search_term);
    $exercises_stmt->execute();
    $exercises_results = $exercises_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Search Assignments (for students)
    if ($user_role === 'student') {
        $assignments_stmt = $conn->prepare("
            SELECT DISTINCT
                a.id, a.title, a.description, a.due_date,
                e.title as exercise_title,
                l.title as lesson_title,
                u.full_name as instructor_name
            FROM assignments a
            JOIN exercises e ON e.id = a.exercise_id
            JOIN lessons l ON l.id = e.lesson_id
            JOIN users u ON u.id = a.instructor_id
            LEFT JOIN assignment_students ast ON ast.assignment_id = a.id AND ast.student_id = ?
            WHERE (a.assigned_to = 'all' OR (a.assigned_to = 'specific' AND ast.student_id = ?))
            AND (a.title LIKE ? OR a.description LIKE ? OR e.title LIKE ?)
            ORDER BY 
                CASE 
                    WHEN a.title LIKE ? THEN 1
                    WHEN e.title LIKE ? THEN 2
                    ELSE 3
                END
            LIMIT 10
        ");
        $assignments_stmt->bind_param("iisssss", $user_id, $user_id, $search_term, $search_term, $search_term, $search_term, $search_term);
        $assignments_stmt->execute();
        $assignments_results = $assignments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

$total_results = count($lessons_results) + count($exercises_results) + count($assignments_results);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Code Lab @ HELP</title>
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

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .search-header {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .search-box {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .search-box input {
            flex: 1;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-box button {
            background-color: #358efb;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
        }

        .search-box button:hover {
            background-color: #2a72c9;
        }

        .results-section {
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #358efb;
        }

        .section-header h2 {
            font-size: 1.5rem;
        }

        .result-count {
            color: #aaa;
            font-size: 0.9rem;
        }

        .results-grid {
            display: grid;
            gap: 1rem;
        }

        .result-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #358efb;
            transition: all 0.2s;
            cursor: pointer;
        }

        .result-card:hover {
            background-color: #1f2a3a;
            transform: translateX(5px);
        }

        .result-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            color: #358efb;
        }

        .result-description {
            color: #ccc;
            margin-bottom: 0.8rem;
            line-height: 1.5;
        }

        .result-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .meta-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .badge-category {
            background-color: #2196f3;
        }

        .badge-difficulty {
            background-color: #ff9800;
        }

        .badge-points {
            background-color: #4caf50;
        }

        .badge-lesson {
            background-color: #9c27b0;
        }

        .empty-state {
            background-color: #1a2332;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
            color: #888;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
        }

        .highlight {
            background-color: #ffd54f;
            color: #000;
            padding: 0.1rem 0.2rem;
            border-radius: 2px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Code Lab @ HELP</div>
        <ul class="nav-links">
            <?php if ($user_role === 'student'): ?>
                <li><a href="studentDashboard.php">Dashboard</a></li>
                <li><a href="browser.php">Browse</a></li>
                <li><a href="exercises.php">Exercises</a></li>
                <li><a href="my_assignments.php">My Assignments</a></li>
                <li><a href="progress.php">Progress</a></li>
            <?php elseif ($user_role === 'instructor'): ?>
                <li><a href="instructorDashboard.php">Dashboard</a></li>
                <li><a href="create_exercise.php">Create Exercise</a></li>
                <li><a href="manage_assignments.php">Assignments</a></li>
                <li><a href="view_submissions.php">Submissions</a></li>
            <?php else: ?>
                <li><a href="adminDashboard.php">Dashboard</a></li>
            <?php endif; ?>
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($user_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <div class="search-header">
            <h1>Search Results</h1>
            <form action="search.php" method="GET" class="search-box">
                <input type="text" name="q" placeholder="Search for lessons, exercises, and more..." 
                       value="<?php echo htmlspecialchars($search_query); ?>" required>
                <button type="submit">Search</button>
            </form>
            <?php if (!empty($search_query)): ?>
                <p style="margin-top: 1rem; color: #aaa;">
                    Found <?php echo $total_results; ?> results for "<?php echo htmlspecialchars($search_query); ?>"
                </p>
            <?php endif; ?>
        </div>

        <?php if (empty($search_query)): ?>
            <div class="empty-state">
                <h3>Start searching</h3>
                <p>Enter a keyword to search for lessons, exercises, and assignments</p>
            </div>
        <?php elseif ($total_results === 0): ?>
            <div class="empty-state">
                <h3>No results found</h3>
                <p>Try searching with different keywords or check your spelling</p>
            </div>
        <?php else: ?>
            
            <!-- Lessons Results -->
            <?php if (!empty($lessons_results)): ?>
                <div class="results-section">
                    <div class="section-header">
                        <h2>Lessons</h2>
                        <span class="result-count"><?php echo count($lessons_results); ?> found</span>
                    </div>
                    <div class="results-grid">
                        <?php foreach ($lessons_results as $lesson): ?>
                            <div class="result-card" onclick="window.location.href='exercises.php?lesson=<?php echo $lesson['id']; ?>'">
                                <div class="result-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                                <?php if ($lesson['description']): ?>
                                    <div class="result-description">
                                        <?php echo htmlspecialchars(substr($lesson['description'], 0, 150)); ?>...
                                    </div>
                                <?php endif; ?>
                                <div class="result-meta">
                                    <span class="meta-badge badge-category">
                                        <?php echo strtoupper($lesson['category']); ?>
                                    </span>
                                    <span class="meta-badge badge-difficulty">
                                        <?php echo strtoupper($lesson['difficulty']); ?>
                                    </span>
                                    <span style="color: #aaa; font-size: 0.9rem;">
                                        <?php echo $lesson['duration_minutes']; ?> minutes
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Exercises Results -->
            <?php if (!empty($exercises_results)): ?>
                <div class="results-section">
                    <div class="section-header">
                        <h2>Exercises</h2>
                        <span class="result-count"><?php echo count($exercises_results); ?> found</span>
                    </div>
                    <div class="results-grid">
                        <?php foreach ($exercises_results as $exercise): ?>
                            <div class="result-card" onclick="window.location.href='exercises.php?exercise=<?php echo $exercise['id']; ?>'">
                                <div class="result-title"><?php echo htmlspecialchars($exercise['title']); ?></div>
                                <?php if ($exercise['description']): ?>
                                    <div class="result-description">
                                        <?php echo htmlspecialchars(substr($exercise['description'], 0, 150)); ?>...
                                    </div>
                                <?php endif; ?>
                                <div class="result-meta">
                                    <span class="meta-badge badge-lesson">
                                        <?php echo htmlspecialchars($exercise['lesson_title']); ?>
                                    </span>
                                    <span class="meta-badge badge-difficulty">
                                        <?php echo strtoupper($exercise['difficulty']); ?>
                                    </span>
                                    <span class="meta-badge badge-points">
                                        <?php echo $exercise['points']; ?> pts
                                    </span>
                                    <span style="color: #aaa; font-size: 0.9rem;">
                                        <?php echo ucfirst($exercise['category']); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Assignments Results (Students Only) -->
            <?php if ($user_role === 'student' && !empty($assignments_results)): ?>
                <div class="results-section">
                    <div class="section-header">
                        <h2>My Assignments</h2>
                        <span class="result-count"><?php echo count($assignments_results); ?> found</span>
                    </div>
                    <div class="results-grid">
                        <?php foreach ($assignments_results as $assignment): ?>
                            <div class="result-card" onclick="window.location.href='my_assignments.php#assignment-<?php echo $assignment['id']; ?>'">
                                <div class="result-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                <div class="result-description">
                                    Exercise: <?php echo htmlspecialchars($assignment['exercise_title']); ?>
                                    <?php if ($assignment['description']): ?>
                                        <br><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)); ?>...
                                    <?php endif; ?>
                                </div>
                                <div class="result-meta">
                                    <span class="meta-badge badge-lesson">
                                        <?php echo htmlspecialchars($assignment['lesson_title']); ?>
                                    </span>
                                    <span style="color: #aaa; font-size: 0.9rem;">
                                        By <?php echo htmlspecialchars($assignment['instructor_name']); ?>
                                    </span>
                                    <?php if ($assignment['due_date']): ?>
                                        <span style="color: #ff9800; font-size: 0.9rem;">
                                            Due: <?php echo date('M j, Y', strtotime($assignment['due_date'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'logout.php';
            }
        }
    </script>
</body>
</html>