<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['full_name'];

$success = '';
$error = '';

// Get all exercises for dropdown
$exercises_result = $conn->query("
    SELECT e.id, e.title, l.title as lesson_title 
    FROM exercises e 
    JOIN lessons l ON l.id = e.lesson_id 
    ORDER BY l.order_index, e.order_index
");

// Get all students
$students_result = $conn->query("SELECT id, full_name, email FROM users WHERE role = 'student' ORDER BY full_name");

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_assignment'])) {
    $exercise_id = intval($_POST['exercise_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $assigned_to = $_POST['assigned_to'];
    $due_date = $_POST['due_date'];
    $max_attempts = intval($_POST['max_attempts']);
    
    if (empty($title) || $exercise_id === 0) {
        $error = "Please fill in required fields.";
    } else {
        // Insert assignment
        $stmt = $conn->prepare("
            INSERT INTO assignments 
            (exercise_id, instructor_id, title, description, assigned_to, due_date, max_attempts) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iissssi", $exercise_id, $instructor_id, $title, $description, $assigned_to, $due_date, $max_attempts);
        
        if ($stmt->execute()) {
            $assignment_id = $conn->insert_id;
            
            // If assigned to specific students, add them
            if ($assigned_to === 'specific' && !empty($_POST['student_ids'])) {
                $student_ids = $_POST['student_ids'];
                $insert_students = $conn->prepare("
                    INSERT INTO assignment_students (assignment_id, student_id) VALUES (?, ?)
                ");
                
                foreach ($student_ids as $student_id) {
                    $sid = intval($student_id);
                    $insert_students->bind_param("ii", $assignment_id, $sid);
                    $insert_students->execute();
                }
            }
            
            $success = "Assignment created successfully!";
        } else {
            $error = "Failed to create assignment.";
        }
    }
}

// Get existing assignments
$assignments_query = "
    SELECT 
        a.*,
        e.title as exercise_title,
        l.title as lesson_title,
        COUNT(DISTINCT as_st.student_id) as assigned_count,
        COUNT(DISTINCT CASE WHEN as_st.status = 'submitted' THEN as_st.student_id END) as submitted_count
    FROM assignments a
    JOIN exercises e ON e.id = a.exercise_id
    JOIN lessons l ON l.id = e.lesson_id
    LEFT JOIN assignment_students as_st ON as_st.assignment_id = a.id
    WHERE a.instructor_id = ?
    GROUP BY a.id
    ORDER BY a.created_at DESC
";
$assignments_stmt = $conn->prepare($assignments_query);
$assignments_stmt->bind_param("i", $instructor_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Assignments - Code Lab @ HELP</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-header h1 { font-size: 2rem; }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background-color: #358efb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2a72c9;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.7);
            z-index: 1000;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            max-width: 700px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .close-btn {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #2e3f54;
            background-color: #2e3f54;
            color: white;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .student-selector {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #2e3f54;
            border-radius: 8px;
            padding: 0.5rem;
            background-color: #2e3f54;
        }

        .student-item {
            padding: 0.5rem;
            margin-bottom: 0.3rem;
        }

        .student-item label {
            font-weight: normal;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }

        .alert-success { background-color: #4caf50; }
        .alert-error { background-color: #f44336; }

        .assignments-grid {
            display: grid;
            gap: 1.5rem;
        }

        .assignment-card {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 4px solid #358efb;
        }

        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }

        .assignment-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 0.3rem;
        }

        .assignment-meta {
            display: flex;
            gap: 2rem;
            color: #aaa;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .badge-all { background-color: #4caf50; }
        .badge-specific { background-color: #ff9800; }

        .stats {
            display: flex;
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .stat-item {
            background-color: #2e3f54;
            padding: 0.8rem 1.2rem;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: #358efb;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #aaa;
        }

        #assignedToSelect {
            margin-bottom: 1rem;
        }

        #studentListContainer {
            display: none;
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
            <div>
                <h1>Manage Assignments</h1>
                <p style="color: #aaa;">Create and track coding assignments for your students</p>
            </div>
            <button class="btn btn-primary" onclick="openModal()">+ New Assignment</button>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="assignments-grid">
            <?php if ($assignments->num_rows > 0): ?>
                <?php while ($assignment = $assignments->fetch_assoc()): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <div>
                                <div class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                                <div style="color: #aaa;">
                                    <?php echo htmlspecialchars($assignment['exercise_title']); ?> • 
                                    <?php echo htmlspecialchars($assignment['lesson_title']); ?>
                                </div>
                            </div>
                            <span class="badge badge-<?php echo $assignment['assigned_to']; ?>">
                                <?php echo strtoupper($assignment['assigned_to']); ?>
                            </span>
                        </div>

                        <?php if ($assignment['description']): ?>
                            <p style="color: #ccc; margin-bottom: 1rem;">
                                <?php echo htmlspecialchars($assignment['description']); ?>
                            </p>
                        <?php endif; ?>

                        <div class="stats">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $assignment['assigned_count']; ?></div>
                                <div class="stat-label">Students Assigned</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $assignment['submitted_count']; ?></div>
                                <div class="stat-label">Submissions</div>
                            </div>
                        </div>

                        <div class="assignment-meta">
                            <span>Due: <?php echo $assignment['due_date'] ? date('M j, Y', strtotime($assignment['due_date'])) : 'No deadline'; ?></span>
                            <span>Max Attempts: <?php echo $assignment['max_attempts'] > 0 ? $assignment['max_attempts'] : 'Unlimited'; ?></span>
                            <span>Created: <?php echo date('M j, Y', strtotime($assignment['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="assignment-card">
                    <div style="text-align: center; padding: 2rem; color: #888;">
                        <h3>No assignments yet</h3>
                        <p>Create your first assignment to get started!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="assignmentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create New Assignment</h2>
                <button class="close-btn" onclick="closeModal()">&times;</button>
            </div>

            <form method="POST">
                <div class="form-group">
                    <label>Exercise <span style="color: #f44336;">*</span></label>
                    <select name="exercise_id" required>
                        <option value="">Select an exercise</option>
                        <?php 
                        $exercises_result->data_seek(0);
                        while ($exercise = $exercises_result->fetch_assoc()): 
                        ?>
                            <option value="<?php echo $exercise['id']; ?>">
                                <?php echo htmlspecialchars($exercise['title']); ?> (<?php echo htmlspecialchars($exercise['lesson_title']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Assignment Title <span style="color: #f44336;">*</span></label>
                    <input type="text" name="title" placeholder="e.g., Week 5 Homework" required>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" placeholder="Additional instructions or context"></textarea>
                </div>

                <div class="form-group">
                    <label>Assign To <span style="color: #f44336;">*</span></label>
                    <select name="assigned_to" id="assignedToSelect" required onchange="toggleStudentList()">
                        <option value="all">All Students</option>
                        <option value="specific">Specific Students</option>
                    </select>
                </div>

                <div class="form-group" id="studentListContainer">
                    <label>Select Students</label>
                    <div class="student-selector">
                        <?php 
                        $students_result->data_seek(0);
                        while ($student = $students_result->fetch_assoc()): 
                        ?>
                            <div class="student-item">
                                <label>
                                    <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>">
                                    <?php echo htmlspecialchars($student['full_name']); ?> (<?php echo htmlspecialchars($student['email']); ?>)
                                </label>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label>Due Date</label>
                    <input type="datetime-local" name="due_date">
                </div>

                <div class="form-group">
                    <label>Max Attempts (0 = unlimited)</label>
                    <input type="number" name="max_attempts" value="0" min="0">
                </div>

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn" style="background-color: #666; color: white;" onclick="closeModal()">Cancel</button>
                    <button type="submit" name="create_assignment" class="btn btn-primary">Create Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'login.php';
            }
        }

        function openModal() {
            document.getElementById('assignmentModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('assignmentModal').classList.remove('active');
        }

        function toggleStudentList() {
            const select = document.getElementById('assignedToSelect');
            const container = document.getElementById('studentListContainer');
            
            if (select.value === 'specific') {
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('assignmentModal');
            if (event.target === modal) {
                closeModal();
            }
        }
    </script>
</body>
</html> 