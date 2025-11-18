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

// Handle final submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $score = 0;
    $total = 10;
    
    $correct_answers = [
        1 => 'JavaScript',
        2 => 'let',
        3 => 'console.log()',
        4 => 'function',
        5 => 'document.getElementById()',
        6 => 'addEventListener',
        7 => '===',
        8 => 'if',
        9 => 'array',
        10 => 'return'
    ];
    
    $answers = $_SESSION['quiz_answers'] ?? [];
    foreach ($correct_answers as $q => $correct) {
        if (isset($answers[$q]) && $answers[$q] === $correct) {
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
    
    $_SESSION['final_score'] = $score;
    $_SESSION['final_total'] = $total;
    $_SESSION['final_passed'] = $passed;
    $_SESSION['correct_answers'] = $correct_answers;
    
    header("Location: quiz_javascript_NEW.php?results=1");
    exit();
}

$showing_results = isset($_GET['results']) && isset($_SESSION['final_score']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript Quiz | Code Lab @ HELP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #1a1a2e; color: white; }
        
        .top-nav { background-color: #16213e; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .exit-btn { background-color: #e94560; color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        
        .progress-container { background-color: #16213e; padding: 1.5rem 2rem; }
        .progress-text { font-size: 1rem; color: #aaa; margin-bottom: 1rem; }
        .progress-bar-outer { width: 100%; height: 10px; background-color: #0f3460; border-radius: 10px; overflow: hidden; }
        .progress-bar-inner { height: 100%; background: linear-gradient(90deg, #f7971e 0%, #ffd200 100%); transition: width 0.5s ease; }
        
        .quiz-container { max-width: 900px; margin: 0 auto; padding: 3rem 2rem; min-height: calc(100vh - 200px); display: flex; align-items: center; justify-content: center; }
        
        .question-card { display: none; width: 100%; animation: fadeIn 0.5s; }
        .question-card.active { display: block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .card-content { background-color: #16213e; padding: 3rem; border-radius: 15px; box-shadow: 0 5px 30px rgba(0,0,0,0.3); }
        .question-header { font-size: 0.9rem; color: #f7971e; margin-bottom: 1rem; font-weight: bold; }
        .question-text { font-size: 1.5rem; margin-bottom: 2.5rem; line-height: 1.6; }
        
        .options { display: flex; flex-direction: column; gap: 1rem; }
        .option { background-color: #0f3460; padding: 1.5rem; border-radius: 10px; cursor: pointer; transition: all 0.3s; border: 3px solid transparent; display: flex; align-items: center; gap: 1rem; }
        .option:hover { background-color: #16213e; border-color: #f7971e; transform: translateX(5px); }
        .option input[type="radio"] { width: 24px; height: 24px; cursor: pointer; }
        .option.selected { border-color: #f7971e; background-color: #16213e; }
        
        .input-answer { background-color: #0f3460; padding: 1.5rem; border-radius: 10px; margin-top: 1rem; }
        .input-answer input { width: 100%; padding: 1rem; border: 2px solid #f7971e; border-radius: 8px; background-color: #16213e; color: white; font-size: 1.1rem; font-family: 'Courier New', monospace; }
        
        .feedback { display: none; margin-top: 2rem; padding: 1.5rem; border-radius: 10px; animation: slideIn 0.3s; }
        .feedback.show { display: block; }
        .feedback.correct { background-color: rgba(76, 175, 80, 0.2); border: 2px solid #4caf50; }
        .feedback.wrong { background-color: rgba(244, 67, 54, 0.2); border: 2px solid #f44336; }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .nav-buttons { display: flex; justify-content: space-between; margin-top: 2.5rem; gap: 1rem; }
        .nav-btn { padding: 1rem 2.5rem; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: all 0.3s; }
        .btn-check { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: white; }
        .btn-next { background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; display: none; }
        .btn-next.show { display: block; }
        .btn-submit { background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%); color: white; }
        .nav-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        
        .results-container { background-color: #16213e; padding: 3rem; border-radius: 15px; text-align: center; }
        .score-display { font-size: 5rem; font-weight: bold; margin: 2rem 0; }
        .score-display.passed { color: #4caf50; }
        .score-display.failed { color: #f44336; }
        
        .answer-review { background-color: #0f3460; padding: 1.5rem; border-radius: 10px; margin: 1rem 0; text-align: left; }
        .correct-ans { border-left: 4px solid #4caf50; }
        .wrong-ans { border-left: 4px solid #f44336; }
        
        .action-buttons { display: flex; gap: 1rem; justify-content: center; margin-top: 2rem; flex-wrap: wrap; }
        .action-btn { padding: 1rem 2rem; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .btn-retry { background-color: #ff9800; color: white; }
        .btn-hub { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-back { background-color: #666; color: white; }

        .celebration { margin-top: 2rem; padding: 2rem; background: linear-gradient(135deg, rgba(76, 175, 80, 0.2), rgba(102, 126, 234, 0.2)); border-radius: 15px; }

        @media (max-width: 768px) {
            .card-content { padding: 2rem 1.5rem; }
            .nav-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="logo">⚡ JavaScript Knowledge Quiz</div>
        <button class="exit-btn" onclick="if(confirm('Exit quiz? Progress will be lost.')) window.location.href='learning_hub.php'">← Exit</button>
    </div>

    <?php if (!$showing_results): ?>
        <div class="progress-container">
            <div class="progress-text">Question <span id="currentQ">1</span> of 10</div>
            <div class="progress-bar-outer">
                <div class="progress-bar-inner" id="progressBar" style="width: 10%;"></div>
            </div>
        </div>

        <div class="quiz-container">
            <div id="questionsContainer"></div>
        </div>

        <form id="quizForm" method="POST">
            <input type="hidden" name="submit_quiz" value="1">
        </form>

    <?php else: ?>
        <div class="quiz-container">
            <div class="results-container">
                <h2><?php echo $_SESSION['final_passed'] ? '🎉 Congratulations!' : '📚 Keep Learning!'; ?></h2>
                <div class="score-display <?php echo $_SESSION['final_passed'] ? 'passed' : 'failed'; ?>">
                    <?php echo $_SESSION['final_score']; ?> / <?php echo $_SESSION['final_total']; ?>
                </div>
                <div style="font-size: 1.3rem; margin-bottom: 2rem;">
                    <?php if ($_SESSION['final_passed']): ?>
                        <p style="color: #4caf50;">Amazing! You've completed ALL THREE lessons! 🎊</p>
                        <p>You're now a web developer! 🚀</p>
                        <div class="celebration">
                            <div style="font-size: 4rem; margin: 1rem 0;">🏆</div>
                            <h3 style="color: #4caf50;">Learning Path Complete!</h3>
                            <p style="margin-top: 1rem; color: #ddd;">You've mastered HTML, CSS, and JavaScript!<br>You can now build amazing websites!</p>
                        </div>
                    <?php else: ?>
                        <p style="color: #ff9800;">You need 7/10 to pass. Review and try again!</p>
                    <?php endif; ?>
                </div>

                <h3 style="margin-top: 3rem; margin-bottom: 1rem;">📝 Your Answers</h3>
                <?php
                $questions = [
                    1 => 'Language for web interactivity?',
                    2 => 'Modern variable keyword?',
                    3 => 'Function to print to console?',
                    4 => 'Keyword to define a function?',
                    5 => 'Method to select by ID?',
                    6 => 'Method to attach events?',
                    7 => 'Strict equality operator?',
                    8 => 'Keyword for conditionals?',
                    9 => 'Data structure for lists?',
                    10 => 'Keyword to output from function?'
                ];

                $user_answers = $_SESSION['quiz_answers'] ?? [];
                $correct_answers = $_SESSION['correct_answers'];

                foreach ($questions as $num => $question) {
                    $user_ans = $user_answers[$num] ?? '(No answer)';
                    $correct_ans = $correct_answers[$num];
                    $is_correct = ($user_ans === $correct_ans);
                    ?>
                    <div class="answer-review <?php echo $is_correct ? 'correct-ans' : 'wrong-ans'; ?>">
                        <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 0.5rem;">
                            <span style="font-size: 1.5rem;"><?php echo $is_correct ? '✅' : '❌'; ?></span>
                            <strong>Q<?php echo $num; ?>:</strong> <?php echo $question; ?>
                        </div>
                        <?php if (!$is_correct): ?>
                            <p style="color: #f44336;"><strong>Your answer:</strong> <?php echo htmlspecialchars($user_ans); ?></p>
                            <p style="color: #4caf50;"><strong>Correct:</strong> <?php echo htmlspecialchars($correct_ans); ?></p>
                        <?php else: ?>
                            <p style="color: #4caf50;"><strong>✓ Correct!</strong> <?php echo htmlspecialchars($correct_ans); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php
                }
                ?>

                <div class="action-buttons">
                    <a href="lesson_javascript_NEW.php" class="action-btn btn-back">📖 Review Lesson</a>
                    <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="action-btn btn-retry" onclick="<?php unset($_SESSION['quiz_answers']); ?>">🔄 Retake Quiz</a>
                    <a href="learning_hub.php" class="action-btn btn-hub">🏠 Back to Hub</a>
                </div>
            </div>
        </div>
    <?php 
    unset($_SESSION['quiz_answers'], $_SESSION['final_score'], $_SESSION['final_total'], $_SESSION['final_passed'], $_SESSION['correct_answers']);
    endif; ?>

    <script>
        const questions = [
            {
                num: 1,
                text: "Which language makes websites interactive?",
                type: "multiple",
                options: ["HTML", "CSS", "JavaScript", "Python"],
                correct: "JavaScript"
            },
            {
                num: 2,
                text: "Which keyword declares a modern variable?",
                type: "input",
                placeholder: "Type the keyword...",
                correct: "let"
            },
            {
                num: 3,
                text: "Which function prints to the console?",
                type: "multiple",
                options: ["print()", "console.log()", "log()", "display()"],
                correct: "console.log()"
            },
            {
                num: 4,
                text: "Which keyword is used to define a function?",
                type: "input",
                placeholder: "Type the keyword...",
                correct: "function"
            },
            {
                num: 5,
                text: "Which method selects an element by ID?",
                type: "multiple",
                options: [
                    "getElementById()",
                    "document.getElementById()",
                    "getElement()",
                    "selectById()"
                ],
                correct: "document.getElementById()"
            },
            {
                num: 6,
                text: "Which method attaches an event listener?",
                type: "input",
                placeholder: "Type the method name...",
                correct: "addEventListener"
            },
            {
                num: 7,
                text: "Which operator checks strict equality?",
                type: "multiple",
                options: ["==", "===", "=", "!="],
                correct: "==="
            },
            {
                num: 8,
                text: "Which keyword is used for conditional statements?",
                type: "input",
                placeholder: "Type the keyword...",
                correct: "if"
            },
            {
                num: 9,
                text: "What is the data structure for storing multiple values?",
                type: "multiple",
                options: ["object", "array", "string", "number"],
                correct: "array"
            },
            {
                num: 10,
                text: "Which keyword returns a value from a function?",
                type: "input",
                placeholder: "Type the keyword...",
                correct: "return"
            }
        ];

        let currentQuestion = 0;
        const userAnswers = {};

        function renderQuestion(index) {
            const q = questions[index];
            const container = document.getElementById('questionsContainer');
            
            let html = `
                <div class="question-card active">
                    <div class="card-content">
                        <div class="question-header">Question ${q.num} of 10</div>
                        <div class="question-text">${q.text}</div>
            `;

            if (q.type === "multiple") {
                html += '<div class="options">';
                q.options.forEach(opt => {
                    const checked = userAnswers[q.num] === opt ? 'checked' : '';
                    html += `
                        <label class="option ${checked ? 'selected' : ''}" onclick="selectOption(this, ${q.num}, '${opt.replace(/'/g, "\\'")}')">
                            <input type="radio" name="q${q.num}" value="${opt}" ${checked}>
                            <span>${opt}</span>
                        </label>
                    `;
                });
                html += '</div>';
            } else {
                const value = userAnswers[q.num] || '';
                html += `
                    <div class="input-answer">
                        <input type="text" id="answer${q.num}" placeholder="${q.placeholder}" value="${value}" onchange="saveInput(${q.num}, this.value)">
                    </div>
                `;
            }

            html += `
                        <div id="feedback${q.num}" class="feedback"></div>
                        <div class="nav-buttons">
                            ${index > 0 ? '<button class="nav-btn" onclick="prevQuestion()">← Previous</button>' : '<div></div>'}
                            <button class="nav-btn btn-check" id="checkBtn${q.num}" onclick="checkAnswer(${q.num})">Check Answer</button>
                            <button class="nav-btn btn-next" id="nextBtn${q.num}" onclick="nextQuestion()">
                                ${index === questions.length - 1 ? 'Finish →' : 'Next Question →'}
                            </button>
                        </div>
                    </div>
                </div>
            `;

            container.innerHTML = html;
            updateProgress();
        }

        function selectOption(element, qNum, value) {
            document.querySelectorAll('.option').forEach(opt => opt.classList.remove('selected'));
            element.classList.add('selected');
            userAnswers[qNum] = value;
        }

        function saveInput(qNum, value) {
            userAnswers[qNum] = value.trim();
        }

        function checkAnswer(qNum) {
            const q = questions.find(question => question.num === qNum);
            const userAnswer = userAnswers[qNum] || (q.type === 'input' ? document.getElementById(`answer${qNum}`).value.trim() : null);
            
            if (!userAnswer) {
                alert('Please select or enter an answer first!');
                return;
            }

            if (q.type === 'input') userAnswers[qNum] = userAnswer;

            const isCorrect = userAnswer === q.correct;
            const feedback = document.getElementById(`feedback${qNum}`);
            
            feedback.className = 'feedback show ' + (isCorrect ? 'correct' : 'wrong');
            feedback.innerHTML = isCorrect 
                ? '<strong style="font-size: 1.2rem;">✅ Correct! Excellent work!</strong><p>You got it! Click "Next" to continue.</p>'
                : `<strong style="font-size: 1.2rem;">❌ Not quite right</strong><p><strong>Correct answer:</strong> ${q.correct}</p><p>Click "Next" to continue.</p>`;
            
            document.getElementById(`checkBtn${qNum}`).style.display = 'none';
            document.getElementById(`nextBtn${qNum}`).classList.add('show');
        }

        function nextQuestion() {
            if (currentQuestion < questions.length - 1) {
                currentQuestion++;
                renderQuestion(currentQuestion);
            } else {
                submitQuiz();
            }
        }

        function prevQuestion() {
            if (currentQuestion > 0) {
                currentQuestion--;
                renderQuestion(currentQuestion);
            }
        }

        function updateProgress() {
            const progress = ((currentQuestion + 1) / 10) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('currentQ').textContent = currentQuestion + 1;
        }

        function submitQuiz() {
            const form = document.getElementById('quizForm');
            for (let qNum in userAnswers) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'quiz_answers[' + qNum + ']';
                input.value = userAnswers[qNum];
                form.appendChild(input);
            }
            <?php $_SESSION['quiz_answers'] = []; ?>
            form.submit();
        }

        renderQuestion(0);
    </script>
</body>
</html>