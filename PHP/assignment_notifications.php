<?php
/**
 * Assignment Notification System
 * Sends email notifications when assignments are created or approaching due date
 */

function sendAssignmentNotification($assignment_id, $conn) {
    // Get assignment details
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            e.title as exercise_title,
            u.full_name as instructor_name,
            u.email as instructor_email
        FROM assignments a
        JOIN exercises e ON e.id = a.exercise_id
        JOIN users u ON u.id = a.instructor_id
        WHERE a.id = ?
    ");
    $stmt->bind_param("i", $assignment_id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    
    if (!$assignment) return false;
    
    // Get students to notify
    $students = [];
    if ($assignment['assigned_to'] === 'all') {
        $students_query = $conn->query("SELECT email, full_name FROM users WHERE role = 'student'");
        while ($row = $students_query->fetch_assoc()) {
            $students[] = $row;
        }
    } else {
        $students_query = $conn->prepare("
            SELECT u.email, u.full_name 
            FROM users u
            JOIN assignment_students ast ON ast.student_id = u.id
            WHERE ast.assignment_id = ?
        ");
        $students_query->bind_param("i", $assignment_id);
        $students_query->execute();
        $result = $students_query->get_result();
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
    
    // Create email content
    $subject = "New Assignment: " . $assignment['title'];
    $due_date_text = $assignment['due_date'] ? 
        "Due: " . date('F j, Y g:i A', strtotime($assignment['due_date'])) : 
        "No deadline";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background-color: #2e3f54; color: white; padding: 20px; text-align: center; }
            .content { background-color: #f4f4f4; padding: 20px; }
            .assignment-details { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #358efb; }
            .button { display: inline-block; padding: 12px 24px; background-color: #358efb; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Code Lab @ HELP</h1>
                <p>New Assignment Available</p>
            </div>
            <div class='content'>
                <h2>Hello!</h2>
                <p>Your instructor {$assignment['instructor_name']} has assigned you a new coding exercise.</p>
                
                <div class='assignment-details'>
                    <h3>{$assignment['title']}</h3>
                    <p><strong>Exercise:</strong> {$assignment['exercise_title']}</p>
                    <p><strong>Description:</strong> {$assignment['description']}</p>
                    <p><strong>{$due_date_text}</strong></p>
                    <p><strong>Max Attempts:</strong> " . ($assignment['max_attempts'] > 0 ? $assignment['max_attempts'] : 'Unlimited') . "</p>
                </div>
                
                <p>Log in to your account to start working on this assignment.</p>
                <a href='http://localhost/CodeLab@HELP/PHP/my_assignments.php' class='button'>View Assignment</a>
            </div>
            <div class='footer'>
                <p>Code Lab @ HELP - Learning Management System</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    // Send emails (for demo, we'll just log them)
    // In production, use PHPMailer or similar
    foreach ($students as $student) {
        // Uncomment and configure when ready to send real emails
        /*
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Code Lab <noreply@codelab.help>" . "\r\n";
        
        mail($student['email'], $subject, $message, $headers);
        */
        
        // For now, log to file
        $log_entry = date('Y-m-d H:i:s') . " - Email would be sent to: " . $student['email'] . " - Assignment: " . $assignment['title'] . "\n";
        file_put_contents('logs/email_notifications.log', $log_entry, FILE_APPEND);
    }
    
    return true;
}

/**
 * Check for upcoming due dates and send reminders
 * Run this via cron job daily
 */
function sendDueDateReminders($conn) {
    // Find assignments due in 24 hours
    $tomorrow = date('Y-m-d H:i:s', strtotime('+24 hours'));
    $today = date('Y-m-d H:i:s');
    
    $query = "
        SELECT DISTINCT
            a.id, a.title, a.due_date,
            e.title as exercise_title,
            u.email as student_email,
            u.full_name as student_name
        FROM assignments a
        JOIN exercises e ON e.id = a.exercise_id
        LEFT JOIN assignment_students ast ON ast.assignment_id = a.id
        LEFT JOIN users u ON (
            (a.assigned_to = 'all' AND u.role = 'student') OR
            (a.assigned_to = 'specific' AND u.id = ast.student_id)
        )
        WHERE a.due_date BETWEEN ? AND ?
        AND (ast.status IS NULL OR ast.status != 'submitted')
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $today, $tomorrow);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($reminder = $result->fetch_assoc()) {
        $subject = "Reminder: Assignment Due Soon - " . $reminder['title'];
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <h2>Assignment Reminder</h2>
            <p>Hi {$reminder['student_name']},</p>
            <p>This is a friendly reminder that your assignment is due in 24 hours:</p>
            <div style='background: #f4f4f4; padding: 15px; margin: 15px 0;'>
                <h3>{$reminder['title']}</h3>
                <p><strong>Exercise:</strong> {$reminder['exercise_title']}</p>
                <p><strong>Due:</strong> " . date('F j, Y g:i A', strtotime($reminder['due_date'])) . "</p>
            </div>
            <p>Don't forget to submit your work before the deadline!</p>
            <p><a href='http://localhost/CodeLab@HELP/PHP/my_assignments.php' style='background: #358efb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>View Assignment</a></p>
        </body>
        </html>
        ";
        
        // Log reminder (in production, send actual email)
        $log_entry = date('Y-m-d H:i:s') . " - Reminder sent to: " . $reminder['student_email'] . " - Assignment: " . $reminder['title'] . "\n";
        file_put_contents('logs/email_notifications.log', $log_entry, FILE_APPEND);
    }
    
    return true;
}
?>