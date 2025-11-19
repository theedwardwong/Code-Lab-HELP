<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];
$lesson_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($lesson_id === 0) {
    header("Location: instructor_lessons.php");
    exit();
}

// Get lesson details
$lesson_query = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
$lesson_query->bind_param("i", $lesson_id);
$lesson_query->execute();
$lesson_result = $lesson_query->get_result();

if ($lesson_result->num_rows === 0) {
    header("Location: instructor_lessons.php");
    exit();
}

$lesson = $lesson_result->fetch_assoc();

// Get exercises for this lesson
$exercises_query = $conn->prepare("SELECT * FROM exercises WHERE lesson_id = ? ORDER BY order_index, id");
$exercises_query->bind_param("i", $lesson_id);
$exercises_query->execute();
$exercises = $exercises_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo htmlspecialchars($lesson['title']); ?> - Code Lab @ HELP</title>
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
    .lesson-header {
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
    .lesson-meta {
      display: flex;
      gap: 1.5rem;
      flex-wrap: wrap;
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
    .section-title {
      font-size: 1.5rem;
      margin-bottom: 1.5rem;
      color: #f1f5f9;
    }
    .exercises-grid {
      display: grid;
      gap: 1.5rem;
    }
    .exercise-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 1.5rem;
      border: 1px solid #334155;
      transition: all 0.3s;
    }
    .exercise-card:hover {
      border-color: #3b82f6;
      transform: translateY(-2px);
    }
    .exercise-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }
    .exercise-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }
    .exercise-description {
      color: #94a3b8;
      margin-bottom: 1rem;
    }
    .btn-view {
      padding: 0.6rem 1.2rem;
      background-color: #3b82f6;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
      text-decoration: none;
      display: inline-block;
    }
    .btn-view:hover {
      background-color: #2563eb;
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
    <a href="instructor_lessons.php" class="back-btn">
      ← Back to Lessons
    </a>

    <div class="lesson-header">
      <h1><?php echo htmlspecialchars($lesson['title']); ?></h1>
      <p style="color: #94a3b8; margin-bottom: 1.5rem;">
        <?php echo htmlspecialchars($lesson['description'] ?? 'No description'); ?>
      </p>
      <div class="lesson-meta">
        <span class="badge badge-<?php echo $lesson['category']; ?>">
          <?php echo strtoupper($lesson['category']); ?>
        </span>
        <span class="difficulty difficulty-<?php echo $lesson['difficulty']; ?>">
          <?php echo strtoupper($lesson['difficulty']); ?>
        </span>
        <span style="color: #94a3b8;">⏱️ <?php echo $lesson['duration_minutes']; ?> minutes</span>
        <span style="color: #94a3b8;">📝 Order: <?php echo $lesson['order_index']; ?></span>
      </div>
    </div>

    <h2 class="section-title">Exercises</h2>

    <div class="exercises-grid">
      <?php if ($exercises->num_rows > 0): ?>
        <?php while ($exercise = $exercises->fetch_assoc()): ?>
          <div class="exercise-card">
            <div class="exercise-header">
              <div style="flex: 1;">
                <div class="exercise-title"><?php echo htmlspecialchars($exercise['title']); ?></div>
                <div class="exercise-description">
                  <?php echo htmlspecialchars($exercise['description'] ?? 'No description'); ?>
                </div>
              </div>
              <span class="difficulty difficulty-<?php echo $exercise['difficulty']; ?>">
                <?php echo strtoupper($exercise['difficulty']); ?>
              </span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="color: #94a3b8;">📊 <?php echo $exercise['points']; ?> points</span>
              <a href="instructor_exercise_details.php?id=<?php echo $exercise['id']; ?>" class="btn-view">
                View Exercise
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color: #94a3b8; text-align: center; padding: 2rem;">
          No exercises for this lesson yet. 
          <a href="instructor_create_exercise.php" style="color: #3b82f6;">Create one now!</a>
        </p>
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