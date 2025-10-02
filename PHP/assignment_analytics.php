<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['full_name'];

// Get assignment ID from URL
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($assignment_id === 0) {
    header("Location: manage_assignments.php");
    exit();
}

// Get assignment details
$assignment_query = "
    SELECT 
        a.*,
        e.title as exercise_title,
        e.points as max_points,
        l.title as lesson_title
    FROM assignments a
    JOIN exercises e ON e.id = a.exercise_id
    JOIN lessons l ON l.id = e.lesson_id
    WHERE a.id = ? AND a.instructor_id = ?
";
$stmt = $conn->prepare($assignment_query);
$stmt->bind_param("ii", $assignment_id, $instructor_id);
$stmt->execute();
$assignment = $stmt->get_result()->fetch_assoc();

if (!$assignment) {
    header("Location: manage_assignments.php");
    exit();
}

// Get student progress on this assignment
$progress_query = "
    SELECT 
        u.id as student_id,
        u.full_name,
        u.email,
        COALESCE(ast.status, 'pending') as status,
        COALESCE(ast.attempts, 0) as attempts,
        (SELECT es.submitted_at FROM exercise_submissions es 
         WHERE es.exercise_id = ? AND es.student_id = u.id 
         ORDER BY es.submitted_at DESC LIMIT 1) as last_submission,
        (SELECT es.status FROM exercise_submissions es 
         WHERE es.exercise_id = ? AND es.student_id = u.id 
         ORDER BY es.submitted_at DESC LIMIT 1) as submission_status,
        (SELECT es.score FROM exercise_submissions es 
         WHERE es.exercise_id = ? AND es.student_id = u.id 
         ORDER BY es.submitted_at DESC LIMIT 1) as latest_score
    FROM users u
    LEFT JOIN assignment_students ast ON ast.assignment_id = ? AND ast.student_id = u.id
    WHERE (? = 'all' AND u.role = 'student')
       OR (? = 'specific' AND ast.student_id = u.id)
    ORDER BY u.full_name
";

$stmt2 = $conn->prepare($progress_query);
$stmt2->bind_param("iiiiss", 
    $assignment['exercise_id'], 
    $assignment['exercise_id'], 
    $assignment['exercise_id'],
    $assignment_id,
    $assignment['assigned_to'],
    $assignment['assigned_to']
);
$stmt2->execute();
$students = $stmt2->get_result();

// Calculate statistics
$stats = [
    'total' => 0,
    'submitted' => 0,
    'in_progress' => 0,
    'pending' => 0,
    'passed' => 0,
    'avg_score' => 0,
    'avg_attempts' => 0
];

$students_array = [];
$total_score = 0;
$total_attempts = 0;
$score_count = 0;

while ($row = $students->fetch_assoc()) {
    $stats['total']++;
    
    if ($row['status'] === 'submitted') {
        $stats['submitted']++;
    } elseif ($row['status'] === 'in_progress') {
        $stats['in_progress']++;
    } else {
        $stats['pending']++;
    }
    
    if ($row['submission_status'] === 'passed') {
        $stats['passed']++;
    }
    
    if ($row['latest_score'] !== null) {
        $total_score += $row['latest_score'];
        $score_count++;
    }
    
    $total_attempts += $row['attempts'];
    $students_array[] = $row;
}

