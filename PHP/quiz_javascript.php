<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];
$lesson_id = 3;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $total = 10;
    
    $correct_answers = [
        'q1' => 'A programming language for web interactivity',
        'q2' => 'let',
        'q3' => 'console.log()',
        'q4' => 'function greet() {}',
        'q5' => 'String',
        'q6' => '===',
        'q7' => '["apple", "banana"]',
        'q8' => 'if (x > 10) {}',
        'q9' => 'addEventListener',
        'q10' => 'const'
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
    
    header("Location: quiz_javascript.php?results=1");
    exit();
}

$showing_results = isset($_GET['results']) && isset($_SESSION['quiz_score']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript Quiz | Code Lab @ HELP</title>
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
        .quiz-header { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); padding: 2rem; border-radius: 12px; margin-bottom: 2rem; text-align: center; color: #111; }
        .quiz-header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
        .question-card { background-color: #1a2332; padding: 2rem; border-radius: 12px; margin-bottom: 1.5rem; border-left: 4px solid #f7971e; }
        .question-number { color: #f7971e; font-weight: bold; font-size: 1.2rem; margin-bottom: 0.5rem; }
        .question-text { font-size: 1.1rem; margin-bottom: 1.5rem; line-height: 1.6; }
        .options { display: flex; flex-direction: column; gap: 1rem; }
        .option { background-color: #2e3f54; padding: 1rem; border-radius: 8px; cursor: pointer; transition: all 0.3s; border: 2px solid transparent; display: flex; align-items: center; gap: 1rem; }
        .option:hover { background-color: #3a4d64; border-color: #f7971e; }
        .option input[type="radio"] { width: 20px; height: 20px; cursor: pointer; }
        .fill-blank { background-color: #2e3f54; padding: 1rem; border-radius: 8px; margin-top: 1rem; }
        .fill-blank input { width: 100%; padding: 0.8rem; border: 2px solid #f7971e; border-radius: 6px; background-color: #1a2332; color: white; font-size: 1rem; font-family: 'Courier New', monospace; }
        .submit-btn { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #111; border: none; padding: 1rem 3rem; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; width: 100%; margin-top: 2rem; transition: all 0.3s; }
        .submit-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(247, 151, 30, 0.3); }
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
        .action-buttons { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }
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
            <li><a href="lesson_javascript.php">Back to Lesson</a></li>
        </ul>
        <div>
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <?php if ($showing_results): ?>
            <div class="quiz-header">
                <h1>📊 JavaScript Quiz Results</h1>
                <p>Here's how you did!</p>
            </div>

            <div class="results-container">
                <h2><?php echo $_SESSION['quiz_passed'] ? '🎉 Amazing Work!' : '📚 Keep Coding!'; ?></h2>
                <div class="score-display <?php echo $_SESSION['quiz_passed'] ? 'passed' : 'failed'; ?>">
                    <?php echo $_SESSION['quiz_score']; ?> / <?php echo $_SESSION['quiz_total']; ?>
                </div>
                <div class="result-message">
                    <?php if ($_SESSION['quiz_passed']): ?>
                        <p style="color: #4caf50;">Congratulations! You've mastered JavaScript! 🚀</p>
                        <p>You've completed all three lessons!</p>
                    <?php else: ?>
                        <p style="color: #ff9800;">You need 7/10 to pass. Keep practicing!</p>
                        <p>JavaScript takes time - don't give up! 💪</p>
                    <?php endif; ?>
                </div>

                <h3 style="margin-top: 3rem; margin-bottom: 1rem;">📝 Answer Review</h3>
                
                <?php
                $correct_answers = [
                    'q1' => ['answer' => 'A programming language for web interactivity', 'question' => 'What is JavaScript?'],
                    'q2' => ['answer' => 'let', 'question' => 'Which keyword declares a changeable variable?'],
                    'q3' => ['answer' => 'console.log()', 'question' => 'How do you print to console?'],
                    'q4' => ['answer' => 'function greet() {}', 'question' => 'What is correct function syntax?'],
                    'q5' => ['answer' => 'String', 'question' => 'What is "Hello" data type?'],
                    'q6' => ['answer' => '===', 'question' => 'Strict equality operator?'],
                    'q7' => ['answer' => '["apple", "banana"]', 'question' => 'How to declare an array?'],
                    'q8' => ['answer' => 'if (x > 10) {}', 'question' => 'Correct if statement syntax?'],
                    'q9' => ['answer' => 'addEventListener', 'question' => 'Method to add click event?'],
                    'q10' => ['answer' => 'const', 'question' => 'Keyword for constant values?']
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
                    <a href="lesson_javascript.php" class="action-btn back-btn">📖 Review Lesson</a>
                    <a href="quiz_javascript.php" class="action-btn retry-btn">🔄 Retake Quiz</a>
                    <a href="learning_hub.php" class="action-btn next-btn">🏆 View Progress</a>
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
                <h1>⚡ JavaScript Mastery Quiz</h1>
                <p>Final challenge! 7/10 to pass.</p>
            </div>

            <form method="POST">
                <div class="question-card">
                    <div class="question-number">Question 1 of 10</div>
                    <div class="question-text">What is JavaScript?</div>
                    <div class="options">
                        <label class="option"><input type="radio" name="q1" value="A styling language" required><span>A styling language</span></label>
                        <label class="option"><input type="radio" name="q1" value="A programming language for web interactivity"><span>A programming language for web interactivity</span></label>
                        <label class="option"><input type="radio" name="q1" value="A markup language"><span>A markup language</span></label>
                    </div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 2 of 10</div>
                    <div class="question-text">Which keyword declares a changeable variable?</div>
                    <div class="fill-blank"><input type="text" name="q2" placeholder="Type keyword..." required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 3 of 10</div>
                    <div class="question-text">How do you print to console in JavaScript?</div>
                    <div class="fill-blank"><input type="text" name="q3" placeholder="function()" required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 4 of 10</div>
                    <div class="question-text">What is the correct syntax to declare a function?</div>
                    <div class="options">
                        <label class="option"><input type="radio" name="q4" value="func greet() {}" required><span>func greet() {}</span></label>
                        <label class="option"><input type="radio" name="q4" value="function greet() {}"><span>function greet() {}</span></label>
                        <label class="option"><input type="radio" name="q4" value="def greet() {}"><span>def greet() {}</span></label>
                    </div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 5 of 10</div>
                    <div class="question-text">What data type is "Hello"?</div>
                    <div class="fill-blank"><input type="text" name="q5" required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 6 of 10</div>
                    <div class="question-text">Which operator checks strict equality?</div>
                    <div class="options">
                        <label class="option"><input type="radio" name="q6" value="==" required><span>==</span></label>
                        <label class="option"><input type="radio" name="q6" value="==="><span>===</span></label>
                        <label class="option"><input type="radio" name="q6" value="="><span>=</span></label>
                    </div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 7 of 10</div>
                    <div class="question-text">How do you declare an array with items "apple" and "banana"?</div>
                    <div class="fill-blank"><input type="text" name="q7" placeholder='["item1", "item2"]' required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 8 of 10</div>
                    <div class="question-text">What is the correct if statement syntax?</div>
                    <div class="options">
                        <label class="option"><input type="radio" name="q8" value="if x > 10 {}" required><span>if x > 10 {}</span></label>
                        <label class="option"><input type="radio" name="q8" value="if (x > 10) {}"><span>if (x > 10) {}</span></label>
                        <label class="option"><input type="radio" name="q8" value="if [x > 10] {}"><span>if [x > 10] {}</span></label>
                    </div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 9 of 10</div>
                    <div class="question-text">Which method adds a click event to a button?</div>
                    <div class="fill-blank"><input type="text" name="q9" required></div>
                </div>

                <div class="question-card">
                    <div class="question-number">Question 10 of 10</div>
                    <div class="question-text">Which keyword declares a constant (unchangeable) value?</div>
                    <div class="fill-blank"><input type="text" name="q10" required></div>
                </div>

                <button type="submit" name="submit_quiz" class="submit-btn">Submit Quiz 🚀</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>