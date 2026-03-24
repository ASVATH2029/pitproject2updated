<?php
require_once __DIR__ . '/session.php';
require_login();
session_write_close();

$filename = $_GET['file'] ?? '';
if (empty($filename)) {
    http_response_code(400);
    exit('No file specified');
}

$safe = basename($filename);
if ($safe !== $filename || strpos($filename, '..') !== false) {
    http_response_code(403);
    exit('Invalid filename');
}

$target_user = get_username();
if (is_admin() && !empty($_GET['target'])) {
    $target_user = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($_GET['target']));
}
$user_dir = get_user_dir($target_user);
$filepath = $user_dir . '/' . $safe;

if (!is_safe_path($filepath, $user_dir) || !is_file($filepath)) {
    http_response_code(404);
    exit('File not found');
}

$size = filesize($filepath);
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . rawurlencode($safe) . '"');
header('Content-Length: ' . $size);
header('Cache-Control: no-cache, must-revalidate');
readfile($filepath);
exit;
