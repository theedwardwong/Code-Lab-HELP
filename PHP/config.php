<?php
/**
 * Configuration File for Code Lab @ HELP
 * 
 * IMPORTANT: Add this file to .gitignore to keep credentials secure
 * Never commit database credentials to version control
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'codelab@help');

// Application Settings
define('APP_NAME', 'Code Lab @ HELP');
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds

// Security Settings
define('ENABLE_CSRF_PROTECTION', true);
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// File Upload Settings (for future use)
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'zip']);

// Email Settings (for password delivery - configure with your SMTP)
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@codelab.help');
define('SMTP_PASS', 'your-smtp-password');
define('SMTP_FROM_EMAIL', 'noreply@codelab.help');
define('SMTP_FROM_NAME', 'Code Lab @ HELP');

// Error Reporting
// Set to false in production
define('DISPLAY_ERRORS', true);

if (DISPLAY_ERRORS) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/logs/php-errors.log');
}

// Timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

// Session Configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// CSRF Token Functions
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    if (!isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

// Function to create CSRF input field
function csrfField() {
    $token = generateCSRFToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>