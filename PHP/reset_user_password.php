<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User ID required']);
    exit();
}

$user_id = intval($data['user_id']);

// Verify user exists
$check_stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE id = ?");
$check_stmt->bind_param("i", $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit();
}

$user = $result->fetch_assoc();

// Generate new random password (8 characters)
$new_password = bin2hex(random_bytes(4));

// Hash password
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update password and set first_login flag
$update_stmt = $conn->prepare("
    UPDATE users 
    SET password = ?, first_login = 1
    WHERE id = ?
");
$update_stmt->bind_param("si", $hashed_password, $user_id);

if ($update_stmt->execute()) {
    // Log password reset (optional - for audit trail)
    // You could create a password_resets table to track this
    
    echo json_encode([
        'success' => true,
        'new_password' => $new_password,
        'user_email' => $user['email'],
        'user_name' => $user['full_name'],
        'message' => 'Password reset successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>