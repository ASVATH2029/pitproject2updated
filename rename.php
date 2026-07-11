<?php
require_once __DIR__ . '/session.php';
require_login();
session_write_close();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$old_name = trim($input['old_name'] ?? '');
$new_name = trim($input['new_name'] ?? '');

if (empty($old_name) || empty($new_name)) {
    echo json_encode(['error' => 'Names cannot be empty']);
    exit;
}

$target_user = get_username();
$user_dir = get_user_dir($target_user);

$safe_old = sanitize_filename($old_name);
$safe_new = sanitize_filename($new_name);

if ($safe_old !== $old_name || $safe_new !== $new_name) {
    echo json_encode(['error' => 'Invalid characters in filename. Use alphanumeric characters, dashes, and underscores.']);
    exit;
}

// ── Executable Quarantine ───────────────────────────────────────────────────
$ext = strtolower(pathinfo($safe_new, PATHINFO_EXTENSION));
if (in_array($ext, BLOCKED_EXTENSIONS, true)) {
    echo json_encode(['error' => 'Rename blocked. Executable files are not permitted for security reasons.']);
    exit;
}

$old_path = $user_dir . '/' . $safe_old;
$new_path = $user_dir . '/' . $safe_new;

if (!file_exists($old_path)) {
    echo json_encode(['error' => 'Original file does not exist']);
    exit;
}
if (file_exists($new_path)) {
    echo json_encode(['error' => 'A file with the new name already exists']);
    exit;
}

if (rename($old_path, $new_path)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['error' => 'Rename failed on the filesystem level.']);
}
