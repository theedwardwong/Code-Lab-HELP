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
<style>
    body {
    margin: 0;
    font-family: 'Segoe UI', sans-serif;
    background-color: #2e3f54;
    color: white;
    }

    /* Navbar (preserved style) */
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

    /* Form Container */
    .form-container {
    max-width: 500px;
    margin: 3rem auto;
    padding: 2rem;
    background-color: transparent;
    }

    .form-container h2 {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    }

    .form-container p {
    margin-bottom: 2rem;
    font-size: 1rem;
    color: #ccc;
    }

    label {
    display: block;
    margin: 1rem 0 0.3rem;
    }

    .required {
    color: red;
    }

    input[type="text"],
    input[type="email"],
    input[type="password"] {
    width: 100%;
    padding: 0.8rem;
    border-radius: 8px;
    border: 1px solid #ccc;
    outline: none;
    }

    .role-buttons {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    }

    .role-btn {
    flex: 1;
    padding: 0.6rem 0;
    border-radius: 8px;
    border: 1px solid #444;
    background-color: #2e3f54;
    color: white;
    cursor: pointer;
    transition: 0.2s;
    }

    .role-btn.active {
    background-color: #111b25;
    border: 1px solid white;
    }

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
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    cursor: pointer;
    }

    .create-btn {
    background-color: #358efb;
    border: none;
    color: white;
    padding: 0.6rem 1.2rem;
    border-radius: 8px;
    cursor: pointer;
    }
</style>

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create New User</title>
  <link rel="stylesheet" href="create-user.css" />
</head>
<body>

  <!-- Navbar (Same as always) -->
  <nav class="navbar">
    <div class="logo">Code Lab @ HELP</div>
    <ul class="nav-links">
      <li><a href="#">Dashboard</a></li>
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

  <main class="form-container">
    <h2>Create New User</h2>
    <p>Create a new user account for Instructor or Student!</p>

    <?php if (!empty($error)): ?>
      <p style="color: red; text-align:center;"><?= htmlspecialchars($error) ?></p>
      <?php elseif (!empty($success)): ?>
        <p style="color: limegreen; text-align:center;"><?= $success ?></p>
      <?php endif; ?>

    <form action="registration.php" method="POST">
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
  </main>
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
