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

// Check if student has already completed this lesson's quiz
$progress_check = $conn->prepare("SELECT * FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = ?");
$progress_check->bind_param("ii", $student_id, $lesson_id);
$progress_check->execute();
$progress = $progress_check->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HTML Basics - Interactive Tutorial | Code Lab @ HELP</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            border-left: 4px solid #667eea;
        }

        .content-section h2 {
            color: #667eea;
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }

        .content-section h3 {
            color: #8b9dc3;
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

        .try-it-yourself {
            background-color: #2e3f54;
            padding: 2rem;
            border-radius: 12px;
            margin: 2rem 0;
            border: 2px solid #667eea;
        }

        .try-it-yourself h3 {
            color: #667eea;
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
            color: #667eea;
        }

        .CodeMirror {
            height: 300px;
            font-size: 14px;
        }

        .output-frame {
            width: 100%;
            height: 300px;
            border: none;
            background-color: white;
        }

        .run-button {
            background-color: #667eea;
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
            background-color: #764ba2;
            transform: translateY(-2px);
        }

        .info-box {
            background-color: rgba(102, 126, 234, 0.1);
            border-left: 4px solid #667eea;
            padding: 1rem;
            margin: 1rem 0;
            border-radius: 4px;
        }

        .warning-box {
            background-color: rgba(255, 152, 0, 0.1);
            border-left: 4px solid #ff9800;
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
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

        .image-container {
            text-align: center;
            margin: 2rem 0;
        }

        .image-container img {
            max-width: 100%;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
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
        <div class="lesson-header">
            <h1>🌐 HTML Basics - Interactive Tutorial</h1>
            <p>Master the foundation of web development with hands-on practice</p>
            <?php if ($progress && $progress['quiz_passed']): ?>
                <div class="completed-badge">✓ Completed - Score: <?php echo $progress['quiz_score']; ?>/<?php echo $progress['quiz_total']; ?></div>
            <?php else: ?>
                <div class="progress-badge">📚 In Progress</div>
            <?php endif; ?>
        </div>

        <!-- Section 1: Introduction -->
        <div class="content-section">
            <h2>📖 What is HTML?</h2>
            <p>
                <strong>HTML (HyperText Markup Language)</strong> is the standard language for creating web pages. 
                It describes the structure of a webpage using a series of elements (tags) that tell the browser 
                how to display content.
            </p>
            
            <div class="info-box">
                <strong>💡 Did you know?</strong> HTML was created by Tim Berners-Lee in 1991. The latest version is HTML5!
            </div>

            <h3>Why Learn HTML?</h3>
            <p>HTML is the backbone of every website you visit. Whether you want to build:</p>
            <ul style="margin-left: 2rem; line-height: 2;">
                <li>Personal blogs or portfolios</li>
                <li>E-commerce websites</li>
                <li>Social media platforms</li>
                <li>Mobile applications</li>
            </ul>
            <p>...you MUST know HTML!</p>

            <!-- Video Tutorial -->
            <h3>🎥 Watch: HTML in 10 Minutes</h3>
            <div class="video-container">
                <iframe src="https://www.youtube.com/embed/salY_Sm6mv4" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                </iframe>
            </div>
        </div>

        <!-- Section 2: HTML Structure -->
        <div class="content-section">
            <h2>🏗️ Basic HTML Structure</h2>
            <p>Every HTML document follows this basic structure:</p>

            <div class="code-example">
                <pre>&lt;!DOCTYPE html&gt;
&lt;html&gt;
  &lt;head&gt;
    &lt;title&gt;My First Webpage&lt;/title&gt;
  &lt;/head&gt;
  &lt;body&gt;
    &lt;h1&gt;Hello World!&lt;/h1&gt;
    &lt;p&gt;This is my first paragraph.&lt;/p&gt;
  &lt;/body&gt;
&lt;/html&gt;</pre>
            </div>

            <div class="tip-box">
                <strong>✅ Pro Tip:</strong> Always remember to close your tags! Every opening tag like &lt;p&gt; needs a closing tag &lt;/p&gt;
            </div>

            <h3>Understanding the Structure:</h3>
            <ul style="margin-left: 2rem; line-height: 2; color: #ddd;">
                <li><code>&lt;!DOCTYPE html&gt;</code> - Tells the browser this is an HTML5 document</li>
                <li><code>&lt;html&gt;</code> - Root element of the page</li>
                <li><code>&lt;head&gt;</code> - Contains metadata (title, links to CSS, etc.)</li>
                <li><code>&lt;body&gt;</code> - Contains all visible content</li>
            </ul>

            <!-- Animated Diagram -->
            <div class="image-container">
                <img src="https://www.w3schools.com/html/img_chrome.png" alt="HTML Structure Diagram">
                <p style="color: #aaa; margin-top: 0.5rem;">Visual representation of HTML structure</p>
            </div>
        </div>

        <!-- Section 3: Common HTML Tags -->
        <div class="content-section">
            <h2>🏷️ Essential HTML Tags</h2>
            
            <h3>Headings (H1 to H6)</h3>
            <p>HTML has 6 levels of headings, from largest to smallest:</p>
            <div class="code-example">
                <pre>&lt;h1&gt;Main Heading&lt;/h1&gt;
&lt;h2&gt;Subheading&lt;/h2&gt;
&lt;h3&gt;Smaller Heading&lt;/h3&gt;
&lt;h4&gt;Even Smaller&lt;/h4&gt;
&lt;h5&gt;Getting Tiny&lt;/h5&gt;
&lt;h6&gt;Smallest Heading&lt;/h6&gt;</pre>
            </div>

            <h3>Paragraphs and Text Formatting</h3>
            <div class="code-example">
                <pre>&lt;p&gt;This is a paragraph.&lt;/p&gt;
&lt;strong&gt;Bold text&lt;/strong&gt;
&lt;em&gt;Italic text&lt;/em&gt;
&lt;u&gt;Underlined text&lt;/u&gt;
&lt;br&gt; &lt;!-- Line break --&gt;</pre>
            </div>

            <h3>Links and Images</h3>
            <div class="code-example">
                <pre>&lt;a href="https://www.example.com"&gt;Click me!&lt;/a&gt;
&lt;img src="image.jpg" alt="Description"&gt;</pre>
            </div>

            <h3>Lists</h3>
            <div class="code-example">
                <pre>&lt;!-- Unordered List --&gt;
&lt;ul&gt;
  &lt;li&gt;Item 1&lt;/li&gt;
  &lt;li&gt;Item 2&lt;/li&gt;
&lt;/ul&gt;

&lt;!-- Ordered List --&gt;
&lt;ol&gt;
  &lt;li&gt;First&lt;/li&gt;
  &lt;li&gt;Second&lt;/li&gt;
&lt;/ol&gt;</pre>
            </div>
        </div>

        <!-- Section 4: Try It Yourself! -->
        <div class="content-section">
            <h2>💻 Try It Yourself!</h2>
            <p>The best way to learn HTML is by practicing! Edit the code below and click "Run Code" to see the result:</p>

            <div class="try-it-yourself">
                <h3>Interactive Code Editor</h3>
                <p style="color: #aaa; margin-bottom: 1rem;">Modify the HTML code and see instant results!</p>
                
                <div class="editor-container">
                    <div class="editor-panel">
                        <div class="panel-header">✏️ HTML Code</div>
                        <textarea id="htmlCode"><!DOCTYPE html>
<html>
<head>
  <title>My Page</title>
  <style>
    body { font-family: Arial; padding: 20px; }
    h1 { color: #667eea; }
  </style>
</head>
<body>
  <h1>Hello, World!</h1>
  <p>Edit this HTML code to create your own webpage!</p>
  <p><strong>Try adding:</strong></p>
  <ul>
    <li>More headings</li>
    <li>A link to your favorite website</li>
    <li>An image</li>
  </ul>
</body>
</html></textarea>
                    </div>
                    
                    <div class="editor-panel">
                        <div class="panel-header">👁️ Output</div>
                        <iframe id="output" class="output-frame"></iframe>
                    </div>
                </div>
                
                <button class="run-button" onclick="runCode()">▶️ Run Code</button>
            </div>

            <div class="warning-box">
                <strong>⚠️ Challenge:</strong> Try creating a simple "About Me" page with:
                <ul style="margin-left: 2rem; margin-top: 0.5rem;">
                    <li>A main heading with your name</li>
                    <li>A paragraph about yourself</li>
                    <li>A list of your hobbies</li>
                </ul>
            </div>
        </div>

        <!-- Section 5: More Examples -->
        <div class="content-section">
            <h2>🎨 Real-World Example</h2>
            <p>Here's what a simple profile card looks like in HTML:</p>

            <div class="code-example">
                <pre>&lt;div class="profile-card"&gt;
  &lt;img src="avatar.jpg" alt="Profile Picture"&gt;
  &lt;h2&gt;John Doe&lt;/h2&gt;
  &lt;p&gt;Web Developer&lt;/p&gt;
  &lt;a href="mailto:john@example.com"&gt;Contact Me&lt;/a&gt;
&lt;/div&gt;</pre>
            </div>

            <h3>🎬 Advanced HTML Tutorial</h3>
            <div class="video-container">
                <iframe src="https://www.youtube.com/embed/pQN-pnXPaVg" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                        allowfullscreen>
                </iframe>
            </div>
        </div>

        <!-- Quiz Section -->
        <div class="quiz-section">
            <h2 style="color: #667eea; margin-bottom: 1rem;">🎯 Ready to Test Your Knowledge?</h2>
            <p style="color: #ddd; margin-bottom: 2rem;">
                You've learned the basics of HTML! Now it's time to prove what you know.<br>
                Complete the quiz to unlock the next lesson: CSS Styling!
            </p>
            
            <?php if ($progress && $progress['quiz_passed']): ?>
                <div style="margin-bottom: 1rem;">
                    <span class="completed-badge" style="font-size: 1.1rem;">✓ Quiz Completed!</span>
                </div>
                <p style="color: #4caf50; margin-bottom: 1rem;">Great job! You can retake the quiz or move to the next lesson.</p>
                <a href="quiz_html.php" class="quiz-button">🔄 Retake Quiz</a>
                <a href="lesson_css.php" class="quiz-button" style="margin-left: 1rem; background: linear-gradient(135deg, #4caf50 0%, #45a049 100%);">➡️ Next: CSS Lesson</a>
            <?php else: ?>
                <a href="quiz_html.php" class="quiz-button">Start Quiz →</a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

    <script>
        // Initialize CodeMirror
        var editor = CodeMirror.fromTextArea(document.getElementById('htmlCode'), {
            mode: 'htmlmixed',
            theme: 'monokai',
            lineNumbers: true,
            autoCloseTags: true,
            lineWrapping: true
        });

        // Run code on initial load
        window.onload = function() {
            runCode();
        };

        function runCode() {
            var code = editor.getValue();
            var output = document.getElementById('output');
            
            output.srcdoc = code;
        }

        // Auto-run code when user stops typing (debounce)
        var timeout;
        editor.on('change', function() {
            clearTimeout(timeout);
            timeout = setTimeout(runCode, 1000);
        });
    </script>
</body>
</html>