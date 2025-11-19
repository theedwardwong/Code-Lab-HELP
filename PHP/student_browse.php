<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}
$student_name = $_SESSION['full_name'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : 'all';
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : 'all';

$query = "SELECT * FROM lessons WHERE 1=1";
if (!empty($search)) {
    $query .= " AND (title LIKE '%$search%' OR description LIKE '%$search%')";
}
if ($category !== 'all') {
    $query .= " AND category = '$category'";
}
if ($difficulty !== 'all') {
    $query .= " AND difficulty = '$difficulty'";
}
$query .= " ORDER BY created_at DESC";
$lessons = $conn->query($query);
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"/><title>Browse - Code Lab</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background-color:#1a2332;color:#e4e7eb}
.navbar{background-color:#0f1419;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;box-shadow:0 2px 8px rgba(0,0,0,.3)}
.logo a{color:white;text-decoration:none;font-weight:600;font-size:1.2rem}
.nav-links{list-style:none;display:flex;gap:.5rem;margin:0;padding:0}
.nav-links li a{color:#9ca3af;text-decoration:none;padding:.6rem 1rem;border-radius:6px;font-size:.95rem}
.nav-links li a:hover,.nav-links li a.active{background-color:#1e293b;color:white}
.nav-icons{display:flex;align-items:center;gap:1.2rem}
.username{font-weight:600;color:#e4e7eb}
.logout-btn{background-color:#1e293b;color:white;border:1px solid #334155;padding:.5rem 1.2rem;cursor:pointer;border-radius:6px}
.container{max-width:1400px;margin:0 auto;padding:2rem}
h2{font-size:2rem;color:#f1f5f9;margin-bottom:2rem}
.filters{background-color:#1e293b;padding:1.5rem;border-radius:12px;margin-bottom:2rem;display:flex;gap:1rem}
.filters input,.filters select{padding:.8rem;border:1px solid #334155;border-radius:8px;background-color:#0f172a;color:white}
.filters input{flex:1}
.lessons-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(350px,1fr));gap:1.5rem}
.lesson-card{background-color:#1e293b;border-radius:12px;padding:1.5rem;border:1px solid #334155;transition:all .3s;cursor:pointer}
.lesson-card:hover{transform:translateY(-5px);border-color:#3b82f6;box-shadow:0 8px 20px rgba(59,130,246,.2)}
.lesson-title{font-size:1.2rem;font-weight:600;color:#f1f5f9;margin-bottom:.5rem}
.lesson-description{color:#94a3b8;margin-bottom:1rem}
.badge{padding:.3rem .8rem;border-radius:12px;font-size:.8rem;font-weight:600}
.badge-frontend{background-color:#ff6b6b;color:white}
.badge-backend{background-color:#4ecdc4;color:white}
.badge-fullstack{background-color:#95e1d3;color:white}
</style>
</head><body>
<nav class="navbar"><div class="logo"><a href="studentDashboard.php">Code Lab @ HELP</a></div>
<ul class="nav-links">
<li><a href="studentDashboard.php">Dashboard</a></li>
<li><a href="student_browse.php" class="active">Browse</a></li>
<li><a href="learning_hub.php">Learning Hub</a></li>
<li><a href="student_assignments.php">My Assignments</a></li>
<li><a href="student_progress.php">Progress</a></li>
</ul>
<div class="nav-icons"><span class="icon">🔔</span><span class="icon">⚙️</span><span class="icon">👤</span>
<span class="username"><?php echo htmlspecialchars($student_name);?></span>
<button class="logout-btn" onclick="if(confirm('Log out?'))location.href='logout.php'">Log Out</button></div></nav>
<div class="container"><h2>Browse Lessons</h2>
<form method="GET" class="filters">
<input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search);?>">
<select name="category"><option value="all">All Categories</option>
<option value="frontend" <?php echo $category==='frontend'?'selected':'';?>>Frontend</option>
<option value="backend" <?php echo $category==='backend'?'selected':'';?>>Backend</option>
<option value="fullstack" <?php echo $category==='fullstack'?'selected':'';?>>Full Stack</option></select>
<select name="difficulty"><option value="all">All Levels</option>
<option value="easy" <?php echo $difficulty==='easy'?'selected':'';?>>Easy</option>
<option value="medium" <?php echo $difficulty==='medium'?'selected':'';?>>Medium</option>
<option value="hard" <?php echo $difficulty==='hard'?'selected':'';?>>Hard</option></select>
<button type="submit" style="padding:.8rem 1.5rem;background:#3b82f6;color:white;border:none;border-radius:8px;cursor:pointer">Search</button>
</form>
<div class="lessons-grid">
<?php if($lessons->num_rows>0): while($lesson=$lessons->fetch_assoc()):?>
<div class="lesson-card" onclick="location.href='student_lesson_view.php?id=<?php echo $lesson['id'];?>'">
<div class="lesson-title"><?php echo htmlspecialchars($lesson['title']);?></div>
<div class="lesson-description"><?php echo htmlspecialchars($lesson['description']??'');?></div>
<span class="badge badge-<?php echo $lesson['category'];?>"><?php echo strtoupper($lesson['category']);?></span>
</div>
<?php endwhile; else:?>
<p style="color:#94a3b8">No lessons found</p>
<?php endif;?>
</div></div></body></html>