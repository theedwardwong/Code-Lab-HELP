<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['submission_id'])) {
    echo json_encode(['success' => false, 'message' => 'Submission ID required']);
    exit();
}

$submission_id = intval($_GET['submission_id']);
$user_role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

// If student, verify they own the submission
if ($user_role === 'student') {
    $verify_stmt = $conn->prepare("SELECT student_id FROM exercise_submissions WHERE id = ?");
    $verify_stmt->bind_param("i", $submission_id);
    $verify_stmt->execute();
    $result = $verify_stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Submission not found']);
        exit();
    }
    
    $submission = $result->fetch_assoc();
    if ($submission['student_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit();
    }
}

// Get all feedback for this submission
$feedback_query = "
    SELECT 
        if2.*, 
        u.full_name as instructor_name,
        u.email as instructor_email
    FROM instructor_feedback if2
    JOIN users u ON u.id = if2.instructor_id
    WHERE if2.submission_id = ?
    ORDER BY if2.created_at DESC
";

$stmt = $conn->prepare($feedback_query);
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$result = $stmt->get_result();

$feedback_list = [];
while ($row = $result->fetch_assoc()) {
    $feedback_list[] = $row;
}

echo json_encode([
    'success' => true,
    'feedback' => $feedback_list
]);
?>