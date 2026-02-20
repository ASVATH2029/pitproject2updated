<?php
require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['error' => 'No file selected']);
    exit;
}

$user_dir = get_user_dir(get_username());
if (!is_dir($user_dir)) {
    echo json_encode(['error' => 'User directory not found']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server limit',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form limit',
        UPLOAD_ERR_PARTIAL => 'File only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
    ];
    echo json_encode(['error' => $errors[$file['error']] ?? 'Upload error']);
    exit;
}

if ($file['size'] > MAX_UPLOAD_SIZE) {
    echo json_encode(['error' => 'File exceeds 50MB limit']);
    exit;
}

$current_usage = dir_size($user_dir);
if (($current_usage + $file['size']) > UPLOAD_QUOTA) {
    echo json_encode(['error' => 'Upload would exceed your 200MB quota. Current usage: ' . round($current_usage / 1024 / 1024, 1) . 'MB']);
    exit;
}

$safe_name = sanitize_filename($file['name']);
$dest = $user_dir . '/' . $safe_name;

$counter = 1;
while (file_exists($dest)) {
    $info = pathinfo($safe_name);
    $dest = $user_dir . '/' . $info['filename'] . '_' . $counter . '.' . ($info['extension'] ?? '');
    $counter++;
}

if (move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode(['success' => true, 'filename' => basename($dest)]);
} else {
    echo json_encode(['error' => 'Failed to save file']);
}
