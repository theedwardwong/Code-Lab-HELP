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

// Get assignments for this student
// Either "all students" assignments or specifically assigned to them
$assignments_query = "
    SELECT 
        a.*,
        e.title as exercise_title,
        e.difficulty as exercise_difficulty,
        e.points as exercise_points,
        l.title as lesson_title,
        l.category,
        u.full_name as instructor_name,
        COALESCE(ast.status, 'pending') as my_status,
        COALESCE(ast.attempts, 0) as my_attempts,
        (SELECT COUNT(*) FROM exercise_submissions es 
         WHERE es.exercise_id = e.id AND es.student_id = ? AND es.status = 'passed') as completed
    FROM assignments a
    JOIN exercises e ON e.id = a.exercise_id
    JOIN lessons l ON l.id = e.lesson_id
    JOIN users u ON u.id = a.instructor_id
    LEFT JOIN assignment_students ast ON ast.assignment_id = a.id AND ast.student_id = ?
    WHERE a.assigned_to = 'all' 
       OR (a.assigned_to = 'specific' AND ast.student_id = ?)
    ORDER BY 
        CASE WHEN a.due_date IS NULL THEN 1 ELSE 0 END,
        a.due_date ASC,
        a.created_at DESC
";

$stmt = $conn->prepare($assignments_query);
$stmt->bind_param("iii", $student_id, $student_id, $student_id);
$stmt->execute();
$assignments = $stmt->get_result();

// Calculate statistics
$stats = [
    'total' => 0,
    'pending' => 0,
    'in_progress' => 0,
    'completed' => 0,
    'overdue' => 0
];

