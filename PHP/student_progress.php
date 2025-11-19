<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$student_id = $_SESSION['user_id'];

// Get comprehensive stats
$stats_query = $conn->prepare("
    SELECT 
        COUNT(*) as total_assignments,
        COUNT(CASE WHEN status = 'graded' THEN 1 END) as completed,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
        COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted,
        AVG(CASE WHEN grade IS NOT NULL THEN grade END) as avg_grade,
        MAX(grade) as highest_grade,
        MIN(grade) as lowest_grade
    FROM assignments 
    WHERE student_id = ?
");
$stats_query->bind_param("i", $student_id);
$stats_query->execute();
$stats = $stats_query->get_result()->fetch_assoc();

// Get recent graded assignments
$recent_query = $conn->prepare("
    SELECT 
        a.grade,
        a.graded_at,
        a.feedback,
        e.title as exercise_title,
        l.title as lesson_title
    FROM assignments a
    INNER JOIN exercises e ON e.id = a.exercise_id
    INNER JOIN lessons l ON l.id = e.lesson_id
    WHERE a.student_id = ? AND a.status = 'graded'
    ORDER BY a.graded_at DESC
    LIMIT 5
");
$recent_query->bind_param("i", $student_id);
$recent_query->execute();
$recent_grades = $recent_query->get_result();

// Calculate completion rate
$completion_rate = $stats['total_assignments'] > 0 
    ? round(($stats['completed'] / $stats['total_assignments']) * 100) 
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Progress - Code Lab @ HELP</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
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
    }
    .nav-links li a {
      color: #9ca3af;
      text-decoration: none;
      padding: 0.6rem 1rem;
      border-radius: 6px;
    }
    .nav-links li a.active {
      background-color: #1e293b;
      color: white;
    }
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2.5rem 3rem;
    }
    h2 {
      font-size: 2rem;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }
    .subtitle {
      color: #94a3b8;
      margin-bottom: 2.5rem;
    }
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
    }
    .stat-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      text-align: center;
      border: 2px solid transparent;
      transition: all 0.3s;
    }
    .stat-card:nth-child(1) { border-color: #3b82f6; }
    .stat-card:nth-child(2) { border-color: #10b981; }
    .stat-card:nth-child(3) { border-color: #f59e0b; }
    .stat-card:nth-child(4) { border-color: #ef4444; }
    .stat-card:nth-child(5) { border-color: #8b5cf6; }
    .stat-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }
    .stat-number {
      font-size: 3rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }
    .stat-card:nth-child(1) .stat-number { color: #60a5fa; }
    .stat-card:nth-child(2) .stat-number { color: #34d399; }
    .stat-card:nth-child(3) .stat-number { color: #fbbf24; }
    .stat-card:nth-child(4) .stat-number { color: #f87171; }
    .stat-card:nth-child(5) .stat-number { color: #a78bfa; }
    .stat-label {
      color: #94a3b8;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 0.5px;
    }
    .progress-section {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      border: 1px solid #334155;
    }
    .section-title {
      font-size: 1.5rem;
      color: #f1f5f9;
      margin-bottom: 1.5rem;
    }
    .progress-bar {
      background-color: #0f172a;
      height: 30px;
      border-radius: 15px;
      overflow: hidden;
      margin-bottom: 0.5rem;
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #3b82f6, #8b5cf6);
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-weight: 600;
      transition: width 1s ease;
    }
    .recent-grades {
      display: grid;
      gap: 1rem;
    }
    .grade-card {
      background-color: #0f172a;
      border-radius: 8px;
      padding: 1.5rem;
      border-left: 4px solid #10b981;
    }
    .grade-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 0.5rem;
    }
    .grade-title {
      font-weight: 600;
      color: #f1f5f9;
    }
    .grade-value {
      font-size: 1.5rem;
      font-weight: 700;
      color: #a78bfa;
    }
    .grade-lesson {
      color: #94a3b8;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="studentDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="studentDashboard.php">Dashboard</a></li>
      <li><a href="student_browse.php">Browse</a></li>
      <li><a href="learning_hub.php">Learning Hub</a></li>
      <li><a href="student_assignments.php">My Assignments</a></li>
      <li><a href="student_progress.php" class="active">Progress</a></li>
    </ul>
    <div style="color: white; font-weight: 600;"><?php echo htmlspecialchars($student_name); ?></div>
  </nav>

  <div class="container">
    <h2>📊 My Progress</h2>
    <p class="subtitle">Track your learning journey and achievements</p>

    <div class="stats-grid">
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['total_assignments']; ?></div>
        <div class="stat-label">Total Assignments</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['completed']; ?></div>
        <div class="stat-label">Completed</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['pending']; ?></div>
        <div class="stat-label">Pending</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo $stats['submitted']; ?></div>
        <div class="stat-label">Submitted</div>
      </div>
      <div class="stat-card">
        <div class="stat-number"><?php echo round($stats['avg_grade'] ?? 0); ?>%</div>
        <div class="stat-label">Average Grade</div>
      </div>
    </div>

    <div class="progress-section">
      <h3 class="section-title">Completion Rate</h3>
      <div class="progress-bar">
        <div class="progress-fill" style="width: <?php echo $completion_rate; ?>%">
          <?php echo $completion_rate; ?>%
        </div>
      </div>
      <p style="color: #94a3b8; text-align: center; margin-top: 1rem;">
        You've completed <?php echo $stats['completed']; ?> out of <?php echo $stats['total_assignments']; ?> assignments
      </p>
    </div>

    <?php if ($stats['highest_grade'] !== null): ?>
    <div class="progress-section">
      <h3 class="section-title">Grade Range</h3>
      <div style="display: flex; gap: 2rem; justify-content: center;">
        <div style="text-align: center;">
          <div style="font-size: 2.5rem; font-weight: 700; color: #10b981;">
            <?php echo $stats['highest_grade']; ?>%
          </div>
          <div style="color: #94a3b8; margin-top: 0.5rem;">Highest Grade</div>
        </div>
        <div style="text-align: center;">
          <div style="font-size: 2.5rem; font-weight: 700; color: #f59e0b;">
            <?php echo $stats['lowest_grade']; ?>%
          </div>
          <div style="color: #94a3b8; margin-top: 0.5rem;">Lowest Grade</div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php if ($recent_grades->num_rows > 0): ?>
    <div class="progress-section">
      <h3 class="section-title">Recent Grades</h3>
      <div class="recent-grades">
        <?php while ($grade = $recent_grades->fetch_assoc()): ?>
          <div class="grade-card">
            <div class="grade-header">
              <div>
                <div class="grade-title"><?php echo htmlspecialchars($grade['exercise_title']); ?></div>
                <div class="grade-lesson"><?php echo htmlspecialchars($grade['lesson_title']); ?></div>
              </div>
              <div class="grade-value"><?php echo $grade['grade']; ?>%</div>
            </div>
            <?php if ($grade['feedback']): ?>
              <div style="color: #cbd5e1; margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid #334155;">
                💬 <?php echo htmlspecialchars($grade['feedback']); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</body>
</html>