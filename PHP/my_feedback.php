<?php
session_start();
include 'db_connect.php';

// Check if user is student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];

// Get all submissions with feedback
$submissions_query = "
    SELECT 
        es.*,
        e.title as exercise_title,
        e.points as max_points,
        l.title as lesson_title,
        l.category,
        (SELECT COUNT(*) FROM instructor_feedback if2 WHERE if2.submission_id = es.id) as feedback_count,
        (SELECT MAX(if3.created_at) FROM instructor_feedback if3 WHERE if3.submission_id = es.id) as last_feedback_date
    FROM exercise_submissions es
    JOIN exercises e ON e.id = es.exercise_id
    JOIN lessons l ON l.id = e.lesson_id
    WHERE es.student_id = ?
    ORDER BY es.submitted_at DESC
";

$stmt = $conn->prepare($submissions_query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$submissions = $stmt->get_result();

// Count feedback
$total_feedback_count = 0;
$new_feedback_count = 0;
$submissions_array = [];

while ($row = $submissions->fetch_assoc()) {
    $total_feedback_count += $row['feedback_count'];
    $submissions_array[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Feedback - Code Lab @ HELP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
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

        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .nav-links { list-style: none; display: flex; gap: 1.5rem; }
        .nav-links li a { color: white; text-decoration: none; }
        .nav-icons { display: flex; align-items: center; gap: 1rem; }
        
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
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stats-card {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 2rem;
        }

        .stat-value {
            font-size: 3rem;
            font-weight: bold;
            color: #358efb;
        }

        .stat-label {
            color: #aaa;
            margin-top: 0.5rem;
            font-size: 1.1rem;
        }

        .submissions-grid {
            display: grid;
            gap: 1.5rem;
        }

        .submission-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #358efb;
            transition: all 0.2s;
        }

        .submission-card:hover {
            background-color: #1f2a3a;
            transform: translateY(-2px);
        }

        .submission-card.has-feedback {
            border-left-color: #4caf50;
        }

        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .submission-title {
            font-size: 1.2rem;
            font-weight: bold;
            color: #358efb;
        }

        .feedback-badge {
            background-color: #4caf50;
            color: white;
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .submission-meta {
            display: flex;
            gap: 2rem;
            margin: 1rem 0;
            color: #aaa;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-passed { background-color: #4caf50; color: white; }
        .status-failed { background-color: #f44336; color: white; }
        .status-pending { background-color: #ff9800; color: white; }

        .btn-view {
            padding: 0.6rem 1.2rem;
            background-color: #358efb;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
        }

        .btn-view:hover {
            background-color: #2a72c9;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .modal-content {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            max-width: 900px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #2e3f54;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 2rem;
            cursor: pointer;
        }

        .section-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin: 1.5rem 0 1rem;
            color: #358efb;
        }

        .code-viewer {
            background-color: #1e1e1e;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            overflow-x: auto;
        }

        .code-viewer pre {
            color: #d4d4d4;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
        }

        .feedback-item {
            background-color: #2e3f54;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #4caf50;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            color: #aaa;
        }

        .feedback-text {
            color: white;
            line-height: 1.6;
            margin-bottom: 1rem;
        }

        .manual-grade {
            background-color: #4caf50;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-block;
        }

        .empty-state {
            background-color: #1a2332;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
            color: #888;
        }

        .empty-state h3 {
            margin-bottom: 1rem;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #2e3f54;
        }

        .detail-label {
            color: #aaa;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Code Lab @ HELP</div>
        <ul class="nav-links">
            <li><a href="studentDashboard.php">Dashboard</a></li>
            <li><a href="browser.php">Browse</a></li>
            <li><a href="exercises.php">Exercises</a></li>
            <li><a href="my_assignments.php">My Assignments</a></li>
            <li><a href="progress.php">Progress</a></li>
            <li><a href="my_feedback.php">My Feedback</a></li>
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>My Instructor Feedback</h1>
            <p style="color: #aaa;">Review personalized feedback from your instructors</p>
        </div>

        <div class="stats-card">
            <div class="stat-value"><?php echo $total_feedback_count; ?></div>
            <div class="stat-label">Total Feedback Received</div>
        </div>

        <div class="submissions-grid">
            <?php if (count($submissions_array) > 0): ?>
                <?php foreach ($submissions_array as $submission): ?>
                    <div class="submission-card <?php echo $submission['feedback_count'] > 0 ? 'has-feedback' : ''; ?>">
                        <div class="submission-header">
                            <div>
                                <div class="submission-title"><?php echo htmlspecialchars($submission['exercise_title']); ?></div>
                                <div style="color: #aaa; margin-top: 0.3rem;">
                                    <?php echo htmlspecialchars($submission['lesson_title']); ?>
                                </div>
                            </div>
                            <?php if ($submission['feedback_count'] > 0): ?>
                                <span class="feedback-badge">
                                    <?php echo $submission['feedback_count']; ?> Feedback(s)
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="submission-meta">
                            <span>
                                <span class="status-badge status-<?php echo $submission['status']; ?>">
                                    <?php echo strtoupper($submission['status']); ?>
                                </span>
                            </span>
                            <span>Score: <strong><?php echo $submission['score']; ?></strong> / <?php echo $submission['max_points']; ?></span>
                            <span>Submitted: <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></span>
                        </div>

                        <?php if ($submission['feedback_count'] > 0): ?>
                            <div style="color: #4caf50; margin-top: 0.5rem;">
                                ✓ Last feedback: <?php echo date('M j, g:i A', strtotime($submission['last_feedback_date'])); ?>
                            </div>
                        <?php endif; ?>

                        <button class="btn-view" 
                                data-submission-id="<?php echo $submission['id']; ?>"
                                data-submission='<?php echo htmlspecialchars(json_encode($submission), ENT_QUOTES); ?>'
                                onclick="viewFeedbackData(this)">
                            View Code & Feedback
                        </button>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No submissions yet</h3>
                    <p>Complete some exercises to receive instructor feedback!</p>
                    <a href="exercises.php" style="color: #358efb; text-decoration: none; font-weight: bold; margin-top: 1rem; display: inline-block;">
                        Go to Exercises →
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Feedback Modal -->
    <div class="modal" id="feedbackModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Submission & Feedback Details</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'logout.php';
            }
        }

        function viewFeedbackData(button) {
            const submissionId = button.getAttribute('data-submission-id');
            const submission = JSON.parse(button.getAttribute('data-submission'));
            viewFeedback(submissionId, submission);
        }

        function viewFeedback(submissionId, submission) {
            const modal = document.getElementById('feedbackModal');
            const modalBody = document.getElementById('modalBody');

            // Fetch feedback
            fetch(`get_feedback.php?submission_id=${submissionId}`)
                .then(response => response.json())
                .then(data => {
                    let feedbackHtml = '';
                    
                    if (data.success && data.feedback.length > 0) {
                        feedbackHtml = '<div class="section-title">Instructor Feedback</div>';
                        data.feedback.forEach(fb => {
                            const fbDate = new Date(fb.created_at).toLocaleString();
                            feedbackHtml += `
                                <div class="feedback-item">
                                    <div class="feedback-header">
                                        <span><strong>From: ${fb.instructor_name}</strong></span>
                                        <span>${fbDate}</span>
                                    </div>
                                    <div class="feedback-text">${escapeHtml(fb.feedback)}</div>
                                    ${fb.grade !== null ? `
                                        <div class="manual-grade">
                                            ⭐ Manual Grade: ${fb.grade} / ${submission.max_points}
                                        </div>
                                    ` : ''}
                                </div>
                            `;
                        });
                    } else {
                        feedbackHtml = `
                            <div style="text-align: center; padding: 2rem; color: #888;">
                                <h3>No instructor feedback yet</h3>
                                <p>Your instructor hasn't provided feedback for this submission.</p>
                            </div>
                        `;
                    }

                    const statusClass = 'status-' + submission.status;

                    modalBody.innerHTML = `
                        <div style="background-color: #2e3f54; padding: 1.5rem; border-radius: 8px; margin-bottom: 1.5rem;">
                            <div class="detail-row">
                                <span class="detail-label">Exercise:</span>
                                <span>${submission.exercise_title}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Lesson:</span>
                                <span>${submission.lesson_title}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Status:</span>
                                <span class="status-badge ${statusClass}">${submission.status.toUpperCase()}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Your Score:</span>
                                <span><strong>${submission.score} / ${submission.max_points}</strong></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Submitted:</span>
                                <span>${new Date(submission.submitted_at).toLocaleString()}</span>
                            </div>
                        </div>

                        ${feedbackHtml}

                        <div class="section-title">Your Submitted Code</div>
                        <div class="code-viewer">
                            <pre>${escapeHtml(submission.submitted_code)}</pre>
                        </div>

                        ${submission.feedback ? `
                            <div class="section-title">Auto-Generated Feedback</div>
                            <div style="background-color: #2e3f54; padding: 1rem; border-radius: 8px;">
                                ${escapeHtml(submission.feedback)}
                            </div>
                        ` : ''}
                    `;

                    modal.classList.add('active');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load feedback');
                });
        }

        function closeModal() {
            document.getElementById('feedbackModal').classList.remove('active');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.onclick = function(event) {
            const modal = document.getElementById('feedbackModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>