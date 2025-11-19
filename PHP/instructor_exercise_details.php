<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];
$exercise_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($exercise_id === 0) {
    header("Location: instructor_lessons.php");
    exit();
}

// Get exercise details with lesson info
$exercise_query = $conn->prepare("
    SELECT e.*, l.title as lesson_title, l.category 
    FROM exercises e
    INNER JOIN lessons l ON l.id = e.lesson_id
    WHERE e.id = ?
");
$exercise_query->bind_param("i", $exercise_id);
$exercise_query->execute();
$exercise_result = $exercise_query->get_result();

if ($exercise_result->num_rows === 0) {
    header("Location: instructor_lessons.php");
    exit();
}

$exercise = $exercise_result->fetch_assoc();

// Get assignment statistics
$stats_query = $conn->prepare("
    SELECT 
        COUNT(DISTINCT a.id) as total_assigned,
        COUNT(DISTINCT CASE WHEN a.status = 'submitted' THEN a.id END) as submitted_count,
        COUNT(DISTINCT CASE WHEN a.status = 'graded' THEN a.id END) as graded_count,
        COUNT(DISTINCT CASE WHEN a.status = 'pending' THEN a.id END) as pending_count
    FROM assignments a
    WHERE a.exercise_id = ?
");
$stats_query->bind_param("i", $exercise_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// Get students who have this assignment
$students_query = $conn->prepare("
    SELECT 
        u.full_name,
        u.email,
        a.status,
        a.grade,
        a.due_date,
        a.submitted_at,
        a.graded_at
    FROM assignments a
    INNER JOIN users u ON u.id = a.student_id
    WHERE a.exercise_id = ?
    ORDER BY a.created_at DESC
");
$students_query->bind_param("i", $exercise_id);
$students_query->execute();
$students_result = $students_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo htmlspecialchars($exercise['title']); ?> - Code Lab @ HELP</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background-color: #1a2332;
      color: #e4e7eb;
    }
    .navbar {
      background-color: #0f1419;
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    .logo a {
      color: white;
      text-decoration: none;
      font-weight: 600;
      font-size: 1.2rem;
    }
    .nav-links {
      list-style: none;
      display: flex;
      gap: 0.5rem;
      margin: 0;
      padding: 0;
    }
    .nav-links li a {
      color: #9ca3af;
      text-decoration: none;
      padding: 0.6rem 1rem;
      border-radius: 6px;
      font-size: 0.95rem;
    }
    .nav-links li a:hover,
    .nav-links li a.active {
      background-color: #1e293b;
      color: white;
    }
    .nav-icons {
      display: flex;
      align-items: center;
      gap: 1.2rem;
    }
    .username {
      font-weight: 600;
      color: #e4e7eb;
    }
    .logout-btn {
      background-color: #1e293b;
      color: white;
      border: 1px solid #334155;
      padding: 0.5rem 1.2rem;
      cursor: pointer;
      border-radius: 6px;
      font-size: 0.9rem;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: #3b82f6;
      text-decoration: none;
      margin-bottom: 1.5rem;
      font-size: 0.95rem;
    }
    .back-btn:hover {
      color: #60a5fa;
    }
    .exercise-header {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      border: 1px solid #334155;
    }
    h1 {
      font-size: 2.5rem;
      margin-bottom: 1rem;
      color: #f1f5f9;
    }
    .exercise-meta {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
      margin-bottom: 1.5rem;
    }
    .badge {
      padding: 0.4rem 1rem;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
    }
    .badge-frontend { background-color: #ff6b6b; color: white; }
    .badge-backend { background-color: #4ecdc4; color: white; }
    .badge-fullstack { background-color: #95e1d3; color: white; }
    .difficulty {
      padding: 0.4rem 1rem;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 600;
    }
    .difficulty-easy { background-color: #4caf50; color: white; }
    .difficulty-medium { background-color: #ff9800; color: white; }
    .difficulty-hard { background-color: #f44336; color: white; }
    
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 1rem;
      margin-bottom: 2rem;
    }
    .stat-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 1.5rem;
      border: 1px solid #334155;
      text-align: center;
    }
    .stat-number {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    .stat-label {
      color: #94a3b8;
      font-size: 0.9rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .stat-card:nth-child(1) .stat-number { color: #60a5fa; }
    .stat-card:nth-child(2) .stat-number { color: #fbbf24; }
    .stat-card:nth-child(3) .stat-number { color: #34d399; }
    .stat-card:nth-child(4) .stat-number { color: #a78bfa; }
    
    .content-section {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      border: 1px solid #334155;
    }
    .section-title {
      font-size: 1.5rem;
      color: #f1f5f9;
      margin-bottom: 1rem;
      font-weight: 600;
    }
    .code-block {
      background-color: #0f172a;
      border: 1px solid #334155;
      border-radius: 8px;
      padding: 1.5rem;
      overflow-x: auto;
      font-family: 'Courier New', monospace;
      color: #e4e7eb;
      white-space: pre-wrap;
      margin-top: 1rem;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }
    th {
      background-color: #0f172a;
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: #f1f5f9;
      border-bottom: 2px solid #334155;
    }
    td {
      padding: 1rem;
      border-bottom: 1px solid #334155;
      color: #cbd5e1;
    }
    tr:hover {
      background-color: #0f172a;
    }
    .status-badge {
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .status-pending { background-color: #6b7280; color: white; }
    .status-submitted { background-color: #3b82f6; color: white; }
    .status-graded { background-color: #10b981; color: white; }
    .empty-state {
      text-align: center;
      padding: 3rem;
      color: #94a3b8;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="instructorDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="instructorDashboard.php">Dashboard</a></li>
      <li><a href="instructor_lessons.php" class="active">Browse</a></li>
      <li><a href="instructor_create_exercise.php">Create Exercise</a></li>
      <li><a href="instructor_assignments.php">Assignments</a></li>
      <li><a href="instructor_submissions.php">Submissions</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($instructor_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <div class="container">
    <a href="instructor_lesson_details.php?id=<?php echo $exercise['lesson_id']; ?>" class="back-btn">
      ← Back to Lesson
    </a>

    <div class="exercise-header">
      <h1><?php echo htmlspecialchars($exercise['title']); ?></h1>
      <p style="color: #94a3b8; margin-bottom: 1.5rem;">
        Lesson: <?php echo htmlspecialchars($exercise['lesson_title']); ?>
      </p>
      <div class="exercise-meta">
        <span class="badge badge-<?php echo $exercise['category']; ?>">
          <?php echo strtoupper($exercise['category']); ?>
        </span>
        <span class="difficulty difficulty-<?php echo $exercise['difficulty']; ?>">
          <?php echo strtoupper($exercise['difficulty']); ?>
        </span>
        <span style="color: #94a3b8;">📊 <?php echo $exercise['points']; ?> points</span>
        <span style="color: #94a3b8;">📝 Order: <?php echo $exercise['order_index']; ?></span>
      </div>
    </div>

    <!-- Statistics -->
    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_assigned']; ?></div>
        <div class="stat-label">Total Assigned</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['pending_count']; ?></div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['submitted_count']; ?></div>
        <div class="stat-label">Submitted</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['graded_count']; ?></div>
        <div class="stat-label">Graded</div>
      </div>
    </div>

    <!-- Description -->
    <?php if (!empty($exercise['description'])): ?>
    <div class="content-section">
      <h2 class="section-title">Description</h2>
      <p style="color: #cbd5e1;"><?php echo nl2br(htmlspecialchars($exercise['description'])); ?></p>
    </div>
    <?php endif; ?>

    <!-- Instructions -->
    <div class="content-section">
      <h2 class="section-title">Instructions</h2>
      <p style="color: #cbd5e1;"><?php echo nl2br(htmlspecialchars($exercise['instructions'])); ?></p>
    </div>

    <!-- Starter Code -->
    <?php if (!empty($exercise['starter_code'])): ?>
    <div class="content-section">
      <h2 class="section-title">Starter Code</h2>
      <div class="code-block"><?php echo htmlspecialchars($exercise['starter_code']); ?></div>
    </div>
    <?php endif; ?>

    <!-- Solution Code -->
    <?php if (!empty($exercise['solution_code'])): ?>
    <div class="content-section">
      <h2 class="section-title">Solution Code</h2>
      <div class="code-block"><?php echo htmlspecialchars($exercise['solution_code']); ?></div>
    </div>
    <?php endif; ?>

    <!-- Assigned Students -->
    <div class="content-section">
      <h2 class="section-title">Assigned Students</h2>
      
      <?php if ($students_result->num_rows > 0): ?>
        <table>
          <thead>
            <tr>
              <th>Student Name</th>
              <th>Email</th>
              <th>Status</th>
              <th>Grade</th>
              <th>Due Date</th>
              <th>Submitted</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($student = $students_result->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                <td><?php echo htmlspecialchars($student['email']); ?></td>
                <td>
                  <span class="status-badge status-<?php echo $student['status']; ?>">
                    <?php echo strtoupper($student['status']); ?>
                  </span>
                </td>
                <td>
                  <?php echo $student['grade'] ? $student['grade'] . '%' : '-'; ?>
                </td>
                <td>
                  <?php echo $student['due_date'] ? date('M d, Y', strtotime($student['due_date'])) : '-'; ?>
                </td>
                <td>
                  <?php echo $student['submitted_at'] ? date('M d, Y H:i', strtotime($student['submitted_at'])) : '-'; ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <p>No students assigned yet.</p>
          <a href="instructor_assignments.php" style="color: #3b82f6;">Assign this exercise to students</a>
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