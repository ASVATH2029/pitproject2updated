<?php
/*
 * reset_resend_otp.php — Regenerates and resends the password-reset OTP
 * Returns JSON: {"ok":true} or {"ok":false,"msg":"..."}
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

if (($_SESSION['pw_reset_step'] ?? '') !== 'otp') {
    echo json_encode(['ok' => false, 'msg' => 'No active reset. Please start again.']);
    exit;
}

if (($_SESSION['pw_reset_resends'] ?? 0) >= 3) {
    echo json_encode(['ok' => false, 'msg' => 'Maximum resends reached. Please start over.']);
    exit;
}

// Rate-limit: 30s between resends
if ((time() - ($_SESSION['pw_reset_otp_at'] ?? 0)) < 30) {
    $wait = 30 - (time() - $_SESSION['pw_reset_otp_at']);
    echo json_encode(['ok' => false, 'msg' => "Please wait {$wait}s before resending."]);
    exit;
}

$otp = generate_otp();
$_SESSION['pw_reset_otp']      = $otp;
$_SESSION['pw_reset_otp_at']   = time();
$_SESSION['pw_reset_attempts'] = 0;
$_SESSION['pw_reset_resends']++;

if (!send_otp_email($_SESSION['pw_reset_email'], $otp, 'reset')) {
    echo json_encode(['ok' => false, 'msg' => 'Failed to send email. Please try again.']);
    exit;
}

echo json_encode(['ok' => true]);
exit;
