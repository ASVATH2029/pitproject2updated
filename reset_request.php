<?php
require_once __DIR__ . '/config.php';
session_start();

if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit;
}

$username = $_POST['username'] ?? '';
$email = $_POST['email'] ?? '';

// Validate username format
if (!preg_match('/^[a-zA-Z0-9_]{1,32}$/', $username)) {
    header('Location: forgot_password.php?status=error');
    exit;
}

// Check if the user exists (file-based)
if (!user_exists($username)) {
    header('Location: forgot_password.php?status=error');
    exit;
}

// Log the reset request for the admin to review
$log_entry = date('Y-m-d H:i:s') . ' | User: ' . $username . ' | Email: ' . $email . PHP_EOL;
file_put_contents('/tmp/pits_reset_requests.log', $log_entry, FILE_APPEND | LOCK_EX);

header('Location: forgot_password.php?status=sent');
exit;
