<?php
require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');

if (!is_admin() || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Unauthorized or invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$target_user = trim($input['target'] ?? '');

$target_user = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($target_user));

if (empty($target_user)) {
    echo json_encode(['error' => 'Target user empty']);
    exit;
}

$user_dir = get_user_dir($target_user);

if (!is_dir($user_dir) || !file_exists($user_dir . '/.user')) {
    echo json_encode(['error' => 'User does not exist']);
    exit;
}

if ($target_user === get_username()) {
    echo json_encode(['error' => 'Cannot delete yourself']);
    exit;
}

// Recursively delete folder
shell_exec('rm -rf ' . escapeshellarg($user_dir));

echo json_encode(['success' => true]);
