<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm      = $_POST['confirm_password'];

    if ($new_password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, first_login = 0 WHERE id = ?");
        $stmt->bind_param("si", $hashed, $_SESSION['user_id']);
        if ($stmt->execute()) {
            $success = "Password updated! Redirecting to dashboard...";
            if ($_SESSION['role'] === 'student') {
                header("refresh:2;url=studentDashboard.php");
            } elseif ($_SESSION['role'] === 'instructor') {
                header("refresh:2;url=instructorDashboard.php");
            } else {
                header("refresh:2;url=dashboard.php"); // fallback
            }
        } else {
            $error = "Something went wrong.";
        }
    }
}
?>

<!-- You can keep your beautiful form container here -->
<!DOCTYPE html>
<html>
<head>
<style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #2e3f54;
      color: white;
    }

    .form-container {
      max-width: 500px;
      margin: 5rem auto;
      padding: 2rem;
    }

    h2 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    p {
      margin-bottom: 2rem;
      font-size: 1rem;
      color: #ccc;
    }

    label {
      display: block;
      margin: 1rem 0 0.3rem;
    }

    input[type="password"] {
      width: 100%;
      padding: 0.8rem;
      border-radius: 8px;
      border: 1px solid #ccc;
      outline: none;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      margin-top: 2rem;
    }

    .submit-btn {
      background-color: #358efb;
      border: none;
      color: white;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      cursor: pointer;
    }

    .message {
      text-align: center;
      margin-bottom: 1rem;
    }

    .message.success {
      color: limegreen;
    }

    .message.error {
      color: red;
    }
  </style>
  <title>Change Password</title>
</head>
<body>
  <main class="form-container">
    <h2>Set a New Password</h2>
    <p>This is your first login. Please change your password to continue.</p>

    <?php if (!empty($error)): ?>
      <p style="color: red;"><?= $error ?></p>
    <?php elseif (!empty($success)): ?>
      <p style="color: limegreen;"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST">
      <label for="new_password">New Password</label>
      <input type="password" id="new_password" name="new_password" required />

      <label for="confirm_password">Confirm New Password</label>
      <input type="password" id="confirm_password" name="confirm_password" required />

      <div class="form-actions">
        <button type="submit" class="submit-btn">Change Password</button>
      </div>
    </form>
  </main>
</body>
</html>