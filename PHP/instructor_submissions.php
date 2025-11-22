<?php
session_start();
require_once 'db_connect.php';  // FIXED PATH

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];

// Handle grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_grade'])) {
    $assignment_id = $_POST['assignment_id'];
    $grade = $_POST['grade'];
    $feedback = $_POST['feedback'];
    
    $stmt = $conn->prepare("UPDATE assignments SET grade = ?, feedback = ?, status = 'graded', graded_at = NOW() WHERE id = ?");
    $stmt->bind_param("isi", $grade, $feedback, $assignment_id);
    
    if ($stmt->execute()) {
        $success_message = "Grade submitted successfully!";
    }
}

// Fetch all submissions
$query = "
    SELECT 
        a.id,
        a.student_id,
        a.exercise_id,
        a.status,
        a.submitted_code,
        a.submitted_at,
        a.due_date,
        a.grade,
        a.feedback,
        u.full_name as student_name,
        u.email as student_email,
        e.title as exercise_title,
        e.description as exercise_description,
        e.instructions as exercise_instructions,
        e.starter_code,
        e.solution_code,
        l.title as lesson_title
    FROM assignments a
    JOIN users u ON a.student_id = u.id
    JOIN exercises e ON a.exercise_id = e.id
    JOIN lessons l ON e.lesson_id = l.id
    WHERE a.status IN ('submitted', 'graded')
    ORDER BY a.submitted_at DESC
";

