<?php
require_once __DIR__ . '/session.php';
require_login();
session_write_close();

$target_user = get_username();
if (is_admin() && !empty($_GET['target'])) {
    $target_user = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($_GET['target']));
}
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

$usage = dir_size($user_dir);
echo json_encode([
    'files' => $files,
    'usage' => $usage,
    'quota' => UPLOAD_QUOTA,
    'usage_percent' => round(($usage / UPLOAD_QUOTA) * 100, 1)
]);
