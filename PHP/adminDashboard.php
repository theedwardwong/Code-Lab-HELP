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
      position: relative;
      overflow: hidden;
    }

    .stat-box:nth-child(1) {
      border-color: #3b82f6;
      color: #60a5fa;
    }

    .stat-box:nth-child(2) {
      border-color: #10b981;
      color: #34d399;
    }

    .stat-box:nth-child(3) {
      border-color: #f59e0b;
      color: #fbbf24;
    }

    .stat-box:nth-child(4) {
      border-color: #ef4444;
      color: #f87171;
    }

    .stat-box:nth-child(5) {
      border-color: #8b5cf6;
      color: #a78bfa;
    }

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

    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 2rem;
    }

    .info-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      border: 1px solid #334155;
      box-shadow: 0 2px 8px rgba(0,0,0,0.2);
    }

    .info-card h4 {
      color: #60a5fa;
      font-size: 1.2rem;
      margin-bottom: 1.5rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .info-card ul {
      list-style: none;
      padding: 0;
    }

    .info-card li {
      padding: 0.8rem 0;
      color: #cbd5e1;
      border-bottom: 1px solid #334155;
      font-size: 0.95rem;
    }

    .info-card li:last-child {
      border-bottom: none;
    }

    .info-card li::before {
      content: "✓ ";
      color: #10b981;
      font-weight: bold;
      margin-right: 0.7rem;
    }

    .info-card a {
      color: #60a5fa;
      text-decoration: none;
      transition: color 0.3s;
    }

    .info-card a:hover {
      color: #93c5fd;
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="adminDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="adminDashboard.php" class="active">Dashboard</a></li>
      <li><a href="registration.php">Register User</a></li>
      <li><a href="create_course.php">Create Course</a></li>
      <li><a href="view_courses.php">View Courses</a></li>
      <li><a href="manage_users.php">Manage Users</a></li>
      <li><a href="system_settings.php">System Settings</a></li>
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
    <p class="subtitle">Manage users, courses, and system settings</p>

    <div class="stats">
      <div class="stat-box">
        <?php echo $stats['total_users']; ?>
        <span>Total Users</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['total_students']; ?>
        <span>Students</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['new_users']; ?>
        <span>New Users</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['total_instructors']; ?>
        <span>Instructors</span>
      </div>
      <div class="stat-box">
        <?php echo $stats['new_instructors']; ?>
        <span>New Instructors</span>
      </div>
    </div>

    <div class="welcome-section">
      <h3>👋 Welcome, <?php echo htmlspecialchars($admin_name); ?>!</h3>
      <p>Manage your CodeLab @ HELP platform from here</p>
      <div class="quick-actions">
        <a href="registration.php" class="quick-action-btn">➕ Register New User</a>
        <a href="create_course.php" class="quick-action-btn">📚 Create Course</a>
        <a href="manage_users.php" class="quick-action-btn">👥 Manage Users</a>
      </div>
    </div>

    <div class="info-grid">
      <div class="info-card">
        <h4>📊 System Overview</h4>
        <ul>
          <li>Total Users: <?php echo $stats['total_users']; ?></li>
          <li>Active Students: <?php echo $stats['total_students']; ?></li>
          <li>Active Instructors: <?php echo $stats['total_instructors']; ?></li>
          <li>New Registrations (7 days): <?php echo $stats['new_users']; ?></li>
        </ul>
      </div>

      <div class="info-card">
        <h4>🎯 Quick Access</h4>
        <ul>
          <li><a href="registration.php">Register new students or instructors</a></li>
          <li><a href="manage_users.php">Edit or delete user accounts</a></li>
          <li><a href="create_course.php">Add new courses or lessons</a></li>
          <li><a href="system_settings.php">Configure platform settings</a></li>
        </ul>
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