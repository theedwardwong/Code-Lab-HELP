<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$student_id = $_SESSION['user_id'];

// Get statistics
$stats = [];

// Total lessons available
$result = $conn->query("SELECT COUNT(*) as count FROM lessons");
$stats['total_lessons'] = $result->fetch_assoc()['count'];

// Assignments
$result = $conn->prepare("SELECT COUNT(*) as count FROM assignments WHERE student_id = ?");
$result->bind_param("i", $student_id);
$result->execute();
$stats['my_assignments'] = $result->get_result()->fetch_assoc()['count'];

// Completed assignments
$result = $conn->prepare("SELECT COUNT(*) as count FROM assignments WHERE student_id = ? AND status = 'graded'");
$result->bind_param("i", $student_id);
$result->execute();
$stats['completed'] = $result->get_result()->fetch_assoc()['count'];

// Pending assignments
$result = $conn->prepare("SELECT COUNT(*) as count FROM assignments WHERE student_id = ? AND status = 'pending'");
$result->bind_param("i", $student_id);
$result->execute();
$stats['pending'] = $result->get_result()->fetch_assoc()['count'];

// Average grade
$result = $conn->prepare("SELECT AVG(grade) as avg_grade FROM assignments WHERE student_id = ? AND grade IS NOT NULL");
$result->bind_param("i", $student_id);
$result->execute();
$avg_result = $result->get_result()->fetch_assoc();
$stats['avg_grade'] = $avg_result['avg_grade'] ? round($avg_result['avg_grade']) : 0;

// Get recent lessons
$lessons_query = "SELECT * FROM lessons ORDER BY created_at DESC LIMIT 3";
$recent_lessons = $conn->query($lessons_query);

