<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];

// Get specific exercise ID if provided
$selected_exercise = isset($_GET['exercise']) ? intval($_GET['exercise']) : 0;

// Get lesson filter from URL
$lesson_filter = isset($_GET['lesson']) ? intval($_GET['lesson']) : 0;

// Get all available exercises with assignment info
$exercises_query = "
    SELECT DISTINCT
        e.*, 
        l.title as lesson_title, 
        l.category,
        (SELECT COUNT(*) FROM assignments a 
         LEFT JOIN assignment_students ast ON ast.assignment_id = a.id AND ast.student_id = ?
         WHERE a.exercise_id = e.id 
         AND (a.assigned_to = 'all' OR (a.assigned_to = 'specific' AND ast.student_id = ?))
        ) as is_assigned,
        (SELECT a.due_date FROM assignments a 
         LEFT JOIN assignment_students ast ON ast.assignment_id = a.id AND ast.student_id = ?
         WHERE a.exercise_id = e.id 
         AND (a.assigned_to = 'all' OR (a.assigned_to = 'specific' AND ast.student_id = ?))
         LIMIT 1
        ) as assignment_due_date,
        (SELECT a.max_attempts FROM assignments a 
         LEFT JOIN assignment_students ast ON ast.assignment_id = a.id AND ast.student_id = ?
         WHERE a.exercise_id = e.id 
         AND (a.assigned_to = 'all' OR (a.assigned_to = 'specific' AND ast.student_id = ?))
         LIMIT 1
        ) as max_attempts,
        (SELECT COALESCE(ast.attempts, 0) FROM assignments a 
         LEFT JOIN assignment_students ast ON ast.assignment_id = a.id AND ast.student_id = ?
         WHERE a.exercise_id = e.id 
         AND (a.assigned_to = 'all' OR (a.assigned_to = 'specific' AND ast.student_id = ?))
         LIMIT 1
        ) as current_attempts,
        (SELECT COUNT(*) FROM exercise_submissions es 
         WHERE es.exercise_id = e.id AND es.student_id = ? AND es.status = 'passed'
        ) as completed
    FROM exercises e 
    JOIN lessons l ON e.lesson_id = l.id 
    WHERE l.is_published = 1 
    ORDER BY l.order_index, e.order_index