$assignments_array = [];
while ($row = $assignments->fetch_assoc()) {
    $stats['total']++;
    
    if ($row['completed'] > 0) {
        $stats['completed']++;
    } elseif ($row['my_status'] === 'in_progress') {
        $stats['in_progress']++;
    } else {
        $stats['pending']++;
    }
    
    if ($row['due_date'] && strtotime($row['due_date']) < time() && $row['completed'] == 0) {
        $stats['overdue']++;
    }
    
    $assignments_array[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assignments - Code Lab @ HELP</title>
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
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
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
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
            border-left: 4px solid #358efb;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #358efb;
        }

        .stat-label {
            color: #aaa;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }

        .stat-card.pending { border-left-color: #ff9800; }
        .stat-card.pending .stat-value { color: #ff9800; }

        .stat-card.in-progress { border-left-color: #2196f3; }
        .stat-card.in-progress .stat-value { color: #2196f3; }

        .stat-card.completed { border-left-color: #4caf50; }
        .stat-card.completed .stat-value { color: #4caf50; }

        .stat-card.overdue { border-left-color: #f44336; }
        .stat-card.overdue .stat-value { color: #f44336; }

        /* Assignments Grid */
        .assignments-grid {
            display: grid;
            gap: 1.5rem;
        }

        .assignment-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #358efb;
            transition: all 0.2s;
        }

        .assignment-card:hover {
            background-color: #1f2a3a;
            transform: translateY(-2px);
        }

        .assignment-card.completed {
            border-left-color: #4caf50;
            opacity: 0.8;
        }

        .assignment-card.overdue {
            border-left-color: #f44336;
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .assignment-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .exercise-name {
            color: #aaa;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-pending {
            background-color: #ff9800;
            color: white;
        }

        .status-in_progress {
            background-color: #2196f3;
            color: white;
        }

        .status-completed {
            background-color: #4caf50;
            color: white;
        }

        .assignment-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background-color: #2e3f54;
            border-radius: 8px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
        }

        .meta-label {
            color: #aaa;
            font-size: 0.8rem;
            margin-bottom: 0.3rem;
        }

        .meta-value {
            font-weight: bold;
        }

        .due-date {
            color: #ff9800;
        }

        .due-date.overdue {
            color: #f44336;
        }

        .actions {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #358efb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2a72c9;
        }

        .btn-success {
            background-color: #4caf50;
            color: white;
        }

        .btn-disabled {
            background-color: #666;
            color: #aaa;
            cursor: not-allowed;
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

        .difficulty-badge {
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .difficulty-easy { background-color: #4caf50; }
        .difficulty-medium { background-color: #ff9800; }
        .difficulty-hard { background-color: #f44336; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Code Lab @ HELP</div>
        <ul class="nav-links">
            <li><a href="studentDashboard.php">Dashboard</a></li>
            <li><a href="browser.php">Browse</a></li>
            <li><a href="exercises.php">Exercises</a></li>
            <li><a href="my_assignments.php">My Assignments</a></li>
            <li><a href="progress.php">Progress</a></li>
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>My Assignments</h1>
            <p style="color: #aaa;">Complete your assigned coding exercises before the due date</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Assignments</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card in-progress">
                <div class="stat-value"><?php echo $stats['in_progress']; ?></div>
                <div class="stat-label">In Progress</div>
            </div>
            <div class="stat-card completed">
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <?php if ($stats['overdue'] > 0): ?>
            <div class="stat-card overdue">
                <div class="stat-value"><?php echo $stats['overdue']; ?></div>
                <div class="stat-label">Overdue</div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Assignments List -->
        <div class="assignments-grid">
            <?php if (count($assignments_array) > 0): ?>
                <?php foreach ($assignments_array as $assignment): ?>
                    <?php
                    $is_completed = $assignment['completed'] > 0;
                    $is_overdue = $assignment['due_date'] && strtotime($assignment['due_date']) < time() && !$is_completed;
                    $card_class = $is_completed ? 'completed' : ($is_overdue ? 'overdue' : '');
                    
                    $max_attempts_reached = $assignment['max_attempts'] > 0 && 
                                          $assignment['my_attempts'] >= $assignment['max_attempts'];
                    ?>
                    <div class="assignment-card <?php echo $card_class; ?>">
                        <div class="assignment-header">
                            <div>
                                <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                <div class="exercise-name">
                                    <?php echo htmlspecialchars($assignment['exercise_title']); ?> • 
                                    <?php echo htmlspecialchars($assignment['lesson_title']); ?>
                                    <span class="difficulty-badge difficulty-<?php echo $assignment['exercise_difficulty']; ?>">
                                        <?php echo strtoupper($assignment['exercise_difficulty']); ?>
                                    </span>
                                </div>
                            </div>
                            <span class="status-badge status-<?php echo $is_completed ? 'completed' : $assignment['my_status']; ?>">
                                <?php echo $is_completed ? 'COMPLETED' : strtoupper(str_replace('_', ' ', $assignment['my_status'])); ?>
                            </span>
                        </div>

                        <?php if ($assignment['description']): ?>
                            <p style="color: #ccc; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($assignment['description']); ?>
                            </p>
                        <?php endif; ?>

                        <div class="assignment-meta">
                            <div class="meta-item">
                                <span class="meta-label">Instructor</span>
                                <span class="meta-value"><?php echo htmlspecialchars($assignment['instructor_name']); ?></span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Due Date</span>
                                <span class="meta-value <?php echo $is_overdue ? 'due-date overdue' : 'due-date'; ?>">
                                    <?php echo $assignment['due_date'] ? date('M j, Y g:i A', strtotime($assignment['due_date'])) : 'No deadline'; ?>
                                </span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Points</span>
                                <span class="meta-value"><?php echo $assignment['exercise_points']; ?> pts</span>
                            </div>
                            <div class="meta-item">
                                <span class="meta-label">Attempts</span>
                                <span class="meta-value">
                                    <?php echo $assignment['my_attempts']; ?> / 
                                    <?php echo $assignment['max_attempts'] > 0 ? $assignment['max_attempts'] : '∞'; ?>
                                </span>
                            </div>
                        </div>

                        <div class="actions">
                            <?php if ($is_completed): ?>
                                <button class="btn btn-success" disabled>✓ Completed</button>
                            <?php elseif ($max_attempts_reached): ?>
                                <button class="btn btn-disabled" disabled>Max Attempts Reached</button>
                            <?php else: ?>
                                <a href="exercises.php?exercise=<?php echo $assignment['exercise_id']; ?>" class="btn btn-primary">
                                    <?php echo $assignment['my_status'] === 'in_progress' ? 'Continue' : 'Start'; ?> Assignment
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No assignments yet</h3>
                    <p>Your instructor hasn't assigned any exercises to you yet.</p>
                    <p style="margin-top: 1rem;">Check back later or browse available exercises in the meantime.</p>
                    <a href="exercises.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Exercises</a>
                </div>
            <?php endif; ?>
        </div>
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