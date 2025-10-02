<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];

// Get all courses
$courses_query = "
    SELECT l.*, u.full_name as creator_name,
           COUNT(DISTINCT e.id) as exercise_count
    FROM lessons l
    LEFT JOIN users u ON u.id = l.created_by
    LEFT JOIN exercises e ON e.lesson_id = l.id
    GROUP BY l.id
    ORDER BY l.order_index, l.created_at DESC
";
$courses = $conn->query($courses_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>View All Courses - Code Lab @ HELP</title>
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
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }

    .page-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }

    .page-header h1 {
      font-size: 2rem;
    }

    .btn-primary {
      padding: 0.8rem 1.5rem;
      background-color: #358efb;
      color: white;
      text-decoration: none;
      border-radius: 8px;
      font-weight: bold;
    }

    .courses-grid {
      display: grid;
      gap: 1.5rem;
    }

    .course-card {
      background-color: #1a2332;
      padding: 1.5rem;
      border-radius: 12px;
      border-left: 4px solid #358efb;
    }

    .course-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }

    .course-title {
      font-size: 1.3rem;
      font-weight: bold;
      margin-bottom: 0.5rem;
    }

    .course-meta {
      display: flex;
      gap: 2rem;
      color: #aaa;
      font-size: 0.9rem;
      margin-top: 1rem;
    }

    .badge {
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.85rem;
      font-weight: bold;
    }

    .badge-frontend { background-color: #ff6b6b; }
    .badge-backend { background-color: #4ecdc4; }
    .badge-fullstack { background-color: #95e1d3; }

    .difficulty {
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      font-size: 0.85rem;
    }

    .difficulty-beginner { background-color: #4caf50; }
    .difficulty-intermediate { background-color: #ff9800; }
    .difficulty-advanced { background-color: #f44336; }

    .empty-state {
      text-align: center;
      padding: 3rem;
      background-color: #1a2332;
      border-radius: 12px;
      color: #888;
    }
    .course-actions {
      display: flex;
      gap: 1rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #2e3f54;
    }

    .btn-edit,
    .btn-delete {
      padding: 0.5rem 1rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      font-size: 0.9rem;
      transition: all 0.2s;
    }

    .btn-edit {
      background-color: #358efb;
      color: white;
    }

    .btn-edit:hover {
      background-color: #2a72c9;
    }

    .btn-delete {
      background-color: #f44336;
      color: white;
    }

    .btn-delete:hover {
      background-color: #d32f2f;
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
      <div>
        <h1>All Courses</h1>
        <p style="color: #aaa;">View and manage all courses and lessons</p>
      </div>
      <a href="create_course.php" class="btn-primary">+ Create New Course</a>
    </div>

    <div class="courses-grid">
      <?php while ($course = $courses->fetch_assoc()): ?>
      <div class="course-card">
        <div class="course-header">
          <div>
            <div class="course-title"><?php echo htmlspecialchars($course['title']); ?></div>
            <div style="color: #aaa; margin-top: 0.5rem;">
              <?php echo htmlspecialchars($course['description'] ?? 'No description'); ?>
            </div>
          </div>
          <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <span class="badge badge-<?php echo $course['category']; ?>">
              <?php echo strtoupper($course['category']); ?>
            </span>
            <span class="difficulty difficulty-<?php echo $course['difficulty']; ?>">
              <?php echo strtoupper($course['difficulty']); ?>
            </span>
          </div>
        </div>

        <div class="course-meta">
          <span>Duration: <?php echo $course['duration_minutes']; ?> mins</span>
          <span>Exercises: <?php echo $course['exercise_count']; ?></span>
          <span>Created by: <?php echo htmlspecialchars($course['creator_name'] ?? 'Unknown'); ?></span>
          <span>Order: <?php echo $course['order_index']; ?></span>
        </div>

        <div class="course-actions">
          <button class="btn-edit" onclick="window.location.href='edit_course.php?id=<?php echo $course['id']; ?>'">
            ✏️ Edit
          </button>
          <button class="btn-delete" onclick="confirmDelete(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title'], ENT_QUOTES); ?>')">
            🗑️ Delete
          </button>
        </div>
      </div>
    <?php endwhile; ?>
    </div>
  </div>

  <script>
    function confirmLogout() {
      if (confirm("Are you sure you want to log out?")) {
        window.location.href = 'logout.php';
      }
    }

    function confirmDelete(courseId, courseTitle) {
      if (confirm(`Are you sure you want to delete "${courseTitle}"?\n\nThis will also delete all associated exercises. This action cannot be undone.`)) {
        window.location.href = 'delete_course.php?id=' + courseId;
      }
    }
  </script>
</body>
</html>