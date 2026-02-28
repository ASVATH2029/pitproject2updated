<?php
session_start();
if (!empty($_SESSION['username'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: signup.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$confirm = $_POST['confirm_password'] ?? '';

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

// Check if user already exists
$check = shell_exec('id ' . escapeshellarg($username) . ' 2>&1');
if (strpos($check, 'no such user') === false) {
    header('Location: signup.php?error=1');
    exit;
}

// Create the Linux user with the provided password
$cmd = sprintf(
    'sudo useradd -m -s /bin/bash %s && echo %s:%s | sudo chpasswd',
    escapeshellarg($username),
    escapeshellarg($username),
    escapeshellarg($password)
);

$output = [];
$exit_code = 0;
exec($cmd . ' 2>&1', $output, $exit_code);

if ($exit_code !== 0) {
    error_log('PITS register failed: ' . implode(' ', $output));
    header('Location: signup.php?error=5');
    exit;
}

// Set correct permissions on the new user's home directory
exec('sudo chown ' . escapeshellarg($username) . ':www-data ' . escapeshellarg('/home/' . $username) . ' 2>&1');
exec('sudo chmod 750 ' . escapeshellarg('/home/' . $username) . ' 2>&1');

header('Location: signup.php?success=1');
exit;