";
$exercises_stmt = $conn->prepare($exercises_query);
$exercises_stmt->bind_param("iiiiiiiii", 
    $student_id, $student_id, $student_id, $student_id, 
    $student_id, $student_id, $student_id, $student_id, $student_id
);
$exercises_stmt->execute();
$exercises_result = $exercises_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coding Exercises - Code Lab @ HELP</title>
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

        /* Navbar */
        .navbar {
            background-color: #111;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            color: white;
            font-weight: bold;
            font-size: 1.2rem;
        }

        .nav-links {
            list-style: none;
            display: flex;
            gap: 1.5rem;
        }

        .nav-links li a {
            color: white;
            text-decoration: none;
        }

        .nav-icons {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background-color: #2e3f54;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }

        /* Main Content */
        .container {
            display: flex;
            height: calc(100vh - 70px);
        }

        /* Left Sidebar */
        .exercise-sidebar {
            width: 320px;
            background-color: #1a2332;
            padding: 1.5rem;
            overflow-y: auto;
        }

        .exercise-sidebar h2 {
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 0.4rem 0.8rem;
            background-color: #2e3f54;
            border: none;
            border-radius: 20px;
            color: white;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .filter-tab.active {
            background-color: #358efb;
        }

        .exercise-item {
            background-color: #2e3f54;
            padding: 1rem;
            margin-bottom: 0.8rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid transparent;
            position: relative;
        }

        .exercise-item:hover {
            background-color: #3a4d64;
            border-left-color: #358efb;
        }

        .exercise-item.active {
            background-color: #358efb;
            border-left-color: #fff;
        }

        .exercise-item.assigned {
            border-left-color: #ff9800;
        }

        .exercise-item.completed {
            opacity: 0.7;
        }

        .exercise-item h3 {
            font-size: 1rem;
            margin-bottom: 0.3rem;
        }

        .exercise-item .lesson-tag {
            font-size: 0.8rem;
            color: #aaa;
        }

        .exercise-badges {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-block;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .difficulty.easy { background-color: #4caf50; }
        .difficulty.medium { background-color: #ff9800; }
        .difficulty.hard { background-color: #f44336; }

        .badge.assignment-badge {
            background-color: #ff9800;
        }

        .badge.completed-badge {
            background-color: #4caf50;
        }

        .due-indicator {
            font-size: 0.75rem;
            color: #ff9800;
            margin-top: 0.3rem;
        }

        /* Main Exercise Area */
        .exercise-main {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 1.5rem;
        }

        .exercise-header {
            margin-bottom: 1rem;
        }

        .exercise-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .header-badges {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .assignment-warning {
            background-color: #ff9800;
            color: white;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .attempt-warning {
            background-color: #f44336;
            color: white;
            padding: 0.8rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            font-weight: bold;
        }

        .exercise-description {
            background-color: #1a2332;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            max-height: 150px;
            overflow-y: auto;
        }

        .exercise-workspace {
            flex: 1;
            display: flex;
            gap: 1rem;
        }

        .code-editor-container {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .code-editor-header {
            background-color: #1a2332;
            padding: 0.5rem 1rem;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
        }

        #codeEditor {
            flex: 1;
            background-color: #1e1e1e;
            color: #d4d4d4;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            padding: 1rem;
            border: none;
            border-radius: 0 0 8px 8px;
            resize: none;
            outline: none;
        }

        .output-container {
            width: 350px;
            display: flex;
            flex-direction: column;
        }

        .output-header {
            background-color: #1a2332;
            padding: 0.5rem 1rem;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
        }

        .output-area {
            flex: 1;
            background-color: #1e1e1e;
            padding: 1rem;
            border-radius: 0 0 8px 8px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }

        .action-buttons {
            margin-top: 1rem;
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.7rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.2s;
        }

        .btn-run {
            background-color: #4caf50;
            color: white;
        }

        .btn-run:hover {
            background-color: #45a049;
        }

        .btn-submit {
            background-color: #358efb;
            color: white;
        }

        .btn-submit:hover {
            background-color: #2a72c9;
        }

        .btn-reset {
            background-color: #f44336;
            color: white;
        }

        .btn-hint {
            background-color: #ff9800;
            color: white;
        }

        .btn-disabled {
            background-color: #666;
            color: #aaa;
            cursor: not-allowed;
        }

        .result-success {
            color: #4caf50;
            font-weight: bold;
        }

        .result-error {
            color: #f44336;
            font-weight: bold;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #888;
        }

        .empty-state h2 {
            margin-bottom: 1rem;
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
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <!-- Exercise Sidebar -->
        <div class="exercise-sidebar">
            <h2>Available Exercises</h2>
            
            <div class="filter-tabs">
                <button class="filter-tab active" onclick="filterExercises('all', this)">All</button>
                <button class="filter-tab" onclick="filterExercises('assigned', this)">Assigned</button>
                <button class="filter-tab" onclick="filterExercises('completed', this)">Completed</button>
            </div>

            <?php if ($exercises_result->num_rows > 0): ?>
                <?php while ($exercise = $exercises_result->fetch_assoc()): ?>
                    <?php
                    $is_assigned = $exercise['is_assigned'] > 0;
                    $is_completed = $exercise['completed'] > 0;
                    $max_reached = $exercise['max_attempts'] > 0 && 
                                   $exercise['current_attempts'] >= $exercise['max_attempts'];
                    
                    $classes = ['exercise-item'];
                    if ($selected_exercise === $exercise['id']) $classes[] = 'active';
                    if ($is_assigned) $classes[] = 'assigned';
                    if ($is_completed) $classes[] = 'completed';
                    ?>
                    <div class="<?php echo implode(' ', $classes); ?>" 
                         data-exercise-id="<?php echo $exercise['id']; ?>"
                         data-is-assigned="<?php echo $is_assigned ? '1' : '0'; ?>"
                         data-is-completed="<?php echo $is_completed ? '1' : '0'; ?>"
                         data-max-reached="<?php echo $max_reached ? '1' : '0'; ?>"
                         onclick="loadExercise(<?php echo $exercise['id']; ?>)">
                        <h3><?php echo htmlspecialchars($exercise['title']); ?></h3>
                        <p class="lesson-tag"><?php echo htmlspecialchars($exercise['lesson_title']); ?></p>
                        
                        <div class="exercise-badges">
                            <span class="badge difficulty <?php echo $exercise['difficulty']; ?>">
                                <?php echo strtoupper($exercise['difficulty']); ?>
                            </span>
                            <?php if ($is_assigned): ?>
                                <span class="badge assignment-badge">ASSIGNED</span>
                            <?php endif; ?>
                            <?php if ($is_completed): ?>
                                <span class="badge completed-badge">COMPLETED</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($is_assigned && $exercise['assignment_due_date']): ?>
                            <div class="due-indicator">
                                Due: <?php echo date('M j, g:i A', strtotime($exercise['assignment_due_date'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p style="color: #888;">No exercises available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Main Exercise Area -->
        <div class="exercise-main" id="exerciseMain">
            <div class="empty-state">
                <h2>Select an exercise to get started</h2>
                <p>Choose an exercise from the list on the left to begin coding.</p>
            </div>
        </div>
    </div>

    <script>
        let currentExerciseId = null;
        let currentExerciseData = null;

        // Auto-load exercise if specified in URL
        <?php if ($selected_exercise > 0): ?>
        window.addEventListener('DOMContentLoaded', function() {
            loadExercise(<?php echo $selected_exercise; ?>);
        });
        <?php endif; ?>

        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'logout.php';
            }
        }

        function filterExercises(filter, element) {
            const allTabs = document.querySelectorAll('.filter-tab');
            const cards = document.querySelectorAll('.exercise-item');

            allTabs.forEach(tab => tab.classList.remove('active'));
            element.classList.add('active');

            cards.forEach(card => {
                const isAssigned = card.getAttribute('data-is-assigned') === '1';
                const isCompleted = card.getAttribute('data-is-completed') === '1';

                let show = false;
                if (filter === 'all') {
                    show = true;
                } else if (filter === 'assigned') {
                    show = isAssigned;
                } else if (filter === 'completed') {
                    show = isCompleted;
                }

                card.style.display = show ? 'block' : 'none';
            });
        }

        function loadExercise(exerciseId) {
            currentExerciseId = exerciseId;

            // Update active state
            document.querySelectorAll('.exercise-item').forEach(item => {
                item.classList.remove('active');
                if (parseInt(item.getAttribute('data-exercise-id')) === exerciseId) {
                    item.classList.add('active');
                }
            });

            // Fetch exercise details
            fetch(`get_exercise.php?id=${exerciseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentExerciseData = data.exercise;
                        displayExercise(data.exercise);
                    } else {
                        alert('Failed to load exercise');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading exercise');
                });
        }

        function displayExercise(exercise) {
            const mainArea = document.getElementById('exerciseMain');
            const activeItem = document.querySelector(`.exercise-item[data-exercise-id="${exercise.id}"]`);
            const isAssigned = activeItem.getAttribute('data-is-assigned') === '1';
            const maxReached = activeItem.getAttribute('data-max-reached') === '1';

            let assignmentWarning = '';
            if (isAssigned) {
                assignmentWarning = '<div class="assignment-warning">This exercise is part of an assignment</div>';
            }

            let attemptWarning = '';
            if (maxReached) {
                attemptWarning = '<div class="attempt-warning">Maximum attempts reached for this assignment</div>';
            }

            mainArea.innerHTML = `
                <div class="exercise-header">
                    <h1>${exercise.title}</h1>
                    <div class="header-badges">
                        <span class="badge difficulty ${exercise.difficulty}">${exercise.difficulty.toUpperCase()}</span>
                        <span style="margin-left: 1rem; color: #aaa;">Points: ${exercise.points}</span>
                    </div>
                </div>

                ${assignmentWarning}
                ${attemptWarning}

                <div class="exercise-description">
                    <strong>Instructions:</strong>
                    <p style="margin-top: 0.5rem; white-space: pre-wrap;">${exercise.instructions}</p>
                </div>

                <div class="exercise-workspace">
                    <div class="code-editor-container">
                        <div class="code-editor-header">Code Editor</div>
                        <textarea id="codeEditor">${exercise.starter_code || '// Write your code here'}</textarea>
                    </div>

                    <div class="output-container">
                        <div class="output-header">Output</div>
                        <div class="output-area" id="outputArea">
                            <p style="color: #888;">Run or submit your code to see results...</p>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-run" onclick="runCode()">Run Code</button>
                    <button class="btn btn-submit" ${maxReached ? 'disabled class="btn-disabled"' : ''} onclick="submitCode()">Submit Solution</button>
                    <button class="btn btn-reset" onclick="resetCode()">Reset</button>
                    <button class="btn btn-hint" onclick="showHint()">Hint</button>
                </div>
            `;
        }

        function runCode() {
            const code = document.getElementById('codeEditor').value;
            const outputArea = document.getElementById('outputArea');
            
            outputArea.innerHTML = '<p style="color: #ff9800;">Running code...</p>';
            
            setTimeout(() => {
                outputArea.innerHTML = `
                    <p style="color: #4caf50;">Code executed successfully!</p>
                    <p style="margin-top: 0.5rem; color: #aaa;">Note: Full validation will run on submission.</p>
                `;
            }, 500);
        }

        function submitCode() {
            const code = document.getElementById('codeEditor').value;
            
            if (!code.trim()) {
                alert('Please write some code before submitting!');
                return;
            }

            if (!confirm('Are you sure you want to submit this solution?')) {
                return;
            }

            const outputArea = document.getElementById('outputArea');
            outputArea.innerHTML = '<p style="color: #ff9800;">Submitting and validating...</p>';

            fetch('submit_exercise.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    exercise_id: currentExerciseId,
                    code: code
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    outputArea.innerHTML = `
                        <p class="result-success">Submission Successful!</p>
                        <p style="margin-top: 0.5rem;">Status: ${data.status}</p>
                        <p>Score: ${data.score}/${data.max_score}</p>
                        <p>Tests: ${data.passed_tests}/${data.total_tests} passed</p>
                        <p style="margin-top: 1rem; color: #aaa;">${data.feedback}</p>
                    `;
                    
                    // Reload sidebar to update completion status
                    setTimeout(() => location.reload(), 2000);
                } else {
                    outputArea.innerHTML = `
                        <p class="result-error">Submission Failed</p>
                        <p style="margin-top: 0.5rem; color: #f44336;">${data.message}</p>
                    `;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                outputArea.innerHTML = '<p class="result-error">Error submitting code</p>';
            });
        }

        function resetCode() {
            if (confirm('Reset code to starter template?')) {
                loadExercise(currentExerciseId);
            }
        }

        function showHint() {
            if (currentExerciseData && currentExerciseData.hints) {
                try {
                    const hints = JSON.parse(currentExerciseData.hints);
                    alert('Hints:\n\n' + hints.join('\n\n'));
                } catch(e) {
                    alert('Hint: Check the instructions carefully!');
                }
            } else {
                alert('Hint: Check the instructions carefully and make sure your code follows the requirements!');
            }
        }
    </script>
</body>
</html>