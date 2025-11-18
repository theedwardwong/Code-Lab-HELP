<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];
$lesson_id = 2; // CSS lesson

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $total = 10;
    
    $correct_answers = [
        'q1' => 'Cascading Style Sheets',
        'q2' => '<link rel="stylesheet" href="style.css">',
        'q3' => 'color',
        'q4' => 'margin',
        'q5' => '#FF0000',
        'q6' => 'font-size',
        'q7' => 'border',
        'q8' => 'display: flex;',
        'q9' => 'class',
        'q10' => ':hover'
    ];
    
    foreach ($correct_answers as $question => $correct) {
        if (isset($_POST[$question]) && trim($_POST[$question]) === $correct) {
            $score++;
        }
    }
    
    $passed = ($score >= 7);
    
    $check_progress = $conn->prepare("SELECT id FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = ?");
    $check_progress->bind_param("ii", $student_id, $lesson_id);
    $check_progress->execute();
    
    if ($check_progress->get_result()->num_rows > 0) {
        $update = $conn->prepare("UPDATE lesson_quiz_progress SET quiz_score = ?, quiz_total = ?, quiz_passed = ?, attempts = attempts + 1, completed_at = NOW() WHERE student_id = ? AND lesson_id = ?");
        $update->bind_param("iiiii", $score, $total, $passed, $student_id, $lesson_id);
        $update->execute();
    } else {
        $insert = $conn->prepare("INSERT INTO lesson_quiz_progress (student_id, lesson_id, quiz_score, quiz_total, quiz_passed, attempts, completed_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $insert->bind_param("iiiii", $student_id, $lesson_id, $score, $total, $passed);
        $insert->execute();
    }
    
    $_SESSION['quiz_answers'] = $_POST;
    $_SESSION['quiz_score'] = $score;
    $_SESSION['quiz_total'] = $total;
    $_SESSION['quiz_passed'] = $passed;
    
    header("Location: quiz_css.php?results=1");
    exit();
}

