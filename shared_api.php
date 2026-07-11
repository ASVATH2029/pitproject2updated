<?php
/*
 * shared_api.php — Backend API for the per-user "shared" folder
 *
 * The shared folder (.shared/) is nested inside each user's own storage
 * directory, isolated from their personal archive, and counted toward the
 * same per-user quota. Visibility rules (enforced server-side — ?target=
 * is never trusted):
 *   - Self:  any authenticated user may list/upload/download/delete their
 *            own shared folder.
 *   - Staff: read-only access to a student's (collaborator role) shared
 *            folder via ?target=. Cannot view another staff member's.
 *   - Admin: read-only access to any user's (student or staff) shared
 *            folder via ?target=.
 * Upload/delete are always self-only, regardless of role.
 *
 * Actions (via ?action= parameter):
 *   GET  list      List files in a shared folder (?target= for staff/admin)
 *   GET  download  Download a shared file (?target=&file=)
 *   POST upload    Upload a file to your own shared folder
 *   POST delete    Delete a file from your own shared folder
 */

require_once __DIR__ . '/session.php';
require_login();

$action = $_GET['action'] ?? '';
$username = get_username();

/**
 * Resolves which username's shared folder to operate on for a read
 * operation, honoring ?target= only for roles/targets permitted to
 * view someone else's shared folder. Falls back to the caller's own
 * username otherwise.
 */
function resolve_shared_read_target(string $self): string
{
    if (empty($_GET['target'])) {
        return $self;
    }
    $target = preg_replace('/[^a-zA-Z0-9_-]/', '', strtolower($_GET['target']));
    if (empty($target) || $target === $self) {
        return $self;
    }
    if (is_admin()) {
        return $target; // admin may view any user's shared folder
    }
    if (is_staff() && get_role($target) === 'collaborator') {
        return $target; // staff may view students' shared folders only
    }
    return $self; // no permission to view this target — force own folder
}

if ($action === 'download') {
    // Binary response — handled before the JSON header is sent.
    $target_user = resolve_shared_read_target($username);
    $dir = ensure_shared_dir($target_user);
    $file = basename($_GET['file'] ?? '');
    $filepath = $dir . '/' . $file;

    if (empty($file) || !is_file($filepath) || !is_safe_path($filepath, $dir)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . rawurlencode($file) . '"');
    header('Content-Length: ' . filesize($filepath));
    header('Cache-Control: no-cache, must-revalidate');
    readfile($filepath);
    exit;
}

header('Content-Type: application/json');

switch ($action) {

    // ── List files in a shared folder ──────────────────────────────────────
    case 'list':
        $target_user = resolve_shared_read_target($username);
        $dir = ensure_shared_dir($target_user);

        $files = [];
        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..' || $item[0] === '.') continue;
            $path = $dir . '/' . $item;
            if (is_file($path)) {
                $files[] = [
                    'name' => $item,
                    'size' => filesize($path),
                    'modified' => date('Y-m-d H:i', filemtime($path)),
                ];
            }
        }

        echo json_encode([
            'files' => $files,
            'owner' => $target_user,
            'is_own' => $target_user === $username,
        ]);
        break;

    // ── Upload a file to your own shared folder ────────────────────────────
    case 'upload':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        if (empty($_FILES['file'])) {
            echo json_encode(['error' => 'No file provided']);
            exit;
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'Upload failed']);
            exit;
        }
        if ($file['size'] === 0) {
            echo json_encode(['error' => 'Empty files are not allowed']);
            exit;
        }
        if ($file['size'] > MAX_UPLOAD_SIZE) {
            echo json_encode(['error' => 'File exceeds the single-file limit']);
            exit;
        }

        $safe_name = sanitize_filename($file['name']);
        $ext = strtolower(pathinfo($safe_name, PATHINFO_EXTENSION));
        if (in_array($ext, BLOCKED_EXTENSIONS, true)) {
            echo json_encode(['error' => 'Upload blocked. Executable files are not permitted for security reasons.']);
            exit;
        }

        // Combined personal + shared usage counts toward the same quota.
        $personal_dir = ensure_user_dir($username);
        $shared_dir = ensure_shared_dir($username);
        $current_size = dir_size($personal_dir) + shared_dir_size($username);

        if (($current_size + $file['size']) > UPLOAD_QUOTA) {
            $used_mb = round($current_size / 1024 / 1024, 1);
            $quota_mb = round(UPLOAD_QUOTA / 1024 / 1024);
            echo json_encode(['error' => "Quota exceeded. Usage: {$used_mb} MB / {$quota_mb} MB"]);
            exit;
        }

        $dest = $shared_dir . '/' . $safe_name;
        $counter = 1;
        while (file_exists($dest)) {
            $info = pathinfo($safe_name);
            $ext_suffix = isset($info['extension']) ? '.' . $info['extension'] : '';
            $dest = $shared_dir . '/' . $info['filename'] . '_' . $counter . $ext_suffix;
            $counter++;
        }

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            echo json_encode(['success' => true, 'filename' => basename($dest), 'size' => filesize($dest)]);
        } else {
            error_log('PITS shared upload failed: could not move file to ' . $dest);
            echo json_encode(['error' => 'Failed to save file. Check server permissions.']);
        }
        break;

    // ── Delete a file from your own shared folder ──────────────────────────
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $file = basename($input['filename'] ?? '');
        if (empty($file)) {
            echo json_encode(['error' => 'Missing filename']);
            exit;
        }

        $dir = ensure_shared_dir($username);
        $filepath = $dir . '/' . $file;
        if (is_file($filepath) && is_safe_path($filepath, $dir)) {
            unlink($filepath);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'File not found']);
        }
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
        break;
}
