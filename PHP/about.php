<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About - Code Lab @ HELP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #0f1419;
            color: #e4e7eb;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background-color: #1a2332;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }
        
        .logo {
            color: white;
            font-size: 1.3rem;
            font-weight: 700;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            gap: 0.5rem;
        }
        
        .nav-links a {
            color: #9ca3af;
            text-decoration: none;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .nav-links a:hover {
            background-color: rgba(59, 130, 246, 0.1);
            color: white;
        }
        
        .nav-links a.active {
            background-color: #3b82f6;
            color: white;
        }
        
        .container {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 3rem 2rem;
            width: 100%;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 3rem;
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header h1 {
            font-size: 3rem;
            color: #f1f5f9;
            margin-bottom: 1rem;
        }
        
        .page-header p {
            font-size: 1.2rem;
            color: #94a3b8;
        }
        
        .content-card {
            background-color: #1e293b;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            border: 1px solid #334155;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .content-card h2 {
            font-size: 1.8rem;
            color: #3b82f6;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .content-card p {
            color: #cbd5e1;
            line-height: 1.8;
            margin-bottom: 1rem;
            font-size: 1.05rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .feature-box {
            background-color: #0f172a;
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid #334155;
            transition: all 0.3s;
        }
        
        .feature-box:hover {
            border-color: #3b82f6;
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(59, 130, 246, 0.2);
        }
        
        .feature-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.3rem;
            color: #f1f5f9;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }
        
        .feature-description {
            color: #94a3b8;
            line-height: 1.6;
        }
        
        .tech-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .tech-badge {
            background-color: rgba(59, 130, 246, 0.1);
            border: 1px solid #3b82f6;
            color: #60a5fa;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .cta-section {
            text-align: center;
            margin-top: 3rem;
            padding: 2rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-block;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .nav-links {
                display: none;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">Code Lab @ HELP</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php" class="active">About</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Login</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>🎓 About Code Lab @ HELP</h1>
            <p>Interactive Programming Education Platform</p>
        </div>
        
        <div class="content-card">
            <h2>📚 What is Code Lab @ HELP?</h2>
            <p>
                Code Lab @ HELP is an innovative web-based learning platform designed to make programming education 
                interactive, engaging, and accessible. Built specifically for HELP University students, this platform 
                transforms traditional coding education into a gamified, step-by-step learning experience.
            </p>
            <p>
                Inspired by popular learning platforms like Duolingo and W3Schools, Code Lab @ HELP combines the best 
                of both worlds: structured lessons with instant feedback, interactive quizzes, and hands-on coding practice.
            </p>
        </div>
        
        <div class="content-card">
            <h2>🎯 Key Features</h2>
            <div class="features-grid">
                <div class="feature-box">
                    <div class="feature-icon">📖</div>
                    <div class="feature-title">Interactive Lessons</div>
                    <div class="feature-description">
                        Step-by-step learning with video tutorials, code examples, and detailed explanations
                    </div>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">❓</div>
                    <div class="feature-title">Quizzes & Tests</div>
                    <div class="feature-description">
                        Multiple-choice questions with instant feedback to reinforce learning
                    </div>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">💻</div>
                    <div class="feature-title">Code Practice</div>
                    <div class="feature-description">
                        Interactive code editor with syntax highlighting and real-time validation
                    </div>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">💡</div>
                    <div class="feature-title">Hints System</div>
                    <div class="feature-description">
                        Progressive hints to guide students without giving away the answer
                    </div>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">📝</div>
                    <div class="feature-title">Assignments</div>
                    <div class="feature-description">
                        Submit assignments and receive grades with detailed feedback from instructors
                    </div>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">📊</div>
                    <div class="feature-title">Progress Tracking</div>
                    <div class="feature-description">
                        Monitor your learning progress with comprehensive statistics and analytics
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-card">
            <h2>🛠️ Technology Stack</h2>
            <p>Built with modern web technologies for optimal performance and user experience:</p>
            <div class="tech-stack">
                <span class="tech-badge">PHP</span>
                <span class="tech-badge">MySQL</span>
                <span class="tech-badge">HTML5</span>
                <span class="tech-badge">CSS3</span>
                <span class="tech-badge">JavaScript</span>
                <span class="tech-badge">XAMPP</span>
            </div>
        </div>
        
        <div class="content-card">
            <h2>👨‍🎓 Project Information</h2>
            <p>
                <strong>Project Title:</strong> CodeLab @ HELP - Interactive Programming Education Platform<br>
                <strong>Student:</strong> Wong Jia Yaw Edward<br>
                <strong>Student ID:</strong> B1901245<br>
                <strong>Institution:</strong> HELP University<br>
                <strong>Program:</strong> Bachelor of Information Technology (Hons)<br>
                <strong>Course:</strong> BIT304 Final Year Project
            </p>
        </div>
        
        <div class="cta-section">
            <h2 style="color: #f1f5f9; margin-bottom: 1rem;">Ready to Start Learning?</h2>
            <a href="login.php" class="btn-primary">🚀 Get Started</a>
        </div>
    </div>
</body>
</html>