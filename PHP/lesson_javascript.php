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

$progress_check = $conn->prepare("SELECT * FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = ?");
$progress_check->bind_param("ii", $student_id, $lesson_id);
$progress_check->execute();
$progress = $progress_check->get_result()->fetch_assoc();

$css_check = $conn->prepare("SELECT quiz_passed FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = 2");
$css_check->bind_param("i", $student_id);
$css_check->execute();
$css_progress = $css_check->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript - Interactive Tutorial | Code Lab @ HELP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #2e3f54; color: white; }
        .navbar { background-color: #111; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; }
        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .logo a { color: white; text-decoration: none; }
        .nav-links { list-style: none; display: flex; gap: 1.5rem; }
        .nav-links li a { color: white; text-decoration: none; }
        .logout-btn { background-color: #2e3f54; color: white; border: none; padding: 0.4rem 1rem; border-radius: 5px; cursor: pointer; }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .lesson-header { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); padding: 2rem; border-radius: 12px; margin-bottom: 2rem; }
        .lesson-header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; color: #111; }
        .lesson-header p { color: #333; }
        .progress-badge { display: inline-block; padding: 0.5rem 1rem; background-color: rgba(0,0,0,0.2); border-radius: 20px; font-size: 0.9rem; margin-top: 1rem; }
        .content-section { background-color: #1a2332; padding: 2rem; border-radius: 12px; margin-bottom: 2rem; border-left: 4px solid #f7971e; }
        .content-section h2 { color: #f7971e; margin-bottom: 1rem; font-size: 1.8rem; }
        .content-section h3 { color: #ffd200; margin-top: 1.5rem; margin-bottom: 0.8rem; font-size: 1.3rem; }
        .content-section p { line-height: 1.8; color: #ddd; margin-bottom: 1rem; }
        .video-container { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; max-width: 100%; border-radius: 8px; margin: 1.5rem 0; }
        .video-container iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }
        .code-example { background-color: #272822; padding: 1.5rem; border-radius: 8px; margin: 1rem 0; font-family: 'Courier New', monospace; overflow-x: auto; }
        .code-example pre { color: #f8f8f2; margin: 0; }
        .try-it-yourself { background-color: #2e3f54; padding: 2rem; border-radius: 12px; margin: 2rem 0; border: 2px solid #f7971e; }
        .try-it-yourself h3 { color: #f7971e; margin-bottom: 1rem; }
        .editor-container { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-top: 1rem; }
        .editor-panel { background-color: #1a2332; border-radius: 8px; overflow: hidden; }
        .panel-header { background-color: #111; padding: 0.8rem; font-weight: bold; color: #f7971e; }
        .CodeMirror { height: 400px; font-size: 14px; }
        .console-output { width: 100%; height: 400px; background-color: #1e1e1e; color: #0f0; font-family: 'Courier New', monospace; padding: 1rem; overflow-y: auto; border-radius: 0 0 8px 8px; }
        .run-button { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #111; border: none; padding: 0.8rem 2rem; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 1rem; transition: all 0.3s; }
        .run-button:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(247, 151, 30, 0.3); }
        .info-box { background-color: rgba(247, 151, 30, 0.1); border-left: 4px solid #f7971e; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .tip-box { background-color: rgba(76, 175, 80, 0.1); border-left: 4px solid #4caf50; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .quiz-button { display: inline-block; background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: #111; padding: 1rem 3rem; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: all 0.3s; border: none; cursor: pointer; }
        .quiz-button:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(247, 151, 30, 0.3); }
        .quiz-section { text-align: center; padding: 3rem; background-color: #1a2332; border-radius: 12px; margin-top: 2rem; }
        .completed-badge { background-color: #4caf50; color: white; padding: 0.5rem 1rem; border-radius: 20px; display: inline-block; margin-top: 1rem; }
        .locked-message { background-color: rgba(255, 152, 0, 0.1); border: 2px solid #ff9800; padding: 2rem; border-radius: 12px; text-align: center; margin: 2rem 0; }
        .demo-box { background-color: #2e3f54; padding: 2rem; border-radius: 12px; margin: 1.5rem 0; text-align: center; }
        .demo-button { background-color: #f7971e; color: white; border: none; padding: 1rem 2rem; border-radius: 8px; cursor: pointer; font-weight: bold; margin: 0.5rem; }
        @media (max-width: 768px) { .editor-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo"><a href="studentDashboard.php">Code Lab @ HELP</a></div>
        <ul class="nav-links">
            <li><a href="studentDashboard.php">Dashboard</a></li>
            <li><a href="browser.php">Browse</a></li>
            <li><a href="learning_hub.php">Learning Hub</a></li>
        </ul>
        <div>
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <?php if (!$css_progress || !$css_progress['quiz_passed']): ?>
            <div class="locked-message">
                <h2 style="color: #ff9800; margin-bottom: 1rem;">🔒 Lesson Locked</h2>
                <p>Complete the CSS lesson first!</p>
                <a href="lesson_css.php" class="quiz-button" style="margin-top: 1rem; display: inline-block;">Go to CSS Lesson</a>
            </div>
        <?php else: ?>

        <div class="lesson-header">
            <h1>⚡ JavaScript Fundamentals</h1>
            <p>Make your websites interactive and dynamic!</p>
            <?php if ($progress && $progress['quiz_passed']): ?>
                <div class="completed-badge">✓ Completed - Score: <?php echo $progress['quiz_score']; ?>/<?php echo $progress['quiz_total']; ?></div>
            <?php else: ?>
                <div class="progress-badge">📚 In Progress</div>
            <?php endif; ?>
        </div>

        <div class="content-section">
            <h2>💻 What is JavaScript?</h2>
            <p><strong>JavaScript</strong> is a programming language that brings websites to life! While HTML provides structure and CSS makes it pretty, JavaScript adds interactivity - buttons that respond, animations, games, and much more!</p>
            
            <div class="info-box"><strong>💡 Amazing Fact:</strong> JavaScript was created in just 10 days in 1995 by Brendan Eich!</div>

            <h3>🎥 JavaScript in 12 Minutes</h3>
            <div class="video-container">
                <iframe src="https://www.youtube.com/embed/DHjqpvDnNGE" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
        </div>

        <div class="content-section">
            <h2>📝 JavaScript Basics</h2>
            
            <h3>Variables - Store Data</h3>
            <div class="code-example"><pre>// Three ways to declare variables
let name = "John";       // Can be changed
const age = 25;          // Cannot be changed
var oldWay = "legacy";   // Old way (avoid)

console.log(name);       // Output: John</pre></div>

            <h3>Data Types</h3>
            <div class="code-example"><pre>let text = "Hello";      // String
let number = 42;         // Number
let isTrue = true;       // Boolean
let empty = null;        // Null
let notDefined;          // Undefined
let items = [1, 2, 3];   // Array
let person = {           // Object
    name: "Alice",
    age: 30
};</pre></div>

            <h3>Functions - Reusable Code</h3>
            <div class="code-example"><pre>// Function declaration
function greet(name) {
    return "Hello, " + name + "!";
}

console.log(greet("World")); // Hello, World!

// Arrow function (modern)
const add = (a, b) => a + b;
console.log(add(5, 3));      // 8</pre></div>
        </div>

        <div class="content-section">
            <h2>🎮 Interactive Demo</h2>
            <p>Click the buttons to see JavaScript in action!</p>
            
            <div class="demo-box">
                <h3 id="demoText">Hello, World!</h3>
                <button class="demo-button" onclick="changeText()">Change Text</button>
                <button class="demo-button" onclick="changeColor()">Change Color</button>
                <button class="demo-button" onclick="showAlert()">Show Alert</button>
                <button class="demo-button" onclick="addNumber()">Count: <span id="counter">0</span></button>
            </div>

            <script>
                let count = 0;
                function changeText() {
                    document.getElementById('demoText').textContent = 'You clicked the button! 🎉';
                }
                function changeColor() {
                    const colors = ['#f7971e', '#4ecdc4', '#ff6b6b', '#95e1d3', '#c7ecee'];
                    const randomColor = colors[Math.floor(Math.random() * colors.length)];
                    document.getElementById('demoText').style.color = randomColor;
                }
                function showAlert() {
                    alert('This is JavaScript in action! 🚀');
                }
                function addNumber() {
                    count++;
                    document.getElementById('counter').textContent = count;
                }
            </script>
        </div>

        <div class="content-section">
            <h2>🔄 Control Flow</h2>
            
            <h3>If Statements</h3>
            <div class="code-example"><pre>let score = 85;

if (score >= 90) {
    console.log("Grade: A");
} else if (score >= 80) {
    console.log("Grade: B");
} else {
    console.log("Grade: C");
}</pre></div>

            <h3>Loops</h3>
            <div class="code-example"><pre>// For loop
for (let i = 0; i < 5; i++) {
    console.log(i);
}

// While loop
let count = 0;
while (count < 3) {
    console.log(count);
    count++;
}</pre></div>
        </div>

        <div class="content-section">
            <h2>💻 Try It Yourself!</h2>
            <p>Write JavaScript code and see it run instantly!</p>

            <div class="try-it-yourself">
                <h3>JavaScript Playground</h3>
                <p style="color: #aaa; margin-bottom: 1rem;">Write code and click Run to see results in the console!</p>
                
                <div class="editor-container">
                    <div class="editor-panel">
                        <div class="panel-header">✏️ JavaScript Code</div>
                        <textarea id="jsCode">// Welcome to JavaScript!
// Try these examples:

// 1. Variables and Math
let x = 10;
let y = 5;
console.log("Sum:", x + y);

// 2. Functions
function sayHello(name) {
    return `Hello, ${name}!`;
}
console.log(sayHello("Student"));

// 3. Arrays
let fruits = ["apple", "banana", "orange"];
console.log("Fruits:", fruits);
fruits.forEach(fruit => {
    console.log("I like " + fruit);
});

// 4. Objects
let person = {
    name: "Alice",
    age: 25,
    greet: function() {
        return `Hi, I'm ${this.name}!`;
    }
};
console.log(person.greet());

// Try your own code!</textarea>
                    </div>
                    
                    <div class="editor-panel">
                        <div class="panel-header">📺 Console Output</div>
                        <div id="jsConsole" class="console-output">Click "Run Code" to see output...</div>
                    </div>
                </div>
                
                <button class="run-button" onclick="runJSCode()">▶️ Run Code</button>
                <button class="run-button" onclick="clearConsole()" style="background: #666; margin-left: 1rem;">🗑️ Clear Console</button>
            </div>
        </div>

        <div class="quiz-section">
            <h2 style="color: #f7971e; margin-bottom: 1rem;">🎯 Ready for the Final Challenge?</h2>
            <p style="color: #ddd; margin-bottom: 2rem;">You've learned HTML, CSS, and JavaScript!<br>Complete this quiz to prove your mastery!</p>
            
            <?php if ($progress && $progress['quiz_passed']): ?>
                <div style="margin-bottom: 1rem;"><span class="completed-badge" style="font-size: 1.1rem;">✓ Quiz Completed!</span></div>
                <p style="color: #4caf50; margin-bottom: 1rem;">Congratulations! You've completed all lessons! 🎊</p>
                <a href="quiz_javascript.php" class="quiz-button">🔄 Retake Quiz</a>
                <a href="learning_hub.php" class="quiz-button" style="margin-left: 1rem; background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);">🏆 View Your Progress</a>
            <?php else: ?>
                <a href="quiz_javascript.php" class="quiz-button">Start JavaScript Quiz →</a>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

    <script>
        var jsEditor = CodeMirror.fromTextArea(document.getElementById('jsCode'), {
            mode: 'javascript',
            theme: 'monokai',
            lineNumbers: true,
            lineWrapping: true
        });

        function runJSCode() {
            const console = document.getElementById('jsConsole');
            const code = jsEditor.getValue();
            
            // Clear previous output
            console.innerHTML = '';
            
            // Capture console.log
            const originalLog = console.log;
            const logs = [];
            
            console.log = function(...args) {
                logs.push(args.map(arg => 
                    typeof arg === 'object' ? JSON.stringify(arg, null, 2) : String(arg)
                ).join(' '));
            };
            
            try {
                // Execute code
                eval(code);
                
                // Display output
                if (logs.length === 0) {
                    document.getElementById('jsConsole').innerHTML = '<span style="color: #888;">No output (code ran successfully)</span>';
                } else {
                    document.getElementById('jsConsole').innerHTML = logs.join('\n');
                }
            } catch (error) {
                document.getElementById('jsConsole').innerHTML = `<span style="color: #ff6b6b;">❌ Error: ${error.message}</span>`;
            }
            
            // Restore original console.log
            console.log = originalLog;
        }

        function clearConsole() {
            document.getElementById('jsConsole').innerHTML = 'Console cleared...';
        }
    </script>
</body>
</html>