<?php
require_once __DIR__ . '/config.php';
session_start();

if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

// Sanitize username
$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);

// Validate username: letters, numbers, underscores, 3-32 chars
if (!preg_match('/^[a-zA-Z0-9_]{3,32}$/', $username)) {
    header('Location: signup.php?error=2');
    exit;
}

// Password minimum length
if (strlen($password) < 6) {
    header('Location: signup.php?error=3');
    exit;
}

// Passwords must match
if ($password !== $confirm) {
    header('Location: signup.php?error=4');
    exit;
}

$userDir = get_user_dir($username);

// Check if user already exists
if (is_dir($userDir) && file_exists($userDir . '/.user')) {
    header('Location: signup.php?error=1');
    exit;
}

// Create personal directory
if (!mkdir($userDir, 0775, true) && !is_dir($userDir)) {
    error_log('PITS register failed: Could not create directory ' . $userDir);
    header('Location: signup.php?error=5');
    exit;
}

// Save user credentials (file-based)
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$userData = json_encode([
    'username' => $username,
    'password' => $hashedPassword,
    'role' => in_array($username, ADMIN_USERS) ? 'admin' : 'collaborator',
    'created' => date('Y-m-d H:i:s')
]);

if (file_put_contents($userDir . '/.user', $userData) === false) {
    error_log('PITS register failed: Could not write user file');
    header('Location: signup.php?error=5');
    exit;
}

header('Location: signup.php?success=1');
exit;
