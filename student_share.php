<?php
/*
 * student_share.php — Handles file upload from student to staff inbox
 *
 * Security chain:
 *   1. Session check (require_login)
 *   2. POST-only
 *   3. Validates request exists and student is targeted
 *   4. Filename sanitization
 *   5. Blocked extensions check
 *   6. No quota check (staff inbox is unlimited)
 *   7. File saved as: {student_username}__{sanitized_filename}
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

$username = get_username();
$request_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['request_id'] ?? '');

if (empty($request_id)) {
    echo json_encode(['error' => 'Missing request_id']);
    exit;
}

// ── Validate the request exists ───────────────────────────────────────────
$req_file = STAFF_REQUESTS_DIR . '/' . $request_id . '.json';
if (!file_exists($req_file)) {
    echo json_encode(['error' => 'Request not found']);
    exit;
}

$req_data = json_decode(file_get_contents($req_file), true);
if (!is_array($req_data)) {
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// ── Validate student is targeted ──────────────────────────────────────────
$targets = $req_data['target_students'] ?? 'all';
if ($targets !== 'all' && is_array($targets) && !in_array($username, $targets, true)) {
    echo json_encode(['error' => 'This request is not directed at you']);
    exit;
}

// ── Validate file upload ──────────────────────────────────────────────────
if (empty($_FILES['file'])) {
    echo json_encode(['error' => 'No file provided']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder on server',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'Upload blocked by server extension',
    ];
    echo json_encode(['error' => $upload_errors[$file['error']] ?? 'Unknown upload error']);
    exit;
}

if ($file['size'] === 0) {
    echo json_encode(['error' => 'Empty files are not allowed']);
    exit;
}

// ── Sanitize filename ─────────────────────────────────────────────────────
$safe_name = sanitize_filename($file['name']);
$ext = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));

if (in_array($ext, BLOCKED_EXTENSIONS, true)) {
    echo json_encode(['error' => 'Executable files are not permitted']);
    exit;
}

// ── Save to staff inbox ───────────────────────────────────────────────────
$staff_user = $req_data['staff'] ?? '';
$inbox_dir = STAFF_INBOX_DIR . '/' . $staff_user . '/' . $request_id;

if (!is_dir($inbox_dir)) {
    mkdir($inbox_dir, 0775, true);
}

// Filename format: studentname__originalname.ext
$dest_name = $username . '__' . $safe_name;
$dest = $inbox_dir . '/' . $dest_name;

// Auto-increment if duplicate
$counter = 1;
while (file_exists($dest)) {
    $info = pathinfo($safe_name);
    $ext_str = isset($info['extension']) ? '.' . $info['extension'] : '';
    $dest_name = $username . '__' . $info['filename'] . '_' . $counter . $ext_str;
    $dest = $inbox_dir . '/' . $dest_name;
    $counter++;
}

if (move_uploaded_file($file['tmp_name'], $dest)) {
    echo json_encode([
        'success' => true,
        'filename' => basename($dest),
        'size' => filesize($dest)
    ]);
} else {
    error_log('PITS student_share: could not move file to ' . $dest);
    echo json_encode(['error' => 'Failed to save file. Check server permissions.']);
}
