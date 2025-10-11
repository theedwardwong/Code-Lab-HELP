<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get SMTP settings from POST
$smtp_host = $_POST['smtp_host'] ?? '';
$smtp_port = $_POST['smtp_port'] ?? 587;
$smtp_username = $_POST['smtp_username'] ?? '';
$smtp_password = $_POST['smtp_password'] ?? '';
$smtp_from_email = $_POST['smtp_from_email'] ?? '';
$smtp_from_name = $_POST['smtp_from_name'] ?? '';

// Validate required fields
if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password) || empty($smtp_from_email)) {
    echo json_encode([
        'success' => false,
        'message' => 'Please fill in all required SMTP fields (host, username, password, from email)'
    ]);
    exit();
}

// For demonstration purposes, we'll simulate an email test
// In production, you would use PHPMailer or similar library

/*
// Example using PHPMailer (you would need to install it via Composer):
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_username;
    $mail->Password = $smtp_password;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_port;
    
    $mail->setFrom($smtp_from_email, $smtp_from_name);
    $mail->addAddress($_SESSION['email']); // Send test to admin
    
    $mail->Subject = 'Test Email from Code Lab @ HELP';
    $mail->Body = 'This is a test email to verify your SMTP configuration is working correctly.';
    
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully! Check your inbox.'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Email could not be sent. Error: ' . $mail->ErrorInfo
    ]);
}
*/

// For now, simulate success if settings look valid
echo json_encode([
    'success' => true,
    'message' => "Email configuration validated!\n\nHost: $smtp_host\nPort: $smtp_port\nFrom: $smtp_from_email\n\nNote: Actual email sending requires PHPMailer library.\nTo enable real emails:\n1. Install PHPMailer via Composer\n2. Uncomment code in test_email.php\n3. Use your email provider's app-specific password"
]);
?>