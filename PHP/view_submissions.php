<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['full_name'];

// Get filter parameters
$filter_status = $_GET['status'] ?? 'all';
$filter_exercise = $_GET['exercise'] ?? 'all';

// Build query based on filters
$where_conditions = ["1=1"];
$params = [];
$types = "";

if ($filter_status !== 'all') {
    $where_conditions[] = "es.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_exercise !== 'all') {
    $where_conditions[] = "es.exercise_id = ?";
    $params[] = intval($filter_exercise);
    $types .= "i";
}

$where_clause = implode(" AND ", $where_conditions);

// Get all submissions with feedback count
$submissions_query = "
    SELECT 
        es.*,
        e.title as exercise_title,
        e.points as max_points,
        l.title as lesson_title,
        u.full_name as student_name,
        u.email as student_email,
        (SELECT COUNT(*) FROM instructor_feedback if2 WHERE if2.submission_id = es.id) as feedback_count
    FROM exercise_submissions es
    JOIN exercises e ON e.id = es.exercise_id
    JOIN lessons l ON l.id = e.lesson_id
    JOIN users u ON u.id = es.student_id
    WHERE $where_clause
    ORDER BY es.submitted_at DESC
";

$submissions_stmt = $conn->prepare($submissions_query);
if (!empty($params)) {
    $submissions_stmt->bind_param($types, ...$params);
}
$submissions_stmt->execute();
$submissions = $submissions_stmt->get_result();

