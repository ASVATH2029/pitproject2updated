<?php
require_once __DIR__ . '/session.php';
require_login();
session_write_close();

$target_user = get_username();
$user_dir = ensure_user_dir($target_user);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$files = [];
$items = scandir($user_dir);
foreach ($items as $item) {
    if ($item === '.' || $item === '..')
        continue;
    // Skip hidden files (like .user credential file)
    if ($item[0] === '.')
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

$personal_usage = dir_size($user_dir);
$shared_usage = shared_dir_size($target_user);
$total_usage = $personal_usage + $shared_usage;
echo json_encode([
    'files' => $files,
    'usage' => $total_usage,
    'personal_usage' => $personal_usage,
    'shared_usage' => $shared_usage,
    'quota' => UPLOAD_QUOTA,
    'usage_percent' => round(($total_usage / UPLOAD_QUOTA) * 100, 1),
    'personal_usage_percent' => round(($personal_usage / UPLOAD_QUOTA) * 100, 1),
    'shared_usage_percent' => round(($shared_usage / UPLOAD_QUOTA) * 100, 1)
]);
