<?php
session_start();

// Security: Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$first_name = explode(' ', $student_name)[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Student Dashboard - Code Lab @ HELP</title>
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
      <a href="studentDashboard.php">Code Lab @ HELP</a>
    </div>
  <ul class="nav-links">
      <li><a href="studentDashboard.php">Dashboard</a></li>
      <li><a href="browser.php">Browse</a></li>
      <li><a href="exercises.php">Exercises</a></li>
      <li><a href="my_assignments.php">My Assignments</a></li>
      <li><a href="progress.php">Progress</a></li>
  </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($student_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <main class="student-main">
    <div class="welcome-banner">
      <img src="https://i.ibb.co/YRpFs6f/study-banner.png" alt="Welcome Banner" />
      <div class="welcome-text">
        <h1>Welcome back, <?php echo htmlspecialchars($first_name); ?>!</h1>
        <p>Keep learning with your personalized study plan.</p>
        <form action="search.php" method="GET" class="search-bar">
          <input type="text" name="q" placeholder="Search for lessons, tutorials, and more" required />
          <button type="submit">Search</button>
        </form>
      </div>
    </div>

    <section class="lessons-section">
      <h2>Discover new coding lessons</h2>
      <div class="lessons-grid">
        <div class="lesson-card" onclick="window.location.href='exercises.php'">
          <img src="https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg" alt="HTML5">
          <p>HTML Lessons</p>
        </div>
        <div class="lesson-card" onclick="window.location.href='exercises.php'">
          <img src="https://upload.wikimedia.org/wikipedia/commons/d/d5/CSS3_logo_and_wordmark.svg" alt="CSS3">
          <p>CSS Lessons</p>
        </div>
        <div class="lesson-card" onclick="window.location.href='exercises.php'">
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