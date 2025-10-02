<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: view_courses.php");
    exit();
}

$course_id = intval($_GET['id']);

// Delete course (exercises will be deleted automatically due to CASCADE)
$stmt = $conn->prepare("DELETE FROM lessons WHERE id = ?");
$stmt->bind_param("i", $course_id);

if ($stmt->execute()) {
    header("Location: view_courses.php?deleted=1");
} else {
    header("Location: view_courses.php?error=1");
}
exit();
?>