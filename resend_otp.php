<?php
/*
 * resend_otp.php — Regenerates and resends the email verification OTP.
 * Returns JSON: {"ok":true} or {"ok":false,"msg":"..."}
 */
require_once __DIR__ . '/config.php';
session_start();

header('Content-Type: application/json');

$reg = $_SESSION['pending_reg'] ?? null;

if (!$reg) {
    echo json_encode(['ok' => false, 'msg' => 'No pending registration. Please sign up again.']);
    exit;
}

if ($reg['resends'] >= 3) {
    echo json_encode(['ok' => false, 'msg' => 'Maximum resends reached. Please sign up again.']);
    exit;
}

// Rate-limit: at least 30 seconds between resends
if ((time() - $reg['otp_at']) < 30) {
    $wait = 30 - (time() - $reg['otp_at']);
    echo json_encode(['ok' => false, 'msg' => "Please wait {$wait}s before resending."]);
    exit;
}

$otp = generate_otp();
$_SESSION['pending_reg']['otp']      = $otp;
$_SESSION['pending_reg']['otp_at']   = time();
$_SESSION['pending_reg']['attempts'] = 0;
$_SESSION['pending_reg']['resends']++;

if (!send_otp_email($reg['email'], $otp, 'verify')) {
    echo json_encode(['ok' => false, 'msg' => 'Failed to send email. Please try again.']);
    exit;
}

echo json_encode(['ok' => true]);
exit;
