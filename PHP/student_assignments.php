<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$student_id = $_SESSION['user_id'];

// Get all assignments with exercise and lesson details
$query = $conn->prepare("
    SELECT 
        a.*,
        e.title as exercise_title,
        e.description as exercise_description,
        l.title as lesson_title,
        l.category
    FROM assignments a
    INNER JOIN exercises e ON e.id = a.exercise_id
    INNER JOIN lessons l ON l.id = e.lesson_id
    WHERE a.student_id = ?
    ORDER BY 
        CASE 
            WHEN a.status = 'pending' THEN 1
            WHEN a.status = 'submitted' THEN 2
            WHEN a.status = 'graded' THEN 3
        END,
        a.due_date ASC
");
$query->bind_param("i", $student_id);
$query->execute();
$assignments = $query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>My Assignments - Code Lab @ HELP</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
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
    .assignments-grid {
      display: grid;
      gap: 1.5rem;
    }
    .assignment-card {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      border: 1px solid #334155;
      transition: all 0.3s;
    }
    .assignment-card:hover {
      border-color: #3b82f6;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
    }
    .assignment-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 1rem;
    }
    .assignment-title {
      font-size: 1.3rem;
      font-weight: 600;
      color: #f1f5f9;
      margin-bottom: 0.5rem;
    }
    .assignment-lesson {
      color: #94a3b8;
      font-size: 0.95rem;
      margin-bottom: 0.5rem;
    }
    .assignment-description {
      color: #cbd5e1;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }
    .assignment-meta {
      display: flex;
      gap: 2rem;
      flex-wrap: wrap;
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid #334155;
    }
    .meta-item {
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
    }
    .meta-label {
      font-size: 0.85rem;
      color: #94a3b8;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .meta-value {
      font-weight: 600;
      color: #f1f5f9;
    }
    .status-badge {
      padding: 0.5rem 1rem;
      border-radius: 12px;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
    }
    .status-pending {
      background-color: #6b7280;
      color: white;
    }
    .status-submitted {
      background-color: #3b82f6;
      color: white;
    }
    .status-graded {
      background-color: #10b981;
      color: white;
    }
    .btn-start {
      background: linear-gradient(135deg, #3b82f6, #2563eb);
      color: white;
      padding: 0.8rem 1.8rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s;
    }
    .btn-start:hover {
      transform: scale(1.05);
      box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
    }
    .btn-view {
      background-color: #6b7280;
      color: white;
      padding: 0.8rem 1.8rem;
      border-radius: 8px;
      text-decoration: none;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
    }
    .grade-display {
      background-color: #064e3b;
      border: 2px solid #10b981;
      padding: 1rem;
      border-radius: 8px;
      color: #6ee7b7;
      font-weight: 600;
      text-align: center;
    }
    .feedback-box {
      background-color: #0f172a;
      padding: 1rem;
      border-radius: 8px;
      border-left: 4px solid #3b82f6;
      color: #cbd5e1;
      margin-top: 1rem;
    }
    .empty-state {
      text-align: center;
      padding: 4rem 2rem;
      background-color: #1e293b;
      border-radius: 16px;
      border: 2px dashed #334155;
    }
    .empty-state-icon {
      font-size: 4rem;
      margin-bottom: 1rem;
    }
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
      <li><a href="student_assignments.php" class="active">My Assignments</a></li>
      <li><a href="student_progress.php">Progress</a></li>
    </ul>
    <div style="color: white; font-weight: 600;"><?php echo htmlspecialchars($student_name); ?></div>
  </nav>

  <div class="container">
    <h2>📝 My Assignments</h2>
    <p class="subtitle">Complete your assignments and track your progress</p>

    <div class="assignments-grid">
      <?php if ($assignments->num_rows > 0): ?>
        <?php while ($a = $assignments->fetch_assoc()): ?>
          <div class="assignment-card">
            <div class="assignment-header">
              <div style="flex: 1;">
                <div class="assignment-title">
                  <?php echo htmlspecialchars($a['exercise_title']); ?>
                </div>
                <div class="assignment-lesson">
                  📚 <?php echo htmlspecialchars($a['lesson_title']); ?>
                  <span style="background:#ff6b6b;padding:.2rem .6rem;border-radius:8px;margin-left:.5rem;font-size:.75rem">
                    <?php echo strtoupper($a['category']); ?>
                  </span>
                </div>
                <?php if (!empty($a['exercise_description'])): ?>
                  <div class="assignment-description">
                    <?php echo htmlspecialchars($a['exercise_description']); ?>
                  </div>
                <?php endif; ?>
              </div>
              <span class="status-badge status-<?php echo $a['status']; ?>">
                <?php echo $a['status']; ?>
              </span>
            </div>

            <div class="assignment-meta">
              <div class="meta-item">
                <span class="meta-label">Due Date</span>
                <span class="meta-value" style="color: <?php echo strtotime($a['due_date']) < time() ? '#f87171' : '#fbbf24'; ?>">
                  ⏰ <?php echo date('M d, Y', strtotime($a['due_date'])); ?>
                </span>
              </div>

              <?php if ($a['submitted_at']): ?>
                <div class="meta-item">
                  <span class="meta-label">Submitted</span>
                  <span class="meta-value" style="color: #34d399;">
                    ✅ <?php echo date('M d, Y', strtotime($a['submitted_at'])); ?>
                  </span>
                </div>
              <?php endif; ?>

              <?php if ($a['grade'] !== null): ?>
                <div class="meta-item">
                  <span class="meta-label">Grade</span>
                  <span class="meta-value" style="color: #a78bfa; font-size: 1.5rem;">
                    <?php echo $a['grade']; ?>%
                  </span>
                </div>
              <?php endif; ?>

              <div class="meta-item" style="margin-left: auto;">
                <?php if ($a['status'] === 'pending'): ?>
                  <a href="student_exercise_practice.php?id=<?php echo $a['exercise_id']; ?>" class="btn-start">
                    Start Assignment →
                  </a>
                <?php elseif ($a['status'] === 'submitted'): ?>
                  <a href="student_exercise_practice.php?id=<?php echo $a['exercise_id']; ?>" class="btn-view">
                    View Submission
                  </a>
                <?php else: ?>
                  <a href="student_exercise_practice.php?id=<?php echo $a['exercise_id']; ?>" class="btn-view">
                    Review
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <?php if ($a['feedback']): ?>
              <div class="feedback-box">
                <strong>💬 Instructor Feedback:</strong><br>
                <?php echo nl2br(htmlspecialchars($a['feedback'])); ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-state-icon">📝</div>
          <h3 style="color: #f1f5f9; margin-bottom: 0.5rem;">No Assignments Yet</h3>
          <p style="color: #94a3b8; margin-bottom: 1.5rem;">
            Your instructor will assign exercises for you to complete
          </p>
          <a href="learning_hub.php" style="color: #3b82f6; text-decoration: none; font-weight: 600;">
            Explore Learning Hub →
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>