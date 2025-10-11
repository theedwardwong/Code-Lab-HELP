<?php
session_start();
include 'db_connect.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$admin_id = $_SESSION['user_id'];

// Define all available settings
$available_settings = [
    'platform' => ['site_name', 'site_description'],
    'email' => ['smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_from_email', 'smtp_from_name'],
    'security' => ['password_min_length', 'session_timeout', 'max_login_attempts'],
    'features' => ['registration_enabled', 'notifications_enabled', 'maintenance_mode'],
    'defaults' => ['default_exercise_points', 'default_max_attempts']
];

// Flatten settings array
$all_settings = [];
foreach ($available_settings as $category => $settings) {
    foreach ($settings as $setting) {
        $all_settings[$setting] = $category;
    }
}

$updated_count = 0;
$errors = [];

// Process each setting
foreach ($_POST as $key => $value) {
    if (!isset($all_settings[$key])) {
        continue; // Skip unknown settings
    }
    
    $category = $all_settings[$key];
    $sanitized_value = trim($value);
    
    // Validate specific settings
    if ($key === 'password_min_length') {
        $sanitized_value = max(4, min(32, intval($sanitized_value)));
    } elseif ($key === 'session_timeout') {
        $sanitized_value = max(300, min(86400, intval($sanitized_value)));
    } elseif ($key === 'smtp_port') {
        $sanitized_value = max(1, min(65535, intval($sanitized_value)));
    }
    
    // Update or insert setting
    $stmt = $conn->prepare("
        INSERT INTO system_settings (setting_key, setting_value, setting_category, updated_by)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            updated_by = VALUES(updated_by),
            updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->bind_param("sssi", $key, $sanitized_value, $category, $admin_id);
    
    if ($stmt->execute()) {
        $updated_count++;
    } else {
        $errors[] = "Failed to update $key: " . $conn->error;
    }
}

if (empty($errors)) {
    echo json_encode([
        'success' => true,
        'message' => "Successfully updated $updated_count settings",
        'updated_count' => $updated_count
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Some settings failed to update',
        'errors' => $errors,
        'updated_count' => $updated_count
    ]);
}
?>