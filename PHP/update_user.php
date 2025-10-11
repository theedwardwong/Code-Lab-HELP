<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Validate input
if (!isset($_POST['user_id']) || !isset($_POST['full_name']) || !isset($_POST['email']) || !isset($_POST['role'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$user_id = intval($_POST['user_id']);
$full_name = trim($_POST['full_name']);
$email = trim($_POST['email']);
$role = $_POST['role'];

// Validate role
if (!in_array($role, ['student', 'instructor', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Check if email already exists (for different user)
$email_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$email_check->bind_param("si", $email, $user_id);
$email_check->execute();
if ($email_check->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    exit();
}

// Prevent admin from changing their own role to non-admin
if ($user_id == $_SESSION['user_id'] && $role !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cannot change your own admin role']);
    exit();
}

// Update user
$update_stmt = $conn->prepare("
    UPDATE users 
    SET full_name = ?, email = ?, role = ?
    WHERE id = ?
");
$update_stmt->bind_param("sssi", $full_name, $email, $role, $user_id);

if ($update_stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'User updated successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>