<?php
require_once __DIR__ . '/config.php';
session_start();

if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}
session_write_close();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm_password'] ?? '';

// Sanitize username and rigidly standardise to prevent case-sensitive impersonation
$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
$username = strtolower($username);

// Carries the (non-sensitive) username/email back to signup.php on a
// validation-error redirect so the user doesn't have to retype them —
// passwords are never echoed back.
$backfill = '&username=' . urlencode($username) . '&email=' . urlencode($email);

// Validate username
if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
    header('Location: signup.php?error=invalid' . $backfill);
    exit;
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: signup.php?error=invalidemail' . $backfill);
    exit;
}

// Password complexity enforcement
if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password)) {
    header('Location: signup.php?error=weak_password' . $backfill);
    exit;
}

// Passwords must match
if ($password !== $confirm) {
    header('Location: signup.php?error=mismatch' . $backfill);
    exit;
}

$userDir = get_user_dir($username);

// Check if user already exists
if (is_dir($userDir) && file_exists($userDir . '/.user')) {
    header('Location: signup.php?error=exists' . $backfill);
    exit;
}

// Re-open the session (it was closed above after the "already logged in"
// check) so pending_reg actually persists to the session store.
session_start();

// ── Generate OTP & store pending registration in session ──────────────────
$otp = generate_otp();

$_SESSION['pending_reg'] = [
    'username' => $username,
    'email'    => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT),
    'role'     => in_array($username, ADMIN_USERS, true) ? 'admin' : (is_staff_user($username) ? 'staff' : 'collaborator'),
    'created'  => date('Y-m-d H:i:s'),
    'otp'      => $otp,
    'otp_at'   => time(),
    'resends'  => 0,
    'attempts' => 0,
];

// ── Send OTP email ────────────────────────────────────────────────────────
if (!send_otp_email($email, $otp, 'verify')) {
    // Mail failed — clean up session and report error
    unset($_SESSION['pending_reg']);
    header('Location: signup.php?error=mailfail');
    exit;
}

header('Location: verify_email.php');
exit;
