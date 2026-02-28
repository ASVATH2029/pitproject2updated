<?php
require_once __DIR__ . '/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// Sanitize username
$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);

if (!preg_match('/^[a-zA-Z0-9_]{1,32}$/', $username)) {
    header('Location: index.php?error=1');
    exit;
}

// Rate limiting: 5 attempts per 10 minutes per IP
$ip = $_SERVER['REMOTE_ADDR'];
$rate_file = sys_get_temp_dir() . '/pits_rate_' . md5($ip);
$attempts = [];

if (file_exists($rate_file)) {
    $attempts = json_decode(file_get_contents($rate_file), true) ?: [];
    $attempts = array_filter($attempts, function ($t) {
        return $t > time() - 600;
    });
}

if (count($attempts) >= 5) {
    header('Location: index.php?error=2');
    exit;
}

// Log attempt
$attempts[] = time();
file_put_contents($rate_file, json_encode($attempts));

// Authenticate against .user credential file
$userData = get_user_data($username);

if (!$userData || !isset($userData['password'])) {
    header('Location: index.php?error=1');
    exit;
}

if (password_verify($password, $userData['password'])) {
    session_regenerate_id(true);
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $userData['role'] ?? get_role($username);
    $_SESSION['login_time'] = time();
    // Ensure user directory exists
    ensure_user_dir($username);
    header('Location: dashboard.php');
    exit;
}

header('Location: index.php?error=1');
exit;
