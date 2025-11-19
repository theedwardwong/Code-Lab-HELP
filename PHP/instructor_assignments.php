<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['full_name'];
$instructor_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle assignment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_exercise'])) {
    $exercise_id = intval($_POST['exercise_id']);
    $exercise_title = $_POST['exercise_title'];
    $student_ids = $_POST['student_ids'] ?? [];
    $due_date = $_POST['due_date'];
    
    if (empty($student_ids)) {
        $error = "Please select at least one student.";
    } elseif (empty($due_date)) {
        $error = "Please select a due date.";
    } else {
        $success_count = 0;
        $due_datetime = $due_date . ' 23:59:59'; // Set to end of day
        
        foreach ($student_ids as $student_id) {
            $student_id = intval($student_id);
            
            // Check if assignment already exists
            $check = $conn->prepare("
                SELECT id FROM assignments 
                WHERE exercise_id = ? AND student_id = ? AND instructor_id = ?
            ");
            $check->bind_param("iii", $exercise_id, $student_id, $instructor_id);
            $check->execute();
            $exists = $check->get_result();
            
            if ($exists->num_rows === 0) {
                // Create new assignment using your existing table structure
                $stmt = $conn->prepare("
                    INSERT INTO assignments 
                    (exercise_id, instructor_id, student_id, title, description, due_date, status) 
                    VALUES (?, ?, ?, ?, '', ?, 'pending')
                ");
                $stmt->bind_param("iiiss", $exercise_id, $instructor_id, $student_id, $exercise_title, $due_datetime);
                
                if ($stmt->execute()) {
                    $success_count++;
                }
            }
        }
        
        if ($success_count > 0) {
            $success = "Successfully assigned to $success_count student(s)!";
        } else {
            $error = "Assignment already exists for selected student(s).";
        }
    }
}

// Get all students
$students_query = "SELECT id, full_name, email FROM users WHERE role = 'student' ORDER BY full_name";
$students = $conn->query($students_query);

// Get all exercises with assignments count
$assignments_query = "
    SELECT 
        l.title as lesson_title, 
        l.category, 
        e.title as exercise_title, 
        e.difficulty, 
        e.id as exercise_id,
        COUNT(DISTINCT a.id) as assignments_count
    FROM exercises e
    INNER JOIN lessons l ON l.id = e.lesson_id
    LEFT JOIN assignments a ON a.exercise_id = e.id
    GROUP BY e.id
    ORDER BY l.created_at DESC, e.id DESC
";
$assignments = $conn->query($assignments_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Manage Assignments - Code Lab @ HELP</title>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
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
      margin: 0;
      padding: 0;
    }
    .nav-links li a {
      color: #9ca3af;
      text-decoration: none;
      padding: 0.6rem 1rem;
      border-radius: 6px;
      font-size: 0.95rem;
    }
    .nav-links li a:hover,
    .nav-links li a.active {
      background-color: #1e293b;
      color: white;
    }
    .nav-icons {
      display: flex;
      align-items: center;
      gap: 1.2rem;
    }
    .username {
      font-weight: 600;
      color: #e4e7eb;
    }
    .logout-btn {
      background-color: #1e293b;
      color: white;
      border: 1px solid #334155;
      padding: 0.5rem 1.2rem;
      cursor: pointer;
      border-radius: 6px;
      font-size: 0.9rem;
    }
    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 2rem;
    }
    h2 {
      font-size: 2rem;
      margin-bottom: 0.5rem;
      color: #f1f5f9;
    }
    .subtitle {
      color: #94a3b8;
      margin-bottom: 2rem;
    }
    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
    }
    .alert-success {
      background-color: #064e3b;
      border: 1px solid #10b981;
      color: #6ee7b7;
    }
    .alert-error {
      background-color: #7f1d1d;
      border: 1px solid #ef4444;
      color: #fca5a5;
    }
    .assignments-box {
      background-color: #1e293b;
      border-radius: 12px;
      padding: 2rem;
      border: 1px solid #334155;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th {
      background-color: #0f172a;
      padding: 1rem;
      text-align: left;
      font-weight: 600;
      color: #f1f5f9;
      border-bottom: 2px solid #334155;
    }
    td {
      padding: 1rem;
      border-bottom: 1px solid #334155;
      color: #cbd5e1;
    }
    tr:hover {
      background-color: #0f172a;
    }
    .badge {
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .badge-frontend { background-color: #ff6b6b; color: white; }
    .badge-backend { background-color: #4ecdc4; color: white; }
    .badge-fullstack { background-color: #95e1d3; color: white; }
    .difficulty {
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.8rem;
    }
    .difficulty-easy { background-color: #4caf50; color: white; }
    .difficulty-medium { background-color: #ff9800; color: white; }
    .difficulty-hard { background-color: #f44336; color: white; }
    .btn-assign {
      padding: 0.5rem 1rem;
      background-color: #3b82f6;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: 600;
    }
    .assignments-count {
      display: inline-block;
      background-color: #0f172a;
      padding: 0.3rem 0.8rem;
      border-radius: 12px;
      font-size: 0.85rem;
      margin-left: 0.5rem;
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.7);
      overflow-y: auto;
    }
    .modal-content {
      background-color: #1e293b;
      margin: 5% auto;
      padding: 2rem;
      border-radius: 12px;
      width: 90%;
      max-width: 600px;
      border: 1px solid #334155;
      max-height: 80vh;
      overflow-y: auto;
    }
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }
    .modal-title {
      font-size: 1.5rem;
      color: #f1f5f9;
      font-weight: 600;
    }
    .close {
      color: #94a3b8;
      font-size: 2rem;
      font-weight: bold;
      cursor: pointer;
      line-height: 1;
    }
    .close:hover {
      color: #f1f5f9;
    }
    .form-group {
      margin-bottom: 1.5rem;
    }
    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-weight: 600;
      color: #f1f5f9;
    }
    .form-group input[type="date"] {
      width: 100%;
      padding: 0.8rem;
      border: 1px solid #334155;
      border-radius: 8px;
      background-color: #0f172a;
      color: white;
      font-size: 0.95rem;
    }
    .students-list {
      max-height: 300px;
      overflow-y: auto;
      border: 1px solid #334155;
      border-radius: 8px;
      padding: 1rem;
      background-color: #0f172a;
    }
    .student-checkbox {
      display: flex;
      align-items: center;
      padding: 0.8rem;
      margin-bottom: 0.5rem;
      background-color: #1e293b;
      border-radius: 6px;
      cursor: pointer;
      transition: all 0.2s;
    }
    .student-checkbox:hover {
      background-color: #334155;
    }
    .student-checkbox input[type="checkbox"] {
      width: 18px;
      height: 18px;
      margin-right: 1rem;
      cursor: pointer;
    }
    .student-info {
      flex: 1;
    }
    .student-name {
      font-weight: 600;
      color: #f1f5f9;
      margin-bottom: 0.2rem;
    }
    .student-email {
      font-size: 0.85rem;
      color: #94a3b8;
    }
    .select-all {
      padding: 0.8rem;
      margin-bottom: 1rem;
      background-color: #0f172a;
      border-radius: 6px;
      display: flex;
      align-items: center;
      cursor: pointer;
    }
    .select-all input[type="checkbox"] {
      width: 18px;
      height: 18px;
      margin-right: 1rem;
      cursor: pointer;
    }
    .modal-actions {
      display: flex;
      gap: 1rem;
      justify-content: flex-end;
      margin-top: 2rem;
    }
    .btn-cancel {
      padding: 0.8rem 1.5rem;
      background-color: #6b7280;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }
    .btn-submit {
      padding: 0.8rem 1.5rem;
      background-color: #3b82f6;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
    }
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
      <li><a href="instructor_assignments.php" class="active">Assignments</a></li>
      <li><a href="instructor_submissions.php">Submissions</a></li>
    </ul>
    <div class="nav-icons">
      <span class="icon">🔔</span>
      <span class="icon">⚙️</span>
      <span class="icon">👤</span>
      <span class="username"><?php echo htmlspecialchars($instructor_name); ?></span>
      <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
    </div>
  </nav>

  <div class="container">
    <h2>Manage Assignments</h2>
    <p class="subtitle">Assign exercises to students</p>

    <?php if ($success): ?>
      <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="assignments-box">
      <table>
        <thead>
          <tr>
            <th>Lesson</th>
            <th>Exercise</th>
            <th>Category</th>
            <th>Difficulty</th>
            <th>Assigned</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($assignments->num_rows > 0): ?>
            <?php while ($assignment = $assignments->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($assignment['lesson_title']); ?></td>
                <td><?php echo htmlspecialchars($assignment['exercise_title']); ?></td>
                <td>
                  <span class="badge badge-<?php echo $assignment['category']; ?>">
                    <?php echo strtoupper($assignment['category']); ?>
                  </span>
                </td>
                <td>
                  <span class="difficulty difficulty-<?php echo $assignment['difficulty']; ?>">
                    <?php echo strtoupper($assignment['difficulty']); ?>
                  </span>
                </td>
                <td>
                  <span class="assignments-count">
                    <?php echo $assignment['assignments_count']; ?> student(s)
                  </span>
                </td>
                <td>
                  <button class="btn-assign" onclick="openAssignModal(<?php echo $assignment['exercise_id']; ?>, '<?php echo htmlspecialchars($assignment['exercise_title'], ENT_QUOTES); ?>')">
                    Assign to Students
                  </button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align: center; padding: 2rem; color: #94a3b8;">
                No exercises available yet. <a href="instructor_create_exercise.php" style="color: #3b82f6;">Create your first exercise!</a>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Assignment Modal -->
  <div id="assignModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="modalTitle">Assign Exercise to Students</h3>
        <span class="close" onclick="closeAssignModal()">&times;</span>
      </div>

      <form method="POST" action="instructor_assignments.php">
        <input type="hidden" name="exercise_id" id="exercise_id">
        <input type="hidden" name="exercise_title" id="exercise_title">

        <div class="form-group">
          <label>Due Date <span style="color: #f87171;">*</span></label>
          <input type="date" name="due_date" required 
                 min="<?php echo date('Y-m-d'); ?>">
        </div>

        <div class="form-group">
          <label>Select Students <span style="color: #f87171;">*</span></label>
          
          <div class="select-all" onclick="toggleSelectAll()">
            <input type="checkbox" id="selectAll">
            <label for="selectAll" style="cursor: pointer; margin: 0;">Select All Students</label>
          </div>

          <div class="students-list">
            <?php 
            $students->data_seek(0);
            if ($students->num_rows > 0):
              while ($student = $students->fetch_assoc()): 
            ?>
              <label class="student-checkbox">
                <input type="checkbox" name="student_ids[]" value="<?php echo $student['id']; ?>" class="student-checkbox-input">
                <div class="student-info">
                  <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                  <div class="student-email"><?php echo htmlspecialchars($student['email']); ?></div>
                </div>
              </label>
            <?php 
              endwhile;
            else:
            ?>
              <p style="color: #94a3b8; text-align: center; padding: 1rem;">No students found.</p>
            <?php endif; ?>
          </div>
        </div>

        <div class="modal-actions">
          <button type="button" class="btn-cancel" onclick="closeAssignModal()">Cancel</button>
          <button type="submit" name="assign_exercise" class="btn-submit">Assign Exercise</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function confirmLogout() {
      if (confirm("Are you sure you want to log out?")) {
        window.location.href = 'logout.php';
      }
    }

    function openAssignModal(exerciseId, exerciseTitle) {
      document.getElementById('exercise_id').value = exerciseId;
      document.getElementById('exercise_title').value = exerciseTitle;
      document.getElementById('modalTitle').textContent = 'Assign "' + exerciseTitle + '" to Students';
      document.getElementById('assignModal').style.display = 'block';
      
      // Reset checkboxes
      document.getElementById('selectAll').checked = false;
      const checkboxes = document.querySelectorAll('.student-checkbox-input');
      checkboxes.forEach(cb => cb.checked = false);
    }

    function closeAssignModal() {
      document.getElementById('assignModal').style.display = 'none';
    }

    function toggleSelectAll() {
      const selectAll = document.getElementById('selectAll');
      const checkboxes = document.querySelectorAll('.student-checkbox-input');
      
      checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
      });
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('assignModal');
      if (event.target == modal) {
        closeAssignModal();
      }
    }
  </script>
</body>
</html>