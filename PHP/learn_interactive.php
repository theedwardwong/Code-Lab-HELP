<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION['full_name'];
$student_id = $_SESSION['user_id'];
$topic = $_GET['topic'] ?? 'html';
$level = intval($_GET['level'] ?? 1);
$step = intval($_GET['step'] ?? 1);

// Complete lessons content for ALL 7 lessons
$lessons = [
    'html' => [
        1 => [ // HTML Basics
            'title' => 'HTML Basics',
            'steps' => [
                1 => [
                    'type' => 'learn',
                    'title' => 'What is HTML?',
                    'content' => 'HTML stands for <strong>HyperText Markup Language</strong>. It\'s the standard language for creating web pages.<br><br>HTML uses <strong>tags</strong> to structure content. Tags are like instructions that tell the browser how to display content.',
                    'example' => '<h1>Hello World!</h1>
<p>This is a paragraph.</p>',
                    'explanation' => 'The <code>&lt;h1&gt;</code> tag creates a heading, and <code>&lt;p&gt;</code> creates a paragraph.'
                ],
                2 => [
                    'type' => 'quiz',
                    'question' => 'What does HTML stand for?',
                    'options' => [
                        'HyperText Markup Language',
                        'High Tech Modern Language',
                        'Home Tool Markup Language',
                        'Hyperlinks and Text Markup Language'
                    ],
                    'correct' => 0
                ],
                3 => [
                    'type' => 'learn',
                    'title' => 'HTML Document Structure',
                    'content' => 'Every HTML document has this basic structure:',
                    'example' => '<!DOCTYPE html>
<html>
<head>
  <title>Page Title</title>
</head>
<body>
  <h1>Welcome!</h1>
  <p>Content goes here</p>
</body>
</html>',
                    'explanation' => '<ul style="text-align:left; line-height: 1.8;">
                        <li><code>&lt;!DOCTYPE html&gt;</code> - Declares this is HTML5</li>
                        <li><code>&lt;html&gt;</code> - Root element containing everything</li>
                        <li><code>&lt;head&gt;</code> - Contains metadata (not visible)</li>
                        <li><code>&lt;body&gt;</code> - Contains all visible content</li>
                    </ul>'
                ],
                4 => [
                    'type' => 'quiz',
                    'question' => 'Where does the visible content of a webpage go?',
                    'options' => [
                        'In the <head> tag',
                        'In the <body> tag',
                        'In the <html> tag',
                        'In the <!DOCTYPE> tag'
                    ],
                    'correct' => 1
                ],
                5 => [
                    'type' => 'code',
                    'question' => 'Create your first heading!',
                    'instruction' => 'Type the HTML code to create a heading that says "My First Page"',
                    'answer' => '<h1>My First Page</h1>'
                ]
            ]
        ],
        2 => [ // HTML Elements
            'title' => 'HTML Elements',
            'steps' => [
                1 => [
                    'type' => 'learn',
                    'title' => 'Headings in HTML',
                    'content' => 'HTML has 6 levels of headings, from <code>&lt;h1&gt;</code> (largest) to <code>&lt;h6&gt;</code> (smallest).',
                    'example' => '<h1>Main Title (h1)</h1>
<h2>Subtitle (h2)</h2>
<h3>Section Title (h3)</h3>
<h4>Subsection (h4)</h4>',
                    'explanation' => 'Use <code>&lt;h1&gt;</code> for main titles, <code>&lt;h2&gt;</code> for subtitles, and so on.'
                ],
                2 => [
                    'type' => 'quiz',
                    'question' => 'Which heading is the largest?',
                    'options' => ['<h6>', '<h3>', '<h1>', '<h4>'],
                    'correct' => 2
                ],
                3 => [
                    'type' => 'learn',
                    'title' => 'Paragraphs and Links',
                    'content' => 'Use <code>&lt;p&gt;</code> for paragraphs and <code>&lt;a&gt;</code> for links.',
                    'example' => '<p>This is a paragraph.</p>
<a href="https://google.com">Click here</a>',
                    'explanation' => 'The <code>href</code> attribute in <code>&lt;a&gt;</code> specifies where the link goes.'
                ],
                4 => [
                    'type' => 'code',
                    'question' => 'Create a paragraph',
                    'instruction' => 'Create a paragraph that says "Welcome to my website"',
                    'answer' => '<p>Welcome to my website</p>'
                ],
                5 => [
                    'type' => 'quiz',
                    'question' => 'Which tag creates a link?',
                    'options' => ['<link>', '<a>', '<href>', '<url>'],
                    'correct' => 1
                ]
            ]
        ],
        3 => [ // HTML Forms
            'title' => 'HTML Forms',
            'steps' => [
                1 => [
                    'type' => 'learn',
                    'title' => 'What are Forms?',
                    'content' => 'Forms allow users to enter data. They use the <code>&lt;form&gt;</code> tag.',
                    'example' => '<form>
  <input type="text" placeholder="Name">
  <button>Submit</button>
</form>',
                    'explanation' => 'Forms collect user input and can send it to a server.'
                ],
                2 => [
                    'type' => 'learn',
                    'title' => 'Input Types',
                    'content' => 'Different input types for different data:',
                    'example' => '<input type="text" placeholder="Name">
<input type="email" placeholder="Email">
<input type="password" placeholder="Password">',
                    'explanation' => 'Each type validates and displays differently.'
                ],
                3 => [
                    'type' => 'quiz',
                    'question' => 'Which input type is for passwords?',
                    'options' => ['type="secret"', 'type="password"', 'type="hidden"', 'type="pass"'],
                    'correct' => 1
                ],
                4 => [
                    'type' => 'code',
                    'question' => 'Create a text input',
                    'instruction' => 'Create an input field for text',
                    'answer' => '<input type="text">'
                ],
                5 => [
                    'type' => 'quiz',
                    'question' => 'What tag creates a form?',
                    'options' => ['<input>', '<form>', '<submit>', '<data>'],
                    'correct' => 1
                ]
            ]
        ]
    ],
    'css' => [
        1 => [ // CSS Basics
            'title' => 'CSS Basics',
            'steps' => [
                1 => [
                    'type' => 'learn',
                    'title' => 'What is CSS?',
                    'content' => 'CSS stands for <strong>Cascading Style Sheets</strong>. It styles HTML elements.',
                    'example' => 'h1 {
  color: blue;
  font-size: 24px;
}',
                    'explanation' => 'This makes all <code>&lt;h1&gt;</code> headings blue and 24 pixels tall.'
                ],
                2 => [
                    'type' => 'quiz',
                    'question' => 'What does CSS stand for?',
                    'options' => [
                        'Cascading Style Sheets',
                        'Computer Style Sheets',
                        'Creative Style System',
                        'Colorful Style Sheets'
                    ],
                    'correct' => 0
                ],
                3 => [
                    'type' => 'learn',
                    'title' => 'Colors in CSS',
                    'content' => 'You can set colors using names, hex codes, or RGB:',
                    'example' => 'p {
  color: red;
  background-color: #f0f0f0;
}',
                    'explanation' => 'Colors make your website beautiful and readable.'
                ],
                4 => [
                    'type' => 'code',
                    'question' => 'Make text red',
                    'instruction' => 'Write CSS to make a paragraph red',
                    'answer' => 'p { color: red; }'
                ],
                5 => [
                    'type' => 'quiz',
                    'question' => 'Which property changes text color?',
                    'options' => ['text-color', 'color', 'font-color', 'text'],
                    'correct' => 1
                ]
            ]
        ],
        2 => [ // CSS Layout
            'title' => 'CSS Layout',
            'steps' => [
                1 => [
                    'type' => 'learn',
                    'title' => 'The Box Model',
                    'content' => 'Every element is a box with content, padding, border, and margin.',
                    'example' => 'div {
  padding: 20px;
  border: 2px solid black;
  margin: 10px;
}',
                    'explanation' => 'Padding is inside, margin is outside the element.'
                ],
                2 => [
                    'type' => 'quiz',
                    'question' => 'What adds space INSIDE an element?',
                    'options' => ['margin', 'padding', 'border', 'spacing'],
                    'correct' => 1
                ],
                3 => [
                    'type' => 'learn',
                    'title' => 'Flexbox Basics',
                    'content' => 'Flexbox makes layouts easy!',
                    'example' => '.container {
  display: flex;
  justify-content: center;
}',
                    'explanation' => 'This centers all items inside the container.'
                ],
                4 => [
                    'type' => 'code',
                    'question' => 'Add padding',
                    'instruction' => 'Add 10px padding to a div',
                    'answer' => 'div { padding: 10px; }'
                ],
                5 => [
                    'type' => 'quiz',
                    'question' => 'What creates a flex container?',
                    'options' => ['flex: true', 'display: flex', 'layout: flex', 'flex-container: yes'],
                    'correct' => 1
                ]
            ]
        ]
    ],
    'javascript' => [
        1 => [ // JavaScript Basics
            'title' => 'JavaScript Basics',
            'steps' => [
                1 => [
                    'type' => 'learn',
                    'title' => 'What is JavaScript?',
                    'content' => 'JavaScript makes websites interactive! It\'s a programming language that runs in the browser.',
                    'example' => 'let message = "Hello!";
console.log(message);',
                    'explanation' => 'Variables store data. <code>console.log()</code> prints to the console.'
                ],
                2 => [
                    'type' => 'quiz',
                    'question' => 'What keyword declares a variable?',
                    'options' => ['var', 'let', 'const', 'All of the above'],
                    'correct' => 3
                ],
                3 => [
                    'type' => 'learn',
                    'title' => 'Functions',
                    'content' => 'Functions are reusable blocks of code:',
                    'example' => 'function greet(name) {
  return "Hello, " + name;
}

greet("Alice"); // "Hello, Alice"',
                    'explanation' => 'Functions can take inputs (parameters) and return outputs.'
                ],
                4 => [
                    'type' => 'code',
                    'question' => 'Create a variable',
                    'instruction' => 'Create a variable named "age" with value 25',
                    'answer' => 'let age = 25;'
                ],
                5 => [
                    'type' => 'quiz',
                    'question' => 'What keyword defines a function?',
                    'options' => ['func', 'function', 'def', 'fn'],
                    'correct' => 1
                ]
            ]
        ],
        2 => [ // DOM Manipulation
            'title' => 'DOM Manipulation',
            'steps' => [
                1 => [
                    'type' => 'learn',
                    'title' => 'What is the DOM?',
                    'content' => 'The <strong>DOM</strong> (Document Object Model) lets you change HTML with JavaScript!',
                    'example' => 'document.getElementById("demo").innerHTML = "Changed!";',
                    'explanation' => 'This changes the content of an element with id="demo".'
                ],
                2 => [
                    'type' => 'quiz',
                    'question' => 'What does DOM stand for?',
                    'options' => [
                        'Data Object Model',
                        'Document Object Model',
                        'Display Object Mode',
                        'Dynamic Object Management'
                    ],
                    'correct' => 1
                ],
                3 => [
                    'type' => 'learn',
                    'title' => 'Click Events',
                    'content' => 'Make things happen when users click:',
                    'example' => 'button.addEventListener("click", function() {
  alert("Button clicked!");
});',
                    'explanation' => 'This shows an alert when the button is clicked.'
                ],
                4 => [
                    'type' => 'code',
                    'question' => 'Select an element',
                    'instruction' => 'Select element with id "myDiv"',
                    'answer' => 'document.getElementById("myDiv")'
                ],
                5 => [
                    'type' => 'quiz',
                    'question' => 'Which method adds an event listener?',
                    'options' => ['addEvent', 'addEventListener', 'on', 'listen'],
                    'correct' => 1
                ]
            ]
        ]
    ]
];

