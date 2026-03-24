<?php
/*
 * reset_password_action.php — Sets the new password (Step 3)
 */
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

// Must be in new_password step
if (($_SESSION['pw_reset_step'] ?? '') !== 'new_password') {
    header('Location: forgot_password.php');
    exit;
}

$username = $_SESSION['pw_reset_username'] ?? '';
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm']  ?? '';

if (!$username) {
    header('Location: forgot_password.php');
    exit;
}

if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    header('Location: forgot_password.php?error=weak_password');
    exit;
}

if ($password !== $confirm) {
    header('Location: forgot_password.php?error=mismatch');
    exit;
}

// ── Update .user file with new hashed password ────────────────────────────
$userData = get_user_data($username);

if (!$userData) {
    header('Location: forgot_password.php');
    exit;
}

$userData['password'] = password_hash($password, PASSWORD_DEFAULT);
$userDir = get_user_dir($username);

if (file_put_contents($userDir . '/.user', json_encode($userData)) === false) {
    error_log('PITS reset_password_action: Could not write user file for ' . $username);
    header('Location: forgot_password.php?error=fail');
    exit;
}

// ── Clear all reset session vars ──────────────────────────────────────────
foreach (['pw_reset_step','pw_reset_username','pw_reset_email','pw_reset_otp','pw_reset_otp_at','pw_reset_attempts','pw_reset_resends'] as $k) {
    unset($_SESSION[$k]);
}

header('Location: index.php?reset=success');
exit;
