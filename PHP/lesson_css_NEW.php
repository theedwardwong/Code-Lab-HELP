<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['full_name'];
$lesson_id = 2;

$progress_check = $conn->prepare("SELECT * FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = ?");
$progress_check->bind_param("ii", $student_id, $lesson_id);
$progress_check->execute();
$progress = $progress_check->get_result()->fetch_assoc();

$html_check = $conn->prepare("SELECT quiz_passed FROM lesson_quiz_progress WHERE student_id = ? AND lesson_id = 1");
$html_check->bind_param("i", $student_id);
$html_check->execute();
$html_progress = $html_check->get_result()->fetch_assoc();
$html_passed = $html_progress && $html_progress['quiz_passed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSS Styling - Step by Step | Code Lab @ HELP</title>
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
        .lesson-title { font-size: 1.3rem; font-weight: bold; color: #f093fb; }
        .progress-bar-outer { width: 100%; height: 8px; background-color: #0f3460; border-radius: 10px; overflow: hidden; }
        .progress-bar-inner { height: 100%; background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%); transition: width 0.5s ease; border-radius: 10px; }
        .step-dots { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .step-dot { width: 12px; height: 12px; border-radius: 50%; background-color: #0f3460; transition: all 0.3s; cursor: pointer; }
        .step-dot.active { background-color: #f093fb; transform: scale(1.3); }
        .step-dot.completed { background-color: #4caf50; }

        .lesson-container { max-width: 1200px; margin: 0 auto; padding: 2rem; min-height: calc(100vh - 250px); display: flex; align-items: center; justify-content: center; }
        
        .step-content { display: none; width: 100%; animation: fadeIn 0.5s; }
        .step-content.active { display: block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content-card { background-color: #16213e; padding: 3rem; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .content-card h2 { color: #f093fb; font-size: 2rem; margin-bottom: 1.5rem; }
        .content-card h3 { color: #f5576c; margin-top: 2rem; margin-bottom: 1rem; }
        .content-card p { line-height: 1.8; color: #ddd; margin-bottom: 1rem; font-size: 1.1rem; }
        .content-card ul { margin-left: 2rem; line-height: 2; color: #ddd; }

        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 10px; margin: 2rem 0; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

        .code-box { background-color: #272822; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0; font-family: 'Courier New', monospace; overflow-x: auto; }
        .code-box pre { color: #f8f8f2; margin: 0; }

        .editor-container { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 2rem 0; }
        .editor-panel { background-color: #0f3460; border-radius: 10px; overflow: hidden; }
        .panel-header { background-color: #16213e; padding: 0.8rem; font-weight: bold; color: #f093fb; }
        .CodeMirror { height: 400px; font-size: 14px; }
        .output-frame { width: 100%; height: 400px; border: none; background-color: white; }

        .info-box { background-color: rgba(240, 147, 251, 0.1); border-left: 4px solid #f093fb; padding: 1.5rem; margin: 1.5rem 0; border-radius: 5px; }
        .tip-box { background-color: rgba(76, 175, 80, 0.1); border-left: 4px solid #4caf50; padding: 1.5rem; margin: 1.5rem 0; border-radius: 5px; }

        .color-demo { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin: 2rem 0; }
        .color-box { height: 100px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }

        .nav-buttons { display: flex; justify-content: space-between; margin-top: 3rem; gap: 1rem; }
        .nav-btn { padding: 1rem 2.5rem; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: all 0.3s; }
        .btn-prev { background-color: #0f3460; color: white; }
        .btn-prev:hover:not(.btn-disabled) { background-color: #16213e; }
        .btn-next { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
        .btn-next:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(240, 147, 251, 0.3); }
        .btn-quiz { background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; }
        .btn-quiz:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3); }
        .btn-disabled { opacity: 0.5; cursor: not-allowed !important; }

        .completed-badge { background-color: #4caf50; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; }
        .locked-message { background-color: rgba(233, 69, 96, 0.2); border: 2px solid #e94560; padding: 3rem; border-radius: 15px; text-align: center; }

        .animated-box { width: 150px; height: 150px; background: linear-gradient(135deg, #f093fb, #f5576c); border-radius: 20px; margin: 2rem auto; animation: pulse 2s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.1); } }

        @media (max-width: 768px) {
            .editor-container { grid-template-columns: 1fr; }
            .nav-buttons { flex-direction: column; }
            .content-card { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="logo">🎨 CSS Styling - Interactive Lesson</div>
        <button class="exit-btn" onclick="window.location.href='learning_hub.php'">← Exit to Hub</button>
    </div>

    <?php if (!$html_passed): ?>
        <div class="lesson-container">
            <div class="locked-message">
                <h2 style="color: #e94560; margin-bottom: 1rem; font-size: 2rem;">🔒 Lesson Locked</h2>
                <p style="font-size: 1.2rem; margin-bottom: 2rem;">Complete the HTML lesson first!</p>
                <button class="exit-btn" style="padding: 1rem 2rem;" onclick="window.location.href='lesson_html_NEW.php'">Go to HTML Lesson</button>
            </div>
        </div>
    <?php else: ?>

    <div class="progress-container">
        <div class="progress-header">
            <div>
                <div class="lesson-title">CSS Fundamentals</div>
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
                <h2>🌈 Welcome to CSS!</h2>
                <p>CSS makes websites beautiful! Let's learn how to style your web pages.</p>
                
                <div class="info-box">
                    <strong>💡 What is CSS?</strong><br>
                    CSS (Cascading Style Sheets) is the language that styles HTML. It controls colors, fonts, layouts, animations, and everything visual!
                </div>

                <h3>Why Learn CSS?</h3>
                <ul>
                    <li>Make websites beautiful and professional</li>
                    <li>Create responsive designs for all devices</li>
                    <li>Add animations and interactivity</li>
                    <li>Stand out as a developer!</li>
                </ul>

                <div class="tip-box">
                    <strong>✨ Fun Fact:</strong> CSS was first released in 1996. CSS3 added amazing features like animations!
                </div>

                <div style="text-align: center; margin: 2rem 0;">
                    <div class="animated-box"></div>
                    <p style="color: #aaa;">This pulsing animation is pure CSS!</p>
                </div>
            </div>
        </div>

        <!-- STEP 2 -->
        <div class="step-content" data-step="2">
            <div class="content-card">
                <h2>🎥 Watch: CSS in 20 Minutes</h2>
                <p>Quick video introduction to CSS fundamentals!</p>
                
                <div class="video-wrapper">
                    <iframe src="https://www.youtube.com/embed/1PnVor36_40" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>

                <div class="tip-box">
                    <strong>✅ Pro Tip:</strong> CSS is all about practice. Don't worry if it seems complex at first!
                </div>
            </div>
        </div>

        <!-- STEP 3 -->
        <div class="step-content" data-step="3">
            <div class="content-card">
                <h2>📝 CSS Syntax</h2>
                <p>CSS has a simple structure:</p>

                <div class="code-box">
                    <pre>selector {
  property: value;
  another-property: value;
}</pre>
                </div>

                <h3>Example:</h3>
                <div class="code-box">
                    <pre>h1 {
  color: blue;
  font-size: 36px;
  text-align: center;
}</pre>
                </div>

                <div style="margin: 2rem 0;">
                    <p><strong style="color: #f093fb;">selector</strong> - What to style (h1, p, div, etc.)</p>
                    <p><strong style="color: #f093fb;">property</strong> - What to change (color, size, etc.)</p>
                    <p><strong style="color: #f093fb;">value</strong> - How to change it (blue, 36px, etc.)</p>
                </div>

                <div class="info-box">
                    <strong>📌 Remember:</strong> Always end properties with semicolons ;
                </div>
            </div>
        </div>

        <!-- STEP 4 -->
        <div class="step-content" data-step="4">
            <div class="content-card">
                <h2>🎨 Colors in CSS</h2>
                <p>CSS has multiple ways to define colors!</p>

                <div class="color-demo">
                    <div class="color-box" style="background-color: #f093fb;">Hex: #f093fb</div>
                    <div class="color-box" style="background-color: rgb(240, 147, 251);">RGB: (240,147,251)</div>
                    <div class="color-box" style="background-color: red;">Name: red</div>
                    <div class="color-box" style="background: linear-gradient(45deg, #f093fb, #f5576c);">Gradient</div>
                </div>

                <div class="code-box">
                    <pre>/* Named colors */
color: red;

/* Hexadecimal */
color: #f093fb;

/* RGB */
color: rgb(240, 147, 251);

/* With transparency */
color: rgba(240, 147, 251, 0.7);</pre>
                </div>
            </div>
        </div>

        <!-- STEP 5 -->
        <div class="step-content" data-step="5">
            <div class="content-card">
                <h2>💻 Try It Yourself!</h2>
                <p>Experiment with CSS styling:</p>

                <div class="editor-container">
                    <div class="editor-panel">
                        <div class="panel-header">✏️ HTML + CSS</div>
                        <textarea id="cssCode"><!DOCTYPE html>
<html>
<head>
  <style>
    body {
      font-family: Arial;
      padding: 20px;
      background: linear-gradient(135deg, #667eea, #764ba2);
    }
    
    .card {
      background: white;
      padding: 30px;
      border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      max-width: 400px;
      margin: 0 auto;
    }
    
    h1 {
      color: #f093fb;
      text-align: center;
    }
    
    p {
      color: #666;
      line-height: 1.6;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>Styled Card</h1>
    <p>Try changing colors, sizes, or adding borders!</p>
  </div>
</body>
</html></textarea>
                    </div>
                    
                    <div class="editor-panel">
                        <div class="panel-header">👁️ Live Preview</div>
                        <iframe id="output" class="output-frame"></iframe>
                    </div>
                </div>

                <div class="tip-box">
                    <strong>⚡ Challenge:</strong> Change the gradient colors and card styling!
                </div>
            </div>
        </div>

        <!-- STEP 6 -->
        <div class="step-content" data-step="6">
            <div class="content-card" style="text-align: center;">
                <h2>🎯 Ready for the Quiz!</h2>
                <p style="font-size: 1.2rem; margin: 2rem 0;">Excellent work learning CSS!</p>
                
                <div style="font-size: 5rem; margin: 2rem 0;">🎨</div>
                
                <p style="font-size: 1.1rem; color: #ddd; margin-bottom: 2rem;">
                    You've mastered CSS basics!<br>
                    Time to test your styling skills.
                </p>

                <div class="info-box" style="text-align: left;">
                    <strong>📝 Quiz Topics:</strong>
                    <ul style="margin-top: 1rem;">
                        <li>CSS syntax & properties</li>
                        <li>Colors & styling</li>
                        <li>Selectors & layout</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div style="max-width: 1200px; margin: 0 auto; padding: 0 2rem 2rem;">
        <div class="nav-buttons">
            <button class="nav-btn btn-prev btn-disabled" id="prevBtn" onclick="changeStep(-1)" disabled>← Previous</button>
            <button class="nav-btn btn-next" id="nextBtn" onclick="changeStep(1)">Next Step →</button>
        </div>
    </div>

    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/htmlmixed/htmlmixed.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/css/css.min.js"></script>

    <script>
        let currentStep = 1;
        const totalSteps = 6;
        let editor = null;

        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($html_passed): ?>
            updateStepDots();
            updateProgress();
            <?php endif; ?>
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
            if (step > currentStep) return;
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
                nextBtn.onclick = function() { window.location.href = 'quiz_css_NEW.php'; };
            } else {
                nextBtn.textContent = 'Next Step →';
                nextBtn.className = 'nav-btn btn-next';
                nextBtn.onclick = function() { changeStep(1); };
            }
        }

        function initCodeEditor() {
            editor = CodeMirror.fromTextArea(document.getElementById('cssCode'), {
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
            document.getElementById('output').srcdoc = editor.getValue();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowRight' && currentStep < totalSteps) changeStep(1);
            else if (e.key === 'ArrowLeft' && currentStep > 1) changeStep(-1);
        });
    </script>
</body>
</html>