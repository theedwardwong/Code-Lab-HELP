<!DOCTYPE html>
<html lang="en">
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

    .nav-links {
    list-style: none;
    display: flex;
    gap: 1.5rem;
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
    background-color: #2e3f54;
    border: 1px solid #ccc;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    min-width: 130px;
    }

    .stat-box span {
    display: block;
    font-size: 0.9rem;
    color: #ccc;
    margin-top: 0.3rem;
    }

    .actions {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    }

    .action-box {
    background-color: #111b25;
    padding: 1.5rem;
    border-radius: 1rem;
    flex: 1;
    min-width: 300px;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    }

    .action-item {
    background-color: transparent;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.8rem;
    border-radius: 0.5rem;
    transition: background 0.2s;
    }

    .action-item:hover {
    background-color: #1d2d40;
    cursor: pointer;
    }

    .action-item .icon {
    font-size: 1.8rem;
    margin-right: 1rem;
    }

    .arrow {
    font-size: 1.5rem;
    }

    .action-item-link {
      text-decoration: none;
      color: inherit;
      display: block;
    }

    .action-item-link:hover .action-item {
      background-color: #1c2d3e;
      transition: background-color 0.3s;
      cursor: pointer;
    }
    .logo a:hover {
    text-decoration: underline;
    }
</style>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="admin-dashboard.css" />
</head>
<body>

  <!-- Navbar (preserved structure) -->
  <nav class="navbar">
    <div class="logo">
        <a href="<?php
            echo ($_SESSION['role'] === 'student') ? 'studentDashboard.php' :
                (($_SESSION['role'] === 'instructor') ? 'instructorDashboard.php' : '#');
        ?>" style="color: white; text-decoration: none;">
            Code Lab @ HELP
        </a>
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
      <span class="username">John Smith</span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <main class="dashboard-container">
    <h2>Dashboard</h2>

    <div class="stats">
      <div class="stat-box">100<br><span>Total Users</span></div>
      <div class="stat-box">100<br><span>Active Users</span></div>
      <div class="stat-box">100<br><span>New Users</span></div>
      <div class="stat-box">100<br><span>Total Instructors</span></div>
      <div class="stat-box">100<br><span>Active Instructors</span></div>
      <div class="stat-box">100<br><span>New Instructors</span></div>
    </div>

    <section class="actions">
      <div class="action-box">
        <a href="registration.php" class="action-item-link">
          <div class="action-item">
            <div class="icon">🧍</div>
            <div>
              <h4>Register New User</h4>
              <p>Register new users to the platform</p>
            </div>
            <span class="arrow">→</span>
          </div>
        </a>

        <a href="#" class="action-item-link">
          <div class="action-item">
            <div class="icon">➕</div>
            <div>
              <h4>Create New Course</h4>
              <p>Add a new course or event to the platform</p>
            </div>
            <span class="arrow">→</span>
          </div>
        </a>

        <a href="#" class="action-item-link">
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
              <p>Manage your organization’s settings</p>
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
      window.location.href = 'login.php';
    }
  }
  </script>

</body>
</html>
