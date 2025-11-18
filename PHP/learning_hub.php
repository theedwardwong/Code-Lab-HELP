<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];

// Get progress for all lessons
$progress_query = $conn->prepare("
    SELECT lesson_id, quiz_score, quiz_total, quiz_passed, attempts, completed_at 
    FROM lesson_quiz_progress 
    WHERE student_id = ?
");
$progress_query->bind_param("i", $student_id);
$progress_query->execute();
$progress_result = $progress_query->get_result();

$progress_data = [];
while ($row = $progress_result->fetch_assoc()) {
    $progress_data[$row['lesson_id']] = $row;
}

// Calculate completion status with proper checks
$html_passed = isset($progress_data[1]) && isset($progress_data[1]['quiz_passed']) && $progress_data[1]['quiz_passed'];
$css_passed = isset($progress_data[2]) && isset($progress_data[2]['quiz_passed']) && $progress_data[2]['quiz_passed'];
$js_passed = isset($progress_data[3]) && isset($progress_data[3]['quiz_passed']) && $progress_data[3]['quiz_passed'];
$total_completed = ($html_passed ? 1 : 0) + ($css_passed ? 1 : 0) + ($js_passed ? 1 : 0);
$overall_progress = ($total_completed / 3) * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Learning Hub | Code Lab @ HELP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #2e3f54; color: white; }
        .navbar { background-color: #111; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .logo a { color: white; text-decoration: none; }
        .nav-links { list-style: none; display: flex; gap: 1.5rem; }
        .nav-links li a { color: white; text-decoration: none; }
        .logout-btn { background-color: #2e3f54; color: white; border: none; padding: 0.4rem 1rem; border-radius: 5px; cursor: pointer; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 3rem; border-radius: 12px; margin-bottom: 3rem; text-align: center; }
        .page-header h1 { font-size: 3rem; margin-bottom: 1rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 3rem; }
        .stat-card { background-color: #1a2332; padding: 2rem; border-radius: 12px; text-align: center; border: 2px solid #667eea; }
        .stat-number { font-size: 3rem; font-weight: bold; color: #667eea; margin-bottom: 0.5rem; }
        .stat-label { color: #aaa; font-size: 0.9rem; }
        .lessons-grid { display: grid; gap: 2rem; }
        .lesson-card { background-color: #1a2332; padding: 2rem; border-radius: 12px; display: flex; gap: 2rem; align-items: center; border-left: 5px solid #667eea; transition: all 0.3s; }
        .lesson-card:hover { transform: translateX(10px); box-shadow: 0 5px 20px rgba(102, 126, 234, 0.3); }
        .lesson-icon { font-size: 4rem; min-width: 80px; text-align: center; }
        .lesson-content { flex: 1; }
        .lesson-title { font-size: 1.8rem; margin-bottom: 0.5rem; }
        .lesson-description { color: #aaa; margin-bottom: 1rem; }
        .lesson-meta { display: flex; gap: 2rem; margin-top: 1rem; font-size: 0.9rem; color: #888; }
        .lesson-actions { display: flex; gap: 1rem; flex-wrap: wrap; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-success { background-color: #4caf50; color: white; }
        .btn-secondary { background-color: #666; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .status-badge { padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold; display: inline-block; }
        .badge-completed { background-color: #4caf50; color: white; }
        .badge-in-progress { background-color: #ff9800; color: white; }
        .badge-locked { background-color: #666; color: white; }
        .badge-not-started { background-color: #333; color: #aaa; }
        .celebration { text-align: center; padding: 3rem; background-color: #1a2332; border-radius: 12px; margin-top: 2rem; }
        .celebration h2 { color: #4caf50; font-size: 2.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo"><a href="studentDashboard.php">Code Lab @ HELP</a></div>
        <ul class="nav-links">
            <li><a href="studentDashboard.php">Dashboard</a></li>
            <li><a href="browser.php">Browse</a></li>
            <li><a href="learning_hub.php">Learning Hub</a></li>
        </ul>
        <div>
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>🎓 Your Learning Journey</h1>
            <p>Master web development step by step!</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo $total_completed; ?>/3</div>
                <div class="stat-label">Lessons Completed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo round($overall_progress); ?>%</div>
                <div class="stat-label">Overall Progress</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">
                    <?php 
                    $total_score = 0;
                    $total_possible = 0;
                    foreach ($progress_data as $prog) {
                        if (isset($prog['quiz_passed']) && $prog['quiz_passed']) {
                            $total_score += $prog['quiz_score'];
                            $total_possible += $prog['quiz_total'];
                        }
                    }
                    echo $total_possible > 0 ? round(($total_score / $total_possible) * 100) : 0;
                    ?>%
                </div>
                <div class="stat-label">Average Quiz Score</div>
            </div>
        </div>

        <div class="lessons-grid">
            <!-- HTML Lesson -->
            <div class="lesson-card" style="border-left-color: #667eea;">
                <div class="lesson-icon">🌐</div>
                <div class="lesson-content">
                    <div class="lesson-title">HTML Basics</div>
                    <div class="lesson-description">Learn the foundation of web development - structure your web pages!</div>
                    <div style="margin: 1rem 0;">
                        <?php if ($html_passed): ?>
                            <span class="status-badge badge-completed">✓ Completed</span>
                            <?php if (isset($progress_data[1])): ?>
                                <span style="margin-left: 1rem; color: #4caf50;">
                                    Score: <?php echo $progress_data[1]['quiz_score']; ?>/<?php echo $progress_data[1]['quiz_total']; ?>
                                </span>
                            <?php endif; ?>
                        <?php elseif (isset($progress_data[1])): ?>
                            <span class="status-badge badge-in-progress">📚 In Progress</span>
                        <?php else: ?>
                            <span class="status-badge badge-not-started">Not Started</span>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-meta">
                        <span>⏱️ 45 minutes</span>
                        <span>📊 Difficulty: Beginner</span>
                        <?php if (isset($progress_data[1])): ?>
                            <span>🔄 Attempts: <?php echo $progress_data[1]['attempts']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-actions" style="margin-top: 1.5rem;">
                        <a href="lesson_html_NEW.php" class="btn btn-primary">📖 Start Lesson</a>
                        <?php if (isset($progress_data[1])): ?>
                            <a href="quiz_html_NEW.php" class="btn btn-secondary">📝 Take Quiz</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- CSS Lesson -->
            <div class="lesson-card" style="border-left-color: #f093fb;">
                <div class="lesson-icon">🎨</div>
                <div class="lesson-content">
                    <div class="lesson-title">CSS Styling</div>
                    <div class="lesson-description">Make websites beautiful with colors, layouts, and animations!</div>
                    <div style="margin: 1rem 0;">
                        <?php if (!$html_passed): ?>
                            <span class="status-badge badge-locked">🔒 Locked - Complete HTML first</span>
                        <?php elseif ($css_passed): ?>
                            <span class="status-badge badge-completed">✓ Completed</span>
                            <?php if (isset($progress_data[2])): ?>
                                <span style="margin-left: 1rem; color: #4caf50;">
                                    Score: <?php echo $progress_data[2]['quiz_score']; ?>/<?php echo $progress_data[2]['quiz_total']; ?>
                                </span>
                            <?php endif; ?>
                        <?php elseif (isset($progress_data[2])): ?>
                            <span class="status-badge badge-in-progress">📚 In Progress</span>
                        <?php else: ?>
                            <span class="status-badge badge-not-started">Not Started</span>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-meta">
                        <span>⏱️ 50 minutes</span>
                        <span>📊 Difficulty: Beginner</span>
                        <?php if (isset($progress_data[2])): ?>
                            <span>🔄 Attempts: <?php echo $progress_data[2]['attempts']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-actions" style="margin-top: 1.5rem;">
                        <?php if ($html_passed): ?>
                            <a href="lesson_css_NEW.php" class="btn btn-primary">📖 Start Lesson</a>
                            <?php if (isset($progress_data[2])): ?>
                                <a href="quiz_css_NEW.php" class="btn btn-secondary">📝 Take Quiz</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">🔒 Locked</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- JavaScript Lesson -->
            <div class="lesson-card" style="border-left-color: #f7971e;">
                <div class="lesson-icon">⚡</div>
                <div class="lesson-content">
                    <div class="lesson-title">JavaScript Fundamentals</div>
                    <div class="lesson-description">Bring your websites to life with interactivity and programming!</div>
                    <div style="margin: 1rem 0;">
                        <?php if (!$css_passed): ?>
                            <span class="status-badge badge-locked">🔒 Locked - Complete CSS first</span>
                        <?php elseif ($js_passed): ?>
                            <span class="status-badge badge-completed">✓ Completed</span>
                            <?php if (isset($progress_data[3])): ?>
                                <span style="margin-left: 1rem; color: #4caf50;">
                                    Score: <?php echo $progress_data[3]['quiz_score']; ?>/<?php echo $progress_data[3]['quiz_total']; ?>
                                </span>
                            <?php endif; ?>
                        <?php elseif (isset($progress_data[3])): ?>
                            <span class="status-badge badge-in-progress">📚 In Progress</span>
                        <?php else: ?>
                            <span class="status-badge badge-not-started">Not Started</span>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-meta">
                        <span>⏱️ 60 minutes</span>
                        <span>📊 Difficulty: Intermediate</span>
                        <?php if (isset($progress_data[3])): ?>
                            <span>🔄 Attempts: <?php echo $progress_data[3]['attempts']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="lesson-actions" style="margin-top: 1.5rem;">
                        <?php if ($css_passed): ?>
                            <a href="lesson_javascript_NEW.php" class="btn btn-primary">📖 Start Lesson</a>
                            <?php if (isset($progress_data[3])): ?>
                                <a href="quiz_javascript_NEW.php" class="btn btn-secondary">📝 Take Quiz</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled style="opacity: 0.5; cursor: not-allowed;">🔒 Locked</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($total_completed === 3): ?>
            <div class="celebration">
                <h2>🎉 Congratulations!</h2>
                <p style="font-size: 1.3rem; color: #ddd; margin-bottom: 2rem;">
                    You've completed all three foundational web development lessons!<br>
                    You're ready to build amazing websites! 🚀
                </p>
                <div style="font-size: 4rem; margin: 2rem 0;">🏆</div>
                <p style="color: #aaa;">Keep practicing and exploring more advanced topics!</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>