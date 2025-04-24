<?php


session_start();
include 'db_connect.php'; // Make sure this file connects to your database



if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user from database
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['email'] = $user['email'];
        
            // NEW: Check first login
            if ($user['first_login'] == 1) {
                header("Location: change-password.php");
            } else {
                if ($user['role'] === 'student') {
                    header("Location: studentDashboard.php");
                } elseif ($user['role'] === 'instructor') {
                    header("Location: instructorDashboard.php");
                } elseif ($user['role'] === 'admin') {
                    header("Location: adminDashboard.php");
                } else {
                    header("Location: dashboard.php"); // fallback (in case)
                }
            }
            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <style>
            /* Reset & fonts */
        * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: 'Segoe UI', sans-serif;
        }

        body {
        background-color: #2e3f54;
        color: #333;
        }

        /* Navbar */
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
        transition: color 0.2s;
        }

        .nav-links li a:hover {
        color: #b8e0ff;
        }

        .login-btn {
        padding: 0.3rem 1rem;
        background-color: #2e3f54;
        border-radius: 5px;
        }

        /* Login container */
        .login-container {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 85vh;
        padding: 2rem;
        }

        .login-box {
        background-color: white;
        padding: 2rem;
        border-radius: 1rem;
        width: 100%;
        max-width: 400px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .login-box h2 {
        text-align: center;
        margin-bottom: 2rem;
        font-weight: bold;
        }

        form label {
        display: block;
        margin: 1rem 0 0.3rem;
        }

        form input[type="email"],
        form input[type="password"] {
        width: 100%;
        padding: 0.8rem;
        border-radius: 8px;
        border: 1px solid #ccc;
        outline: none;
        }

        .password-container {
        position: relative;
        }

        .password-container .toggle-password {
        position: absolute;
        top: 50%;
        right: 1rem;
        transform: translateY(-50%);
        cursor: pointer;
        }

        .options {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 1rem 0;
        font-size: 0.9rem;
        }

        button[type="submit"] {
        width: 100%;
        padding: 0.8rem;
        border: none;
        border-radius: 8px;
        background-color: #2e3f54;
        color: white;
        font-size: 1rem;
        cursor: pointer;
        margin-top: 1rem;
        transition: background-color 0.2s;
        }

        button[type="submit"]:hover {
        background-color: #1e2b3a;
        }

    </style>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login Page</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <nav class="navbar">
    <div class="logo">Code Lab @ HELP</div>
    <ul class="nav-links">
      <li><a href="#">About</a></li>
      <li><a href="#">Contact</a></li>
      <li><a class="login-btn" href="login.php">Login</a></li>
    </ul>
  </nav>

  <main class="login-container">
    <div class="login-box">
      <h2>Code Lab @ HELP</h2>

      <?php if (!empty($error)): ?>
        <p style="color: red; text-align: center;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <form action="login.php" method="post">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" placeholder="Enter your email" required />

        <label for="password">Password</label>
        <div class="password-container">
          <input type="password" id="password" name="password" placeholder="Enter your password" required />
          <span class="toggle-password">&#128065;</span>
        </div>

        <div class="options">
          <label><input type="checkbox" name="remember" /> Remember me</label>
          <a href="#" class="forgot">Forgot Password?</a>
        </div>
        <button type="submit">Login</button>
      </form>
    </div>
  </main>
</body>
</html>