$showing_results = isset($_GET['results']) && isset($_SESSION['quiz_score']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Quiz | Code Lab @ HELP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #2e3f54; color: white; }
        .navbar { background-color: #111; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .logo a { color: white; text-decoration: none; }
        .nav-links { list-style: none; display: flex; gap: 1.5rem; }
        .nav-links li a { color: white; text-decoration: none; }
        .logout-btn { background-color: #2e3f54; color: white; border: none; padding: 0.4rem 1rem; border-radius: 5px; cursor: pointer; }
        .container { max-width: 900px; margin: 0 auto; padding: 2rem; }
        .quiz-header { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 2rem; border-radius: 12px; margin-bottom: 2rem; text-align: center; }
        .quiz-header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .question-card { background-color: #1a2332; padding: 2rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid #f093fb; }
        .question-number { color: #f093fb; font-weight: bold; font-size: 1.2rem; margin-bottom: 0.5rem; }
        .question-text { font-size: 1.1rem; margin-bottom: 1.5rem; line-height: 1.6; }
        .options { display: flex; flex-direction: column; gap: 1rem; }
        .option { background-color: #2e3f54; padding: 1rem; border-radius: 8px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; display: flex; align-items: center; gap: 1rem; }
        .option:hover { background-color: #3a4d64; border-color: #f093fb; }
        .option input[type="radio"] { width: 20px; height: 20px; cursor: pointer; }
        .fill-blank { background-color: #2e3f54; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .fill-blank input { width: 100%; padding: 0.8rem; border: 2px solid #f093fb; border-radius: 6px; background-color: #1a2332; color: white; font-size: 1rem; font-family: 'Courier New', monospace; }
        .submit-btn { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; padding: 1rem 3rem; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; width: 100%; margin-top: 2rem; transition: all 0.3s; }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(240, 147, 251, 0.3); }
        .results-container { background-color: #1a2332; padding: 3rem; border-radius: 12px; text-align: center; }
        .score-display { font-size: 4rem; font-weight: bold; margin: 2rem 0; }
        .score-display.passed { color: #4caf50; }
        .score-display.failed { color: #f44336; }
        .result-message { font-size: 1.3rem; margin-bottom: 2rem; }
        .answer-review { background-color: #2e3f54; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; text-align: left; }
        .correct-answer { border-left: 4px solid #4caf50; }
        .wrong-answer { border-left: 4px solid #f44336; }
        .answer-feedback { display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem; }
        .feedback-icon { font-size: 2rem; }
        .correct-answer-text { color: #4caf50; font-weight: bold; margin-top: 0.5rem; }
        .action-buttons { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; }
        .action-btn { padding: 1rem 2rem; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .retry-btn { background-color: #ff9800; color: white; }
        .next-btn { background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; }
        .back-btn { background-color: #666; color: white; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo"><a href="studentDashboard.php">Code Lab @ HELP</a></div>
        <ul class="nav-links">
            <li><a href="studentDashboard.php">Dashboard</a></li>
            <li><a href="learning_hub.php">Learning Hub</a></li>
            <li><a href="lesson_css.php">Back to Lesson</a></li>
        </ul>
        <div>
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <?php if ($showing_results): ?>
            <div class="quiz-header">
                <h1>📊 CSS Quiz Results</h1>
                <p>Here's how you did on the CSS quiz!</p>
            </div>

            <div class="results-container">
                <h2><?php echo $_SESSION['quiz_passed'] ? '🎉 Fantastic!' : '📚 Keep Learning!'; ?></h2>
                <div class="score-display <?php echo $_SESSION['quiz_passed'] ? 'passed' : 'failed'; ?>">
                    <?php echo $_SESSION['quiz_score']; ?> / <?php echo $_SESSION['quiz_total']; ?>
                </div>
                <div class="result-message">
                    <?php if ($_SESSION['quiz_passed']): ?>
                        <p style="color: #4caf50;">Outstanding! You've mastered CSS basics! 🎨</p>
                        <p>Ready to learn JavaScript next!</p>
                    <?php else: ?>
                        <p style="color: #ff9800;">You need 7/10 to pass. Review the lesson and try again!</p>
                        <p>CSS takes practice - you'll get it! 💪</p>
                    <?php endif; ?>
                </div>

                <h3 style="margin-top: 3rem; margin-bottom: 1rem;">📝 Answer Review</h3>
                
                <?php
                $correct_answers = [
                    'q1' => ['answer' => 'Cascading Style Sheets', 'question' => 'What does CSS stand for?'],
                    'q2' => ['answer' => '<link rel="stylesheet" href="style.css">', 'question' => 'How do you link an external CSS file?'],
                    'q3' => ['answer' => 'color', 'question' => 'Which property changes text color?'],
                    'q4' => ['answer' => 'margin', 'question' => 'Which property adds space outside an element?'],
                    'q5' => ['answer' => '#FF0000', 'question' => 'What is the hex code for red?'],
                    'q6' => ['answer' => 'font-size', 'question' => 'Which property changes text size?'],
                    'q7' => ['answer' => 'border', 'question' => 'Which property adds a border?'],
                    'q8' => ['answer' => 'display: flex;', 'question' => 'How do you enable Flexbox layout?'],
                    'q9' => ['answer' => 'class', 'question' => 'Which attribute targets multiple elements with CSS?'],
                    'q10' => ['answer' => ':hover', 'question' => 'Which pseudo-class styles on mouse hover?']
                ];

                $question_num = 1;
                foreach ($correct_answers as $key => $data) {
                    $user_answer = $_SESSION['quiz_answers'][$key] ?? '';
                    $is_correct = (trim($user_answer) === $data['answer']);
                    ?>
                    <div class="answer-review <?php echo $is_correct ? 'correct-answer' : 'wrong-answer'; ?>">
                        <div class="answer-feedback">
                            <span class="feedback-icon"><?php echo $is_correct ? '✅' : '❌'; ?></span>
                            <div style="flex: 1;"><strong>Q<?php echo $question_num; ?>:</strong> <?php echo $data['question']; ?></div>
                        </div>
                        <?php if (!$is_correct): ?>
                            <p><strong>Your answer:</strong> <span style="color: #f44336;"><?php echo htmlspecialchars($user_answer ?: '(No answer)'); ?></span></p>
                            <p class="correct-answer-text">Correct: <?php echo htmlspecialchars($data['answer']); ?></p>
                        <?php else: ?>
                            <p style="color: #4caf50;"><strong>✓ Correct!</strong> <?php echo htmlspecialchars($data['answer']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                    $question_num++;
                }
                ?>

                <div class="action-buttons">
                    <a href="lesson_css.php" class="action-btn back-btn">📖 Review Lesson</a>
                    <a href="quiz_css.php" class="action-btn retry-btn">🔄 Retake Quiz</a>
                    <?php if ($_SESSION['quiz_passed']): ?>
                        <a href="lesson_javascript.php" class="action-btn next-btn">➡️ Next: JavaScript</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            unset($_SESSION['quiz_answers']);
            unset($_SESSION['quiz_score']);
            unset($_SESSION['quiz_total']);
            unset($_SESSION['quiz_passed']);
            ?>

        <?php else: ?>
            <div class="quiz-header">
                <h1>🎯 CSS Styling Quiz</h1>
                <p>Test your CSS knowledge! 7/10 to pass.</p>
            </div>

            <form method="POST">
                <div class="question-card">
                    <div class="question-number">Question 1 of 10</div>
                    <div class="question-text">What does CSS stand for?</div>
                    <div class="options">
                        <label class="option"><input type="radio" name="q1" value="Computer Style Sheets" required><span>Computer Style Sheets</span></label>
                        <label class="option"><input type="radio" name="q1" value="Cascading Style Sheets"><span>Cascading Style Sheets</span></label>
                        <label class="option"><input type="radio" name="q1" value="Creative Style System"><span>Creative Style System</span></label>
                    </div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 2 of 10</div>
                    <div class="question-text">How do you link an external CSS file in HTML?</div>
                    <div class="fill-blank"><input type="text" name="q2" placeholder="Type the complete tag..." required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 3 of 10</div>
                    <div class="question-text">Which CSS property changes the text color?</div>
                    <div class="fill-blank"><input type="text" name="q3" placeholder="property name" required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 4 of 10</div>
                    <div class="question-text">Which property adds space OUTSIDE an element?</div>
                    <div class="options">
                        <label class="option"><input type="radio" name="q4" value="padding" required><span>padding</span></label>
                        <label class="option"><input type="radio" name="q4" value="margin"><span>margin</span></label>
                        <label class="option"><input type="radio" name="q4" value="border"><span>border</span></label>
                    </div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 5 of 10</div>
                    <div class="question-text">What is the hexadecimal code for RED color?</div>
                    <div class="fill-blank"><input type="text" name="q5" placeholder="#??????" required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 6 of 10</div>
                    <div class="question-text">Which property changes text size?</div>
                    <div class="fill-blank"><input type="text" name="q6" required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 7 of 10</div>
                    <div class="question-text">Which property adds a border around an element?</div>
                    <div class="fill-blank"><input type="text" name="q7" required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 8 of 10</div>
                    <div class="question-text">How do you enable Flexbox layout?</div>
                    <div class="options">
                        <label class="option"><input type="radio" name="q8" value="display: block;" required><span>display: block;</span></label>
                        <label class="option"><input type="radio" name="q8" value="display: flex;"><span>display: flex;</span></label>
                        <label class="option"><input type="radio" name="q8" value="flex: 1;"><span>flex: 1;</span></label>
                    </div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 9 of 10</div>
                    <div class="question-text">Which HTML attribute do you use to target multiple elements with the same CSS?</div>
                    <div class="fill-blank"><input type="text" name="q9" required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 10 of 10</div>
                    <div class="question-text">Which CSS pseudo-class is used to style an element when you mouse over it?</div>
                    <div class="fill-blank"><input type="text" name="q10" placeholder=":????" required></div>
                </div>

                <button type="submit" name="submit_quiz" class="submit-btn">Submit Quiz 🚀</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>