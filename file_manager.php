<?php
require_once __DIR__ . '/session.php';
require_login();

// Ensure the user's project directory exists
$user_dir = ensure_user_dir(get_username());

header('Content-Type: application/json');

$files = [];
$items = scandir($user_dir);
foreach ($items as $item) {
    if ($item === '.' || $item === '..')
        continue;
    $path = $user_dir . '/' . $item;
    if (is_file($path)) {
        $files[] = [
            'name' => $item,
            'size' => filesize($path),
            'modified' => date('Y-m-d H:i', filemtime($path)),
            'type' => pathinfo($item, PATHINFO_EXTENSION)
        ];
    }
}

$usage = dir_size($user_dir);
echo json_encode([
    'files' => $files,
    'usage' => $usage,
    'quota' => UPLOAD_QUOTA,
    'usage_percent' => round(($usage / UPLOAD_QUOTA) * 100, 1)
]);
