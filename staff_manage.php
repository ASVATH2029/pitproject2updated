<?php
/*
 * staff_manage.php — Admin API for managing staff roles
 *
 * Staff status is driven entirely by the uploaded roster file — there is
 * no manual per-user promote/demote. To revoke someone's staff access,
 * re-upload a roster that omits their username.
 *
 * Actions:
 *   POST (multipart)  ?action=bulk_upload + staff_file  — Replace the staff
 *                     roster with the usernames listed in the file (one per
 *                     line; also accepts comma/semicolon separated).
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

    // Admins already have full access and are never listed as staff
    $usernames = array_values(array_filter($usernames, function ($u) {
        return !in_array($u, ADMIN_USERS, true);
    }));

    if (empty($usernames)) {
        echo json_encode(['error' => 'No valid usernames found in file']);
        exit;
    }

    // The uploaded roster REPLACES the staff list — it is the single source
    // of truth for who is staff. Any previously-staffed username left off
    // this file loses staff access (and is demoted back to a student).
    $existing = get_staff_list();
    $usernames = array_values(array_unique($usernames));
    save_staff_list($usernames);

    $added = count(array_diff($usernames, $existing));
    $removed = count(array_diff($existing, $usernames));
    echo json_encode([
        'success' => true,
        'count' => $added,
        'removed' => $removed,
        'total' => count($usernames)
    ]);
    exit;
}

echo json_encode(['error' => 'Unknown action: ' . $action]);
