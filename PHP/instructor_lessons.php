<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];

// Get filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : 'all';
$difficulty_filter = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'all';
$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query
$query = "SELECT * FROM lessons WHERE 1=1";

if ($category_filter !== 'all') {
    $query .= " AND category = '$category_filter'";
}

if ($difficulty_filter !== 'all') {
    $query .= " AND difficulty = '$difficulty_filter'";
}

if (!empty($search_term)) {
    $search_term_sql = '%' . $search_term . '%';
    $query .= " AND (title LIKE '$search_term_sql' OR description LIKE '$search_term_sql')";
}

$query .= " ORDER BY order_index, created_at DESC";
$lessons = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Browse Lessons - Code Lab @ HELP</title>
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

    .logo {
      color: white;
      font-weight: 600;
      font-size: 1.2rem;
    }

    .logo a {
      color: white;
      text-decoration: none;
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
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }

    .page-header {
      margin-bottom: 2rem;
    }

    .page-header h2 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: #f1f5f9;
    }

    .page-header p {
      color: #94a3b8;
    }

    .filters-box {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      border: 1px solid #334155;
    }

    .filters-row {
      display: grid;
      grid-template-columns: 1fr auto auto auto;
      gap: 1rem;
      align-items: center;
    }

    .search-input,
    .filter-select {
      padding: 0.8rem;
      border: 1px solid #334155;
      border-radius: 8px;
      background-color: #0f172a;
      color: white;
      font-size: 0.95rem;
    }

    .btn-search {
      padding: 0.8rem 1.5rem;
      background-color: #3b82f6;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }

    .lessons-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
      gap: 1.5rem;
    }

    .lesson-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 1.5rem;
      border: 1px solid #334155;
      transition: all 0.3s;
      cursor: pointer;
    }

    .lesson-card:hover {
      transform: translateY(-5px);
      border-color: #3b82f6;
      box-shadow: 0 8px 20px rgba(59, 130, 246, 0.2);
    }

    .lesson-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }

    .lesson-title {
      font-size: 1.2rem;
      font-weight: 600;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }

    .lesson-description {
      color: #94a3b8;
      font-size: 0.9rem;
      margin-bottom: 1rem;
    }

    .lesson-meta {
      display: flex;
      gap: 1rem;
      font-size: 0.85rem;
      color: #6b7280;
    }

    .badge {
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .badge-frontend { background-color: #ff6b6b; color: white; }
    .badge-backend { background-color: #4ecdc4; color: white; }
    .badge-fullstack { background-color: #95e1d3; color: white; }

    .difficulty {
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
    }

    .difficulty-beginner { background-color: #4caf50; color: white; }
    .difficulty-intermediate { background-color: #ff9800; color: white; }
    .difficulty-advanced { background-color: #f44336; color: white; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="instructorDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="instructorDashboard.php">Dashboard</a></li>
      <li><a href="instructor_lessons.php" class="active">Browse</a></li>
      <li><a href="instructor_create_exercise.php">Create Exercise</a></li>
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
      <h2>Browse Lessons</h2>
      <p>Explore all available lessons and courses</p>
    </div>

    <form method="GET" class="filters-box">
      <div class="filters-row">
        <input type="text" name="search" class="search-input" 
               placeholder="Search lessons..." 
               value="<?php echo htmlspecialchars($search_term); ?>">
        
        <select name="category" class="filter-select">
          <option value="all" <?php echo $category_filter === 'all' ? 'selected' : ''; ?>>All Categories</option>
          <option value="frontend" <?php echo $category_filter === 'frontend' ? 'selected' : ''; ?>>Frontend</option>
          <option value="backend" <?php echo $category_filter === 'backend' ? 'selected' : ''; ?>>Backend</option>
          <option value="fullstack" <?php echo $category_filter === 'fullstack' ? 'selected' : ''; ?>>Full Stack</option>
        </select>

        <select name="difficulty" class="filter-select">
          <option value="all" <?php echo $difficulty_filter === 'all' ? 'selected' : ''; ?>>All Levels</option>
          <option value="beginner" <?php echo $difficulty_filter === 'beginner' ? 'selected' : ''; ?>>Beginner</option>
          <option value="intermediate" <?php echo $difficulty_filter === 'intermediate' ? 'selected' : ''; ?>>Intermediate</option>
          <option value="advanced" <?php echo $difficulty_filter === 'advanced' ? 'selected' : ''; ?>>Advanced</option>
        </select>

        <button type="submit" class="btn-search">Search</button>
      </div>
    </form>

    <div class="lessons-grid">
      <?php if ($lessons->num_rows > 0): ?>
        <?php while ($lesson = $lessons->fetch_assoc()): ?>
          <div class="lesson-card" onclick="window.location.href='instructor_lesson_details.php?id=<?php echo $lesson['id']; ?>'">
            <div class="lesson-header">
              <div>
                <div class="lesson-title"><?php echo htmlspecialchars($lesson['title']); ?></div>
              </div>
              <div style="display: flex; gap: 0.5rem;">
                <span class="badge badge-<?php echo $lesson['category']; ?>">
                  <?php echo strtoupper($lesson['category']); ?>
                </span>
                <span class="difficulty difficulty-<?php echo $lesson['difficulty']; ?>">
                  <?php echo strtoupper($lesson['difficulty']); ?>
                </span>
              </div>
            </div>
            <div class="lesson-description">
              <?php echo htmlspecialchars($lesson['description'] ?? 'No description'); ?>
            </div>
            <div class="lesson-meta">
              <span>⏱️ <?php echo $lesson['duration_minutes']; ?> mins</span>
              <span>📝 Order: <?php echo $lesson['order_index']; ?></span>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color: #94a3b8; grid-column: 1 / -1; text-align: center; padding: 2rem;">
          No lessons found matching your filters.
        </p>
      <?php endif; ?>
    </div>
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