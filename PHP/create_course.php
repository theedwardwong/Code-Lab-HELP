<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $category = $_POST['category'];
    $difficulty = $_POST['difficulty'];
    $duration = intval($_POST['duration_minutes']);
    $order_index = intval($_POST['order_index']);
    
    if (empty($title) || empty($category)) {
        $error = "Please fill in all required fields.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO lessons 
            (title, description, category, difficulty, duration_minutes, order_index, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("ssssiii", $title, $description, $category, $difficulty, $duration, $order_index, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $success = "Course created successfully!";
        } else {
            $error = "Failed to create course. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create New Course - Code Lab @ HELP</title>
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

    .logout-btn {
      background-color: #2e3f54;
      color: white;
      border: none;
      padding: 0.4rem 1rem;
      border-radius: 5px;
      cursor: pointer;
    }

    .container {
      max-width: 800px;
      margin: 0 auto;
      padding: 2rem;
    }

    .page-header {
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
    }

    .form-container {
      background-color: #1a2332;
      padding: 2rem;
      border-radius: 12px;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: bold;
    }

    .required {
      color: #f44336;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
      width: 100%;
      padding: 0.8rem;
      border-radius: 8px;
      border: 1px solid #2e3f54;
      background-color: #2e3f54;
      color: white;
      font-family: inherit;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
    }

    .form-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: 2rem;
    }

    .btn {
      padding: 0.8rem 1.5rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
    }

    .btn-primary {
      background-color: #358efb;
      color: white;
    }

    .btn-secondary {
      background-color: #666;
      color: white;
    }

    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }

    .alert-success {
      background-color: #4caf50;
    }

    .alert-error {
      background-color: #f44336;
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
      <span><?php echo htmlspecialchars($admin_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <div class="container">
    <div class="page-header">
      <h1>Create New Course</h1>
      <p style="color: #aaa;">Add a new lesson or course to the platform</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="form-container">
      <div class="form-group">
        <label>Course Title <span class="required">*</span></label>
        <input type="text" name="title" placeholder="e.g., HTML Basics" required>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" placeholder="Brief description of what students will learn"></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Category <span class="required">*</span></label>
          <select name="category" required>
            <option value="frontend">Frontend Development</option>
            <option value="backend">Backend Development</option>
            <option value="fullstack">Full Stack Development</option>
          </select>
        </div>

        <div class="form-group">
          <label>Difficulty <span class="required">*</span></label>
          <select name="difficulty" required>
            <option value="beginner">Beginner</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Duration (minutes)</label>
          <input type="number" name="duration_minutes" value="30" min="0">
        </div>

        <div class="form-group">
          <label>Order Index</label>
          <input type="number" name="order_index" value="1" min="1">
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='adminDashboard.php'">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Course</button>
      </div>
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