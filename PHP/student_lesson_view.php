<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$lesson_id = intval($_GET['id'] ?? 0);

if ($lesson_id === 0) {
    header("Location: student_browse.php");
    exit();
}

$lesson_query = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
$lesson_query->bind_param("i", $lesson_id);
$lesson_query->execute();
$lesson = $lesson_query->get_result()->fetch_assoc();

if (!$lesson) {
    header("Location: student_browse.php");
    exit();
}

$exercises_query = $conn->prepare("SELECT * FROM exercises WHERE lesson_id = ? ORDER BY order_index");
$exercises_query->bind_param("i", $lesson_id);
$exercises_query->execute();
$exercises = $exercises_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo htmlspecialchars($lesson['title']); ?></title>
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
    .nav-links li a:hover {
      background-color: #1e293b;
      color: white;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 2rem;
    }
    .back-btn {
      display: inline-block;
      color: #3b82f6;
      text-decoration: none;
      margin-bottom: 1.5rem;
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
      color: #f1f5f9;
      margin-bottom: 1rem;
    }
    .lesson-description {
      color: #94a3b8;
      margin-bottom: 1rem;
      line-height: 1.6;
    }
    .badge {
      display: inline-block;
      background-color: #ff6b6b;
      padding: 0.4rem 1rem;
      border-radius: 12px;
      color: white;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .exercises-section h2 {
      font-size: 1.5rem;
      color: #f1f5f9;
      margin-bottom: 1.5rem;
    }
    .exercise-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 1.5rem;
      margin-bottom: 1.5rem;
      border: 1px solid #334155;
      transition: all 0.3s;
    }
    .exercise-card:hover {
      border-color: #3b82f6;
      transform: translateY(-2px);
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
    .btn-start {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      text-decoration: none;
      display: inline-block;
      font-weight: 600;
      transition: all 0.3s;
    }
    .btn-start:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
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
      <li><a href="student_progress.php">Progress</a></li>
    </ul>
  </nav>

  <div class="container">
    <a href="learning_hub.php" class="back-btn">← Back to Learning Hub</a>

    <div class="lesson-header">
      <h1><?php echo htmlspecialchars($lesson['title']); ?></h1>
      <p class="lesson-description"><?php echo htmlspecialchars($lesson['description'] ?? 'Learn the fundamentals'); ?></p>
      <div>
        <span class="badge"><?php echo strtoupper($lesson['category']); ?></span>
        <span style="color: #94a3b8; margin-left: 1rem;">⏱️ <?php echo $lesson['duration_minutes']; ?> minutes</span>
        <span style="color: #94a3b8; margin-left: 1rem;">📊 <?php echo strtoupper($lesson['difficulty']); ?></span>
      </div>
    </div>

    <div class="exercises-section">
      <h2>📝 Exercises</h2>
      
      <?php if ($exercises->num_rows > 0): ?>
        <?php $counter = 1; while ($ex = $exercises->fetch_assoc()): ?>
          <div class="exercise-card">
            <div style="display: flex; justify-content: space-between; align-items: start;">
              <div style="flex: 1;">
                <div style="color: #3b82f6; font-weight: 700; margin-bottom: 0.5rem;">
                  Exercise <?php echo $counter++; ?>
                </div>
                <div class="exercise-title"><?php echo htmlspecialchars($ex['title']); ?></div>
                <div class="exercise-description"><?php echo htmlspecialchars($ex['description'] ?? 'Practice your skills'); ?></div>
              </div>
              <div>
                <a href="student_exercise_practice.php?id=<?php echo $ex['id']; ?>" class="btn-start">
                  Start Exercise →
                </a>
              </div>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color: #94a3b8; text-align: center; padding: 2rem; background-color: #1e293b; border-radius: 12px;">
          No exercises available yet for this lesson.
        </p>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>