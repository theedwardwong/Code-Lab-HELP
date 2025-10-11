<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is instructor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'instructor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Validate input
if (!isset($_POST['submission_id']) || !isset($_POST['feedback'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

$instructor_id = $_SESSION['user_id'];
$submission_id = intval($_POST['submission_id']);
$feedback = trim($_POST['feedback']);
$grade = isset($_POST['grade']) && $_POST['grade'] !== '' ? intval($_POST['grade']) : null;

if (empty($feedback)) {
    echo json_encode(['success' => false, 'message' => 'Feedback cannot be empty']);
    exit();
}

// Verify submission exists
$check_stmt = $conn->prepare("SELECT id FROM exercise_submissions WHERE id = ?");
$check_stmt->bind_param("i", $submission_id);
$check_stmt->execute();
if ($check_stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Submission not found']);
    exit();
}

// Insert feedback
$insert_stmt = $conn->prepare("
    INSERT INTO instructor_feedback (submission_id, instructor_id, feedback, grade, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$insert_stmt->bind_param("iisi", $submission_id, $instructor_id, $feedback, $grade);

if ($insert_stmt->execute()) {
    $feedback_id = $conn->insert_id;
    
    // If manual grade provided, update submission score
    if ($grade !== null) {
        $update_stmt = $conn->prepare("UPDATE exercise_submissions SET score = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $grade, $submission_id);
        $update_stmt->execute();
    }
    
    echo json_encode([
        'success' => true,
        'feedback_id' => $feedback_id,
        'message' => 'Feedback submitted successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>