// Get upcoming assignments
$assignments_query = $conn->prepare("
    SELECT a.*, e.title as exercise_title, l.title as lesson_title, l.category
    FROM assignments a
    INNER JOIN exercises e ON e.id = a.exercise_id
    INNER JOIN lessons l ON l.id = e.lesson_id
    WHERE a.student_id = ? AND a.status = 'pending'
    ORDER BY a.due_date ASC
    LIMIT 5
");
$assignments_query->bind_param("i", $student_id);
$assignments_query->execute();
$upcoming_assignments = $assignments_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Dashboard - Code Lab @ HELP</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
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

    .logo {
      color: white;
      font-weight: 600;
      font-size: 1.2rem;
    }

    .logo a {
      color: white;
      text-decoration: none;
    }

    .logo a:hover {
      color: #60a5fa;
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
      transition: all 0.3s;
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

    .icon {
      font-size: 1.2rem;
      color: #9ca3af;
      cursor: pointer;
      transition: color 0.3s;
    }

    .icon:hover {
      color: #60a5fa;
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
      transition: all 0.3s;
    }

    .logout-btn:hover {
      background-color: #334155;
      border-color: #475569;
    }

    .dashboard-container {
      padding: 2.5rem 3rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    h2 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: #f1f5f9;
      font-weight: 600;
    }

    .subtitle {
      color: #94a3b8;
      margin-bottom: 2.5rem;
      font-size: 0.95rem;
    }

    .stats {
      display: grid;
      grid-template-columns: repeat(5, 1fr);
      gap: 1.5rem;
      margin-bottom: 3rem;
    }

    .stat-box {
      background-color: #1e293b;
      border: 2px solid transparent;
      border-radius: 12px;
      padding: 2rem 1.5rem;
      text-align: center;
      font-size: 2.8rem;
      font-weight: 700;
      transition: all 0.3s;
    }

    .stat-box:nth-child(1) { border-color: #3b82f6; color: #60a5fa; }
    .stat-box:nth-child(2) { border-color: #10b981; color: #34d399; }
    .stat-box:nth-child(3) { border-color: #f59e0b; color: #fbbf24; }
    .stat-box:nth-child(4) { border-color: #ef4444; color: #f87171; }
    .stat-box:nth-child(5) { border-color: #8b5cf6; color: #a78bfa; }

    .stat-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0,0,0,0.3);
    }

    .stat-box span {
      display: block;
      font-size: 0.85rem;
      color: #94a3b8;
      margin-top: 0.8rem;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .welcome-section {
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      border-radius: 16px;
      padding: 3rem;
      text-align: center;
      margin-bottom: 3rem;
      border: 1px solid #334155;
      box-shadow: 0 4px 12px rgba(0,0,0,0.2);
    }

    .welcome-section h3 {
      font-size: 2rem;
      margin-bottom: 1rem;
      color: #f1f5f9;
      font-weight: 600;
    }

    .welcome-section p {
      font-size: 1rem;
      color: #94a3b8;
      margin-bottom: 2rem;
    }

    .quick-actions {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    .quick-action-btn {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
      padding: 0.9rem 1.8rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.95rem;
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .quick-action-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
    }

    .content-grid {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 2rem;
      margin-bottom: 3rem;
    }

    .section-title {
      font-size: 1.5rem;
      color: #f1f5f9;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }

    .lessons-grid {
      display: grid;
      gap: 1.5rem;
    }

    .lesson-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 1.5rem;
      border: 1px solid #334155;
      transition: all 0.3s;
      cursor: pointer;
    }

    .lesson-card:hover {
      transform: translateY(-5px);
      border-color: #3b82f6;
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
    }

    .lesson-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }

    .lesson-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }

    .lesson-description {
      font-size: 0.9rem;
      color: #94a3b8;
      margin-bottom: 1rem;
    }

    .lesson-meta {
      display: flex;
      gap: 1rem;
      font-size: 0.85rem;
      color: #6b7280;
    }

    .badge {
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .badge-frontend { background-color: #ff6b6b; color: white; }
    .badge-backend { background-color: #4ecdc4; color: white; }
    .badge-fullstack { background-color: #95e1d3; color: white; }

    .assignments-list {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .assignment-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 1.2rem;
      border: 1px solid #334155;
      transition: all 0.3s;
    }

    .assignment-card:hover {
      border-color: #f59e0b;
      transform: translateX(5px);
    }

    .assignment-title {
      font-weight: 600;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }

    .assignment-lesson {
      font-size: 0.85rem;
      color: #94a3b8;
      margin-bottom: 0.5rem;
    }

    .due-date {
      font-size: 0.8rem;
      color: #fbbf24;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .empty-state {
      text-align: center;
      padding: 2rem;
      color: #94a3b8;
      background-color: #1e293b;
      border-radius: 12px;
      border: 1px solid #334155;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="studentDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="studentDashboard.php" class="active">Dashboard</a></li>
      <li><a href="student_browse.php">Browse</a></li>
      <li><a href="learning_hub.php">Learning Hub</a></li>
      <li><a href="student_assignments.php">My Assignments</a></li>
      <li><a href="student_progress.php">Progress</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($student_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <main class="dashboard-container">
    <h2>Student Dashboard</h2>
    <p class="subtitle">Track your learning progress and complete assignments</p>

    <div class="stats">
      <div class="stat-box">
        <?php echo $stats['total_lessons']; ?>
        <span>Total Lessons</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['my_assignments']; ?>
        <span>My Assignments</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['completed']; ?>
        <span>Completed</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['pending']; ?>
        <span>Pending</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['avg_grade']; ?>%
        <span>Average Grade</span>
      </div>
    </div>

    <div class="welcome-section">
      <h3>👋 Welcome back, <?php echo htmlspecialchars($student_name); ?>!</h3>
      <p>Ready to continue your coding journey?</p>
      <div class="quick-actions">
        <a href="learning_hub.php" class="quick-action-btn">📚 Start Learning</a>
        <a href="student_browse.php" class="quick-action-btn">🔍 Browse Lessons</a>
        <a href="student_assignments.php" class="quick-action-btn">📝 View Assignments</a>
      </div>
    </div>

    <div class="content-grid">
      <div>
        <h3 class="section-title">Recent Lessons</h3>
        <div class="lessons-grid">
          <?php if ($recent_lessons->num_rows > 0): ?>
            <?php while ($lesson = $recent_lessons->fetch_assoc()): ?>
              <div class="lesson-card" onclick="window.location.href='student_lesson_view.php?id=<?php echo $lesson['id']; ?>'">
                <div class="lesson-header">
                  <div>
                    <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
                    <div class="lesson-description"><?php echo htmlspecialchars($lesson['description'] ?? 'No description'); ?></div>
                  </div>
                  <span class="badge badge-<?php echo $lesson['category']; ?>">
                    <?php echo strtoupper($lesson['category']); ?>
                  </span>
                </div>
                <div class="lesson-meta">
                  <span>⏱️ <?php echo $lesson['duration_minutes']; ?> mins</span>
                  <span>📊 <?php echo strtoupper($lesson['difficulty']); ?></span>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="empty-state">No lessons available yet.</div>
          <?php endif; ?>
        </div>
      </div>

      <div>
        <h3 class="section-title">Upcoming Assignments</h3>
        <div class="assignments-list">
          <?php if ($upcoming_assignments->num_rows > 0): ?>
            <?php while ($assignment = $upcoming_assignments->fetch_assoc()): ?>
              <div class="assignment-card">
                <div class="assignment-title"><?php echo htmlspecialchars($assignment['exercise_title']); ?></div>
                <div class="assignment-lesson"><?php echo htmlspecialchars($assignment['lesson_title']); ?></div>
                <div class="due-date">
                  ⏰ Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
                </div>
              </div>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="empty-state">No pending assignments</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>

  <script>
    function confirmLogout() {
      if (confirm("Are you sure you want to log out?")) {
        window.location.href = 'logout.php';
      }
    }
  </script>
</body>
</html>