<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['exercise_id']) || !isset($data['code'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$student_id = $_SESSION['user_id'];
$exercise_id = intval($data['exercise_id']);
$submitted_code = trim($data['code']);

if (empty($submitted_code)) {
    echo json_encode(['success' => false, 'message' => 'Code cannot be empty']);
    exit();
}

// Get exercise details including solution
$stmt = $conn->prepare("
    SELECT e.*, l.id as lesson_id 
    FROM exercises e 
    JOIN lessons l ON l.id = e.lesson_id 
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
$max_score = $exercise['points'];
$lesson_id = $exercise['lesson_id'];

// Simple validation logic (in production, use sandboxed execution)
$score = 0;
$status = 'failed';
$passed_tests = 0;
$total_tests = 3; // Default number of tests
$feedback = '';

// Basic code validation
$code_length = strlen($submitted_code);
$has_comments = (strpos($submitted_code, '//') !== false || strpos($submitted_code, '/*') !== false);
$has_function = (strpos($submitted_code, 'function') !== false);

// Scoring logic (simplified for demo)
if ($code_length > 50) {
    $score += intval($max_score * 0.3);
    $passed_tests++;
    $feedback .= "Good: Code has substantial implementation. ";
} else {
    $feedback .= "Issue: Code seems too short for a complete solution. ";
}

if ($has_comments) {
    $score += intval($max_score * 0.2);
    $passed_tests++;
    $feedback .= "Good: Code includes comments. ";
}

if ($has_function || strpos($submitted_code, '=>') !== false) {
    $score += intval($max_score * 0.5);
    $passed_tests++;
    $feedback .= "Good: Code structure detected. ";
} else {
    $feedback .= "Consider: Using functions for better code organization. ";
}

// Determine pass/fail
if ($score >= ($max_score * 0.6)) {
    $status = 'passed';
    $feedback = "Congratulations! Your solution meets the requirements. " . $feedback;
} else {
    $feedback = "Your solution needs improvement. " . $feedback;
}

// Insert submission
$insert_stmt = $conn->prepare("
    INSERT INTO exercise_submissions 
    (exercise_id, student_id, submitted_code, status, score, passed_tests, total_tests, feedback, submitted_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
$insert_stmt->bind_param(
    "iissiiis",
    $exercise_id,
    $student_id,
    $submitted_code,
    $status,
    $score,
    $passed_tests,
    $total_tests,
    $feedback
);

if (!$insert_stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save submission']);
    exit();
}

$submission_id = $conn->insert_id;

// Check if this exercise is part of any assignment for this student
$assignment_check = $conn->prepare("
    SELECT a.id as assignment_id, a.max_attempts, ast.attempts
    FROM assignments a
    LEFT JOIN assignment_students ast ON ast.assignment_id = a.id AND ast.student_id = ?
    WHERE a.exercise_id = ? 
      AND (a.assigned_to = 'all' OR (a.assigned_to = 'specific' AND ast.student_id = ?))
");
$assignment_check->bind_param("iii", $student_id, $exercise_id, $student_id);
$assignment_check->execute();
$assignment_result = $assignment_check->get_result();

if ($assignment_result->num_rows > 0) {
    $assignment = $assignment_result->fetch_assoc();
    $assignment_id = $assignment['assignment_id'];
    $current_attempts = $assignment['attempts'] ?? 0;
    
    // Check if max attempts reached
    if ($assignment['max_attempts'] > 0 && $current_attempts >= $assignment['max_attempts']) {
        echo json_encode([
            'success' => false, 
            'message' => 'Maximum attempts reached for this assignment'
        ]);
        exit();
    }
    
    // Update assignment status
    if ($assignment['assigned_to'] === 'all') {
        // For "all students" assignments, insert or update record
        $check_record = $conn->prepare("SELECT id FROM assignment_students WHERE assignment_id = ? AND student_id = ?");
        $check_record->bind_param("ii", $assignment_id, $student_id);
        $check_record->execute();
        
        if ($check_record->get_result()->num_rows === 0) {
            $insert_record = $conn->prepare("INSERT INTO assignment_students (assignment_id, student_id, status, attempts) VALUES (?, ?, 'in_progress', 1)");
            $insert_record->bind_param("ii", $assignment_id, $student_id);
            $insert_record->execute();
        } else {
            $new_status = ($status === 'passed') ? 'submitted' : 'in_progress';
            $update_assignment = $conn->prepare("
                UPDATE assignment_students 
                SET status = ?, attempts = attempts + 1
                WHERE assignment_id = ? AND student_id = ?
            ");
            $update_assignment->bind_param("sii", $new_status, $assignment_id, $student_id);
            $update_assignment->execute();
        }
    } else {
        // For specific students, update existing record
        $new_status = ($status === 'passed') ? 'submitted' : 'in_progress';
        $update_assignment = $conn->prepare("
            UPDATE assignment_students 
            SET status = ?, attempts = attempts + 1
            WHERE assignment_id = ? AND student_id = ?
        ");
        $update_assignment->bind_param("sii", $new_status, $assignment_id, $student_id);
        $update_assignment->execute();
    }
}

// Update or create student progress
$progress_check = $conn->prepare("
    SELECT id, completion_percentage FROM student_progress 
    WHERE student_id = ? AND lesson_id = ?
");
$progress_check->bind_param("ii", $student_id, $lesson_id);
$progress_check->execute();
$progress_result = $progress_check->get_result();

if ($progress_result->num_rows > 0) {
    // Update existing progress
    $progress = $progress_result->fetch_assoc();
    $new_percentage = min(100, $progress['completion_percentage'] + 10);
    $new_status = ($new_percentage >= 100) ? 'completed' : 'in_progress';
    
    $update_progress = $conn->prepare("
        UPDATE student_progress 
        SET completion_percentage = ?, 
            status = ?,
            time_spent_minutes = time_spent_minutes + 5
        WHERE id = ?
    ");
    $update_progress->bind_param("isi", $new_percentage, $new_status, $progress['id']);
    $update_progress->execute();
} else {
    // Create new progress entry
    $insert_progress = $conn->prepare("
        INSERT INTO student_progress 
        (student_id, lesson_id, status, completion_percentage, started_at, time_spent_minutes) 
        VALUES (?, ?, 'in_progress', 10, NOW(), 5)
    ");
    $insert_progress->bind_param("ii", $student_id, $lesson_id);
    $insert_progress->execute();
}

// Return success response
echo json_encode([
    'success' => true,
    'submission_id' => $submission_id,
    'status' => $status,
    'score' => $score,
    'max_score' => $max_score,
    'passed_tests' => $passed_tests,
    'total_tests' => $total_tests,
    'feedback' => $feedback,
    'message' => 'Submission recorded successfully'
]);
?>