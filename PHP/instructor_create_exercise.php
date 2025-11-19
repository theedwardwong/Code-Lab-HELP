<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];
$success = '';
$error = '';

// Get all lessons for dropdown
$lessons_query = "SELECT id, title, category FROM lessons ORDER BY title";
$lessons = $conn->query($lessons_query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = intval($_POST['lesson_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $instructions = trim($_POST['instructions']);
    $starter_code = trim($_POST['starter_code']);
    $solution_code = trim($_POST['solution_code']);
    $difficulty = $_POST['difficulty'];
    
    if (empty($title) || empty($instructions) || $lesson_id === 0) {
        $error = "Please fill in all required fields.";
    } else {
        // Match your database structure exactly
        $stmt = $conn->prepare("
            INSERT INTO exercises 
            (lesson_id, title, description, instructions, starter_code, solution_code, difficulty) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issssss", $lesson_id, $title, $description, $instructions, $starter_code, $solution_code, $difficulty);
        
        if ($stmt->execute()) {
            $success = "Exercise created successfully!";
            $title = $description = $instructions = $starter_code = $solution_code = '';
        } else {
            $error = "Failed to create exercise. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Create Exercise - Code Lab @ HELP</title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
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

    .logo a {
      color: white;
      text-decoration: none;
      font-weight: 600;
      font-size: 1.2rem;
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
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
      padding: 2rem;
    }

    .page-header h2 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: #f1f5f9;
    }

    .page-header p {
      color: #94a3b8;
      margin-bottom: 2rem;
    }

    .form-box {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2.5rem;
      border: 1px solid #334155;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #f1f5f9;
    }

    .required {
      color: #f87171;
    }

    input,
    select,
    textarea {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #334155;
      border-radius: 8px;
      background-color: #0f172a;
      color: white;
      font-family: inherit;
    }

    textarea {
      min-height: 120px;
      resize: vertical;
      font-family: 'Courier New', monospace;
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
      font-weight: 600;
    }

    .btn-primary {
      background-color: #3b82f6;
      color: white;
    }

    .btn-secondary {
      background-color: #6b7280;
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
      <a href="instructorDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="instructorDashboard.php">Dashboard</a></li>
      <li><a href="instructor_lessons.php">Browse</a></li>
      <li><a href="instructor_create_exercise.php" class="active">Create Exercise</a></li>
      <li><a href="instructor_assignments.php">Assignments</a></li>
      <li><a href="instructor_submissions.php">Submissions</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($instructor_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <div class="container">
    <div class="page-header">
      <h2>Create New Exercise</h2>
      <p>Build new coding challenges for students</p>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" class="form-box">
      <div class="form-group">
        <label>Lesson <span class="required">*</span></label>
        <select name="lesson_id" required>
          <option value="0">-- Select Lesson --</option>
          <?php 
          $lessons->data_seek(0);
          while ($lesson = $lessons->fetch_assoc()): 
          ?>
            <option value="<?php echo $lesson['id']; ?>">
              <?php echo htmlspecialchars($lesson['title']); ?> (<?php echo strtoupper($lesson['category']); ?>)
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Exercise Title <span class="required">*</span></label>
        <input type="text" name="title" placeholder="e.g., Build a Contact Form" 
               value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
      </div>

      <div class="form-group">
        <label>Description</label>
        <textarea name="description" placeholder="Brief description of what students will build"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
      </div>

      <div class="form-group">
        <label>Instructions <span class="required">*</span></label>
        <textarea name="instructions" placeholder="Detailed step-by-step instructions for students..." required><?php echo htmlspecialchars($instructions ?? ''); ?></textarea>
      </div>

      <div class="form-group">
        <label>Starter Code</label>
        <textarea name="starter_code" placeholder="// Initial code for students..."><?php echo htmlspecialchars($starter_code ?? ''); ?></textarea>
      </div>

      <div class="form-group">
        <label>Solution Code</label>
        <textarea name="solution_code" placeholder="// Complete solution code..."><?php echo htmlspecialchars($solution_code ?? ''); ?></textarea>
      </div>

      <div class="form-group">
        <label>Difficulty <span class="required">*</span></label>
        <select name="difficulty" required>
          <option value="easy">Easy</option>
          <option value="medium">Medium</option>
          <option value="hard">Hard</option>
        </select>
      </div>

      <div class="form-actions">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='instructorDashboard.php'">Cancel</button>
        <button type="submit" class="btn btn-primary">Create Exercise</button>
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