$stats['avg_score'] = $score_count > 0 ? round($total_score / $score_count, 1) : 0;
$stats['avg_attempts'] = $stats['total'] > 0 ? round($total_attempts / $stats['total'], 1) : 0;
$stats['completion_rate'] = $stats['total'] > 0 ? round(($stats['submitted'] / $stats['total']) * 100, 1) : 0;
$stats['pass_rate'] = $stats['submitted'] > 0 ? round(($stats['passed'] / $stats['submitted']) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Analytics - Code Lab @ HELP</title>
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

        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .nav-links { list-style: none; display: flex; gap: 1.5rem; }
        .nav-links li a { color: white; text-decoration: none; }
        .nav-icons { display: flex; align-items: center; gap: 1rem; }
        .logout-btn {
            background-color: #2e3f54;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }

        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 2rem;
        }

        .back-link {
            color: #358efb;
            text-decoration: none;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .page-header {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .assignment-meta {
            display: flex;
            gap: 2rem;
            margin-top: 1rem;
            color: #aaa;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

        .stat-card.success { border-left-color: #4caf50; }
        .stat-card.success .stat-value { color: #4caf50; }

        .stat-card.warning { border-left-color: #ff9800; }
        .stat-card.warning .stat-value { color: #ff9800; }

        /* Student Table */
        .students-container {
            background-color: #1a2332;
            border-radius: 12px;
            overflow: hidden;
        }

        .table-header {
            padding: 1.5rem;
            background-color: #111b25;
            border-bottom: 2px solid #2e3f54;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #111b25;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #2e3f54;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #2e3f54;
        }

        tbody tr:hover {
            background-color: #1f2a3a;
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-pending {
            background-color: #666;
            color: white;
        }

        .status-in_progress {
            background-color: #2196f3;
            color: white;
        }

        .status-submitted {
            background-color: #ff9800;
            color: white;
        }

        .status-passed {
            background-color: #4caf50;
            color: white;
        }

        .status-failed {
            background-color: #f44336;
            color: white;
        }

        .progress-bar {
            width: 100px;
            height: 8px;
            background-color: #2e3f54;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background-color: #358efb;
            transition: width 0.3s;
        }

        .action-link {
            color: #358efb;
            text-decoration: none;
        }

        .action-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Code Lab @ HELP</div>
        <ul class="nav-links">
            <li><a href="instructorDashboard.php">Dashboard</a></li>
            <li><a href="create_exercise.php">Create Exercise</a></li>
            <li><a href="manage_assignments.php">Assignments</a></li>
            <li><a href="view_submissions.php">Submissions</a></li>
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($instructor_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <a href="manage_assignments.php" class="back-link">← Back to Assignments</a>

        <div class="page-header">
            <h1><?php echo htmlspecialchars($assignment['title']); ?></h1>
            <p style="color: #aaa; margin-top: 0.5rem;">
                <?php echo htmlspecialchars($assignment['exercise_title']); ?> • 
                <?php echo htmlspecialchars($assignment['lesson_title']); ?>
            </p>
            <div class="assignment-meta">
                <span>Created: <?php echo date('M j, Y', strtotime($assignment['created_at'])); ?></span>
                <span>Due: <?php echo $assignment['due_date'] ? date('M j, Y g:i A', strtotime($assignment['due_date'])) : 'No deadline'; ?></span>
                <span>Max Attempts: <?php echo $assignment['max_attempts'] > 0 ? $assignment['max_attempts'] : 'Unlimited'; ?></span>
                <span>Max Points: <?php echo $assignment['max_points']; ?></span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card warning">
                <div class="stat-value"><?php echo $stats['submitted']; ?></div>
                <div class="stat-label">Submitted</div>
            </div>
            <div class="stat-card success">
                <div class="stat-value"><?php echo $stats['passed']; ?></div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['completion_rate']; ?>%</div>
                <div class="stat-label">Completion Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['pass_rate']; ?>%</div>
                <div class="stat-label">Pass Rate</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['avg_score']; ?></div>
                <div class="stat-label">Avg Score</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['avg_attempts']; ?></div>
                <div class="stat-label">Avg Attempts</div>
            </div>
        </div>

        <!-- Student Progress Table -->
        <div class="students-container">
            <div class="table-header">
                <h2>Student Progress</h2>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Student Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Attempts</th>
                        <th>Latest Score</th>
                        <th>Progress</th>
                        <th>Last Submission</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_array as $student): ?>
                        <?php
                        $progress = 0;
                        if ($student['status'] === 'submitted' || $student['submission_status'] === 'passed') {
                            $progress = 100;
                        } elseif ($student['status'] === 'in_progress') {
                            $progress = 50;
                        }
                        
                        $display_status = $student['submission_status'] ?: $student['status'];
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($student['full_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $display_status; ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $display_status)); ?>
                                </span>
                            </td>
                            <td><?php echo $student['attempts']; ?> / <?php echo $assignment['max_attempts'] > 0 ? $assignment['max_attempts'] : '∞'; ?></td>
                            <td>
                                <?php if ($student['latest_score'] !== null): ?>
                                    <strong><?php echo $student['latest_score']; ?></strong> / <?php echo $assignment['max_points']; ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                </div>
                            </td>
                            <td>
                                <?php if ($student['last_submission']): ?>
                                    <?php echo date('M j, g:i A', strtotime($student['last_submission'])); ?>
                                <?php else: ?>
                                    <span style="color: #888;">Not submitted</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($student['last_submission']): ?>
                                    <a href="view_submissions.php?student=<?php echo $student['student_id']; ?>&exercise=<?php echo $assignment['exercise_id']; ?>" 
                                       class="action-link">View Code</a>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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