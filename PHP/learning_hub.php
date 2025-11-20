<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$student_id = $_SESSION['user_id'];

// Get progress for each lesson
$progress_query = $conn->prepare("
    SELECT lesson_id, COUNT(*) as completed
    FROM lesson_progress
    WHERE student_id = ?
    GROUP BY lesson_id
");
$progress_query->bind_param("i", $student_id);
$progress_query->execute();
$progress_result = $progress_query->get_result();
$progress_data = [];
while ($row = $progress_result->fetch_assoc()) {
    $progress_data[$row['lesson_id']] = $row['completed'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Learning Hub - Code Lab @ HELP</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      color: #fff;
    }
    .navbar {
      background-color: rgba(15, 20, 25, 0.95);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 8px rgba(0,0,0,0.3);
      backdrop-filter: blur(10px);
    }
    .logo a {
      color: white;
      text-decoration: none;
      font-weight: 700;
      font-size: 1.3rem;
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
      border-radius: 8px;
      transition: all 0.3s;
    }
    .nav-links li a.active {
      background-color: rgba(59, 130, 246, 0.2);
      color: white;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 3rem 2rem;
    }
    .page-header {
      text-align: center;
      margin-bottom: 3rem;
    }
    .page-header h1 {
      font-size: 3.5rem;
      margin-bottom: 1rem;
      text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    }
    .page-header p {
      font-size: 1.2rem;
      opacity: 0.9;
    }
    .learning-path {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 20px;
      padding: 3rem;
      margin-bottom: 3rem;
      border: 2px solid rgba(255, 255, 255, 0.2);
    }
    .path-title {
      font-size: 2rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .lesson-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 2rem;
    }
    .lesson-card {
      background: rgba(255, 255, 255, 0.95);
      border-radius: 16px;
      padding: 2rem;
      color: #1a202c;
      cursor: pointer;
      transition: all 0.3s;
      position: relative;
      overflow: hidden;
    }
    .lesson-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 5px;
      background: linear-gradient(90deg, #667eea, #764ba2);
    }
    .lesson-card:hover {
      transform: translateY(-10px);
      box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    }
    .lesson-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
    }
    .lesson-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      color: #2d3748;
    }
    .lesson-description {
      color: #718096;
      margin-bottom: 1rem;
      line-height: 1.6;
    }
    .lesson-progress {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 1rem;
    }
    .progress-bar {
      flex: 1;
      height: 8px;
      background-color: #e2e8f0;
      border-radius: 10px;
      overflow: hidden;
    }
    .progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #48bb78, #38a169);
      transition: width 0.5s;
    }
    .lesson-stats {
      display: flex;
      justify-content: space-between;
      font-size: 0.9rem;
      color: #718096;
    }
    .btn-start {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      border: none;
      border-radius: 12px;
      font-weight: 700;
      font-size: 1rem;
      cursor: pointer;
      margin-top: 1rem;
      transition: all 0.3s;
    }
    .btn-start:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
    }
    .locked {
      opacity: 0.6;
      pointer-events: none;
    }
    .locked::after {
      content: '🔒';
      position: absolute;
      top: 1rem;
      right: 1rem;
      font-size: 2rem;
    }
    .logout-btn{background-color:#1e293b;color:white;border:1px solid #334155;padding:.5rem 1.2rem;cursor:pointer;border-radius:6px}


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
      <li><a href="learning_hub.php" class="active">Learning Hub</a></li>
      <li><a href="student_assignments.php">My Assignments</a></li>
      <li><a href="student_progress.php">Progress</a></li>
    </ul>
    
    <div class="nav-icons"><span class="icon">🔔</span><span class="icon">⚙️</span><span class="icon">👤</span>
<span class="username"><?php echo htmlspecialchars($student_name);?></span>
<button class="logout-btn" onclick="if(confirm('Log out?'))location.href='logout.php'">Log Out</button></div></nav>
  </nav>

  <div class="container">
    <div class="page-header">
      <h1>🎓 Interactive Learning Hub</h1>
      <p>Learn to code step-by-step with interactive lessons and quizzes</p>
    </div>

    <!-- HTML Path -->
    <div class="learning-path">
      <div class="path-title">
        <span style="font-size: 3rem;">🎨</span>
        <span>HTML - Website Structure</span>
      </div>
      <div class="lesson-cards">
        <div class="lesson-card" onclick="window.location.href='learn_interactive.php?topic=html&level=1'">
          <div class="lesson-icon">📝</div>
          <div class="lesson-title">HTML Basics</div>
          <div class="lesson-description">
            Learn the fundamental building blocks of web pages
          </div>
          <div class="lesson-progress">
            <div class="progress-bar">
              <div class="progress-fill" style="width: <?php echo isset($progress_data[1]) ? min(100, $progress_data[1] * 20) : 0; ?>%"></div>
            </div>
            <span><?php echo isset($progress_data[1]) ? $progress_data[1] : 0; ?>/5</span>
          </div>
          <div class="lesson-stats">
            <span>⏱️ 15 mins</span>
            <span>⭐ Beginner</span>
          </div>
          <button class="btn-start">Start Learning →</button>
        </div>

        <div class="lesson-card" onclick="window.location.href='learn_interactive.php?topic=html&level=2'">
          <div class="lesson-icon">🏗️</div>
          <div class="lesson-title">HTML Elements</div>
          <div class="lesson-description">
            Master headings, paragraphs, links, and images
          </div>
          <div class="lesson-progress">
            <div class="progress-bar">
              <div class="progress-fill" style="width: 0%"></div>
            </div>
            <span>0/5</span>
          </div>
          <div class="lesson-stats">
            <span>⏱️ 20 mins</span>
            <span>⭐ Beginner</span>
          </div>
          <button class="btn-start">Start Learning →</button>
        </div>

        <div class="lesson-card" onclick="window.location.href='learn_interactive.php?topic=html&level=3'">
          <div class="lesson-icon">📋</div>
          <div class="lesson-title">HTML Forms</div>
          <div class="lesson-description">
            Create interactive forms with input fields and buttons
          </div>
          <div class="lesson-progress">
            <div class="progress-bar">
              <div class="progress-fill" style="width: 0%"></div>
            </div>
            <span>0/5</span>
          </div>
          <div class="lesson-stats">
            <span>⏱️ 25 mins</span>
            <span>⭐ Intermediate</span>
          </div>
          <button class="btn-start">Start Learning →</button>
        </div>
      </div>
    </div>

    <!-- CSS Path -->
    <div class="learning-path">
      <div class="path-title">
        <span style="font-size: 3rem;">🎨</span>
        <span>CSS - Styling & Design</span>
      </div>
      <div class="lesson-cards">
        <div class="lesson-card" onclick="window.location.href='learn_interactive.php?topic=css&level=1'">
          <div class="lesson-icon">🎨</div>
          <div class="lesson-title">CSS Basics</div>
          <div class="lesson-description">
            Learn to style your web pages with colors and fonts
          </div>
          <div class="lesson-stats">
            <span>⏱️ 20 mins</span>
            <span>⭐ Beginner</span>
          </div>
          <button class="btn-start">Start Learning →</button>
        </div>

        <div class="lesson-card" onclick="window.location.href='learn_interactive.php?topic=css&level=2'">
          <div class="lesson-icon">📦</div>
          <div class="lesson-title">CSS Layout</div>
          <div class="lesson-description">
            Master flexbox and grid for page layouts
          </div>
          <div class="lesson-stats">
            <span>⏱️ 30 mins</span>
            <span>⭐ Intermediate</span>
          </div>
          <button class="btn-start">Start Learning →</button>
        </div>
      </div>
    </div>

    <!-- JavaScript Path -->
    <div class="learning-path">
      <div class="path-title">
        <span style="font-size: 3rem;">⚡</span>
        <span>JavaScript - Interactivity</span>
      </div>
      <div class="lesson-cards">
        <div class="lesson-card" onclick="window.location.href='learn_interactive.php?topic=javascript&level=1'">
          <div class="lesson-icon">⚡</div>
          <div class="lesson-title">JavaScript Basics</div>
          <div class="lesson-description">
            Learn variables, functions, and basic programming
          </div>
          <div class="lesson-stats">
            <span>⏱️ 25 mins</span>
            <span>⭐ Beginner</span>
          </div>
          <button class="btn-start">Start Learning →</button>
        </div>

        <div class="lesson-card" onclick="window.location.href='learn_interactive.php?topic=javascript&level=2'">
          <div class="lesson-icon">🎯</div>
          <div class="lesson-title">DOM Manipulation</div>
          <div class="lesson-description">
            Make your websites interactive and dynamic
          </div>
          <div class="lesson-stats">
            <span>⏱️ 30 mins</span>
            <span>⭐ Intermediate</span>
          </div>
          <button class="btn-start">Start Learning →</button>
        </div>
      </div>
    </div>
  </div>
</body>
</html>