// Get exercises for filter dropdown
$exercises_for_filter = $conn->query("
    SELECT DISTINCT e.id, e.title, l.title as lesson_title
    FROM exercises e
    JOIN lessons l ON l.id = e.lesson_id
    ORDER BY l.order_index, e.order_index
");

// Get statistics
$stats_query = "
    SELECT 
        COUNT(*) as total_submissions,
        COUNT(CASE WHEN es.status = 'passed' THEN 1 END) as passed_count,
        COUNT(CASE WHEN es.status = 'failed' THEN 1 END) as failed_count,
        COUNT(CASE WHEN es.status = 'pending' THEN 1 END) as pending_count
    FROM exercise_submissions es
    JOIN exercises e ON e.id = es.exercise_id
    JOIN lessons l ON l.id = e.lesson_id
";
$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Submissions - Code Lab @ HELP</title>
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
            max-width: 1600px;
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

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #358efb;
        }

        .stat-label {
            color: #aaa;
            margin-top: 0.5rem;
        }

        /* Filters */
        .filters {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .filters label {
            font-weight: bold;
        }

        .filters select {
            padding: 0.5rem;
            border-radius: 8px;
            border: 1px solid #2e3f54;
            background-color: #2e3f54;
            color: white;
            cursor: pointer;
        }

        /* Submissions Table */
        .submissions-container {
            background-color: #1a2332;
            border-radius: 12px;
            overflow: hidden;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background-color: #111b25;
        }

        th {
            padding: 1rem;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid #2e3f54;
        }

        td {
            padding: 1rem;
            border-bottom: 1px solid #2e3f54;
        }

        tbody tr:hover {
            background-color: #1f2a3a;
        }

        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-passed {
            background-color: #4caf50;
            color: white;
        }

        .status-failed {
            background-color: #f44336;
            color: white;
        }

        .status-pending {
            background-color: #ff9800;
            color: white;
        }

        .action-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            background-color: #358efb;
            color: white;
        }

        .action-btn:hover {
            background-color: #2a72c9;
        }

        .feedback-badge {
            background-color: #4caf50;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
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
            max-width: 1000px;
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
            line-height: 1;
        }

        .submission-details {
            margin-bottom: 1.5rem;
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
            word-wrap: break-word;
        }

        .section-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin: 1.5rem 0 1rem;
            color: #358efb;
        }

        /* Feedback Section */
        .feedback-section {
            background-color: #2e3f54;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .feedback-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #444;
            background-color: #1a2332;
            color: white;
            font-family: inherit;
            resize: vertical;
        }

        .feedback-form label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #444;
            background-color: #1a2332;
            color: white;
        }

        .btn-submit-feedback {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
        }

        .btn-submit-feedback:hover {
            background-color: #45a049;
        }

        .previous-feedback {
            background-color: #1a2332;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #358efb;
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            color: #aaa;
            font-size: 0.9rem;
        }

        .feedback-text {
            color: white;
            line-height: 1.6;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }

        .success-message {
            background-color: #4caf50;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: none;
        }

        .success-message.show {
            display: block;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">Code Lab @ HELP</div>
        <ul class="nav-links">
            <li><a href="instructorDashboard.php">Dashboard</a></li>
            <li><a href="create_exercise.php">Create Exercise</a></li>
            <li><a href="manage_assignments.php">Assignments</a></li>
            <li><a href="view_submissions.php">Submissions</a></li>
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($instructor_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>Student Submissions</h1>
            <p style="color: #aaa;">Review code and provide personalized feedback</p>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total_submissions']; ?></div>
                <div class="stat-label">Total Submissions</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #4caf50;"><?php echo $stats['passed_count']; ?></div>
                <div class="stat-label">Passed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f44336;"><?php echo $stats['failed_count']; ?></div>
                <div class="stat-label">Failed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ff9800;"><?php echo $stats['pending_count']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>

        <!-- Filters -->
        <form class="filters" method="GET">
            <label>Status:</label>
            <select name="status" onchange="this.form.submit()">
                <option value="all" <?php echo $filter_status === 'all' ? 'selected' : ''; ?>>All</option>
                <option value="passed" <?php echo $filter_status === 'passed' ? 'selected' : ''; ?>>Passed</option>
                <option value="failed" <?php echo $filter_status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                <option value="pending" <?php echo $filter_status === 'pending' ? 'selected' : ''; ?>>Pending</option>
            </select>

            <label>Exercise:</label>
            <select name="exercise" onchange="this.form.submit()">
                <option value="all">All Exercises</option>
                <?php while ($ex = $exercises_for_filter->fetch_assoc()): ?>
                    <option value="<?php echo $ex['id']; ?>" <?php echo $filter_exercise == $ex['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($ex['title']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </form>

        <!-- Submissions Table -->
        <div class="submissions-container">
            <?php if ($submissions->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Exercise</th>
                            <th>Lesson</th>
                            <th>Status</th>
                            <th>Score</th>
                            <th>Submitted</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($sub = $submissions->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($sub['student_name']); ?></strong><br>
                                    <small style="color: #aaa;"><?php echo htmlspecialchars($sub['student_email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($sub['exercise_title']); ?></td>
                                <td><?php echo htmlspecialchars($sub['lesson_title']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $sub['status']; ?>">
                                        <?php echo strtoupper($sub['status']); ?>
                                    </span>
                                </td>
                                <td><strong><?php echo $sub['score']; ?></strong> / <?php echo $sub['max_points']; ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($sub['submitted_at'])); ?></td>
                                <td>
                                    <button class="action-btn" 
                                            data-submission='<?php echo htmlspecialchars(json_encode($sub), ENT_QUOTES); ?>'
                                            onclick="viewSubmissionData(this)">
                                        View & Feedback
                                    </button>
                                    <?php if ($sub['feedback_count'] > 0): ?>
                                        <span class="feedback-badge"><?php echo $sub['feedback_count']; ?> feedback(s)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No submissions found</h3>
                    <p>No students have submitted code yet, or try adjusting your filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Submission Detail Modal -->
    <div class="modal" id="submissionModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Submission Details & Feedback</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>
            <div id="successMessage" class="success-message"></div>
            <div id="modalBody"></div>
        </div>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'login.php';
            }
        }

        function viewSubmissionData(button) {
            const submission = JSON.parse(button.getAttribute('data-submission'));
            viewSubmission(submission);
        }

        function viewSubmission(submission) {
            const modal = document.getElementById('submissionModal');
            const modalBody = document.getElementById('modalBody');

            const statusClass = 'status-' + submission.status;
            
            // Fetch existing feedback
            fetch(`get_feedback.php?submission_id=${submission.id}`)
                .then(response => response.json())
                .then(data => {
                    let previousFeedbackHtml = '';
                    if (data.success && data.feedback.length > 0) {
                        previousFeedbackHtml = '<div class="section-title">Previous Feedback</div>';
                        data.feedback.forEach(fb => {
                            const fbDate = new Date(fb.created_at).toLocaleString();
                            previousFeedbackHtml += `
                                <div class="previous-feedback">
                                    <div class="feedback-header">
                                        <span><strong>${fb.instructor_name}</strong></span>
                                        <span>${fbDate}</span>
                                    </div>
                                    <div class="feedback-text">${escapeHtml(fb.feedback)}</div>
                                    ${fb.grade ? `<div style="margin-top: 0.5rem; color: #4caf50;"><strong>Manual Grade: ${fb.grade}</strong></div>` : ''}
                                </div>
                            `;
                        });
                    }

                    modalBody.innerHTML = `
                        <div class="submission-details">
                            <div class="detail-row">
                                <span class="detail-label">Student:</span>
                                <span>${submission.student_name}</span>
                            </div>
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
                                <span class="detail-label">Auto Score:</span>
                                <span>${submission.score} / ${submission.max_points} points</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Tests Passed:</span>
                                <span>${submission.passed_tests} / ${submission.total_tests}</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Submitted:</span>
                                <span>${new Date(submission.submitted_at).toLocaleString()}</span>
                            </div>
                        </div>

                        <div class="section-title">Submitted Code</div>
                        <div class="code-viewer">
                            <pre>${escapeHtml(submission.submitted_code)}</pre>
                        </div>

                        ${submission.feedback ? `
                            <div class="section-title">Auto-Generated Feedback</div>
                            <div style="background-color: #2e3f54; padding: 1rem; border-radius: 8px;">
                                ${escapeHtml(submission.feedback)}
                            </div>
                        ` : ''}

                        ${previousFeedbackHtml}

                        <div class="section-title">Add Your Feedback</div>
                        <div class="feedback-section">
                            <form class="feedback-form" onsubmit="submitFeedback(event, ${submission.id})">
                                <div class="form-group">
                                    <label>Your Feedback/Hints:</label>
                                    <textarea name="feedback" placeholder="Provide personalized feedback, suggestions, or hints to help the student improve..." required></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Manual Grade (Optional):</label>
                                    <input type="number" name="grade" min="0" max="${submission.max_points}" placeholder="Override auto-grade (0-${submission.max_points})">
                                </div>
                                <button type="submit" class="btn-submit-feedback">Submit Feedback</button>
                            </form>
                        </div>
                    `;

                    modal.classList.add('active');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to load previous feedback');
                });
        }

        function submitFeedback(event, submissionId) {
            event.preventDefault();
            
            const form = event.target;
            const formData = new FormData(form);
            formData.append('submission_id', submissionId);

            fetch('add_instructor_feedback.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const successMsg = document.getElementById('successMessage');
                    successMsg.textContent = 'Feedback submitted successfully!';
                    successMsg.classList.add('show');
                    
                    // Clear form
                    form.reset();
                    
                    // Hide success message after 3 seconds
                    setTimeout(() => {
                        successMsg.classList.remove('show');
                    }, 3000);
                    
                    // Reload the modal to show new feedback
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to submit feedback');
            });
        }

        function closeModal() {
            document.getElementById('submissionModal').classList.remove('active');
            document.getElementById('successMessage').classList.remove('show');
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        window.onclick = function(event) {
            const modal = document.getElementById('submissionModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html>