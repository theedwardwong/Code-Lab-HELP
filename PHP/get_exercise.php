<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Exercise ID required']);
    exit();
}

$exercise_id = intval($_GET['id']);

// Get exercise with lesson details
$stmt = $conn->prepare("
    SELECT e.*, l.title as lesson_title, l.category 
    FROM exercises e 
    JOIN lessons l ON e.lesson_id = l.id 
    WHERE e.id = ?
");
$stmt->bind_param("i", $exercise_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Exercise not found']);
    exit();
}

$exercise = $result->fetch_assoc();

// Don't send solution code to frontend
unset($exercise['solution_code']);
unset($exercise['test_cases']);

echo json_encode([
    'success' => true,
    'exercise' => $exercise
]);
?>