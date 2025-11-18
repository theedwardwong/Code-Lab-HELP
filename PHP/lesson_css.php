<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];
$lesson_id = 2; // CSS Basics lesson

// Check quiz progress
$progress_check = $conn->prepare("SELECT * FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = ?");
$progress_check->bind_param("ii", $student_id, $lesson_id);
$progress_check->execute();
$progress = $progress_check->get_result()->fetch_assoc();

// Check if HTML lesson was completed
$html_check = $conn->prepare("SELECT quiz_passed FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = 1");
$html_check->bind_param("i", $student_id);
$html_check->execute();
$html_progress = $html_check->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Styling - Interactive Tutorial | Code Lab @ HELP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
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
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .lesson-header {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
        }

        .lesson-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .progress-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background-color: rgba(255,255,255,0.2);
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 1rem;
        }

        .content-section {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            border-left: 4px solid #f093fb;
        }

        .content-section h2 {
            color: #f093fb;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .content-section h3 {
            color: #f5576c;
            margin-top: 1.5rem;
            margin-bottom: 0.8rem;
            font-size: 1.3rem;
        }

        .content-section p {
            line-height: 1.8;
            color: #ddd;
            margin-bottom: 1rem;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            max-width: 100%;
            border-radius: 8px;
            margin: 1.5rem 0;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none;
        }

        .code-example {
            background-color: #272822;
            padding: 1.5rem;
            border-radius: 8px;
            margin: 1rem 0;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
        }

        .code-example pre {
            color: #f8f8f2;
            margin: 0;
        }

        .demo-box {
            background-color: #2e3f54;
            padding: 2rem;
            border-radius: 12px;
            margin: 1.5rem 0;
        }

        .demo-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .demo-item {
            background-color: #1a2332;
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            transition: all 0.3s;
        }

        .demo-item:hover {
            transform: scale(1.05);
        }

        .color-demo {
            width: 100%;
            height: 100px;
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .try-it-yourself {
            background-color: #2e3f54;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
            border: 2px solid #f093fb;
        }

        .try-it-yourself h3 {
            color: #f093fb;
            margin-bottom: 1rem;
        }

        .editor-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }

        .editor-panel {
            background-color: #1a2332;
            border-radius: 8px;
            overflow: hidden;
        }

        .panel-header {
            background-color: #111;
            padding: 0.8rem;
            font-weight: bold;
            color: #f093fb;
        }

        .CodeMirror {
            height: 400px;
            font-size: 14px;
        }

        .output-frame {
            width: 100%;
            height: 400px;
            border: none;
            background-color: white;
        }

        .run-button {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            border: none;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 1rem;
            transition: all 0.3s;
        }

        .run-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(240, 147, 251, 0.3);
        }

        .info-box {
            background-color: rgba(240, 147, 251, 0.1);
            border-left: 4px solid #f093fb;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }

        .tip-box {
            background-color: rgba(76, 175, 80, 0.1);
            border-left: 4px solid #4caf50;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }

        .quiz-button {
            display: inline-block;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 1rem 3rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.1rem;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
        }

        .quiz-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(240, 147, 251, 0.3);
        }

        .quiz-section {
            text-align: center;
            padding: 3rem;
            background-color: #1a2332;
            border-radius: 12px;
            margin-top: 2rem;
        }

        .completed-badge {
            background-color: #4caf50;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            display: inline-block;
            margin-top: 1rem;
        }

        .locked-message {
            background-color: rgba(255, 152, 0, 0.1);
            border: 2px solid #ff9800;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            margin: 2rem 0;
        }

        .animation-demo {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            margin: 1.5rem 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 200px;
        }

        .animated-box {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 15px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-30px); }
        }

        @media (max-width: 768px) {
            .editor-container {
                grid-template-columns: 1fr;
            }
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
            <li><a href="browser.php">Browse</a></li>
            <li><a href="learning_hub.php">Learning Hub</a></li>
        </ul>
        <div>
            <span><?php echo htmlspecialchars($student_name); ?></span>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <?php if (!$html_progress || !$html_progress['quiz_passed']): ?>
            <div class="locked-message">
                <h2 style="color: #ff9800; margin-bottom: 1rem;">🔒 Lesson Locked</h2>
                <p>You need to complete the HTML lesson and pass its quiz first!</p>
                <a href="lesson_html.php" class="quiz-button" style="margin-top: 1rem; display: inline-block;">Go to HTML Lesson</a>
            </div>
        <?php else: ?>

        <div class="lesson-header">
            <h1>🎨 CSS Styling - Interactive Tutorial</h1>
            <p>Learn to make websites beautiful with CSS!</p>
            <?php if ($progress && $progress['quiz_passed']): ?>
                <div class="completed-badge">✓ Completed - Score: <?php echo $progress['quiz_score']; ?>/<?php echo $progress['quiz_total']; ?></div>
            <?php else: ?>
                <div class="progress-badge">📚 In Progress</div>
            <?php endif; ?>
        </div>

        <!-- Section 1: Introduction -->
        <div class="content-section">
            <h2>🌈 What is CSS?</h2>
            <p>
                <strong>CSS (Cascading Style Sheets)</strong> is a language used to style and layout web pages. 
                While HTML provides the structure, CSS makes it look beautiful! You can control colors, fonts, 
                spacing, layouts, animations, and much more.
            </p>
            
            <div class="info-box">
                <strong>💡 Fun Fact:</strong> CSS was first released in 1996. The latest version is CSS3, which added amazing features like animations and gradients!
            </div>

            <!-- Animated Demo -->
            <h3>✨ See CSS in Action!</h3>
            <div class="animation-demo">
                <div class="animated-box"></div>
            </div>
            <p style="text-align: center; color: #aaa;">This floating animation is pure CSS! No JavaScript needed! 🚀</p>

            <!-- Video Tutorial -->
            <h3>🎥 Watch: CSS in 20 Minutes</h3>
            <div class="video-container">
                <iframe src="https://www.youtube.com/embed/1PnVor36_40" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                </iframe>
            </div>
        </div>

        <!-- Section 2: CSS Syntax -->
        <div class="content-section">
            <h2>📝 CSS Syntax</h2>
            <p>CSS rules consist of a <strong>selector</strong> and a <strong>declaration block</strong>:</p>

            <div class="code-example">
                <pre>selector {
  property: value;
  another-property: value;
}</pre>
            </div>

            <h3>Example:</h3>
            <div class="code-example">
                <pre>h1 {
  color: blue;
  font-size: 36px;
  text-align: center;
}</pre>
            </div>

            <p><strong>Breakdown:</strong></p>
            <ul style="margin-left: 2rem; line-height: 2; color: #ddd;">
                <li><code>h1</code> - Selector (targets all &lt;h1&gt; elements)</li>
                <li><code>color: blue;</code> - Property and value</li>
                <li><code>{ }</code> - Declaration block</li>
            </ul>
        </div>

        <!-- Section 3: Adding CSS -->
        <div class="content-section">
            <h2>📌 Three Ways to Add CSS</h2>

            <h3>1. Inline CSS (Not Recommended)</h3>
            <div class="code-example">
                <pre>&lt;h1 style="color: blue;"&gt;Hello!&lt;/h1&gt;</pre>
            </div>

            <h3>2. Internal CSS (Good for Single Pages)</h3>
            <div class="code-example">
                <pre>&lt;head&gt;
  &lt;style&gt;
    h1 { color: blue; }
  &lt;/style&gt;
&lt;/head&gt;</pre>
            </div>

            <h3>3. External CSS (Best Practice!) ✅</h3>
            <div class="code-example">
                <pre>&lt;head&gt;
  &lt;link rel="stylesheet" href="styles.css"&gt;
&lt;/head&gt;</pre>
            </div>

            <div class="tip-box">
                <strong>✅ Pro Tip:</strong> Always use external CSS files for real projects! It keeps your code organized and maintainable.
            </div>
        </div>

        <!-- Section 4: Colors and Backgrounds -->
        <div class="content-section">
            <h2>🎨 Colors in CSS</h2>
            <p>CSS supports multiple ways to define colors:</p>

            <h3>Color Examples:</h3>
            <div class="demo-box">
                <div class="demo-grid">
                    <div class="demo-item">
                        <div class="color-demo" style="background-color: red;"></div>
                        <code>color: red;</code>
                    </div>
                    <div class="demo-item">
                        <div class="color-demo" style="background-color: #3498db;"></div>
                        <code>color: #3498db;</code>
                    </div>
                    <div class="demo-item">
                        <div class="color-demo" style="background-color: rgb(46, 204, 113);"></div>
                        <code>rgb(46, 204, 113)</code>
                    </div>
                    <div class="demo-item">
                        <div class="color-demo" style="background: linear-gradient(45deg, #f093fb, #f5576c);"></div>
                        <code>gradient</code>
                    </div>
                </div>
            </div>

            <div class="code-example">
                <pre>/* Named colors */
h1 { color: red; }

/* Hexadecimal */
p { color: #3498db; }

/* RGB */
div { background-color: rgb(46, 204, 113); }

/* RGBA (with transparency) */
button { background-color: rgba(231, 76, 60, 0.7); }</pre>
            </div>
        </div>

        <!-- Section 5: Common Properties -->
        <div class="content-section">
            <h2>🔧 Essential CSS Properties</h2>

            <h3>Text Styling:</h3>
            <div class="code-example">
                <pre>font-family: Arial, sans-serif;
font-size: 16px;
font-weight: bold;
text-align: center;
text-decoration: underline;
line-height: 1.5;</pre>
            </div>

            <h3>Box Model:</h3>
            <div class="code-example">
                <pre>margin: 10px;           /* Space outside */
padding: 20px;          /* Space inside */
border: 2px solid black;
width: 300px;
height: 200px;</pre>
            </div>

            <h3>Layout:</h3>
            <div class="code-example">
                <pre>display: flex;          /* Flexbox layout */
justify-content: center;
align-items: center;
position: relative;
z-index: 10;</pre>
            </div>
        </div>

        <!-- Section 6: Try It Yourself! -->
        <div class="content-section">
            <h2>💻 Try It Yourself!</h2>
            <p>Experiment with CSS! Try changing colors, sizes, and layouts:</p>

            <div class="try-it-yourself">
                <h3>Interactive CSS Playground</h3>
                <p style="color: #aaa; margin-bottom: 1rem;">Edit both HTML and CSS to see live results!</p>
                
                <div class="editor-container">
                    <div class="editor-panel">
                        <div class="panel-header">✏️ HTML + CSS Code</div>
                        <textarea id="cssCode"><!DOCTYPE html>
<html>
<head>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .card {
      background-color: white;
      border-radius: 15px;
      padding: 30px;
      max-width: 400px;
      margin: 0 auto;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    }
    
    h1 {
      color: #667eea;
      text-align: center;
      margin: 0 0 10px 0;
    }
    
    p {
      color: #666;
      text-align: center;
      line-height: 1.6;
    }
    
    .button {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
      padding: 12px 30px;
      border: none;
      border-radius: 25px;
      font-weight: bold;
      display: block;
      margin: 20px auto 0;
      cursor: pointer;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>Welcome!</h1>
    <p>This is a beautiful card styled with CSS. Try changing the colors, borders, or adding animations!</p>
    <button class="button">Click Me</button>
  </div>
</body>
</html></textarea>
                    </div>
                    
                    <div class="editor-panel">
                        <div class="panel-header">👁️ Live Preview</div>
                        <iframe id="cssOutput" class="output-frame"></iframe>
                    </div>
                </div>
                
                <button class="run-button" onclick="runCSSCode()">▶️ Run Code</button>
            </div>
        </div>

        <!-- Quiz Section -->
        <div class="quiz-section">
            <h2 style="color: #f093fb; margin-bottom: 1rem;">🎯 Ready to Test Your CSS Skills?</h2>
            <p style="color: #ddd; margin-bottom: 2rem;">
                You've learned the essentials of CSS! Time to prove your styling skills.<br>
                Complete the quiz to unlock JavaScript lesson!
            </p>
            
            <?php if ($progress && $progress['quiz_passed']): ?>
                <div style="margin-bottom: 1rem;">
                    <span class="completed-badge" style="font-size: 1.1rem;">✓ Quiz Completed!</span>
                </div>
                <p style="color: #4caf50; margin-bottom: 1rem;">Awesome! You can retake the quiz or continue learning!</p>
                <a href="quiz_css.php" class="quiz-button">🔄 Retake Quiz</a>
                <a href="lesson_javascript.php" class="quiz-button" style="margin-left: 1rem; background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);">➡️ Next: JavaScript Lesson</a>
            <?php else: ?>
                <a href="quiz_css.php" class="quiz-button">Start CSS Quiz →</a>
            <?php endif; ?>
        </div>

        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>

    <script>
        var cssEditor = CodeMirror.fromTextArea(document.getElementById('cssCode'), {
            mode: 'htmlmixed',
            theme: 'monokai',
            lineNumbers: true,
            autoCloseTags: true,
            lineWrapping: true
        });

        window.onload = function() {
            runCSSCode();
        };

        function runCSSCode() {
            var code = cssEditor.getValue();
            var output = document.getElementById('cssOutput');
            output.srcdoc = code;
        }

        var timeout;
        cssEditor.on('change', function() {
            clearTimeout(timeout);
            timeout = setTimeout(runCSSCode, 1000);
        });
    </script>
</body>
</html>