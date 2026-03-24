<?php
/*
 * reset_request.php — OTP Password reset request handler
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/mailer.php';

session_start();
if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit;
}

$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['username'] ?? '');
$username = strtolower($username);
$email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

if (empty($username) || empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: forgot_password.php?status=error');
    exit;
}

$userDir = get_user_dir($username);
$userFile = $userDir . '/.user';

// Check if user and file exist before trying to read JSON
if (is_dir($userDir) && file_exists($userFile)) {
    $userData = json_decode(file_get_contents($userFile), true);
    
    // Verify email matches the one in our database
    if (isset($userData['email']) && strtolower(trim($userData['email'])) === strtolower($email)) {
        
        // Generate a 5-digit OTP
        // Ensure random_int runs safely. If it somehow fails, it throws an Exception
        try {
            $otp = random_int(10000, 99999);
        } catch (Exception $e) {
            $otp = rand(10000, 99999);
        }

        // Store secure hash of OTP and expiration time using password_hash
        $userData['otp_hash'] = password_hash((string)$otp, PASSWORD_DEFAULT);
        $userData['otp_expires'] = time() + (15 * 60); // 15 minutes
        
        if (file_put_contents($userFile, json_encode($userData))) {
            // Build the dynamic email
            $subject = "Your PITS Password Reset Code";
            $message = "
            <div style='font-family: sans-serif; padding: 20px;'>
                <h2>Password Reset Verification</h2>
                <p>Hello {$username},</p>
                <p>We received a request to reset your PITS Student Archival System password.</p>
                <p>Your 5-digit authorization OTP is:</p>
                <h1 style='color: #4CAF50; letter-spacing: 5px;'>{$otp}</h1>
                <p>This code will expire in exactly 15 minutes.</p>
                <p>If you did not request this, you can safely ignore this email.</p>
            </div>";

            send_pits_email($email, $subject, $message);
            
            // Set session variable so verify page knows who is resetting
            session_start();
            $_SESSION['reset_username'] = $username;
            session_write_close();
            
            header('Location: verify_otp.php');
            exit;
        }
    }
}

// Security: ALWAYS redirect silently if the username/email fails so a hacker can't enumerate valid emails
header('Location: verify_otp.php?status=sent');
exit;