$current_lesson = $lessons[$topic][$level] ?? null;
$current_step = $current_lesson['steps'][$step] ?? null;
$total_steps = count($current_lesson['steps'] ?? []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?php echo $current_lesson['title'] ?? 'Learn'; ?> - Code Lab</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .header {
      background: rgba(15, 20, 25, 0.95);
      padding: 1rem 2rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: white;
    }
    .close-btn {
      color: white;
      text-decoration: none;
      font-size: 1.5rem;
      cursor: pointer;
    }
    .progress-bar-container {
      flex: 1;
      height: 10px;
      background: rgba(255,255,255,0.2);
      border-radius: 10px;
      margin: 0 2rem;
      overflow: hidden;
    }
    .progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #48bb78, #38a169);
      width: <?php echo ($step / $total_steps) * 100; ?>%;
      transition: width 0.5s;
    }
    .lesson-container {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }
    .lesson-card {
      background: white;
      border-radius: 20px;
      padding: 3rem;
      max-width: 800px;
      width: 100%;
      box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    }
    .step-title {
      font-size: 2rem;
      color: #2d3748;
      margin-bottom: 2rem;
      text-align: center;
    }
    .step-content {
      font-size: 1.2rem;
      color: #4a5568;
      line-height: 1.8;
      margin-bottom: 2rem;
    }
    .code-example {
      background: #1a202c;
      color: #68d391;
      padding: 1.5rem;
      border-radius: 12px;
      font-family: 'Courier New', monospace;
      margin: 1.5rem 0;
      white-space: pre-wrap;
      font-size: 1rem;
    }
    .quiz-options {
      display: grid;
      gap: 1rem;
      margin: 2rem 0;
    }
    .quiz-option {
      padding: 1.5rem;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      cursor: pointer;
      transition: all 0.3s;
      font-size: 1.1rem;
      background: white;
    }
    .quiz-option:hover {
      border-color: #667eea;
      background: #f7fafc;
      transform: translateX(10px);
    }
    .quiz-option.correct {
      border-color: #48bb78;
      background: #f0fff4;
    }
    .quiz-option.wrong {
      border-color: #f56565;
      background: #fff5f5;
    }
    .code-input {
      width: 100%;
      padding: 1rem;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-family: 'Courier New', monospace;
      font-size: 1.1rem;
      margin: 1rem 0;
    }
    .btn-container {
      display: flex;
      justify-content: space-between;
      margin-top: 2rem;
    }
    .btn {
      padding: 1rem 2rem;
      border: none;
      border-radius: 12px;
      font-size: 1.1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.3s;
    }
    .btn-next {
      background: linear-gradient(135deg, #667eea, #764ba2);
      color: white;
    }
    .btn-next:hover {
      transform: scale(1.05);
      box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
    }
    .btn-skip {
      background: #e2e8f0;
      color: #4a5568;
    }
    .feedback {
      padding: 1rem;
      border-radius: 12px;
      margin: 1rem 0;
      font-weight: 600;
    }
    .feedback.correct {
      background: #f0fff4;
      color: #22543d;
      border: 2px solid #48bb78;
    }
    .feedback.wrong {
      background: #fff5f5;
      color: #742a2a;
      border: 2px solid #f56565;
    }
  </style>
</head>
<body>
  <div class="header">
    <a href="learning_hub.php" class="close-btn">✕</a>
    <div class="progress-bar-container">
      <div class="progress-bar-fill"></div>
    </div>
    <span><?php echo $step; ?>/<?php echo $total_steps; ?></span>
  </div>

  <div class="lesson-container">
    <div class="lesson-card">
      <?php if ($current_step): ?>
        
        <?php if ($current_step['type'] === 'learn'): ?>
          <div class="step-title"><?php echo $current_step['title']; ?></div>
          <div class="step-content">
            <?php echo $current_step['content']; ?>
            
            <?php if (isset($current_step['example'])): ?>
              <div class="code-example"><?php echo htmlspecialchars($current_step['example']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($current_step['explanation'])): ?>
              <div style="margin-top: 1rem;">
                <?php echo $current_step['explanation']; ?>
              </div>
            <?php endif; ?>
          </div>
          
          <div class="btn-container">
            <div></div>
            <a href="?topic=<?php echo $topic; ?>&level=<?php echo $level; ?>&step=<?php echo $step + 1; ?>" 
               class="btn btn-next">
              Continue →
            </a>
          </div>

        <?php elseif ($current_step['type'] === 'quiz'): ?>
          <div class="step-title"><?php echo $current_step['question']; ?></div>
          
          <div class="quiz-options" id="quizOptions">
            <?php foreach ($current_step['options'] as $index => $option): ?>
              <div class="quiz-option" onclick="checkAnswer(<?php echo $index; ?>, <?php echo $current_step['correct']; ?>)">
                <?php echo htmlspecialchars($option); ?>
              </div>
            <?php endforeach; ?>
          </div>
          
          <div id="feedback"></div>
          
          <div class="btn-container" id="nextBtn" style="display: none;">
            <div></div>
            <a href="?topic=<?php echo $topic; ?>&level=<?php echo $level; ?>&step=<?php echo $step + 1; ?>" 
               class="btn btn-next">
              Continue →
            </a>
          </div>

        <?php elseif ($current_step['type'] === 'code'): ?>
          <div class="step-title"><?php echo $current_step['question']; ?></div>
          <div class="step-content"><?php echo $current_step['instruction']; ?></div>
          
          <textarea class="code-input" id="codeInput" rows="5" placeholder="Type your code here..."></textarea>
          
          <div id="codeFeedback"></div>
          
          <div class="btn-container">
            <button class="btn btn-skip" onclick="showAnswer()">Show Answer</button>
            <button class="btn btn-next" onclick="checkCode('<?php echo htmlspecialchars($current_step['answer'], ENT_QUOTES); ?>')">
              Check Answer
            </button>
          </div>

        <?php endif; ?>

      <?php else: ?>
        <div class="step-title">🎉 Lesson Complete!</div>
        <div class="step-content" style="text-align: center;">
          <p style="font-size: 1.5rem; margin-bottom: 2rem;">
            Great job! You've completed <strong><?php echo $current_lesson['title']; ?></strong>!
          </p>
          <a href="learning_hub.php" class="btn btn-next">
            Back to Learning Hub
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
    function checkAnswer(selected, correct) {
      const options = document.querySelectorAll('.quiz-option');
      const feedback = document.getElementById('feedback');
      const nextBtn = document.getElementById('nextBtn');
      
      options.forEach((opt, idx) => {
        opt.style.pointerEvents = 'none';
        if (idx === correct) {
          opt.classList.add('correct');
        } else if (idx === selected && idx !== correct) {
          opt.classList.add('wrong');
        }
      });
      
      if (selected === correct) {
        feedback.innerHTML = '✅ Correct! Well done!';
        feedback.className = 'feedback correct';
      } else {
        feedback.innerHTML = '❌ Not quite. The correct answer is highlighted in green.';
        feedback.className = 'feedback wrong';
      }
      
      nextBtn.style.display = 'flex';
    }

    function checkCode(correctAnswer) {
      const userCode = document.getElementById('codeInput').value.trim();
      const feedback = document.getElementById('codeFeedback');
      
      if (userCode.toLowerCase().replace(/\s/g, '') === correctAnswer.toLowerCase().replace(/\s/g, '')) {
        feedback.innerHTML = '✅ Perfect! Your code is correct!';
        feedback.className = 'feedback correct';
        setTimeout(() => {
          window.location.href = '?topic=<?php echo $topic; ?>&level=<?php echo $level; ?>&step=<?php echo $step + 1; ?>';
        }, 1500);
      } else {
        feedback.innerHTML = '❌ Not quite right. Try again or click "Show Answer" for help.';
        feedback.className = 'feedback wrong';
      }
    }

    function showAnswer() {
      document.getElementById('codeInput').value = '<?php echo htmlspecialchars($current_step['answer'] ?? '', ENT_QUOTES); ?>';
    }
  </script>
</body>
</html>