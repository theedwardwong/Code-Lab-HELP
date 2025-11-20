<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];
$instructor_id = $_SESSION['user_id'];

// Handle grading
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $assignment_id = intval($_POST['assignment_id']);
    $grade = intval($_POST['grade']);
    $feedback = trim($_POST['feedback']);
    
    $update = $conn->prepare("
        UPDATE assignments 
        SET status = 'graded', grade = ?, feedback = ?, graded_at = NOW()
        WHERE id = ? AND instructor_id = ?
    ");
    $update->bind_param("isii", $grade, $feedback, $assignment_id, $instructor_id);
    
    if ($update->execute()) {
        $success = "Assignment graded successfully!";
    }
}

// Get all submissions
$submissions_query = $conn->prepare("
    SELECT 
        a.*,
        e.title as exercise_title,
        e.description as exercise_description,
        l.title as lesson_title,
        l.category,
        u.full_name as student_name,
        u.email as student_email
    FROM assignments a
    INNER JOIN exercises e ON e.id = a.exercise_id
    INNER JOIN lessons l ON l.id = e.lesson_id
    INNER JOIN users u ON u.id = a.student_id
    WHERE a.instructor_id = ? AND a.status IN ('submitted', 'graded')
    ORDER BY 
        CASE WHEN a.status = 'submitted' THEN 1 ELSE 2 END,
        a.submitted_at DESC
");
$submissions_query->bind_param("i", $instructor_id);
$submissions_query->execute();
$submissions = $submissions_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Review Submissions - Code Lab @ HELP</title>
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
    .nav-links li a.active {
      background-color: #1e293b;
      color: white;
    }
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2.5rem 3rem;
    }
    h2 {
      font-size: 2rem;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }
    .subtitle {
      color: #94a3b8;
      margin-bottom: 2.5rem;
    }
    .submissions-grid {
      display: grid;
      gap: 1.5rem;
    }
    .submission-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      border: 1px solid #334155;
    }
    .submission-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 1.5rem;
    }
    .submission-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }
    .student-info {
      color: #94a3b8;
      font-size: 0.95rem;
    }
    .status-badge {
      padding: 0.5rem 1rem;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .status-submitted {
      background-color: #3b82f6;
      color: white;
    }
    .status-graded {
      background-color: #10b981;
      color: white;
    }
    .submission-details {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1rem;
      margin-bottom: 1.5rem;
      padding: 1rem;
      background-color: #0f172a;
      border-radius: 8px;
    }
    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
    }
    .detail-label {
      font-size: 0.85rem;
      color: #94a3b8;
      text-transform: uppercase;
    }
    .detail-value {
      font-weight: 600;
      color: #f1f5f9;
    }
    .grade-form {
      background-color: #0f172a;
      padding: 1.5rem;
      border-radius: 8px;
      margin-top: 1rem;
    }
    .form-group {
      margin-bottom: 1rem;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #f1f5f9;
    }
    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #334155;
      border-radius: 8px;
      background-color: #1e293b;
      color: white;
      font-family: inherit;
    }
    .form-group textarea {
      min-height: 100px;
      resize: vertical;
    }
    .btn-submit {
      background: linear-gradient(135deg, #10b981, #059669);
      color: white;
      padding: 0.8rem 1.8rem;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 1rem;
    }
    .btn-view {
      background-color: #3b82f6;
      color: white;
      padding: 0.6rem 1.2rem;
      border-radius: 8px;
      text-decoration: none;
      display: inline-block;
      font-weight: 600;
    }
    .graded-info {
      background-color: #064e3b;
      border: 2px solid #10b981;
      padding: 1rem;
      border-radius: 8px;
      color: #6ee7b7;
    }
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background-color: #1e293b;
      border-radius: 16px;
      border: 2px dashed #334155;
    }
    .logout-btn{background-color:#1e293b;color:white;border:1px solid #334155;padding:.5rem 1.2rem;cursor:pointer;border-radius:6px}
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
      <li><a href="instructor_create_exercise.php">Create Exercise</a></li>
      <li><a href="instructor_assignments.php">Assignments</a></li>
      <li><a href="instructor_submissions.php" class="active">Submissions</a></li>
    </ul>
    <div class="nav-icons"><span class="icon">🔔</span><span class="icon">⚙️</span><span class="icon">👤</span>
<span class="username"><?php echo htmlspecialchars($instructor_name);?></span>
<button class="logout-btn" onclick="if(confirm('Log out?'))location.href='logout.php'">Log Out</button></div></nav>

  <div class="container">
    <h2>📝 Review Submissions</h2>
    <p class="subtitle">Grade student assignments and provide feedback</p>

    <?php if (isset($success)): ?>
      <div style="background:#064e3b;border:1px solid #10b981;color:#6ee7b7;padding:1rem;border-radius:8px;margin-bottom:2rem">
        ✅ <?php echo $success; ?>
      </div>
    <?php endif; ?>

    <div class="submissions-grid">
      <?php if ($submissions->num_rows > 0): ?>
        <?php while ($sub = $submissions->fetch_assoc()): ?>
          <div class="submission-card">
            <div class="submission-header">
              <div>
                <div class="submission-title"><?php echo htmlspecialchars($sub['exercise_title']); ?></div>
                <div class="student-info">
                  👤 <?php echo htmlspecialchars($sub['student_name']); ?> 
                  (<?php echo htmlspecialchars($sub['student_email']); ?>)
                </div>
                <div class="student-info">
                  📚 <?php echo htmlspecialchars($sub['lesson_title']); ?>
                </div>
              </div>
              <span class="status-badge status-<?php echo $sub['status']; ?>">
                <?php echo strtoupper($sub['status']); ?>
              </span>
            </div>

            <div class="submission-details">
              <div class="detail-item">
                <span class="detail-label">Due Date</span>
                <span class="detail-value"><?php echo date('M d, Y', strtotime($sub['due_date'])); ?></span>
              </div>
              <div class="detail-item">
                <span class="detail-label">Submitted</span>
                <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($sub['submitted_at'])); ?></span>
              </div>
              <?php if ($sub['status'] === 'graded'): ?>
                <div class="detail-item">
                  <span class="detail-label">Grade</span>
                  <span class="detail-value" style="color: #a78bfa; font-size: 1.5rem;">
                    <?php echo $sub['grade']; ?>%
                  </span>
                </div>
                <div class="detail-item">
                  <span class="detail-label">Graded At</span>
                  <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($sub['graded_at'])); ?></span>
                </div>
              <?php endif; ?>
            </div>

            <?php if ($sub['status'] === 'submitted'): ?>
              <form method="POST" class="grade-form">
                <input type="hidden" name="assignment_id" value="<?php echo $sub['id']; ?>">
                
                <div class="form-group">
                  <label>Grade (0-100)</label>
                  <input type="number" name="grade" min="0" max="100" required>
                </div>

                <div class="form-group">
                  <label>Feedback</label>
                  <textarea name="feedback" placeholder="Provide feedback to the student..."></textarea>
                </div>

                <button type="submit" name="grade_submission" class="btn-submit">
                  ✅ Submit Grade
                </button>
              </form>
            <?php else: ?>
              <div class="graded-info">
                <strong>📝 Your Feedback:</strong><br>
                <?php echo nl2br(htmlspecialchars($sub['feedback'])); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <div style="font-size: 4rem; margin-bottom: 1rem;">📝</div>
          <h3 style="color: #f1f5f9; margin-bottom: 0.5rem;">No Submissions Yet</h3>
          <p style="color: #94a3b8;">
            Student submissions will appear here when they complete assignments
          </p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>