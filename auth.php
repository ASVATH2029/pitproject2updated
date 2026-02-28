<?php
require_once __DIR__ . '/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

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
        return $t > time() - 600; });
}

if (count($attempts) >= 5) {
    header('Location: index.php?error=2');
    exit;
}

// Authenticate via PAM helper
$helper = __DIR__ . '/pam_auth_helper.sh';
$cmd = 'sudo ' . escapeshellcmd($helper) . ' ' . escapeshellarg($username);
$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$process = proc_open($cmd, $descriptors, $pipes);

if (!is_resource($process)) {
    header('Location: index.php?error=1');
    exit;
}

fwrite($pipes[0], $password);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit_code = proc_close($process);

// Log attempt
$attempts[] = time();
file_put_contents($rate_file, json_encode($attempts));

if ($exit_code === 0) {
    session_regenerate_id(true);
    $_SESSION['username'] = $username;
    $_SESSION['role'] = get_role($username);
    $_SESSION['login_time'] = time();
    header('Location: dashboard.php');
    exit;
}

header('Location: index.php?error=1');
exit;
