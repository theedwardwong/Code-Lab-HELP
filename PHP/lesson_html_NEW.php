<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];
$lesson_id = 1;

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
    <title>HTML Basics - Step by Step | Code Lab @ HELP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/monokai.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background-color: #1a1a2e; color: white; overflow-x: hidden; }
        
        .top-nav { background-color: #16213e; padding: 1rem 2rem; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 10px rgba(0,0,0,0.3); }
        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .exit-btn { background-color: #e94560; color: white; border: none; padding: 0.5rem 1.5rem; border-radius: 5px; cursor: pointer; transition: all 0.3s; }
        .exit-btn:hover { background-color: #d63447; transform: translateY(-2px); }

        .progress-container { background-color: #16213e; padding: 1.5rem 2rem; }
        .progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .progress-text { font-size: 0.9rem; color: #aaa; }
        .lesson-title { font-size: 1.3rem; font-weight: bold; color: #667eea; }
        .progress-bar-outer { width: 100%; height: 8px; background-color: #0f3460; border-radius: 10px; overflow: hidden; }
        .progress-bar-inner { height: 100%; background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); transition: width 0.5s ease; border-radius: 10px; }
        .step-dots { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .step-dot { width: 12px; height: 12px; border-radius: 50%; background-color: #0f3460; transition: all 0.3s; cursor: pointer; }
        .step-dot.active { background-color: #667eea; transform: scale(1.3); }
        .step-dot.completed { background-color: #4caf50; }

        .lesson-container { max-width: 1200px; margin: 0 auto; padding: 2rem; min-height: calc(100vh - 250px); display: flex; align-items: center; justify-content: center; }
        
        .step-content { display: none; width: 100%; animation: fadeIn 0.5s; }
        .step-content.active { display: block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content-card { background-color: #16213e; padding: 3rem; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .content-card h2 { color: #667eea; font-size: 2rem; margin-bottom: 1.5rem; }
        .content-card h3 { color: #8b9dc3; margin-top: 2rem; margin-bottom: 1rem; }
        .content-card p { line-height: 1.8; color: #ddd; margin-bottom: 1rem; font-size: 1.1rem; }
        .content-card ul { margin-left: 2rem; line-height: 2; color: #ddd; }

        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 10px; margin: 2rem 0; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

        .code-box { background-color: #272822; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0; font-family: 'Courier New', monospace; overflow-x: auto; }
        .code-box pre { color: #f8f8f2; margin: 0; }

        .editor-container { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 2rem 0; }
        .editor-panel { background-color: #0f3460; border-radius: 10px; overflow: hidden; }
        .panel-header { background-color: #16213e; padding: 0.8rem; font-weight: bold; color: #667eea; }
        .CodeMirror { height: 350px; font-size: 14px; }
        .output-frame { width: 100%; height: 350px; border: none; background-color: white; }

        .info-box { background-color: rgba(102, 126, 234, 0.1); border-left: 4px solid #667eea; padding: 1.5rem; margin: 1.5rem 0; border-radius: 5px; }
        .tip-box { background-color: rgba(76, 175, 80, 0.1); border-left: 4px solid #4caf50; padding: 1.5rem; margin: 1.5rem 0; border-radius: 5px; }

        .nav-buttons { display: flex; justify-content: space-between; margin-top: 3rem; gap: 1rem; }
        .nav-btn { padding: 1rem 2.5rem; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: all 0.3s; }
        .btn-prev { background-color: #0f3460; color: white; }
        .btn-prev:hover:not(.btn-disabled) { background-color: #16213e; }
        .btn-next { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn-next:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3); }
        .btn-quiz { background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; }
        .btn-quiz:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3); }
        .btn-disabled { opacity: 0.5; cursor: not-allowed !important; }

        .completed-badge { background-color: #4caf50; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; }

        .demo-box { background-color: #0f3460; padding: 2rem; border-radius: 10px; margin: 2rem 0; text-align: center; }
        .animated-element { width: 100px; height: 100px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 15px; margin: 2rem auto; animation: float 3s ease-in-out infinite; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }

        @media (max-width: 768px) {
            .editor-container { grid-template-columns: 1fr; }
            .nav-buttons { flex-direction: column; }
            .content-card { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="logo">🌐 HTML Basics - Interactive Lesson</div>
        <button class="exit-btn" onclick="window.location.href='learning_hub.php'">← Exit to Hub</button>
    </div>

    <div class="progress-container">
        <div class="progress-header">
            <div>
                <div class="lesson-title">HTML Fundamentals</div>
                <div class="progress-text">Step <span id="currentStep">1</span> of <span id="totalSteps">6</span></div>
            </div>
            <?php if ($progress && $progress['quiz_passed']): ?>
                <span class="completed-badge">✓ Completed</span>
            <?php endif; ?>
        </div>
        <div class="progress-bar-outer">
            <div class="progress-bar-inner" id="progressBar" style="width: 16.67%;"></div>
        </div>
        <div class="step-dots" id="stepDots"></div>
    </div>

    <div class="lesson-container">
        <!-- STEP 1 -->
        <div class="step-content active" data-step="1">
            <div class="content-card">
                <h2>🌐 Welcome to HTML!</h2>
                <p>You're about to learn the foundation of every website on the internet!</p>
                
                <div class="info-box">
                    <strong>💡 What is HTML?</strong><br>
                    HTML (HyperText Markup Language) is the standard language for creating web pages. It describes the structure of a webpage using elements (tags).
                </div>

                <h3>Why Learn HTML?</h3>
                <p>HTML is essential for:</p>
                <ul>
                    <li>Building websites and web applications</li>
                    <li>Creating landing pages and portfolios</li>
                    <li>Understanding how the web works</li>
                    <li>Starting your web development career</li>
                </ul>

                <div class="tip-box">
                    <strong>✨ Fun Fact:</strong> HTML was created by Tim Berners-Lee in 1991. The latest version is HTML5!
                </div>

                <div class="demo-box">
                    <p style="margin-bottom: 1rem;">This animated box is created with HTML & CSS!</p>
                    <div class="animated-element"></div>
                </div>
            </div>
        </div>

        <!-- STEP 2 -->
        <div class="step-content" data-step="2">
            <div class="content-card">
                <h2>🎥 Watch: HTML in 10 Minutes</h2>
                <p>Let's watch a quick introduction to HTML. This video will give you a solid overview!</p>
                
                <div class="video-wrapper">
                    <iframe src="https://www.youtube.com/embed/salY_Sm6mv4" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>

                <div class="tip-box">
                    <strong>✅ Pro Tip:</strong> Don't worry if you don't understand everything yet! We'll break it down step by step in the next sections.
                </div>
            </div>
        </div>

        <!-- STEP 3 -->
        <div class="step-content" data-step="3">
            <div class="content-card">
                <h2>🏗️ HTML Document Structure</h2>
                <p>Every HTML document follows this basic structure:</p>

                <div class="code-box">
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

                <h3>Understanding Each Part:</h3>
                <div style="margin-left: 2rem; line-height: 2.5;">
                    <p><strong style="color: #667eea;">&lt;!DOCTYPE html&gt;</strong> - Tells browser this is HTML5</p>
                    <p><strong style="color: #667eea;">&lt;html&gt;</strong> - Root element, wraps everything</p>
                    <p><strong style="color: #667eea;">&lt;head&gt;</strong> - Contains metadata (title, links, etc.)</p>
                    <p><strong style="color: #667eea;">&lt;body&gt;</strong> - Contains all visible content</p>
                </div>

                <div class="info-box">
                    <strong>📌 Remember:</strong> Always close your tags! Every &lt;p&gt; needs a &lt;/p&gt;
                </div>
            </div>
        </div>

        <!-- STEP 4 -->
        <div class="step-content" data-step="4">
            <div class="content-card">
                <h2>🏷️ Essential HTML Tags</h2>

                <h3>Headings</h3>
                <div class="code-box">
                    <pre>&lt;h1&gt;Main Heading&lt;/h1&gt;
&lt;h2&gt;Subheading&lt;/h2&gt;
&lt;h3&gt;Smaller Heading&lt;/h3&gt;</pre>
                </div>

                <h3>Text Formatting</h3>
                <div class="code-box">
                    <pre>&lt;p&gt;Paragraph&lt;/p&gt;
&lt;strong&gt;Bold&lt;/strong&gt;
&lt;em&gt;Italic&lt;/em&gt;
&lt;br&gt; &lt;!-- Line break --&gt;</pre>
                </div>

                <h3>Links & Images</h3>
                <div class="code-box">
                    <pre>&lt;a href="url"&gt;Link&lt;/a&gt;
&lt;img src="image.jpg" alt="Description"&gt;</pre>
                </div>

                <h3>Lists</h3>
                <div class="code-box">
                    <pre>&lt;ul&gt;
  &lt;li&gt;Item 1&lt;/li&gt;
  &lt;li&gt;Item 2&lt;/li&gt;
&lt;/ul&gt;</pre>
                </div>
            </div>
        </div>

        <!-- STEP 5 -->
        <div class="step-content" data-step="5">
            <div class="content-card">
                <h2>💻 Try It Yourself!</h2>
                <p>Edit the code and see results instantly:</p>

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
  <h1>Hello World!</h1>
  <p>Edit this code!</p>
  <ul>
    <li>Try changing text</li>
    <li>Add more elements</li>
  </ul>
</body>
</html></textarea>
                    </div>
                    
                    <div class="editor-panel">
                        <div class="panel-header">👁️ Live Preview</div>
                        <iframe id="output" class="output-frame"></iframe>
                    </div>
                </div>

                <div class="tip-box">
                    <strong>⚡ Challenge:</strong> Create an "About Me" page with your name and hobbies!
                </div>
            </div>
        </div>

        <!-- STEP 6 -->
        <div class="step-content" data-step="6">
            <div class="content-card" style="text-align: center;">
                <h2>🎯 You're Ready!</h2>
                <p style="font-size: 1.2rem; margin: 2rem 0;">Great job completing the HTML lesson!</p>
                
                <div style="font-size: 5rem; margin: 2rem 0;">🎉</div>
                
                <p style="font-size: 1.1rem; color: #ddd; margin-bottom: 2rem;">
                    You've learned HTML fundamentals!<br>
                    Time to test your knowledge.
                </p>

                <div class="info-box" style="text-align: left;">
                    <strong>📝 Quiz Topics:</strong>
                    <ul style="margin-top: 1rem;">
                        <li>HTML basics</li>
                        <li>Document structure</li>
                        <li>Common tags</li>
                    </ul>
                </div>

                <div class="tip-box" style="text-align: left;">
                    <strong>✅ Requirements:</strong>
                    <ul style="margin-top: 1rem;">
                        <li>10 questions</li>
                        <li>Need 7/10 to pass</li>
                        <li>Instant feedback</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div style="max-width: 1200px; margin: 0 auto; padding: 0 2rem 2rem;">
        <div class="nav-buttons">
            <button class="nav-btn btn-prev btn-disabled" id="prevBtn" onclick="changeStep(-1)" disabled>
                ← Previous
            </button>
            <button class="nav-btn btn-next" id="nextBtn" onclick="changeStep(1)">
                Next Step →
            </button>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>

    <script>
        let currentStep = 1;
        const totalSteps = 6;
        let editor = null;

        document.addEventListener('DOMContentLoaded', function() {
            updateStepDots();
            updateProgress();
        });

        function changeStep(direction) {
            const newStep = currentStep + direction;
            if (newStep < 1 || newStep > totalSteps) return;
            
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
            currentStep = newStep;
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
            
            if (currentStep === 5 && !editor) initCodeEditor();
            
            updateProgress();
            updateButtons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function updateProgress() {
            const progress = (currentStep / totalSteps) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
            document.getElementById('currentStep').textContent = currentStep;
            document.getElementById('totalSteps').textContent = totalSteps;
            updateStepDots();
        }

        function updateStepDots() {
            const dotsContainer = document.getElementById('stepDots');
            dotsContainer.innerHTML = '';
            
            for (let i = 1; i <= totalSteps; i++) {
                const dot = document.createElement('div');
                dot.className = 'step-dot';
                if (i < currentStep) dot.classList.add('completed');
                if (i === currentStep) dot.classList.add('active');
                dot.onclick = () => jumpToStep(i);
                dotsContainer.appendChild(dot);
            }
        }

        function jumpToStep(step) {
            if (step > currentStep) return; // Can't skip ahead
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.remove('active');
            currentStep = step;
            document.querySelector(`.step-content[data-step="${currentStep}"]`).classList.add('active');
            updateProgress();
            updateButtons();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function updateButtons() {
            const prevBtn = document.getElementById('prevBtn');
            const nextBtn = document.getElementById('nextBtn');
            
            if (currentStep === 1) {
                prevBtn.disabled = true;
                prevBtn.classList.add('btn-disabled');
            } else {
                prevBtn.disabled = false;
                prevBtn.classList.remove('btn-disabled');
            }
            
            if (currentStep === totalSteps) {
                nextBtn.textContent = 'Take Quiz →';
                nextBtn.className = 'nav-btn btn-quiz';
                nextBtn.onclick = function() { window.location.href = 'quiz_html.php'; };
            } else {
                nextBtn.textContent = 'Next Step →';
                nextBtn.className = 'nav-btn btn-next';
                nextBtn.onclick = function() { changeStep(1); };
            }
        }

        function initCodeEditor() {
            editor = CodeMirror.fromTextArea(document.getElementById('htmlCode'), {
                mode: 'htmlmixed',
                theme: 'monokai',
                lineNumbers: true,
                autoCloseTags: true,
                lineWrapping: true
            });
            
            runCode();
            editor.on('change', function() {
                clearTimeout(window.codeTimeout);
                window.codeTimeout = setTimeout(runCode, 1000);
            });
        }

        function runCode() {
            if (!editor) return;
            const code = editor.getValue();
            document.getElementById('output').srcdoc = code;
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' && currentStep < totalSteps) changeStep(1);
            else if (e.key === 'ArrowLeft' && currentStep > 1) changeStep(-1);
        });
    </script>
</body>
</html>