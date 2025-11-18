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
$css_passed = $css_progress && $css_progress['quiz_passed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript - Step by Step | Code Lab @ HELP</title>
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
        .lesson-title { font-size: 1.3rem; font-weight: bold; color: #f7971e; }
        .progress-bar-outer { width: 100%; height: 8px; background-color: #0f3460; border-radius: 10px; overflow: hidden; }
        .progress-bar-inner { height: 100%; background: linear-gradient(90deg, #f7971e 0%, #ffd200 100%); transition: width 0.5s ease; border-radius: 10px; }
        .step-dots { display: flex; gap: 0.5rem; margin-top: 1rem; }
        .step-dot { width: 12px; height: 12px; border-radius: 50%; background-color: #0f3460; transition: all 0.3s; cursor: pointer; }
        .step-dot.active { background-color: #f7971e; transform: scale(1.3); }
        .step-dot.completed { background-color: #4caf50; }

        .lesson-container { max-width: 1200px; margin: 0 auto; padding: 2rem; min-height: calc(100vh - 250px); display: flex; align-items: center; justify-content: center; }
        
        .step-content { display: none; width: 100%; animation: fadeIn 0.5s; }
        .step-content.active { display: block; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .content-card { background-color: #16213e; padding: 3rem; border-radius: 15px; box-shadow: 0 5px 20px rgba(0,0,0,0.3); }
        .content-card h2 { color: #f7971e; font-size: 2rem; margin-bottom: 1.5rem; }
        .content-card h3 { color: #ffd200; margin-top: 2rem; margin-bottom: 1rem; }
        .content-card p { line-height: 1.8; color: #ddd; margin-bottom: 1rem; font-size: 1.1rem; }
        .content-card ul { margin-left: 2rem; line-height: 2; color: #ddd; }

        .video-wrapper { position: relative; padding-bottom: 56.25%; height: 0; overflow: hidden; border-radius: 10px; margin: 2rem 0; }
        .video-wrapper iframe { position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none; }

        .code-box { background-color: #272822; padding: 1.5rem; border-radius: 8px; margin: 1.5rem 0; font-family: 'Courier New', monospace; overflow-x: auto; }
        .code-box pre { color: #f8f8f2; margin: 0; }

        .editor-container { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin: 2rem 0; }
        .editor-panel { background-color: #0f3460; border-radius: 10px; overflow: hidden; }
        .panel-header { background-color: #16213e; padding: 0.8rem; font-weight: bold; color: #f7971e; }
        .CodeMirror { height: 400px; font-size: 14px; }
        .output-frame { width: 100%; height: 400px; border: none; background-color: white; }

        .info-box { background-color: rgba(247, 151, 30, 0.1); border-left: 4px solid #f7971e; padding: 1.5rem; margin: 1.5rem 0; border-radius: 5px; }
        .tip-box { background-color: rgba(76, 175, 80, 0.1); border-left: 4px solid #4caf50; padding: 1.5rem; margin: 1.5rem 0; border-radius: 5px; }

        .interactive-demo { background-color: #0f3460; padding: 2rem; border-radius: 10px; margin: 2rem 0; text-align: center; }
        .demo-button { background: linear-gradient(135deg, #f7971e, #ffd200); border: none; padding: 1rem 2rem; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; color: white; transition: all 0.3s; }
        .demo-button:hover { transform: scale(1.05); box-shadow: 0 5px 15px rgba(247, 151, 30, 0.4); }

        .nav-buttons { display: flex; justify-content: space-between; margin-top: 3rem; gap: 1rem; }
        .nav-btn { padding: 1rem 2.5rem; border: none; border-radius: 8px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: all 0.3s; }
        .btn-prev { background-color: #0f3460; color: white; }
        .btn-prev:hover:not(.btn-disabled) { background-color: #16213e; }
        .btn-next { background: linear-gradient(135deg, #f7971e 0%, #ffd200 100%); color: white; }
        .btn-next:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(247, 151, 30, 0.3); }
        .btn-quiz { background: linear-gradient(135deg, #4caf50 0%, #45a049 100%); color: white; }
        .btn-quiz:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(76, 175, 80, 0.3); }
        .btn-disabled { opacity: 0.5; cursor: not-allowed !important; }

        .completed-badge { background-color: #4caf50; color: white; padding: 0.5rem 1rem; border-radius: 20px; font-size: 0.9rem; }
        .locked-message { background-color: rgba(233, 69, 96, 0.2); border: 2px solid #e94560; padding: 3rem; border-radius: 15px; text-align: center; }

        @media (max-width: 768px) {
            .editor-container { grid-template-columns: 1fr; }
            .nav-buttons { flex-direction: column; }
            .content-card { padding: 2rem 1.5rem; }
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div class="logo">⚡ JavaScript - Interactive Lesson</div>
        <button class="exit-btn" onclick="window.location.href='learning_hub.php'">← Exit to Hub</button>
    </div>

    <?php if (!$css_passed): ?>
        <div class="lesson-container">
            <div class="locked-message">
                <h2 style="color: #e94560; margin-bottom: 1rem; font-size: 2rem;">🔒 Lesson Locked</h2>
                <p style="font-size: 1.2rem; margin-bottom: 2rem;">Complete the CSS lesson first!</p>
                <button class="exit-btn" style="padding: 1rem 2rem;" onclick="window.location.href='lesson_css_NEW.php'">Go to CSS Lesson</button>
            </div>
        </div>
    <?php else: ?>

    <div class="progress-container">
        <div class="progress-header">
            <div>
                <div class="lesson-title">JavaScript Fundamentals</div>
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
                <h2>⚡ Welcome to JavaScript!</h2>
                <p>JavaScript brings websites to life with interactivity and logic!</p>
                
                <div class="info-box">
                    <strong>💡 What is JavaScript?</strong><br>
                    JavaScript is a programming language that makes websites interactive. It runs in the browser and can respond to user actions!
                </div>

                <h3>Why Learn JavaScript?</h3>
                <ul>
                    <li>Create interactive websites</li>
                    <li>Build web applications</li>
                    <li>Power modern frameworks (React, Vue)</li>
                    <li>Full-stack development capability</li>
                </ul>

                <div class="tip-box">
                    <strong>✨ Fun Fact:</strong> JavaScript was created in just 10 days in 1995!
                </div>

                <div class="interactive-demo">
                    <p style="margin-bottom: 1rem;">This is JavaScript in action!</p>
                    <button class="demo-button" onclick="alert('Hello from JavaScript! 🎉')">Click Me!</button>
                </div>
            </div>
        </div>

        <!-- STEP 2 -->
        <div class="step-content" data-step="2">
            <div class="content-card">
                <h2>🎥 Watch: JavaScript in 12 Minutes</h2>
                <p>Quick intro to JavaScript programming!</p>
                
                <div class="video-wrapper">
                    <iframe src="https://www.youtube.com/embed/DHjqpvDnNGE" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                </div>

                <div class="tip-box">
                    <strong>✅ Pro Tip:</strong> JavaScript is easier than you think. Just practice!
                </div>
            </div>
        </div>

        <!-- STEP 3 -->
        <div class="step-content" data-step="3">
            <div class="content-card">
                <h2>📝 JavaScript Basics</h2>

                <h3>Variables</h3>
                <div class="code-box">
                    <pre>let name = "Edward";
const age = 21;
var city = "Kuala Lumpur"; // old way</pre>
                </div>

                <h3>Functions</h3>
                <div class="code-box">
                    <pre>function greet(name) {
  return "Hello, " + name + "!";
}

// Arrow function
const greet = (name) => "Hello, " + name + "!";</pre>
                </div>

                <h3>Console Logging</h3>
                <div class="code-box">
                    <pre>console.log("Hello World!");
console.log(5 + 10); // 15</pre>
                </div>

                <div class="info-box">
                    <strong>📌 Remember:</strong> Use <code>let</code> for variables that change, <code>const</code> for constants!
                </div>
            </div>
        </div>

        <!-- STEP 4 -->
        <div class="step-content" data-step="4">
            <div class="content-card">
                <h2>🎯 DOM Manipulation</h2>
                <p>JavaScript can change HTML content dynamically!</p>

                <h3>Common DOM Methods</h3>
                <div class="code-box">
                    <pre>// Get element
document.getElementById('myId')
document.querySelector('.myClass')

// Change content
element.innerHTML = "New text"
element.style.color = "red"

// Add event listener
button.addEventListener('click', function() {
  alert('Clicked!');
});</pre>
                </div>

                <div class="tip-box">
                    <strong>⚡ Cool Tip:</strong> You can change anything on a webpage with JavaScript!
                </div>
            </div>
        </div>

        <!-- STEP 5 -->
        <div class="step-content" data-step="5">
            <div class="content-card">
                <h2>💻 Try It Yourself!</h2>
                <p>Write JavaScript code and see it run!</p>

                <div class="editor-container">
                    <div class="editor-panel">
                        <div class="panel-header">✏️ HTML + JavaScript</div>
                        <textarea id="jsCode"><!DOCTYPE html>
<html>
<head>
  <style>
    body { 
      font-family: Arial; 
      padding: 20px;
      text-align: center;
    }
    button {
      background: linear-gradient(135deg, #f7971e, #ffd200);
      color: white;
      border: none;
      padding: 15px 30px;
      font-size: 18px;
      border-radius: 8px;
      cursor: pointer;
    }
    #output {
      margin-top: 20px;
      font-size: 24px;
      color: #f7971e;
    }
  </style>
</head>
<body>
  <h1>JavaScript Counter</h1>
  <div id="output">0</div>
  <button onclick="increment()">Click to Increment!</button>
  
  <script>
    let count = 0;
    
    function increment() {
      count++;
      document.getElementById('output').textContent = count;
    }
  </script>
</body>
</html></textarea>
                    </div>
                    
                    <div class="editor-panel">
                        <div class="panel-header">👁️ Live Preview</div>
                        <iframe id="output" class="output-frame"></iframe>
                    </div>
                </div>

                <div class="tip-box">
                    <strong>⚡ Challenge:</strong> Try adding a "Reset" button!
                </div>
            </div>
        </div>

        <!-- STEP 6 -->
        <div class="step-content" data-step="6">
            <div class="content-card" style="text-align: center;">
                <h2>🎯 You're a JavaScript Pro!</h2>
                <p style="font-size: 1.2rem; margin: 2rem 0;">Amazing! You've learned JavaScript!</p>
                
                <div style="font-size: 5rem; margin: 2rem 0;">⚡</div>
                
                <p style="font-size: 1.1rem; color: #ddd; margin-bottom: 2rem;">
                    You now know HTML, CSS, AND JavaScript!<br>
                    You're a full web developer! 🚀
                </p>

                <div class="info-box" style="text-align: left;">
                    <strong>📝 Quiz Topics:</strong>
                    <ul style="margin-top: 1rem;">
                        <li>Variables & functions</li>
                        <li>DOM manipulation</li>
                        <li>Event handling</li>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>

    <script>
        let currentStep = 1;
        const totalSteps = 6;
        let editor = null;

        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($css_passed): ?>
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
                nextBtn.onclick = function() { window.location.href = 'quiz_javascript_NEW.php'; };
            } else {
                nextBtn.textContent = 'Next Step →';
                nextBtn.className = 'nav-btn btn-next';
                nextBtn.onclick = function() { changeStep(1); };
            }
        }

        function initCodeEditor() {
            editor = CodeMirror.fromTextArea(document.getElementById('jsCode'), {
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