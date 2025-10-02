<?php 
session_start(); 
include 'db_connect.php'; 

// Security: Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user information
$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

// Get lessons from database
$lessons_query = "SELECT id, title, category FROM lessons WHERE is_published = 1 ORDER BY order_index LIMIT 3";
$lessons_result = $conn->query($lessons_query);

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

    /* Main content */
    .browse-container {
    padding: 2rem;
    }

    .browse-container h2 {
    font-size: 2rem;
    margin-bottom: 1rem;
    }

    /* Tabs */
    .tabs {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    border-bottom: 2px solid white;
    padding-bottom: 0.5rem;
    }

    .tab {
    background: none;
    border: none;
    color: white;
    font-size: 1rem;
    padding: 0.5rem;
    cursor: pointer;
    position: relative;
    }

    .tab.active::after {
    content: "";
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 3px;
    background-color: white;
    }

    /* Lesson cards */
    .section-title {
    margin-top: 2rem;
    font-size: 1.3rem;
    }

    .lessons-grid {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
    margin-top: 1rem;
    }

    .lesson-card {
    background-color: white;
    color: black;
    padding: 1rem;
    border-radius: 1rem;
    text-align: center;
    width: 150px;
    transition: transform 0.2s;
    display: flex;
    flex-direction: column;
    align-items: center;
    }

    .lesson-card img {
    max-width: 100px;
    height: auto;
    margin-bottom: 0.5rem;
    }

    .lesson-card:hover {
    transform: scale(1.05);
    }
    .logo a {
      color: white;
      text-decoration: none;
    }
    .logo a:hover {
    text-decoration: underline;
    }
</style>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Browse lessons and tutorials</title>
</head>
<body>

  <!-- Navbar -->
  <nav class="navbar">
    <div class="logo">
        <a href="<?php
            echo ($user_role === 'student') ? 'studentDashboard.php' :
                (($user_role === 'instructor') ? 'instructorDashboard.php' : 
                (($user_role === 'admin') ? 'adminDashboard.php' : '#'));
        ?>" style="color: white; text-decoration: none;">
            Code Lab @ HELP
        </a>
    </div>
    <ul class="nav-links">
        <li>
            <a href="<?php
                echo ($user_role === 'student') ? 'studentDashboard.php' :
                    (($user_role === 'instructor') ? 'instructorDashboard.php' : 
                    (($user_role === 'admin') ? 'adminDashboard.php' : '#'));
            ?>">Dashboard</a>
        </li>
      <li><a href="browser.php">Browse</a></li>
      <li><a href="#">Reports</a></li>
      <li><a href="#">Feedback</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($user_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <main class="browse-container">
    <h2>Browse lessons and tutorials</h2>

    <!-- Filter Tabs -->
    <div class="tabs">
      <button class="tab active" onclick="filterLessons('all', this)">All</button>
      <button class="tab" onclick="filterLessons('frontend', this)">Frontend Development</button>
      <button class="tab" onclick="filterLessons('backend', this)">Backend Development</button>
    </div>

    <h3 class="section-title">Newly Added</h3>
    <?php
    // Get lessons from database
    $lessons_query = "SELECT id, title, category FROM lessons WHERE is_published = 1 ORDER BY order_index LIMIT 3";
    $lessons_result = $conn->query($lessons_query);

    // Map lesson categories to images
    $images = [
        'HTML Basics' => 'https://upload.wikimedia.org/wikipedia/commons/6/61/HTML5_logo_and_wordmark.svg',
        'CSS Styling' => 'https://upload.wikimedia.org/wikipedia/commons/d/d5/CSS3_logo_and_wordmark.svg',
        'JavaScript Fundamentals' => 'https://upload.wikimedia.org/wikipedia/commons/6/6a/JavaScript-logo.png'
    ];
    $default_image = 'https://via.placeholder.com/150?text=Lesson';
    ?>

    <div class="lessons-grid">
    <?php while ($lesson = $lessons_result->fetch_assoc()): ?>
        <div class="lesson-card" data-category="<?php echo $lesson['category']; ?>" 
             onclick="window.location.href='exercises.php?lesson=<?php echo $lesson['id']; ?>'">
            <img src="<?php echo isset($images[$lesson['title']]) ? $images[$lesson['title']] : $default_image; ?>" 
                 alt="<?php echo htmlspecialchars($lesson['title']); ?>"
                 onerror="this.src='<?php echo $default_image; ?>'">
            <p><?php echo htmlspecialchars($lesson['title']); ?></p>
        </div>
    <?php endwhile; ?>
</div>
  </main>

  <script>
    function confirmLogout() {
      if (confirm("Are you sure you want to log out?")) {
        window.location.href = 'logout.php';
      }
    }

    function filterLessons(category, element) {
      const allTabs = document.querySelectorAll('.tab');
      const cards = document.querySelectorAll('.lesson-card');

      allTabs.forEach(tab => tab.classList.remove('active'));
      element.classList.add('active');

      cards.forEach(card => {
        const cardCategory = card.getAttribute('data-category');
        if (category === 'all' || cardCategory === category) {
          card.style.display = 'flex';
        } else {
          card.style.display = 'none';
        }
      });
    }
  </script>
</body>
</html>