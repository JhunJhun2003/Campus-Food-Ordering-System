<?php
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

echo "<h1>Testing PHPMailer</h1>";

try {
    $mail = new PHPMailer(true);
    
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'kokyaw3482@gmail.com';
    $mail->Password   = 'fdrbwlxauqtioumr';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    // Recipients - CHANGE THIS TO YOUR EMAIL
    $mail->setFrom('kokyaw3482@gmail.com', 'FOODIE Test');
    $mail->addAddress('your-email@gmail.com', 'Test User'); // <-- CHANGE THIS
    
    // Content
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from FOODIE';
    $mail->Body    = '<h1>✅ Test Email Works!</h1><p>Your PHPMailer configuration is correct.</p>';
    
    $mail->send();
    echo "<p style='color: green;'>✅ Email sent successfully! Check your inbox.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Email failed: " . $e->getMessage() . "</p>";
    echo "<p>Error details: " . $mail->ErrorInfo . "</p>";
}
?>