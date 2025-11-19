<?php
include 'db_connect.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['name']);
    $email     = trim($_POST['email']);
    $role      = $_POST['role'];

    // Generate random 8-character password
    $password = bin2hex(random_bytes(4)); // e.g., "a7f9d3b2"

    // Check if email already exists
    $check = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        // Hash password & insert new user
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $insert = $conn->prepare("INSERT INTO users (full_name, email, role, password) VALUES (?, ?, ?, ?)");
        $insert->bind_param("ssss", $full_name, $email, $role, $hashed);

        if ($insert->execute()) {
            $success = "User created successfully!<br>Email: <strong>$email</strong><br>Temporary Password: <strong>$password</strong>";
        } else {
            $error = "Error occurred. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create New User</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #1a2332;
      color: #e4e7eb;
    }

    /* Navbar */
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
      font-weight: bold;
      font-size: 1.2rem;
    }

    .logo a {
      color: white;
      text-decoration: none;
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

    .nav-links li a:hover {
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
      border: none;
      padding: 0.4rem 1rem;
      border-radius: 5px;
      cursor: pointer;
    }

    /* Page Container */
    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 2rem;
    }

    .page-header {
      margin-bottom: 2rem;
    }

    .page-header h2 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .page-header p {
      color: #94a3b8;
      font-size: 1rem;
    }

    /* Form Container Box */
    .form-container {
      background-color: #1e293b;
      padding: 2rem;
      border-radius: 12px;
      border: 1px solid #334155;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 500;
    }

    .required {
      color: #f44336;
    }

    input[type="text"],
    input[type="email"] {
      width: 100%;
      padding: 0.8rem;
      border-radius: 8px;
      border: 1px solid #334155;
      background-color: #0f172a;
      color: #e4e7eb;
      outline: none;
      margin-bottom: 1.5rem;
      font-family: inherit;
    }

    input:focus {
      border-color: #60a5fa;
    }

    /* Role Buttons */
    .role-buttons {
      display: flex;
      gap: 1rem;
      margin-bottom: 2rem;
    }

    .role-btn {
      flex: 1;
      padding: 0.8rem 0;
      border-radius: 8px;
      border: 2px solid #334155;
      background-color: #0f172a;
      color: #e4e7eb;
      cursor: pointer;
      transition: all 0.3s;
      font-weight: 500;
    }

    .role-btn.active {
      background-color: #1e293b;
      border-color: #60a5fa;
      color: white;
    }

    .role-btn:hover {
      border-color: #60a5fa;
    }

    /* Form Actions */
    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 1rem;
      margin-top: 2rem;
    }

    .cancel-btn {
      background-color: #e25c5c;
      border: none;
      color: white;
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    .create-btn {
      background-color: #358efb;
      border: none;
      color: white;
      padding: 0.8rem 1.5rem;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    /* Alerts */
    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      text-align: center;
    }

    .alert-success {
      background-color: #064e3b;
      border: 1px solid #10b981;
      color: #6ee7b7;
    }

    .alert-error {
      background-color: #7f1d1d;
      border: 1px solid #ef4444;
      color: #fca5a5;
    }
  </style>
</head>
<body>

  <!-- Navbar -->
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
      <li><a href="system_settings.php">System Settings</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container">
    <div class="page-header">
      <h2>Create New User</h2>
      <p>Create a new user account for Instructor or Student!</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($success)): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <!-- Form Box -->
    <form action="registration.php" method="POST" class="form-container">
      <label for="name">Full Name <span class="required">*</span></label>
      <input type="text" id="name" name="name" placeholder="Enter the username's full name" required />

      <label for="email">Email Address <span class="required">*</span></label>
      <input type="email" id="email" name="email" placeholder="Enter the user's email address" required />

      <label>Role <span class="required">*</span></label>
      <div class="role-buttons">
        <input type="hidden" name="role" id="roleInput" value="instructor" />
        <button type="button" class="role-btn active" onclick="selectRole('instructor')">Instructor</button>
        <button type="button" class="role-btn" onclick="selectRole('student')">Student</button>
      </div>

      <div class="form-actions">
        <button type="button" class="cancel-btn" onclick="window.location.href='adminDashboard.php'">Cancel</button>
        <button type="submit" class="create-btn">Create User</button>
      </div>
    </form>
  </div>

  <script>
    function selectRole(role) {
      document.getElementById('roleInput').value = role;
      const buttons = document.querySelectorAll('.role-btn');
      buttons.forEach(btn => btn.classList.remove('active'));
      event.target.classList.add('active');
    }

    function confirmLogout() {
      if (confirm("Are you sure you want to log out?")) {
        window.location.href = 'login.php';
      }
    }
  </script>

</body>
</html>