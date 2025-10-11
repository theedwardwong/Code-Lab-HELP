<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['full_name'];

// Load all settings
$settings_query = "SELECT * FROM system_settings ORDER BY setting_category, setting_key";
$settings_result = $conn->query($settings_query);

$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Helper function to get setting value
function getSetting($key, $default = '') {
    global $settings;
    return isset($settings[$key]) ? $settings[$key] : $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - Code Lab @ HELP</title>
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

        .logo { color: white; font-weight: bold; font-size: 1.2rem; }
        .logo a { color: white; text-decoration: none; }
        .nav-links { list-style: none; display: flex; gap: 1.5rem; margin: 0; padding: 0; }
        .nav-links li a { color: white; text-decoration: none; }
        .nav-icons { display: flex; align-items: center; gap: 1rem; }
        
        .logout-btn {
            background-color: #2e3f54;
            color: white;
            border: none;
            padding: 0.4rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background-color: #4caf50;
            color: white;
        }

        .alert-error {
            background-color: #f44336;
            color: white;
        }

        .settings-container {
            display: grid;
            gap: 2rem;
        }

        .settings-section {
            background-color: #1a2332;
            padding: 2rem;
            border-radius: 12px;
            border-left: 4px solid #358efb;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #2e3f54;
        }

        .section-icon {
            font-size: 2rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .section-description {
            color: #aaa;
            font-size: 0.9rem;
        }

        .form-grid {
            display: grid;
            gap: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: bold;
            color: #ddd;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.8rem;
            border-radius: 8px;
            border: 1px solid #2e3f54;
            background-color: #2e3f54;
            color: white;
            font-family: inherit;
        }

        .form-group textarea {
            min-height: 80px;
            resize: vertical;
        }

        .form-group .helper-text {
            font-size: 0.85rem;
            color: #888;
            margin-top: 0.3rem;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #666;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #4caf50;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }

        .btn {
            padding: 0.8rem 1.5rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1rem;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #358efb;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2a72c9;
        }

        .btn-success {
            background-color: #4caf50;
            color: white;
        }

        .btn-success:hover {
            background-color: #45a049;
        }

        .btn-secondary {
            background-color: #666;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #555;
        }

        .warning-box {
            background-color: #ff9800;
            color: white;
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .maintenance-warning {
            background-color: #f44336;
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            font-size: 1.1rem;
            font-weight: bold;
        }

        .password-strength {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .strength-bar {
            flex: 1;
            height: 4px;
            background-color: #2e3f54;
            border-radius: 2px;
        }

        .strength-bar.active {
            background-color: #4caf50;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <a href="adminDashboard.php">Code Lab @ HELP</a>
        </div>
        <ul class="nav-links">
            <li><a href="adminDashboard.php">Dashboard</a></li>
            <li><a href="manage_users.php">Manage Users</a></li>
            <li><a href="view_courses.php">Courses</a></li>
            <li><a href="system_settings.php">Settings</a></li>
        </ul>
        <div class="nav-icons">
            <span><?php echo htmlspecialchars($admin_name); ?></span>
            <button class="logout-btn" onclick="confirmLogout()">Log Out</button>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <h1>⚙️ System Settings</h1>
            <p style="color: #aaa;">Configure platform-wide settings and preferences</p>
        </div>

        <div id="alertMessage" class="alert"></div>

        <?php if (getSetting('maintenance_mode') == '1'): ?>
            <div class="maintenance-warning">
                ⚠️ MAINTENANCE MODE IS ACTIVE - Only admins can access the system
            </div>
        <?php endif; ?>

        <form id="settingsForm" onsubmit="saveSettings(event)">
            <div class="settings-container">
                
                <!-- Platform Settings -->
                <div class="settings-section">
                    <div class="section-header">
                        <div class="section-icon">🌐</div>
                        <div>
                            <div class="section-title">Platform Settings</div>
                            <div class="section-description">Basic platform information and branding</div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Site Name</label>
                                <input type="text" name="site_name" value="<?php echo htmlspecialchars(getSetting('site_name')); ?>" required>
                                <div class="helper-text">Displayed in navigation and browser title</div>
                            </div>
                            <div class="form-group">
                                <label>Site Description</label>
                                <input type="text" name="site_description" value="<?php echo htmlspecialchars(getSetting('site_description')); ?>">
                                <div class="helper-text">Short tagline for your platform</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Email Configuration -->
                <div class="settings-section">
                    <div class="section-header">
                        <div class="section-icon">📧</div>
                        <div>
                            <div class="section-title">Email Configuration</div>
                            <div class="section-description">SMTP settings for sending emails</div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                <label>SMTP Host</label>
                                <input type="text" name="smtp_host" value="<?php echo htmlspecialchars(getSetting('smtp_host')); ?>" placeholder="smtp.gmail.com">
                                <div class="helper-text">Your mail server address</div>
                            </div>
                            <div class="form-group">
                                <label>SMTP Port</label>
                                <input type="number" name="smtp_port" value="<?php echo getSetting('smtp_port'); ?>" placeholder="587">
                                <div class="helper-text">Usually 587 (TLS) or 465 (SSL)</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>SMTP Username</label>
                                <input type="text" name="smtp_username" value="<?php echo htmlspecialchars(getSetting('smtp_username')); ?>" placeholder="your-email@gmail.com">
                            </div>
                            <div class="form-group">
                                <label>SMTP Password</label>
                                <input type="password" name="smtp_password" value="<?php echo htmlspecialchars(getSetting('smtp_password')); ?>" placeholder="Your app password">
                                <div class="helper-text">Use app-specific password for Gmail</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>From Email</label>
                                <input type="email" name="smtp_from_email" value="<?php echo htmlspecialchars(getSetting('smtp_from_email')); ?>" placeholder="noreply@codelab.help">
                            </div>
                            <div class="form-group">
                                <label>From Name</label>
                                <input type="text" name="smtp_from_name" value="<?php echo htmlspecialchars(getSetting('smtp_from_name')); ?>" placeholder="Code Lab @ HELP">
                            </div>
                        </div>
                        <div class="warning-box">
                            ⚠️ <strong>Note:</strong> Email functionality is currently in development. Settings are saved but not yet active.
                        </div>
                    </div>
                </div>

                <!-- Security Settings -->
                <div class="settings-section">
                    <div class="section-header">
                        <div class="section-icon">🔒</div>
                        <div>
                            <div class="section-title">Security Settings</div>
                            <div class="section-description">Password policies and access control</div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Minimum Password Length</label>
                                <input type="number" name="password_min_length" value="<?php echo getSetting('password_min_length'); ?>" min="4" max="32">
                                <div class="helper-text">Minimum characters required for passwords</div>
                            </div>
                            <div class="form-group">
                                <label>Session Timeout (seconds)</label>
                                <input type="number" name="session_timeout" value="<?php echo getSetting('session_timeout'); ?>" min="300" max="86400">
                                <div class="helper-text">1800 = 30 minutes, 3600 = 1 hour</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Max Login Attempts</label>
                                <input type="number" name="max_login_attempts" value="<?php echo getSetting('max_login_attempts'); ?>" min="3" max="10">
                                <div class="helper-text">Number of failed attempts before lockout</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Feature Toggles -->
                <div class="settings-section">
                    <div class="section-header">
                        <div class="section-icon">🎛️</div>
                        <div>
                            <div class="section-title">Feature Toggles</div>
                            <div class="section-description">Enable or disable platform features</div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label style="display: flex; justify-content: space-between; align-items: center;">
                                <span>
                                    <strong>Registration Enabled</strong>
                                    <div class="helper-text" style="margin-top: 0.3rem;">Allow new users to register</div>
                                </span>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="registration_enabled" value="1" <?php echo getSetting('registration_enabled') == '1' ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>
                        
                        <div class="form-group">
                            <label style="display: flex; justify-content: space-between; align-items: center;">
                                <span>
                                    <strong>Notifications Enabled</strong>
                                    <div class="helper-text" style="margin-top: 0.3rem;">Send email notifications to users</div>
                                </span>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="notifications_enabled" value="1" <?php echo getSetting('notifications_enabled') == '1' ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>

                        <div class="form-group">
                            <label style="display: flex; justify-content: space-between; align-items: center;">
                                <span>
                                    <strong style="color: #f44336;">⚠️ Maintenance Mode</strong>
                                    <div class="helper-text" style="margin-top: 0.3rem; color: #f44336;">Only admins can access the system</div>
                                </span>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="maintenance_mode" value="1" <?php echo getSetting('maintenance_mode') == '1' ? 'checked' : ''; ?>>
                                    <span class="slider"></span>
                                </label>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Default Values -->
                <div class="settings-section">
                    <div class="section-header">
                        <div class="section-icon">📊</div>
                        <div>
                            <div class="section-title">Default Values</div>
                            <div class="section-description">Default settings for exercises and assignments</div>
                        </div>
                    </div>
                    <div class="form-grid">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Default Exercise Points</label>
                                <input type="number" name="default_exercise_points" value="<?php echo getSetting('default_exercise_points'); ?>" min="1" max="100">
                                <div class="helper-text">Default points awarded per exercise</div>
                            </div>
                            <div class="form-group">
                                <label>Default Max Attempts</label>
                                <input type="number" name="default_max_attempts" value="<?php echo getSetting('default_max_attempts'); ?>" min="0" max="10">
                                <div class="helper-text">0 = unlimited attempts</div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.location.reload()">Reset Changes</button>
                <button type="button" class="btn btn-primary" onclick="testEmailSettings()">Test Email</button>
                <button type="submit" class="btn btn-success">💾 Save All Settings</button>
            </div>
        </form>
    </div>

    <script>
        function confirmLogout() {
            if (confirm("Are you sure you want to log out?")) {
                window.location.href = 'logout.php';
            }
        }

        function saveSettings(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            
            // Convert checkboxes to proper values
            const checkboxes = ['registration_enabled', 'notifications_enabled', 'maintenance_mode'];
            checkboxes.forEach(name => {
                if (!formData.has(name)) {
                    formData.append(name, '0');
                }
            });
            
            fetch('save_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('✅ Settings saved successfully!', 'success');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    showAlert('❌ Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('❌ Failed to save settings', 'error');
            });
        }

        function testEmailSettings() {
            const formData = new FormData(document.getElementById('settingsForm'));
            
            showAlert('📧 Testing email configuration...', 'success');
            
            fetch('test_email.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Email Test Successful!\n\n' + data.message);
                } else {
                    alert('❌ Email Test Failed!\n\n' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('❌ Failed to test email settings');
            });
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alertMessage');
            alert.textContent = message;
            alert.className = 'alert alert-' + type + ' show';
            
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }
    </script>
</body>
</html>