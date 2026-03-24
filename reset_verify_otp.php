<?php
/*
 * reset_verify_otp.php — Validates the password-reset OTP (Step 2)
 * On success: advances session to 'new_password' step
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

// Must be in OTP step
if (($_SESSION['pw_reset_step'] ?? '') !== 'otp') {
    header('Location: forgot_password.php');
    exit;
}

// OTP expired
if ((time() - ($_SESSION['pw_reset_otp_at'] ?? 0)) > OTP_EXPIRY_SECS) {
    foreach (['pw_reset_step','pw_reset_username','pw_reset_email','pw_reset_otp','pw_reset_otp_at','pw_reset_attempts','pw_reset_resends'] as $k) {
        unset($_SESSION[$k]);
    }
    header('Location: forgot_password.php?status=expired');
    exit;
}

// Too many attempts
$attempts = (int)($_SESSION['pw_reset_attempts'] ?? 0);
if ($attempts >= 5) {
    foreach (['pw_reset_step','pw_reset_username','pw_reset_email','pw_reset_otp','pw_reset_otp_at','pw_reset_attempts','pw_reset_resends'] as $k) {
        unset($_SESSION[$k]);
    }
    header('Location: forgot_password.php?status=expired');
    exit;
}

$entered = trim($_POST['otp'] ?? '');

if (!hash_equals($_SESSION['pw_reset_otp'] ?? '', $entered)) {
    $_SESSION['pw_reset_attempts']++;
    $remaining = 5 - $_SESSION['pw_reset_attempts'];
    header('Location: forgot_password.php?error=wrong&left=' . $remaining);
    exit;
}

// ── OTP correct: advance to new_password step ─────────────────────────────
$_SESSION['pw_reset_step'] = 'new_password';
unset($_SESSION['pw_reset_otp']); // OTP is consumed

header('Location: forgot_password.php');
exit;