$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submissions - Code Lab @ HELP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #0f1419;
            color: #e4e7eb;
            min-height: 100vh;
        }
        
        .navbar {
            background-color: #1a2332;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .logo {
            color: white;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        .nav-links a {
            color: #9ca3af;
            text-decoration: none;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            transition: all 0.3s;
        }
        
        .nav-links a:hover, .nav-links a.active {
            background-color: #3b82f6;
            color: white;
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
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .page-header p {
            color: #94a3b8;
        }
        
        .success-message {
            background-color: rgba(34, 197, 94, 0.1);
            border: 1px solid #22c55e;
            color: #86efac;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .submissions-list {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .submission-card {
            background-color: #1e293b;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #334155;
            transition: all 0.3s;
        }
        
        .submission-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }
        
        .submission-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .submission-title {
            font-size: 1.3rem;
            color: #f1f5f9;
            margin-bottom: 0.5rem;
        }
        
        .student-info {
            color: #94a3b8;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }
        
        .lesson-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            background-color: #0f172a;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.85rem;
            color: #60a5fa;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-submitted {
            background-color: rgba(59, 130, 246, 0.2);
            color: #60a5fa;
        }
        
        .status-graded {
            background-color: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }
        
        .submission-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin: 1rem 0;
            padding: 1rem;
            background-color: #0f172a;
            border-radius: 8px;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .detail-value {
            color: #cbd5e1;
            font-size: 1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }
        
        /* MODAL STYLES */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            overflow-y: auto;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        
        .modal-content {
            background-color: #1e293b;
            border-radius: 16px;
            width: 100%;
            max-width: 1200px;
            max-height: 90vh;
            overflow-y: auto;
            border: 1px solid #334155;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
        }
        
        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background-color: #1e293b;
            z-index: 10;
        }
        
        .modal-header h2 {
            color: #f1f5f9;
            font-size: 1.5rem;
        }
        
        .close-modal {
            background: none;
            border: none;
            color: #94a3b8;
            font-size: 2rem;
            cursor: pointer;
            padding: 0;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .close-modal:hover {
            background-color: #334155;
            color: #f1f5f9;
        }
        
        .modal-body {
            padding: 2rem;
        }
        
        .code-section {
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.1rem;
            color: #3b82f6;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .code-block {
            background-color: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 1.5rem;
            overflow-x: auto;
        }
        
        .code-block pre {
            margin: 0;
            color: #cbd5e1;
            font-family: 'Courier New', monospace;
            font-size: 0.95rem;
            line-height: 1.6;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .grade-form {
            background-color: #0f172a;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #334155;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            color: #cbd5e1;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background-color: #1e293b;
            border: 2px solid #334155;
            border-radius: 8px;
            color: #e4e7eb;
            font-size: 1rem;
        }
        
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
            font-family: inherit;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            border: none;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(34, 197, 94, 0.4);
        }
        
        .info-box {
            background-color: rgba(59, 130, 246, 0.1);
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .info-box p {
            color: #94a3b8;
            line-height: 1.6;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="instructorDashboard.php" class="logo">Code Lab @ HELP</a>
        <div class="nav-links">
            <a href="instructorDashboard.php">Dashboard</a>
            <a href="instructor_browse.php">Browse</a>
            <a href="instructor_create.php">Create Exercise</a>
            <a href="instructor_assignments.php">Assignments</a>
            <a href="instructor_submissions.php" class="active">Submissions</a>
            <span style="color: #94a3b8;">👨‍🏫 <?php echo htmlspecialchars($instructor_name); ?></span>
            <a href="logout.php">Log Out</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>📝 Review Submissions</h1>
            <p>Grade student assignments and provide feedback</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                ✅ <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <div class="submissions-list">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="submission-card">
                        <div class="submission-header">
                            <div>
                                <div class="submission-title"><?php echo htmlspecialchars($row['exercise_title']); ?></div>
                                <div class="student-info">
                                    👤 <?php echo htmlspecialchars($row['student_name']); ?> 
                                    (<?php echo htmlspecialchars($row['student_email']); ?>)
                                </div>
                                <div class="student-info">
                                    <span class="lesson-badge">
                                        📚 <?php echo htmlspecialchars($row['lesson_title']); ?>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo $row['status']; ?>">
                                    <?php echo strtoupper($row['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="submission-details">
                            <div class="detail-item">
                                <span class="detail-label">Due Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($row['due_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Submitted</span>
                                <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($row['submitted_at'])); ?></span>
                            </div>
                            <?php if ($row['status'] === 'graded'): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Grade</span>
                                    <span class="detail-value"><?php echo $row['grade']; ?>/100</span>
                                </div>
                                <div class="detail-item">
                                    <span class="detail-label">Feedback</span>
                                    <span class="detail-value"><?php echo htmlspecialchars(substr($row['feedback'], 0, 50)); ?>...</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn btn-primary" onclick="viewSubmission(<?php echo $row['id']; ?>)">
                                👁️ View & Grade
                            </button>
                        </div>
                    </div>
                    
                    <!-- HIDDEN DATA FOR MODAL -->
                    <div id="submission-data-<?php echo $row['id']; ?>" style="display:none;">
                        <div data-title="<?php echo htmlspecialchars($row['exercise_title']); ?>"></div>
                        <div data-student="<?php echo htmlspecialchars($row['student_name']); ?>"></div>
                        <div data-instructions><?php echo htmlspecialchars($row['exercise_instructions']); ?></div>
                        <div data-submitted-code><?php echo htmlspecialchars($row['submitted_code']); ?></div>
                        <div data-solution><?php echo htmlspecialchars($row['solution_code']); ?></div>
                        <div data-status="<?php echo $row['status']; ?>"></div>
                        <div data-grade="<?php echo $row['grade'] ?? ''; ?>"></div>
                        <div data-feedback><?php echo htmlspecialchars($row['feedback'] ?? ''); ?></div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="submission-card">
                    <p style="color: #94a3b8; text-align: center; padding: 2rem;">
                        No submissions yet. Assignments will appear here once students submit their work.
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- VIEW SUBMISSION MODAL -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Review Submission</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="info-box">
                    <p><strong>Student:</strong> <span id="modalStudent"></span></p>
                    <p><strong>Exercise:</strong> <span id="modalExercise"></span></p>
                </div>
                
                <div class="code-section">
                    <div class="section-title">📋 Exercise Instructions</div>
                    <div class="code-block">
                        <pre id="modalInstructions"></pre>
                    </div>
                </div>
                
                <div class="code-section">
                    <div class="section-title">💻 Student's Submitted Code</div>
                    <div class="code-block">
                        <pre id="modalSubmittedCode"></pre>
                    </div>
                </div>
                
                <div class="code-section">
                    <div class="section-title">✅ Solution Code (Reference)</div>
                    <div class="code-block">
                        <pre id="modalSolution"></pre>
                    </div>
                </div>
                
                <div class="code-section">
                    <div class="section-title">📊 Grade & Feedback</div>
                    <form method="POST" class="grade-form" id="gradeForm">
                        <input type="hidden" name="assignment_id" id="assignmentId">
                        
                        <div class="form-group">
                            <label for="grade">Grade (0-100)</label>
                            <input type="number" id="grade" name="grade" min="0" max="100" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="feedback">Feedback for Student</label>
                            <textarea id="feedback" name="feedback" required placeholder="Provide detailed feedback to the student..."></textarea>
                        </div>
                        
                        <button type="submit" name="submit_grade" class="btn-submit">
                            ✅ Submit Grade
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function viewSubmission(id) {
            const modal = document.getElementById('viewModal');
            const data = document.getElementById('submission-data-' + id);
            
            // Get data
            const title = data.querySelector('[data-title]').dataset.title;
            const student = data.querySelector('[data-student]').dataset.student;
            const instructions = data.querySelector('[data-instructions]').textContent;
            const submittedCode = data.querySelector('[data-submitted-code]').textContent;
            const solution = data.querySelector('[data-solution]').textContent;
            const status = data.querySelector('[data-status]').dataset.status;
            const grade = data.querySelector('[data-grade]').dataset.grade;
            const feedback = data.querySelector('[data-feedback]').textContent;
            
            // Populate modal
            document.getElementById('modalTitle').textContent = 'Review: ' + title;
            document.getElementById('modalStudent').textContent = student;
            document.getElementById('modalExercise').textContent = title;
            document.getElementById('modalInstructions').textContent = instructions;
            document.getElementById('modalSubmittedCode').textContent = submittedCode;
            document.getElementById('modalSolution').textContent = solution;
            document.getElementById('assignmentId').value = id;
            
            // If already graded, pre-fill
            if (status === 'graded' && grade) {
                document.getElementById('grade').value = grade;
                document.getElementById('feedback').value = feedback;
            } else {
                document.getElementById('grade').value = '';
                document.getElementById('feedback').value = '';
            }
            
            // Show modal
            modal.classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('viewModal').classList.remove('active');
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target === modal) {
                closeModal();
            }
        }
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>