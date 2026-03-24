<?php
require_once __DIR__ . '/session.php';
require_login();
session_write_close();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$filenames = $input['files'] ?? [];

if (empty($filenames) || !is_array($filenames)) {
    echo json_encode(['error' => 'No files selected']);
    exit;
}

$target_user = get_username();
if (is_admin() && !empty($_GET['target'])) {
    $target_user = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($_GET['target']));
}
$user_dir = get_user_dir($target_user);
$deleted = [];
$failed = [];

foreach ($filenames as $name) {
    $safe = basename($name);
    if ($safe !== $name || strpos($name, '..') !== false) {
        $failed[] = $name;
        continue;
    }

    $filepath = $user_dir . '/' . $safe;
    if (!is_safe_path($filepath, $user_dir)) {
        $failed[] = $name;
        continue;
    }

    if (is_file($filepath) && unlink($filepath)) {
        invalidate_quota_cache($user_dir);
        $deleted[] = $safe;
    } else {
        $failed[] = $name;
    }
}

echo json_encode([
    'success' => true,
    'deleted' => $deleted,
    'failed' => $failed
]);
