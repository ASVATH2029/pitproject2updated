<?php
/*
 * upload.php — File upload handler
 *
 * Security chain:
 *   1. Session check (require_login)
 *   2. POST-only
 *   3. PHP upload error check
 *   4. Max single-file size (50 MB)
 *   5. Zero-byte guard
 *   6. Filename sanitization (basename + regex whitelist)
 *   7. Quota check (200 MB per user, .user excluded from size)
 *   8. Duplicate name auto-increment
 *   9. move_uploaded_file() — PHP native, rejects non-tmp files
 */

require_once __DIR__ . '/session.php';
require_login();
session_write_close();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (empty($_FILES['file'])) {
    echo json_encode(['error' => 'No file provided']);
    exit;
}

$file = $_FILES['file'];

// ── PHP upload error ────────────────────────────────────────────────────────
$upload_errors = [
    UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
    UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit',
    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server',
    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
    UPLOAD_ERR_EXTENSION => 'Upload blocked by server extension',
];

if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => $upload_errors[$file['error']] ?? 'Unknown upload error']);
    exit;
}

// ── Zero-byte guard ─────────────────────────────────────────────────────────
if ($file['size'] === 0) {
    echo json_encode(['error' => 'Empty files are not allowed']);
    exit;
}

// ── Max single file size ────────────────────────────────────────────────────
if ($file['size'] > MAX_UPLOAD_SIZE) {
    echo json_encode(['error' => 'File exceeds the 50 MB single-file limit']);
    exit;
}

// ── Sanitize filename ───────────────────────────────────────────────────────
$safe_name = sanitize_filename($file['name']);

// ── Executable Quarantine ───────────────────────────────────────────────────
$ext = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));
if (in_array($ext, BLOCKED_EXTENSIONS, true)) {
    echo json_encode(['error' => 'Upload blocked. Executable files are not permitted for security reasons.']);
    exit;
}

// ── Quota check ─────────────────────────────────────────────────────────────
$target_user = get_username();
if (is_admin() && !empty($_GET['target'])) {
    $target_user = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($_GET['target']));
}
$user_dir = ensure_user_dir($target_user);
$userDir = $user_dir;
$current_size = dir_size($user_dir);

if (($current_size + $file['size']) > UPLOAD_QUOTA) {
    $used_mb = round($current_size / 1024 / 1024, 1);
    $quota_mb = round(UPLOAD_QUOTA / 1024 / 1024);
    echo json_encode([
        'error' => "Quota exceeded. Usage: {$used_mb} MB / {$quota_mb} MB"
    ]);
    exit;
}

// ── Auto-increment duplicate names ─────────────────────────────────────────
$dest = $user_dir . '/' . $safe_name;
$counter = 1;

while (file_exists($dest)) {
    $info = pathinfo($safe_name);
    $ext = isset($info['extension']) ? '.' . $info['extension'] : '';
    $dest = $user_dir . '/' . $info['filename'] . '_' . $counter . $ext;
    $counter++;
}

// ── Move file ───────────────────────────────────────────────────────────────
if (move_uploaded_file($file['tmp_name'], $dest)) {
    invalidate_quota_cache($user_dir); // force fresh quota on next request
    echo json_encode([
        'success' => true,
        'filename' => basename($dest),
        'size' => filesize($dest),
    ]);
} else {
    error_log('PITS upload failed: could not move file to ' . $dest);
    echo json_encode(['error' => 'Failed to save file. Check server permissions.']);
}
