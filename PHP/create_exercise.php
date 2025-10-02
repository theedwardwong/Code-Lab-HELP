<?php
session_start();
include 'db_connect.php';

// Check if user is logged in and is an instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    header("Location: login.php");
    exit();
}

$instructor_id = $_SESSION['user_id'];
$instructor_name = $_SESSION['full_name'];

$success = '';
$error = '';

// Get all lessons for dropdown
$lessons_result = $conn->query("SELECT id, title, category FROM lessons WHERE is_published = 1 ORDER BY order_index");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = intval($_POST['lesson_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $instructions = trim($_POST['instructions']);
    $starter_code = trim($_POST['starter_code']);
    $solution_code = trim($_POST['solution_code']);
    $hints_raw = trim($_POST['hints']);
    if (!empty($hints_raw)) {
        // Split by newlines and create JSON array
        $hints_array = array_filter(array_map('trim', explode("\n", $hints_raw)));
        $hints = !empty($hints_array) ? json_encode($hints_array) : null;
    } else {
        $hints = null;
    }
    $points = intval($_POST['points']);
    $difficulty = $_POST['difficulty'];

    if (empty($title) || empty($instructions) || $lesson_id === 0) {
        $error = "Please fill in all required fields.";
    } else {
        // Insert exercise
        $stmt = $conn->prepare("
            INSERT INTO exercises 
            (lesson_id, title, description, instructions, starter_code, solution_code, hints, points, difficulty) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "issssssis",  // 9 characters: i-s-s-s-s-s-s-i-s
            $lesson_id,      // 1. integer
            $title,          // 2. string
            $description,    // 3. string
            $instructions,   // 4. string
            $starter_code,   // 5. string
            $solution_code,  // 6. string
            $hints,          // 7. string
            $points,         // 8. integer
            $difficulty      // 9. string
        );

        if ($stmt->execute()) {
            $exercise_id = $conn->insert_id;
            $success = "Exercise created successfully! Exercise ID: #$exercise_id";
        } else {
            $error = "Failed to create exercise. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Exercise - Code Lab @ HELP</title>
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

        .container {
            max-width: 1200px;
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

        .form-container {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .required {
            color: #f44336;
        }

        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #2e3f54;
            background-color: #2e3f54;
            color: white;
            font-family: inherit;
            font-size: 1rem;
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
            font-family: 'Courier New', monospace;
        }

        .form-group textarea.large {
            min-height: 200px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .difficulty-selector {
            display: flex;
            gap: 1rem;
        }

        .difficulty-option {
            flex: 1;
            padding: 0.8rem;
            border: 2px solid #2e3f54;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .difficulty-option:hover {
            border-color: #358efb;
        }

        .difficulty-option input[type="radio"] {
            display: none;
        }

        .difficulty-option input[type="radio"]:checked + label {
            background-color: #358efb;
            border-radius: 6px;
            padding: 0.5rem;
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: #358efb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2a72c9;
        }

        .btn-secondary {
            background-color: #666;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #555;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: bold;
        }

        .alert-success {
            background-color: #4caf50;
            color: white;
        }

        .alert-error {
            background-color: #f44336;
            color: white;
        }

        .helper-text {
            font-size: 0.85rem;
            color: #aaa;
            margin-top: 0.3rem;
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
            <h1>Create New Coding Exercise</h1>
            <p style="color: #aaa;">Build interactive coding challenges for your students</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" class="form-container">
            <div class="form-row">
                <div class="form-group">
                    <label>Lesson <span class="required">*</span></label>
                    <select name="lesson_id" required>
                        <option value="">Select a lesson</option>
                        <?php while ($lesson = $lessons_result->fetch_assoc()): ?>
                            <option value="<?php echo $lesson['id']; ?>">
                                <?php echo htmlspecialchars($lesson['title']); ?> (<?php echo ucfirst($lesson['category']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Points <span class="required">*</span></label>
                    <input type="number" name="points" min="5" max="100" value="10" required>
                    <div class="helper-text">Points awarded for completing this exercise</div>
                </div>
            </div>

            <div class="form-group">
                <label>Exercise Title <span class="required">*</span></label>
                <input type="text" name="title" placeholder="e.g., Create a Responsive Navigation Bar" required>
            </div>

            <div class="form-group">
                <label>Difficulty <span class="required">*</span></label>
                <div class="difficulty-selector">
                    <div class="difficulty-option">
                        <input type="radio" name="difficulty" value="easy" id="easy" checked>
                        <label for="easy">Easy</label>
                    </div>
                    <div class="difficulty-option">
                        <input type="radio" name="difficulty" value="medium" id="medium">
                        <label for="medium">Medium</label>
                    </div>
                    <div class="difficulty-option">
                        <input type="radio" name="difficulty" value="hard" id="hard">
                        <label for="hard">Hard</label>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Short Description</label>
                <textarea name="description" placeholder="Brief overview of what students will learn"></textarea>
            </div>

            <div class="form-group">
                <label>Instructions <span class="required">*</span></label>
                <textarea name="instructions" class="large" placeholder="Detailed step-by-step instructions for students" required></textarea>
                <div class="helper-text">Be clear and specific about requirements</div>
            </div>

            <div class="form-group">
                <label>Starter Code</label>
                <textarea name="starter_code" class="large" placeholder="// Initial code template for students&#10;function example() {&#10;  // Your code here&#10;}"></textarea>
                <div class="helper-text">Leave empty if you want students to start from scratch</div>
            </div>

            <div class="form-group">
                <label>Solution Code</label>
                <textarea name="solution_code" class="large" placeholder="// Complete working solution (hidden from students)"></textarea>
                <div class="helper-text">This will be used for reference and auto-grading</div>
            </div>

            <div class="form-group">
                <label>Hints</label>
                <textarea name="hints" placeholder="Helpful tips if students get stuck&#10;Separate multiple hints with line breaks"></textarea>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.location.href='instructorDashboard.php'">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Exercise</button>
            </div>
        </form>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'login.php';
            }
        }

        // Difficulty selector interaction
        document.querySelectorAll('.difficulty-option').forEach(option => {
            option.addEventListener('click', function() {
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
    </script>
</body>
</html>