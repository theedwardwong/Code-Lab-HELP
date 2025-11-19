<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];
$instructor_id = $_SESSION['user_id'];

// Get statistics
$stats = [];

// Total courses/lessons available
$result = $conn->query("SELECT COUNT(*) as count FROM lessons");
$stats['total_lessons'] = $result->fetch_assoc()['count'];

// Total exercises
$result = $conn->query("SELECT COUNT(*) as count FROM exercises");
$stats['total_exercises'] = $result->fetch_assoc()['count'];

// Total students
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['count'];

// Total submissions (if table exists)
$stats['total_submissions'] = 0;
$check_table = $conn->query("SHOW TABLES LIKE 'submissions'");
if ($check_table->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM submissions");
    if ($result) {
        $stats['total_submissions'] = $result->fetch_assoc()['count'];
    }
}

// Pending reviews
$stats['pending_reviews'] = 0;

// Get recent lessons for display
$lessons_query = "SELECT * FROM lessons ORDER BY created_at DESC LIMIT 6";
$lessons_result = $conn->query($lessons_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Instructor Dashboard - Code Lab @ HELP</title>
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

    .section-title {
      font-size: 1.5rem;
      color: #f1f5f9;
      margin-bottom: 1.5rem;
      font-weight: 600;
    }

    .lessons-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
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
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="instructorDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="instructorDashboard.php" class="active">Dashboard</a></li>
      <li><a href="instructor_lessons.php">Browse</a></li>
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

  <main class="dashboard-container">
    <h2>Instructor Dashboard</h2>
    <p class="subtitle">Manage your courses and track student progress</p>

    <div class="stats">
      <div class="stat-box">
        <?php echo $stats['total_lessons']; ?>
        <span>Total Lessons</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['total_exercises']; ?>
        <span>Total Exercises</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['total_students']; ?>
        <span>Students</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['total_submissions']; ?>
        <span>Submissions</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['pending_reviews']; ?>
        <span>Pending Reviews</span>
      </div>
    </div>

    <div class="welcome-section">
      <h3>👋 Welcome back, <?php echo htmlspecialchars($instructor_name); ?>!</h3>
      <p>Manage your courses and track student progress</p>
      <div class="quick-actions">
        <a href="instructor_create_exercise.php" class="quick-action-btn">📝 Create Exercise</a>
        <a href="instructor_assignments.php" class="quick-action-btn">📋 Manage Assignments</a>
        <a href="instructor_submissions.php" class="quick-action-btn">✅ Review Submissions</a>
      </div>
    </div>

    <h3 class="section-title">Available Lessons</h3>
    <div class="lessons-grid">
      <?php if ($lessons_result->num_rows > 0): ?>
        <?php while ($lesson = $lessons_result->fetch_assoc()): ?>
          <div class="lesson-card" onclick="window.location.href='instructor_lesson_details.php?id=<?php echo $lesson['id']; ?>'">
            <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
            <div class="lesson-description"><?php echo htmlspecialchars($lesson['description'] ?? 'No description'); ?></div>
            <div class="lesson-meta">
              <span class="badge badge-<?php echo $lesson['category']; ?>">
                <?php echo strtoupper($lesson['category']); ?>
              </span>
              <span><?php echo $lesson['duration_minutes']; ?> mins</span>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color: #94a3b8;">No lessons available yet.</p>
      <?php endif; ?>
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