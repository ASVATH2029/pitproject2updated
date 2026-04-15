<?php
/*
 * staff_manage.php — Admin API for managing staff roles
 *
 * Actions:
 *   POST (JSON body)  { action: 'promote', username: '...' }  — Add user to staff list
 *   POST (JSON body)  { action: 'demote', username: '...' }   — Remove user from staff list
 *   POST (multipart)  ?action=bulk_upload + staff_file         — Import usernames from .txt/.csv
 *
 * Admin-only. Returns JSON responses.
 */

require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');

if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$action = $_GET['action'] ?? '';

// ── Bulk upload from file ─────────────────────────────────────────────────
if ($action === 'bulk_upload') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['error' => 'POST required']);
        exit;
    }

    if (empty($_FILES['staff_file'])) {
        echo json_encode(['error' => 'No file provided']);
        exit;
    }

    $file = $_FILES['staff_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['error' => 'Upload error']);
        exit;
    }

    // Read file content — one username per line
    $content = file_get_contents($file['tmp_name']);
    if ($content === false) {
        echo json_encode(['error' => 'Could not read file']);
        exit;
    }

    // Parse usernames: split by newlines, commas, or semicolons
    $raw = preg_split('/[\r\n,;]+/', $content);
    $usernames = [];
    foreach ($raw as $line) {
        $u = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', trim($line)));
        if (!empty($u) && strlen($u) <= 32) {
            $usernames[] = $u;
        }
    }

    if (empty($usernames)) {
        echo json_encode(['error' => 'No valid usernames found in file']);
        exit;
    }

    // Merge with existing staff list
    $existing = get_staff_list();
    $merged = array_unique(array_merge($existing, $usernames));
    save_staff_list($merged);

    $new_count = count($merged) - count($existing);
    echo json_encode([
        'success' => true,
        'count' => $new_count,
        'total' => count($merged)
    ]);
    exit;
}

// ── Individual promote/demote (JSON body) ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'POST required']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$act = $input['action'] ?? '';
$username = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $input['username'] ?? ''));

if (empty($username)) {
    echo json_encode(['error' => 'Missing username']);
    exit;
}

// Don't allow promoting admins to staff (they already have full access)
if (in_array($username, ADMIN_USERS, true)) {
    echo json_encode(['error' => 'Admin users cannot be assigned staff role (they already have full access)']);
    exit;
}

$list = get_staff_list();

if ($act === 'promote') {
    if (!in_array($username, $list, true)) {
        $list[] = $username;
        save_staff_list($list);
    }
    echo json_encode(['success' => true, 'role' => 'staff']);
} elseif ($act === 'demote') {
    $list = array_values(array_filter($list, function ($u) use ($username) {
        return $u !== $username;
    }));
    save_staff_list($list);
    echo json_encode(['success' => true, 'role' => 'collaborator']);
} else {
    echo json_encode(['error' => 'Unknown action: ' . $act]);
}
