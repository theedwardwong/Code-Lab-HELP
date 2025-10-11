<?php
/**
 * Maintenance Mode Check
 * Include this file at the top of public-facing pages to enforce maintenance mode
 * 
 * Usage: 
 * include 'maintenance_check.php';
 */

// Load settings helper
require_once 'settings_helper.php';

// Check if maintenance mode is active
if (isMaintenanceMode()) {
    // Allow admins to bypass maintenance mode
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
        // Show maintenance page
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Maintenance Mode - <?php echo getSiteName(); ?></title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: 'Segoe UI', sans-serif;
                    background: linear-gradient(135deg, #2e3f54 0%, #1a2332 100%);
                    color: white;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    padding: 2rem;
                }

                .maintenance-container {
                    text-align: center;
                    max-width: 600px;
                }

                .icon {
                    font-size: 8rem;
                    margin-bottom: 2rem;
                    animation: pulse 2s infinite;
                }

                @keyframes pulse {
                    0%, 100% { transform: scale(1); }
                    50% { transform: scale(1.1); }
                }

                h1 {
                    font-size: 3rem;
                    margin-bottom: 1rem;
                    color: #ff9800;
                }

                p {
                    font-size: 1.2rem;
                    line-height: 1.6;
                    color: #aaa;
                    margin-bottom: 2rem;
                }

                .info-box {
                    background-color: rgba(255, 255, 255, 0.1);
                    padding: 2rem;
                    border-radius: 12px;
                    backdrop-filter: blur(10px);
                }

                .back-link {
                    display: inline-block;
                    margin-top: 2rem;
                    padding: 1rem 2rem;
                    background-color: #358efb;
                    color: white;
                    text-decoration: none;
                    border-radius: 8px;
                    font-weight: bold;
                    transition: all 0.2s;
                }

                .back-link:hover {
                    background-color: #2a72c9;
                    transform: translateY(-2px);
                }
            </style>
        </head>
        <body>
            <div class="maintenance-container">
                <div class="icon">🔧</div>
                <h1>System Maintenance</h1>
                <div class="info-box">
                    <p>We're currently performing scheduled maintenance to improve your experience.</p>
                    <p>The platform will be back online shortly.</p>
                    <p style="margin-top: 1.5rem; font-size: 1rem; color: #666;">
                        Thank you for your patience!
                    </p>
                </div>
                <a href="login.php" class="back-link">← Back to Login</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}
?>