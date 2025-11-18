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
$lesson_id = 1; // HTML Basics lesson

// Handle quiz submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $total = 10;
    
    // Define correct answers
    $correct_answers = [
        'q1' => 'HyperText Markup Language',
        'q2' => '<!DOCTYPE html>',
        'q3' => '<h1>',
        'q4' => '<p>',
        'q5' => '<a href="url">',
        'q6' => 'alt',
        'q7' => '<ul>',
        'q8' => '<br>',
        'q9' => '<body>',
        'q10' => '<strong>'
    ];
    
    // Calculate score
    foreach ($correct_answers as $question => $correct) {
        if (isset($_POST[$question]) && trim($_POST[$question]) === $correct) {
            $score++;
        }
    }
    
    $passed = ($score >= 7); // 70% to pass
    
    // Update or insert progress
    $check_progress = $conn->prepare("SELECT id FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = ?");
    $check_progress->bind_param("ii", $student_id, $lesson_id);
    $check_progress->execute();
    
    if ($check_progress->get_result()->num_rows > 0) {
        // Update existing record
        $update = $conn->prepare("
            UPDATE lesson_quiz_progress 
            SET quiz_score = ?, quiz_total = ?, quiz_passed = ?, attempts = attempts + 1, completed_at = NOW()
            WHERE student_id = ? AND lesson_id = ?
        ");
        $update->bind_param("iiiii", $score, $total, $passed, $student_id, $lesson_id);
        $update->execute();
    } else {
        // Insert new record
        $insert = $conn->prepare("
            INSERT INTO lesson_quiz_progress (student_id, lesson_id, quiz_score, quiz_total, quiz_passed, attempts, completed_at)
            VALUES (?, ?, ?, ?, ?, 1, NOW())
        ");
        $insert->bind_param("iiiii", $student_id, $lesson_id, $score, $total, $passed);
        $insert->execute();
    }
    
    // Store answers for showing results
    $_SESSION['quiz_answers'] = $_POST;
    $_SESSION['quiz_score'] = $score;
    $_SESSION['quiz_total'] = $total;
    $_SESSION['quiz_passed'] = $passed;
    
    header("Location: quiz_html.php?results=1");
    exit();
}

// Check if showing results
$showing_results = isset($_GET['results']) && isset($_SESSION['quiz_score']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTML Quiz | Code Lab @ HELP</title>
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

        .logo a {
            color: white;
            text-decoration: none;
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

        .logout-btn {
            background-color: #2e3f54;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem;
        }

        .quiz-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            text-align: center;
        }

        .quiz-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .question-card {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
        }

        .question-number {
            color: #667eea;
            font-weight: bold;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .question-text {
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .options {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .option {
            background-color: #2e3f54;
            padding: 1rem;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .option:hover {
            background-color: #3a4d64;
            border-color: #667eea;
        }

        .option input[type="radio"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .fill-blank {
            background-color: #2e3f54;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .fill-blank input {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #667eea;
            border-radius: 6px;
            background-color: #1a2332;
            color: white;
            font-size: 1rem;
            font-family: 'Courier New', monospace;
        }

        .code-snippet {
            background-color: #272822;
            padding: 1rem;
            border-radius: 6px;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            color: #f8f8f2;
            overflow-x: auto;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem 3rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            width: 100%;
            margin-top: 2rem;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        /* Results Styles */
        .results-container {
            background-color: #1a2332;
            padding: 3rem;
            border-radius: 12px;
            text-align: center;
        }

        .score-display {
            font-size: 4rem;
            font-weight: bold;
            margin: 2rem 0;
        }

        .score-display.passed {
            color: #4caf50;
        }

        .score-display.failed {
            color: #f44336;
        }

        .result-message {
            font-size: 1.3rem;
            margin-bottom: 2rem;
        }

        .answer-review {
            background-color: #2e3f54;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            text-align: left;
        }

        .correct-answer {
            border-left: 4px solid #4caf50;
        }

        .wrong-answer {
            border-left: 4px solid #f44336;
        }

        .answer-feedback {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .feedback-icon {
            font-size: 2rem;
        }

        .correct-answer-text {
            color: #4caf50;
            font-weight: bold;
            margin-top: 0.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }

        .action-btn {
            padding: 1rem 2rem;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .retry-btn {
            background-color: #ff9800;
            color: white;
        }

        .next-btn {
            background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);
            color: white;
        }

        .back-btn {
            background-color: #666;
            color: white;
        }

        .timer {
            background-color: rgba(255,255,255,0.1);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            margin-top: 1rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="studentDashboard.php">Code Lab @ HELP</a>
        </div>
        <ul class="nav-links">
            <li><a href="studentDashboard.php">Dashboard</a></li>
            <li><a href="learning_hub.php">Learning Hub</a></li>
            <li><a href="lesson_html.php">Back to Lesson</a></li>
        </ul>
        <div>
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <?php if ($showing_results): ?>
            <!-- RESULTS PAGE -->
            <div class="quiz-header">
                <h1>📊 Quiz Results</h1>
                <p>Here's how you did on the HTML quiz!</p>
            </div>

            <div class="results-container">
                <h2><?php echo $_SESSION['quiz_passed'] ? '🎉 Congratulations!' : '📚 Keep Practicing!'; ?></h2>
                <div class="score-display <?php echo $_SESSION['quiz_passed'] ? 'passed' : 'failed'; ?>">
                    <?php echo $_SESSION['quiz_score']; ?> / <?php echo $_SESSION['quiz_total']; ?>
                </div>
                <div class="result-message">
                    <?php if ($_SESSION['quiz_passed']): ?>
                        <p style="color: #4caf50;">Excellent work! You've passed the HTML quiz! 🎊</p>
                        <p>You're ready to move on to CSS Styling!</p>
                    <?php else: ?>
                        <p style="color: #ff9800;">You need 7/10 to pass. Review the lesson and try again!</p>
                        <p>Don't worry, practice makes perfect! 💪</p>
                    <?php endif; ?>
                </div>

                <!-- Answer Review -->
                <h3 style="margin-top: 3rem; margin-bottom: 1rem;">📝 Answer Review</h3>
                
                <?php
                $correct_answers = [
                    'q1' => ['answer' => 'HyperText Markup Language', 'question' => 'What does HTML stand for?'],
                    'q2' => ['answer' => '<!DOCTYPE html>', 'question' => 'What is the correct HTML tag to declare an HTML5 document?'],
                    'q3' => ['answer' => '<h1>', 'question' => 'Which tag creates the largest heading?'],
                    'q4' => ['answer' => '<p>', 'question' => 'Which tag is used for paragraphs?'],
                    'q5' => ['answer' => '<a href="url">', 'question' => 'What is the correct HTML for creating a hyperlink?'],
                    'q6' => ['answer' => 'alt', 'question' => 'Which attribute specifies alternative text for an image?'],
                    'q7' => ['answer' => '<ul>', 'question' => 'Which tag creates an unordered list?'],
                    'q8' => ['answer' => '<br>', 'question' => 'Which tag is used to insert a line break?'],
                    'q9' => ['answer' => '<body>', 'question' => 'Where do you put visible content in HTML?'],
                    'q10' => ['answer' => '<strong>', 'question' => 'Which tag makes text bold (semantic)?']
                ];

                $question_num = 1;
                foreach ($correct_answers as $key => $data) {
                    $user_answer = $_SESSION['quiz_answers'][$key] ?? '';
                    $is_correct = (trim($user_answer) === $data['answer']);
                    ?>
                    <div class="answer-review <?php echo $is_correct ? 'correct-answer' : 'wrong-answer'; ?>">
                        <div class="answer-feedback">
                            <span class="feedback-icon"><?php echo $is_correct ? '✅' : '❌'; ?></span>
                            <div style="flex: 1;">
                                <strong>Question <?php echo $question_num; ?>:</strong> <?php echo $data['question']; ?>
                            </div>
                        </div>
                        <?php if (!$is_correct): ?>
                            <p><strong>Your answer:</strong> <span style="color: #f44336;"><?php echo htmlspecialchars($user_answer ?: '(No answer)'); ?></span></p>
                            <p class="correct-answer-text">Correct answer: <?php echo htmlspecialchars($data['answer']); ?></p>
                        <?php else: ?>
                            <p style="color: #4caf50;"><strong>✓ Correct!</strong> <?php echo htmlspecialchars($data['answer']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                    $question_num++;
                }
                ?>

                <div class="action-buttons">
                    <a href="lesson_html.php" class="action-btn back-btn">📖 Review Lesson</a>
                    <a href="quiz_html.php" class="action-btn retry-btn">🔄 Retake Quiz</a>
                    <?php if ($_SESSION['quiz_passed']): ?>
                        <a href="lesson_css.php" class="action-btn next-btn">➡️ Next: CSS Lesson</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php
            // Clear session data
            unset($_SESSION['quiz_answers']);
            unset($_SESSION['quiz_score']);
            unset($_SESSION['quiz_total']);
            unset($_SESSION['quiz_passed']);
            ?>

        <?php else: ?>
            <!-- QUIZ PAGE -->
            <div class="quiz-header">
                <h1>🎯 HTML Knowledge Quiz</h1>
                <p>Test what you've learned! You need 7/10 to pass.</p>
                <div class="timer" id="timer">⏱️ Time: 00:00</div>
            </div>

            <form method="POST" id="quizForm">
                <!-- Question 1 -->
                <div class="question-card">
                    <div class="question-number">Question 1 of 10</div>
                    <div class="question-text">What does HTML stand for?</div>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q1" value="Hyper Tool Markup Language" required>
                            <span>Hyper Tool Markup Language</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="HyperText Markup Language">
                            <span>HyperText Markup Language</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="Home Text Markup Language">
                            <span>Home Text Markup Language</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q1" value="Hyperlinks and Text Markup Language">
                            <span>Hyperlinks and Text Markup Language</span>
                        </label>
                    </div>
                </div>

                <!-- Question 2 -->
                <div class="question-card">
                    <div class="question-number">Question 2 of 10</div>
                    <div class="question-text">What is the correct HTML tag to declare an HTML5 document?</div>
                    <div class="fill-blank">
                        <input type="text" name="q2" placeholder="Type your answer here..." required>
                    </div>
                </div>

                <!-- Question 3 -->
                <div class="question-card">
                    <div class="question-number">Question 3 of 10</div>
                    <div class="question-text">Which HTML tag creates the largest heading?</div>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q3" value="<h6>" required>
                            <span>&lt;h6&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="<h1>">
                            <span>&lt;h1&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="<heading>">
                            <span>&lt;heading&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q3" value="<head>">
                            <span>&lt;head&gt;</span>
                        </label>
                    </div>
                </div>

                <!-- Question 4 -->
                <div class="question-card">
                    <div class="question-number">Question 4 of 10</div>
                    <div class="question-text">Which HTML tag is used for paragraphs?</div>
                    <div class="fill-blank">
                        <input type="text" name="q4" placeholder="Type the tag (e.g., <tag>)" required>
                    </div>
                </div>

                <!-- Question 5 -->
                <div class="question-card">
                    <div class="question-number">Question 5 of 10</div>
                    <div class="question-text">What is the correct HTML for creating a hyperlink?</div>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q5" value="<link>url</link>" required>
                            <span>&lt;link&gt;url&lt;/link&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value='<a href="url">'>
                            <span>&lt;a href="url"&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value='<url href="link">'>
                            <span>&lt;url href="link"&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q5" value="<hyperlink>">
                            <span>&lt;hyperlink&gt;</span>
                        </label>
                    </div>
                </div>

                <!-- Question 6 -->
                <div class="question-card">
                    <div class="question-number">Question 6 of 10</div>
                    <div class="question-text">Which attribute specifies alternative text for an image if it cannot be displayed?</div>
                    <div class="fill-blank">
                        <input type="text" name="q6" placeholder="Type the attribute name..." required>
                    </div>
                </div>

                <!-- Question 7 -->
                <div class="question-card">
                    <div class="question-number">Question 7 of 10</div>
                    <div class="question-text">Which tag is used to create an unordered (bulleted) list?</div>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q7" value="<ol>" required>
                            <span>&lt;ol&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q7" value="<ul>">
                            <span>&lt;ul&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q7" value="<list>">
                            <span>&lt;list&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q7" value="<li>">
                            <span>&lt;li&gt;</span>
                        </label>
                    </div>
                </div>

                <!-- Question 8 -->
                <div class="question-card">
                    <div class="question-number">Question 8 of 10</div>
                    <div class="question-text">Which tag is used to insert a line break?</div>
                    <div class="fill-blank">
                        <input type="text" name="q8" placeholder="Type the self-closing tag..." required>
                    </div>
                </div>

                <!-- Question 9 -->
                <div class="question-card">
                    <div class="question-number">Question 9 of 10</div>
                    <div class="question-text">
                        In the following HTML structure, where do you put the visible content?
                        <div class="code-snippet">&lt;html&gt;
  &lt;head&gt;&lt;/head&gt;
  &lt;?&gt;&lt;/?&gt;
&lt;/html&gt;</div>
                    </div>
                    <div class="fill-blank">
                        <input type="text" name="q9" placeholder="Which tag? (e.g., <tag>)" required>
                    </div>
                </div>

                <!-- Question 10 -->
                <div class="question-card">
                    <div class="question-number">Question 10 of 10</div>
                    <div class="question-text">Which tag is the semantic way to make text bold in HTML5?</div>
                    <div class="options">
                        <label class="option">
                            <input type="radio" name="q10" value="<b>" required>
                            <span>&lt;b&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q10" value="<strong>">
                            <span>&lt;strong&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q10" value="<bold>">
                            <span>&lt;bold&gt;</span>
                        </label>
                        <label class="option">
                            <input type="radio" name="q10" value="<em>">
                            <span>&lt;em&gt;</span>
                        </label>
                    </div>
                </div>

                <button type="submit" name="submit_quiz" class="submit-btn">Submit Quiz 🚀</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Timer functionality
        let seconds = 0;
        let minutes = 0;
        const timerElement = document.getElementById('timer');
        
        if (timerElement) {
            setInterval(() => {
                seconds++;
                if (seconds >= 60) {
                    seconds = 0;
                    minutes++;
                }
                
                const displaySeconds = seconds < 10 ? '0' + seconds : seconds;
                const displayMinutes = minutes < 10 ? '0' + minutes : minutes;
                
                timerElement.textContent = `⏱️ Time: ${displayMinutes}:${displaySeconds}`;
            }, 1000);
        }

        // Confirm before leaving
        const quizForm = document.getElementById('quizForm');
        if (quizForm) {
            let formSubmitted = false;
            
            quizForm.addEventListener('submit', function() {
                formSubmitted = true;
            });
            
            window.addEventListener('beforeunload', function(e) {
                if (!formSubmitted) {
                    e.preventDefault();
                    e.returnValue = '';
                    return 'Are you sure you want to leave? Your quiz progress will be lost.';
                }
            });
        }
    </script>
</body>
</html>