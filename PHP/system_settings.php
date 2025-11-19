<?php
session_start();
include 'db_connect.php';

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quiz_pass_threshold = intval($_POST['quiz_pass_threshold']);
    $session_timeout = intval($_POST['session_timeout']);
    $enable_notifications = isset($_POST['enable_notifications']) ? 1 : 0;
    
    $success = "System settings updated successfully!";
}

// Default settings (you can load from database)
$settings = [
    'quiz_pass_threshold' => 70,
    'session_timeout' => 30,
    'enable_notifications' => 1
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Settings - Code Lab @ HELP</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #1a2332;
      color: white;
    }

    .navbar {
      background-color: #0f1419;
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
      padding: 0.5rem 1rem;
      border-radius: 4px;
      transition: background-color 0.3s;
    }

    .nav-links li a:hover,
    .nav-links li a.active {
      background-color: #1a2332;
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
      background-color: #1a2332;
      color: white;
      border: 1px solid #5a6c7d;
      padding: 0.4rem 1rem;
      cursor: pointer;
      border-radius: 4px;
    }

    .logout-btn:hover {
      background-color: #4a5f7a;
    }

    .settings-container {
      max-width: 800px;
      margin: 3rem auto;
      background-color: #1e293b;
      padding: 2rem;
      border-radius: 8px;
    }

    h2 {
      color: #6fa3d8;
      margin-bottom: 2rem;
    }

    .setting-group {
      margin-bottom: 2rem;
      padding-bottom: 2rem;
      border-bottom: 1px solid #4a5f7a;
    }

    .setting-group:last-child {
      border-bottom: none;
    }

    .setting-group h3 {
      color: #8bb4d8;
      margin-bottom: 1rem;
      font-size: 1.1rem;
    }

    .setting-group p {
      color: #ccc;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }

    input[type="number"],
    select {
      width: 100%;
      padding: 0.7rem;
      border: 1px solid #5a6c7d;
      border-radius: 4px;
      background-color: #1a2332;
      color: white;
      font-size: 1rem;
      box-sizing: border-box;
    }

    .checkbox-group {
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    input[type="checkbox"] {
      width: 20px;
      height: 20px;
      cursor: pointer;
    }

    .save-btn {
      width: 100%;
      padding: 0.8rem;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      font-weight: bold;
      cursor: pointer;
      transition: transform 0.3s;
      margin-top: 2rem;
    }

    .save-btn:hover {
      transform: translateY(-2px);
    }

    .alert {
      padding: 1rem;
      margin-bottom: 1.5rem;
      border-radius: 4px;
      text-align: center;
    }

    .alert-success {
      background-color: #4caf50;
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
      <li><a href="registration.php">Register User</a></li>
      <li><a href="create_course.php">Create Course</a></li>
      <li><a href="view_courses.php">View Courses</a></li>
      <li><a href="manage_users.php">Manage Users</a></li>
      <li><a href="system_settings.php" class="active">System Settings</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <div class="settings-container">
    <h2>⚙️ System Settings</h2>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="setting-group">
        <h3>Quiz Settings</h3>
        <p>Configure quiz behavior and passing criteria</p>
        <label for="quiz_pass_threshold">Pass Threshold (%)</label>
        <input type="number" id="quiz_pass_threshold" name="quiz_pass_threshold" 
               min="0" max="100" value="<?php echo $settings['quiz_pass_threshold']; ?>" required>
        <small style="color: #aaa;">Students need this percentage to pass quizzes (default: 70%)</small>
      </div>

      <div class="setting-group">
        <h3>Session Settings</h3>
        <p>Manage user session timeout and security</p>
        <label for="session_timeout">Session Timeout (minutes)</label>
        <input type="number" id="session_timeout" name="session_timeout" 
               min="5" max="120" value="<?php echo $settings['session_timeout']; ?>" required>
        <small style="color: #aaa;">Users will be logged out after this period of inactivity</small>
      </div>

      <div class="setting-group">
        <h3>Notification Settings</h3>
        <p>Control system notifications</p>
        <div class="checkbox-group">
          <input type="checkbox" id="enable_notifications" name="enable_notifications" 
                 <?php echo $settings['enable_notifications'] ? 'checked' : ''; ?>>
          <label for="enable_notifications">Enable email notifications</label>
        </div>
        <small style="color: #aaa;">Send emails for important events (registrations, feedback, etc.)</small>
      </div>

      <button type="submit" class="save-btn">💾 Save Settings</button>
    </form>
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