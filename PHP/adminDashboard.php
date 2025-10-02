<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];

// Get real statistics from database
$stats = [];

// Total Users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total Students
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$stats['total_students'] = $result->fetch_assoc()['count'];

// New Users (last 7 days)
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_users'] = $result->fetch_assoc()['count'];

// Total Instructors
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'instructor'");
$stats['total_instructors'] = $result->fetch_assoc()['count'];

// New Instructors (last 7 days)
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'instructor' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['new_instructors'] = $result->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard - Code Lab @ HELP</title>
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

    .dashboard-container {
      padding: 2rem;
    }

    .dashboard-container h2 {
      font-size: 2rem;
      margin-bottom: 1.5rem;
    }

    .stats {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .stat-box {
      background-color: #1a2332;
      border: 2px solid #358efb;
      padding: 1.5rem;
      border-radius: 12px;
      text-align: center;
      min-width: 150px;
      font-size: 2.5rem;
      font-weight: bold;
      color: #358efb;
      transition: transform 0.2s, border-color 0.2s;
    }

    .stat-box:hover {
      transform: translateY(-5px);
      border-color: #4caf50;
    }

    .stat-box span {
      display: block;
      font-size: 0.9rem;
      color: #aaa;
      margin-top: 0.5rem;
      font-weight: normal;
    }

    .actions {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
    }

    .action-box {
      background-color: #1a2332;
      padding: 1.5rem;
      border-radius: 12px;
      flex: 1;
      min-width: 300px;
      display: flex;
      flex-direction: column;
      gap: 1rem;
      border: 1px solid #2e3f54;
    }

    .action-item {
      background-color: #2e3f54;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.2rem;
      border-radius: 8px;
      transition: all 0.3s;
      border: 2px solid transparent;
    }

    .action-item:hover {
      background-color: #3a4d64;
      border-color: #358efb;
      transform: translateX(5px);
      cursor: pointer;
    }

    .action-item-link {
      text-decoration: none;
      color: inherit;
      display: block;
    }

    .action-item h4 {
      margin: 0 0 0.3rem 0;
      color: #358efb;
      font-size: 1.1rem;
    }

    .action-item p {
      margin: 0;
      font-size: 0.85rem;
      color: #ccc;
    }

    .action-item .icon {
      font-size: 2rem;
      margin-right: 1rem;
      filter: grayscale(0%);
      transition: filter 0.3s;
    }

    .action-item:hover .icon {
      filter: brightness(1.3);
    }

    .arrow {
      font-size: 1.5rem;
      color: #358efb;
      transition: transform 0.3s;
    }

    .action-item:hover .arrow {
      transform: translateX(5px);
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="adminDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="adminDashboard.php">Dashboard</a></li>
      <li><a href="#">Members</a></li>
      <li><a href="#">Reports</a></li>
      <li><a href="#">Feedback</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($admin_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <main class="dashboard-container">
    <h2>Admin Dashboard</h2>
    <p style="color: #aaa; margin-bottom: 2rem;">Manage users, courses, and system settings</p>

    <div class="stats">
      <div class="stat-box"><?php echo $stats['total_users']; ?><br><span>Total Users</span></div>
      <div class="stat-box"><?php echo $stats['total_students']; ?><br><span>Students</span></div>
      <div class="stat-box"><?php echo $stats['new_users']; ?><br><span>New Users</span></div>
      <div class="stat-box"><?php echo $stats['total_instructors']; ?><br><span>Instructors</span></div>
      <div class="stat-box"><?php echo $stats['new_instructors']; ?><br><span>New Instructors</span></div>
    </div>

    <section class="actions">
      <div class="action-box">
        <a href="registration.php" class="action-item-link">
          <div class="action-item">
            <div class="icon">🧑</div>
            <div>
              <h4>Register New User</h4>
              <p>Register new users to the platform</p>
            </div>
            <span class="arrow">→</span>
          </div>
        </a>

        <a href="create_course.php" class="action-item-link">
          <div class="action-item">
            <div class="icon">➕</div>
            <div>
              <h4>Create New Course</h4>
              <p>Add a new course or event to the platform</p>
            </div>
            <span class="arrow">→</span>
          </div>
        </a>

        <a href="view_courses.php" class="action-item-link">
          <div class="action-item">
            <div class="icon">📋</div>
            <div>
              <h4>View All Courses</h4>
              <p>View and manage all courses and events</p>
            </div>
            <span class="arrow">→</span>
          </div>
        </a>
      </div>

      <div class="action-box">
        <a href="#" class="action-item-link">
          <div class="action-item">
            <div class="icon">📧</div>
            <div>
              <h4>Invite Instructors</h4>
              <p>Invite instructors to your organization</p>
            </div>
            <span class="arrow">→</span>
          </div>
        </a>

        <a href="#" class="action-item-link">
          <div class="action-item">
            <div class="icon">📈</div>
            <div>
              <h4>View Reports</h4>
              <p>View reports on usage and engagement</p>
            </div>
            <span class="arrow">→</span>
          </div>
        </a>

        <a href="#" class="action-item-link">
          <div class="action-item">
            <div class="icon">⚙️</div>
            <div>
              <h4>Organization Settings</h4>
              <p>Manage your organization's settings</p>
            </div>
            <span class="arrow">→</span>
          </div>
        </a>
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