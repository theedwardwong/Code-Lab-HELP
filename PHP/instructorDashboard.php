<?php
session_start();

// Security: Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];
$first_name = explode(' ', $instructor_name)[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Instructor Dashboard - Code Lab @ HELP</title>
  <style>
    body {
      margin: 0;
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

    .logo a {
      color: white;
      text-decoration: none;
    }

    .logo a:hover {
      text-decoration: underline;
    }

    .nav-links {
      list-style: none;
      display: flex;
      gap: 1.5rem;
      margin: 0;
      padding: 0;
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

    .icon {
      font-size: 1.2rem;
    }

    .username {
      font-weight: bold;
    }

    .logout-btn {
      background-color: #2e3f54;
      color: white;
      border: none;
      padding: 0.4rem 1rem;
      border-radius: 5px;
      cursor: pointer;
    }

    .student-main {
      padding: 2rem;
    }

    .welcome-banner {
      position: relative;
      width: 100%;
      max-height: 300px;
      overflow: hidden;
      border-radius: 1rem;
      margin-bottom: 2rem;
    }

    .welcome-banner img {
      width: 100%;
      height: auto;
      display: block;
      border-radius: 1rem;
    }

    .welcome-text {
      position: absolute;
      top: 30%;
      left: 10%;
      transform: translateY(-30%);
    }

    .welcome-text h1 {
      font-size: 2.5rem;
      margin: 0;
      color: white;
    }

    .welcome-text p {
      margin-top: 0.3rem;
      color: #ddd;
    }

    .search-bar {
      margin-top: 1rem;
      display: flex;
      gap: 0.5rem;
    }

    .search-bar input {
      padding: 0.7rem;
      border-radius: 8px;
      border: none;
      width: 300px;
    }

    .search-bar button {
      background-color: #358efb;
      color: white;
      padding: 0.7rem 1rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
    }

    .quick-actions {
      background-color: #1a2332;
      padding: 2rem;
      border-radius: 1rem;
      margin-bottom: 2rem;
    }

    .quick-actions h2 {
      margin-bottom: 1.5rem;
    }

    .action-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1rem;
    }

    .action-btn {
      background-color: #2e3f54;
      padding: 1.5rem;
      border-radius: 8px;
      text-align: center;
      text-decoration: none;
      color: white;
      transition: all 0.2s;
      border: 2px solid transparent;
      display: block;
    }

    .action-btn:hover {
      background-color: #3a4d64;
      border-color: #358efb;
      transform: translateY(-2px);
    }

    .action-btn h3 {
      margin: 0 0 0.5rem 0;
      color: #358efb;
    }

    .action-btn p {
      margin: 0;
      color: #ccc;
      font-size: 0.9rem;
    }

    .lessons-section {
      margin-top: 3rem;
    }

    .lessons-section h2 {
      font-size: 1.6rem;
      margin-bottom: 1rem;
    }

    .lessons-grid {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
    }

    .lesson-card {
      background-color: white;
      color: black;
      padding: 1rem;
      border-radius: 1rem;
      text-align: center;
      width: 150px;
      transition: transform 0.2s;
      cursor: pointer;
    }

    .lesson-card img {
      max-width: 100px;
      height: auto;
      margin-bottom: 0.5rem;
    }

    .lesson-card:hover {
      transform: scale(1.05);
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
      <li><a href="browser.php">Browse</a></li>
      <li><a href="create_exercise.php">Create Exercise</a></li>
      <li><a href="manage_assignments.php">Assignments</a></li>
      <li><a href="view_submissions.php">Submissions</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($instructor_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <main class="student-main">
    <div class="welcome-banner">
      <img src="https://i.ibb.co/YRpFs6f/study-banner.png" alt="Welcome Banner" />
      <div class="welcome-text">
        <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h1>
        <p>Manage your courses and track student progress.</p>
        <form action="search.php" method="GET" class="search-bar">
          <input type="text" name="q" placeholder="Search for lessons, tutorials, and more" required />
          <button type="submit">Search</button>
        </form>
      </div>
    </div>

    <div class="quick-actions">
      <h2>Quick Actions</h2>
      <div class="action-grid">
        <a href="create_exercise.php" class="action-btn">
          <h3>Create Exercise</h3>
          <p>Build new coding challenges</p>
        </a>
        <a href="manage_assignments.php" class="action-btn">
          <h3>Manage Assignments</h3>
          <p>Assign exercises to students</p>
        </a>
        <a href="view_submissions.php" class="action-btn">
          <h3>Review Submissions</h3>
          <p>Grade student code</p>
        </a>
      </div>
    </div>

    <section class="lessons-section">
      <h2>Available Lessons</h2>
      <div class="lessons-grid">
        <div class="lesson-card" onclick="window.location.href='create_exercise.php'">
          <img src="https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg" alt="HTML5">
          <p>HTML Lessons</p>
        </div>
        <div class="lesson-card" onclick="window.location.href='create_exercise.php'">
          <img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/CSS3_logo_and_wordmark.svg" alt="CSS3">
          <p>CSS Lessons</p>
        </div>
        <div class="lesson-card" onclick="window.location.href='create_exercise.php'">
          <img src="https://upload.wikimedia.org/wikipedia/commons/6/6a/JavaScript-logo.png" alt="JavaScript">
          <p>JavaScript Lessons</p>
        </div>
      </div>
    </section>
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