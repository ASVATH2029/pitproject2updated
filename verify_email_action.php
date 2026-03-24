<?php
/*
 * verify_email_action.php — Validates email OTP and creates the user account
 */
require_once __DIR__ . '/config.php';
session_start();

if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verify_email.php');
    exit;
}

$reg = $_SESSION['pending_reg'] ?? null;

// No pending registration
if (!$reg) {
    header('Location: signup.php?error=expired');
    exit;
}

// OTP expired (10 minutes)
if ((time() - $reg['otp_at']) > OTP_EXPIRY_SECS) {
    unset($_SESSION['pending_reg']);
    header('Location: signup.php?error=expired');
    exit;
}

// Too many failed attempts (max 5)
if ($reg['attempts'] >= 5) {
    unset($_SESSION['pending_reg']);
    header('Location: signup.php?error=expired');
    exit;
}

$entered_otp = trim($_POST['otp'] ?? '');

// OTP mismatch
if (!hash_equals($reg['otp'], $entered_otp)) {
    $_SESSION['pending_reg']['attempts']++;
    $remaining = 5 - $_SESSION['pending_reg']['attempts'];
    header('Location: verify_email.php?error=wrong&left=' . $remaining);
    exit;
}

// ── OTP correct — create user ─────────────────────────────────────────────
$username = $reg['username'];
$userDir  = get_user_dir($username);

// Guard: user created in the meantime?
if (is_dir($userDir) && file_exists($userDir . '/.user')) {
    unset($_SESSION['pending_reg']);
    header('Location: signup.php?error=exists');
    exit;
}

if (!mkdir($userDir, 0775, true) && !is_dir($userDir)) {
    error_log('PITS verify_email: Could not create directory ' . $userDir);
    header('Location: signup.php?error=fail');
    exit;
}

$userData = json_encode([
    'username'       => $reg['username'],
    'email'          => $reg['email'],
    'password'       => $reg['password'],
    'role'           => $reg['role'],
    'created'        => $reg['created'],
    'email_verified' => true,
]);

if (file_put_contents($userDir . '/.user', $userData) === false) {
    error_log('PITS verify_email: Could not write user file');
    header('Location: signup.php?error=fail');
    exit;
}

unset($_SESSION['pending_reg']);
header('Location: signup.php?success=1');
exit;
