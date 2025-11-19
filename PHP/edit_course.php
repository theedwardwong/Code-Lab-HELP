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

// Get course ID
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($course_id === 0) {
    header("Location: view_courses.php");
    exit();
}

// Handle form submission
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
            UPDATE lessons 
            SET title = ?, description = ?, category = ?, difficulty = ?, 
                duration_minutes = ?, order_index = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ssssiii", $title, $description, $category, $difficulty, $duration, $order_index, $course_id);
        
        if ($stmt->execute()) {
            $success = "Course updated successfully!";
        } else {
            $error = "Failed to update course. Please try again.";
        }
    }
}

// Get current course data
$course_query = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
$course_query->bind_param("i", $course_id);
$course_query->execute();
$course_result = $course_query->get_result();

if ($course_result->num_rows === 0) {
    header("Location: view_courses.php");
    exit();
}

$course = $course_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Edit Course - Code Lab @ HELP</title>
  <style>
    * {
      box-sizing: border-box;
    }

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

    .logout-btn {
      background-color: #1a2332;
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

    .page-header p {
      color: #94a3b8;
    }

    .form-container {
      background-color: #1e293b;
      padding: 2rem;
      border-radius: 12px;
      box-sizing: border-box;
      border: 1px solid #334155;
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
      border: 1px solid #334155;
      background-color: #0f172a;
      color: white;
      font-family: inherit;
      box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #60a5fa;
    }

    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.5rem;
      margin-bottom: 1.5rem;
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
        <span class="username"><?php echo htmlspecialchars($admin_name ?? 'Admin'); ?></span>
        <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
      </div>
  </nav>

  <div class="container">
    <div class="page-header">
      <h1>Edit Course</h1>
      <p>Update course information</p>
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
        <input type="text" name="title" placeholder="e.g., HTML Basics" 
               value="<?php echo htmlspecialchars($course['title']); ?>" required>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" placeholder="Brief description of what students will learn"><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Category <span class="required">*</span></label>
          <select name="category" required>
            <option value="frontend" <?php echo $course['category'] === 'frontend' ? 'selected' : ''; ?>>Frontend Development</option>
            <option value="backend" <?php echo $course['category'] === 'backend' ? 'selected' : ''; ?>>Backend Development</option>
            <option value="fullstack" <?php echo $course['category'] === 'fullstack' ? 'selected' : ''; ?>>Full Stack Development</option>
          </select>
        </div>

        <div class="form-group">
          <label>Difficulty <span class="required">*</span></label>
          <select name="difficulty" required>
            <option value="beginner" <?php echo $course['difficulty'] === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
            <option value="intermediate" <?php echo $course['difficulty'] === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
            <option value="advanced" <?php echo $course['difficulty'] === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
          </select>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Duration (minutes)</label>
          <input type="number" name="duration_minutes" 
                 value="<?php echo $course['duration_minutes']; ?>" min="0">
        </div>

        <div class="form-group">
          <label>Order Index</label>
          <input type="number" name="order_index" 
                 value="<?php echo $course['order_index']; ?>" min="1">
        </div>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='view_courses.php'">Cancel</button>
        <button type="submit" class="btn btn-primary">Update Course</button>
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