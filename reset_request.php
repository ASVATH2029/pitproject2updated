<?php
/*
 * reset_request.php — Handles Step 1 of forgot-password flow
 * Validates username + email, generates OTP, sends email, advances to step 2
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

$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['username'] ?? '');
$email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);

if (!preg_match('/^[a-zA-Z0-9_]{1,32}$/', $username) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: forgot_password.php?status=error');
    exit;
}

// ── Verify username + email match ─────────────────────────────────────────
$userData = get_user_data($username);

if (!$userData || !isset($userData['email'])) {
    // User doesn't exist — redirect with same message to prevent enumeration
    header('Location: forgot_password.php?status=error');
    exit;
}

if (!hash_equals(strtolower($userData['email']), strtolower($email))) {
    header('Location: forgot_password.php?status=error');
    exit;
}

// ── Generate OTP & store in session ──────────────────────────────────────
$otp = generate_otp();

$_SESSION['pw_reset_step']     = 'otp';
$_SESSION['pw_reset_username'] = $username;
$_SESSION['pw_reset_email']    = $email;
$_SESSION['pw_reset_otp']      = $otp;
$_SESSION['pw_reset_otp_at']   = time();
$_SESSION['pw_reset_attempts'] = 0;
$_SESSION['pw_reset_resends']  = 0;

// ── Send OTP email ────────────────────────────────────────────────────────
if (!send_otp_email($email, $otp, 'reset')) {
    // Clear reset session on mail failure
    foreach (['pw_reset_step','pw_reset_username','pw_reset_email','pw_reset_otp','pw_reset_otp_at','pw_reset_attempts','pw_reset_resends'] as $k) {
        unset($_SESSION[$k]);
    }
    header('Location: forgot_password.php?status=mailfail');
    exit;
}

// ── Log request for audit ─────────────────────────────────────────────────
$log_dir = PROJECT_DIR . '/.logs';
if (!is_dir($log_dir)) { mkdir($log_dir, 0700, true); }
$log_entry = sprintf("[%s] IP:%s | PW-Reset OTP sent | User:%s\n",
    date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? 'unknown', $username);
file_put_contents($log_dir . '/reset_requests.log', $log_entry, FILE_APPEND | LOCK_EX);

header('Location: forgot_password.php');
exit;
