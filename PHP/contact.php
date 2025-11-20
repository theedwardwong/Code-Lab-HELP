<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - Code Lab @ HELP</title>
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
            max-width: 1000px;
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
        
        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .contact-card {
            background-color: #1e293b;
            border-radius: 16px;
            padding: 2.5rem;
            border: 1px solid #334155;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            text-align: center;
            transition: all 0.3s;
            animation: slideUp 0.6s ease;
        }
        
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .contact-card:hover {
            border-color: #3b82f6;
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(59, 130, 246, 0.3);
        }
        
        .contact-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .contact-card h2 {
            font-size: 1.5rem;
            color: #f1f5f9;
            margin-bottom: 1rem;
        }
        
        .contact-card p {
            color: #94a3b8;
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }
        
        .contact-info {
            background-color: #0f172a;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .contact-link {
            color: #60a5fa;
            text-decoration: none;
            font-weight: 600;
            font-size: 1.1rem;
            display: block;
            transition: all 0.3s;
        }
        
        .contact-link:hover {
            color: #3b82f6;
            text-decoration: underline;
        }
        
        .info-card {
            background-color: #1e293b;
            border-radius: 16px;
            padding: 2.5rem;
            border: 1px solid #334155;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            margin-bottom: 2rem;
        }
        
        .info-card h2 {
            font-size: 1.8rem;
            color: #3b82f6;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-list {
            list-style: none;
            padding: 0;
        }
        
        .info-list li {
            padding: 1rem;
            background-color: #0f172a;
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            align-items: start;
            gap: 1rem;
        }
        
        .info-list li strong {
            color: #60a5fa;
            min-width: 120px;
        }
        
        .info-list li span {
            color: #cbd5e1;
        }
        
        .social-links {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .social-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
        }
        
        .social-btn:hover {
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5);
        }
        
        @media (max-width: 768px) {
            .page-header h1 {
                font-size: 2rem;
            }
            
            .nav-links {
                display: none;
            }
            
            .contact-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">Code Lab @ HELP</a>
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php" class="active">Contact</a>
            <a href="login.php">Login</a>
        </div>
    </nav>
    
    <div class="container">
        <div class="page-header">
            <h1>📧 Get in Touch</h1>
            <p>Have questions? We'd love to hear from you!</p>
        </div>
        
        <div class="contact-grid">
            <div class="contact-card">
                <div class="contact-icon">📧</div>
                <h2>Email</h2>
                <p>Send us an email for any inquiries or support</p>
                <div class="contact-info">
                    <a href="mailto:B1901245@helplive.edu.my" class="contact-link">
                        B1901245@helplive.edu.my
                    </a>
                </div>
            </div>
            
            <div class="contact-card">
                <div class="contact-icon">🎓</div>
                <h2>Student</h2>
                <p>Developed by HELP University student</p>
                <div class="contact-info">
                    <div style="color: #cbd5e1; font-weight: 600;">Wong Jia Yaw Edward</div>
                    <div style="color: #94a3b8; margin-top: 0.5rem;">Student ID: B1901245</div>
                </div>
            </div>
        </div>
        
        <div class="info-card">
            <h2>ℹ️ Contact Information</h2>
            <ul class="info-list">
                <li>
                    <strong>📧 Email:</strong>
                    <span>B1901245@helplive.edu.my</span>
                </li>
                <li>
                    <strong>🏫 Institution:</strong>
                    <span>HELP University</span>
                </li>
                <li>
                    <strong>📚 Program:</strong>
                    <span>Bachelor of Information Technology (Hons)</span>
                </li>
                <li>
                    <strong>🆔 Student ID:</strong>
                    <span>B1901245</span>
                </li>
                <li>
                    <strong>💼 Project:</strong>
                    <span>BIT305 Final Year Project:  Incorporating Visual and Animation Teaching Tools in Computer Programming Classes for Effective Teaching and Learning at HELP University.</span>
                </li>
            </ul>
        </div>
        
        <div class="info-card" style="text-align: center;">
            <h2>🚀 Ready to Start Learning?</h2>
            <p style="color: #94a3b8; margin-bottom: 2rem; font-size: 1.1rem;">
                Join Code Lab @ HELP and begin your coding journey today!
            </p>
            <a href="login.php" style="background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; padding: 1rem 2.5rem; border-radius: 12px; text-decoration: none; font-weight: 700; font-size: 1.1rem; display: inline-block; transition: all 0.3s; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);">
                Login Now →
            </a>
        </div>
    </div>
</body>
</html>