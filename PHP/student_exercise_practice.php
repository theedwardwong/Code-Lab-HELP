<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$student_id = $_SESSION['user_id'];
$exercise_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($exercise_id === 0) {
    header("Location: learning_hub.php");
    exit();
}

// Get exercise details with lesson info - FIXED QUERY
$exercise_query = $conn->prepare("
    SELECT e.*, l.title as lesson_title, l.category
    FROM exercises e
    INNER JOIN lessons l ON l.id = e.lesson_id
    WHERE e.id = ?
");
$exercise_query->bind_param("i", $exercise_id);
$exercise_query->execute();
$exercise_result = $exercise_query->get_result();

if ($exercise_result->num_rows === 0) {
    header("Location: learning_hub.php");
    exit();
}

$exercise = $exercise_result->fetch_assoc();

// Parse hints from JSON if available
$hints = [];
if (!empty($exercise['hints'])) {
    $hints_decoded = json_decode($exercise['hints'], true);
    if (is_array($hints_decoded)) {
        $hints = $hints_decoded;
    }
}

// Handle code submission
$feedback = '';
$success = false;
$show_practice_tab = false; // NEW: Flag to show practice tab

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_code'])) {
    $submitted_code = $_POST['code'];
    $assignment_id = isset($_POST['assignment_id']) ? intval($_POST['assignment_id']) : 0;
    
    if (!empty($submitted_code)) {
        if ($assignment_id > 0) {
            $update = $conn->prepare("
                UPDATE assignments 
                SET status = 'submitted', submitted_at = NOW()
                WHERE id = ? AND student_id = ?
            ");
            $update->bind_param("ii", $assignment_id, $student_id);
            $update->execute();
        }
        
        $success = true;
        $feedback = "Great job! Your code has been submitted successfully.";
        $show_practice_tab = true; // NEW: Stay on practice tab
    } else {
        $feedback = "Please write some code before submitting.";
        $show_practice_tab = true; // NEW: Stay on practice tab
    }
}

// Check if this is an assignment
$assignment_query = $conn->prepare("
    SELECT * FROM assignments 
    WHERE exercise_id = ? AND student_id = ?
    LIMIT 1
");
$assignment_query->bind_param("ii", $exercise_id, $student_id);
$assignment_query->execute();
$assignment_result = $assignment_query->get_result();
$assignment = $assignment_result->num_rows > 0 ? $assignment_result->fetch_assoc() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?php echo htmlspecialchars($exercise['title']); ?> - Code Lab @ HELP</title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
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
    }
    .nav-links li a {
      color: #9ca3af;
      text-decoration: none;
      padding: 0.6rem 1rem;
      border-radius: 6px;
    }
    .nav-links li a:hover {
      background-color: #1e293b;
      color: white;
    }
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }
    .back-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: #3b82f6;
      text-decoration: none;
      margin-bottom: 1.5rem;
    }
    .exercise-header {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      border: 1px solid #334155;
    }
    h1 {
      font-size: 2rem;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }
    .tabs {
      display: flex;
      gap: 0.5rem;
      margin-bottom: 2rem;
      border-bottom: 2px solid #334155;
    }
    .tab {
      padding: 1rem 2rem;
      background: none;
      border: none;
      color: #94a3b8;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      border-bottom: 3px solid transparent;
      transition: all 0.3s;
    }
    .tab.active {
      color: #3b82f6;
      border-bottom-color: #3b82f6;
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }
    .content-section {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      margin-bottom: 2rem;
      border: 1px solid #334155;
    }
    .section-title {
      font-size: 1.5rem;
      color: #f1f5f9;
      margin-bottom: 1rem;
      font-weight: 600;
    }
    .video-container {
      position: relative;
      padding-bottom: 56.25%;
      height: 0;
      overflow: hidden;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      background-color: #0f172a;
    }
    .video-container iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }
    .teaching-content {
      background-color: #0f172a;
      border-radius: 8px;
      padding: 2rem;
      line-height: 1.8;
      color: #cbd5e1;
    }
    .teaching-content h3 {
      color: #3b82f6;
      margin-bottom: 1rem;
      font-size: 1.3rem;
    }
    .teaching-content code {
      background-color: #1e293b;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      color: #60a5fa;
      font-family: 'Courier New', monospace;
    }
    .teaching-content pre {
      background-color: #1e293b;
      padding: 1rem;
      border-radius: 8px;
      overflow-x: auto;
      margin: 1rem 0;
    }
    .code-editor {
      background-color: #0f172a;
      border: 1px solid #334155;
      border-radius: 8px;
      padding: 1.5rem;
      margin-bottom: 1rem;
    }
    .code-editor textarea {
      width: 100%;
      min-height: 300px;
      background-color: #0f172a;
      color: #e4e7eb;
      border: none;
      font-family: 'Courier New', monospace;
      font-size: 0.95rem;
      resize: vertical;
      outline: none;
    }
    .btn-submit {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 1rem 2rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 1rem;
    }
    .btn-hint {
      background-color: #f59e0b;
      color: white;
      padding: 0.8rem 1.5rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      margin-right: 1rem;
    }
    .hint-box {
      background-color: #fef3c7;
      border-left: 4px solid #f59e0b;
      padding: 1rem;
      margin-top: 1rem;
      border-radius: 4px;
      color: #92400e;
      display: none;
    }
    .hint-box.show {
      display: block;
    }
    .feedback {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }
    .feedback.success {
      background-color: #064e3b;
      border: 1px solid #10b981;
      color: #6ee7b7;
    }
    .feedback.error {
      background-color: #7f1d1d;
      border: 1px solid #ef4444;
      color: #fca5a5;
    }
    .animation-demo {
      background-color: #0f172a;
      border-radius: 8px;
      padding: 2rem;
      margin: 1.5rem 0;
      min-height: 200px;
      text-align: center;
    }
    .logout-btn{background-color:#1e293b;color:white;border:1px solid #334155;padding:.5rem 1.2rem;cursor:pointer;border-radius:6px}
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">
      <a href="studentDashboard.php">Code Lab @ HELP</a>
    </div>
    <ul class="nav-links">
      <li><a href="studentDashboard.php">Dashboard</a></li>
      <li><a href="student_browse.php">Browse</a></li>
      <li><a href="learning_hub.php">Learning Hub</a></li>
      <li><a href="student_assignments.php">My Assignments</a></li>
      <li><a href="student_progress.php">Progress</a></li>
    </ul>
    <div class="nav-icons"><span class="icon">🔔</span><span class="icon">⚙️</span><span class="icon">👤</span>
  <span class="username"><?php echo htmlspecialchars($student_name);?></span>
  <button class="logout-btn" onclick="if(confirm('Log out?'))location.href='logout.php'">Log Out</button></div></nav>

  <div class="container">
    <a href="student_lesson_view.php?id=<?php echo $exercise['lesson_id']; ?>" class="back-btn">
      ← Back to Lesson
    </a>

    <div class="exercise-header">
      <h1><?php echo htmlspecialchars($exercise['title']); ?></h1>
      <p style="color: #94a3b8;">📚 Lesson: <?php echo htmlspecialchars($exercise['lesson_title']); ?></p>
      <?php if ($assignment): ?>
        <p style="color: #fbbf24; margin-top: 0.5rem;">
          ⏰ Due: <?php echo date('M d, Y', strtotime($assignment['due_date'])); ?>
          | Status: <strong><?php echo strtoupper($assignment['status']); ?></strong>
        </p>
      <?php endif; ?>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab <?php echo !$show_practice_tab ? 'active' : ''; ?>" onclick="showTab('learn')">📚 Learn</button>
      <button class="tab <?php echo $show_practice_tab ? 'active' : ''; ?>" onclick="showTab('practice')">💻 Practice</button>
      <button class="tab" onclick="showTab('solution')">✅ Solution</button>
    </div>

    <!-- Learn Tab -->
    <div class="tab-content <?php echo !$show_practice_tab ? 'active' : ''; ?>" id="learn">
      <div class="content-section">
        <h2 class="section-title">📺 Video Tutorial</h2>
        <div class="video-container">
          <iframe src="https://www.youtube.com/embed/UB1O30fR-EE" 
                  frameborder="0" 
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                  allowfullscreen>
          </iframe>
        </div>
        <p style="color: #94a3b8; text-align: center;">
          Watch this video tutorial to understand the concepts before coding
        </p>
      </div>

      <div class="content-section">
        <h2 class="section-title">📖 Instructions</h2>
        <div class="teaching-content">
          <?php echo nl2br(htmlspecialchars($exercise['instructions'])); ?>
        </div>
      </div>

      <div class="content-section">
        <h2 class="section-title">🎨 Interactive Example</h2>
        <div class="animation-demo">
          <p style="color: #94a3b8; padding: 2rem;">
            💡 Interactive demonstrations and examples will help you understand<br>
            <strong style="color: #60a5fa;">Watch the video above to see the concepts in action!</strong>
          </p>
        </div>
      </div>
    </div>

    <!-- Practice Tab -->
    <div class="tab-content <?php echo $show_practice_tab ? 'active' : ''; ?>" id="practice">
      <?php if ($feedback): ?>
        <div class="feedback <?php echo $success ? 'success' : 'error'; ?>">
          <?php echo htmlspecialchars($feedback); ?>
        </div>
      <?php endif; ?>

      <div class="content-section">
        <h2 class="section-title">💻 Write Your Code</h2>
        
        <form method="POST">
          <input type="hidden" name="assignment_id" value="<?php echo $assignment['id'] ?? 0; ?>">
          
          <div class="code-editor">
            <textarea name="code" placeholder="// Write your code here..."><?php echo htmlspecialchars($exercise['starter_code'] ?? ''); ?></textarea>
          </div>

          <button type="button" class="btn-hint" onclick="showHint()">💡 Get Hint</button>
          <button type="submit" name="submit_code" class="btn-submit">
            <?php echo $assignment ? '📤 Submit Assignment' : '✅ Check Solution'; ?>
          </button>

          <div class="hint-box" id="hintBox">
            <strong>💡 Hint:</strong><br>
            <span id="hintText">
              <?php 
              if (!empty($hints) && isset($hints[0])) {
                  echo htmlspecialchars($hints[0]);
              } else {
                  echo "Try breaking down the problem into smaller steps.";
              }
              ?>
            </span>
          </div>
        </form>
      </div>
    </div>

    <!-- Solution Tab -->
    <div class="tab-content" id="solution">
      <div class="content-section">
        <h2 class="section-title">✅ Solution Code</h2>
        <?php if (!empty($exercise['solution_code'])): ?>
          <div class="code-editor">
            <pre style="color: #e4e7eb; margin: 0;"><?php echo htmlspecialchars($exercise['solution_code']); ?></pre>
          </div>
        <?php else: ?>
          <p style="color: #94a3b8; text-align: center; padding: 2rem;">
            Complete the practice first to unlock the solution!
          </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <script>
    function showTab(tabName) {
      document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
      });
      document.querySelectorAll('.tab').forEach(btn => {
        btn.classList.remove('active');
      });

      document.getElementById(tabName).classList.add('active');
      event.target.classList.add('active');
    }

    let hintIndex = 0;
    const hints = <?php echo json_encode($hints); ?>;

    function showHint() {
      const hintBox = document.getElementById('hintBox');
      const hintText = document.getElementById('hintText');
      
      if (hints.length > 0 && hints[hintIndex % hints.length]) {
        hintText.textContent = hints[hintIndex % hints.length];
        hintIndex++;
      }
      
      hintBox.classList.add('show');
    }

    // NEW: Auto-open practice tab if form was submitted
    <?php if ($show_practice_tab): ?>
    document.addEventListener('DOMContentLoaded', function() {
      showTab('practice');
      document.querySelectorAll('.tab')[1].classList.add('active');
    });
    <?php endif; ?>
  </script>
</body>
</html>