<?php
/*
 * staff_api.php — Backend API for staff dashboard operations
 *
 * All actions require staff or admin role.
 * Returns JSON responses.
 *
 * Actions (via ?action= parameter):
 *   GET  list_requests     List all requests created by this staff member
 *   GET  list_assignments   List all assignment-type requests (same rules as list_requests)
 *   GET  list_files         List files shared for a specific request (?request_id=)
 *   POST create_request     Create a new document request tile (optionally type=assignment, due_date=)
 *   POST delete_request     Remove a request tile and its files
 *   POST delete_file        Delete a specific shared file
 *   GET  download_file      Download a shared file (?request_id=&file=)
 */

require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// Only staff and admin can use this API
if (!is_staff() && !is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied. Staff role required.']);
    exit;
}

$username = get_username();
$action = $_GET['action'] ?? '';

ensure_staff_dirs();

switch ($action) {

    // ── List this staff member's requests ──────────────────────────────────
    case 'list_requests':
    case 'list_assignments':
        $all = $action === 'list_assignments' ? get_all_assignments() : get_all_requests();
        // Staff sees only their own; admin sees all
        if (!is_admin()) {
            $all = array_values(array_filter($all, function ($r) use ($username) {
                return ($r['staff'] ?? '') === $username;
            }));
        }
        // Count received files per request
        foreach ($all as &$req) {
            $inbox_dir = STAFF_INBOX_DIR . '/' . ($req['staff'] ?? $username) . '/' . $req['id'];
            $req['file_count'] = 0;
            $req['students_responded'] = [];
            if (is_dir($inbox_dir)) {
                $files = array_diff(scandir($inbox_dir), ['.', '..']);
                $req['file_count'] = count($files);
                foreach ($files as $f) {
                    $parts = explode('__', $f, 2);
                    if (count($parts) === 2 && !in_array($parts[0], $req['students_responded'])) {
                        $req['students_responded'][] = $parts[0];
                    }
                }
            }
        }
        unset($req);
        echo json_encode([$action === 'list_assignments' ? 'assignments' : 'requests' => $all]);
        break;

    // ── List files for a specific request ──────────────────────────────────
    case 'list_files':
        $request_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['request_id'] ?? '');
        if (empty($request_id)) {
            echo json_encode(['error' => 'Missing request_id']);
            exit;
        }
        // Resolve the request's owning staff member so admin can browse
        // requests created by other staff, not just their own inbox.
        $req_file = STAFF_REQUESTS_DIR . '/' . $request_id . '.json';
        $owner = $username;
        if (file_exists($req_file)) {
            $req_data = json_decode(file_get_contents($req_file), true);
            $req_owner = $req_data['staff'] ?? $username;
            if (is_admin() || $req_owner === $username) {
                $owner = $req_owner;
            }
        }
        $inbox_dir = STAFF_INBOX_DIR . '/' . $owner . '/' . $request_id;
        $files = [];
        if (is_dir($inbox_dir)) {
            $items = array_diff(scandir($inbox_dir), ['.', '..']);
            foreach ($items as $item) {
                $path = $inbox_dir . '/' . $item;
                if (is_file($path)) {
                    $parts = explode('__', $item, 2);
                    $files[] = [
                        'filename' => $item,
                        'display_name' => $parts[1] ?? $item,
                        'student' => $parts[0] ?? 'unknown',
                        'size' => filesize($path),
                        'modified' => date('Y-m-d H:i', filemtime($path))
                    ];
                }
            }
        }
        echo json_encode(['files' => $files]);
        break;

    // ── Download a shared file ────────────────────────────────────────────
    case 'download_file':
        $request_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['request_id'] ?? '');
        $file = basename($_GET['file'] ?? '');
        if (empty($request_id) || empty($file)) {
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }
        $req_file = STAFF_REQUESTS_DIR . '/' . $request_id . '.json';
        $owner = $username;
        if (file_exists($req_file)) {
            $req_data = json_decode(file_get_contents($req_file), true);
            $req_owner = $req_data['staff'] ?? $username;
            if (is_admin() || $req_owner === $username) {
                $owner = $req_owner;
            }
        }
        $filepath = STAFF_INBOX_DIR . '/' . $owner . '/' . $request_id . '/' . $file;
        if (!is_file($filepath)) {
            echo json_encode(['error' => 'File not found']);
            exit;
        }
        // Switch to binary download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . rawurlencode($file) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filepath);
        exit;

    // ── Create a new request tile ─────────────────────────────────────────
    case 'create_request':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $title = trim($input['title'] ?? '');
        $description = trim($input['description'] ?? '');
        $target_mode = $input['target_mode'] ?? '';   // '' | 'class'
        $targets = $input['target_students'] ?? 'all';
        $target_class = '';
        $type = ($input['type'] ?? 'request') === 'assignment' ? 'assignment' : 'request';
        $due_date = trim($input['due_date'] ?? '');
        if ($due_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $due_date)) {
            $due_date = '';
        }

        if (empty($title)) {
            echo json_encode(['error' => 'Title is required']);
            exit;
        }
        if (strlen($title) > 200) {
            echo json_encode(['error' => 'Title too long (max 200 chars)']);
            exit;
        }
        if (strlen($description) > 2000) {
            echo json_encode(['error' => 'Description too long (max 2000 chars)']);
            exit;
        }

        if ($target_mode === 'class') {
            // Class-scoped: store the class label; resolved per-student at read time.
            $target_class = trim($input['target_class'] ?? '');
            if (!in_array($target_class, get_all_class_names(), true)) {
                echo json_encode(['error' => 'Invalid target class']);
                exit;
            }
            $targets = 'class'; // sentinel — actual audience is by class match
        } elseif ($targets !== 'all' && is_string($targets)) {
            // Explicit username list (comma-separated).
            $targets = array_filter(array_map(function ($u) {
                return strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', trim($u)));
            }, explode(',', $targets)));
            $targets = array_values(array_unique($targets));
            if (empty($targets)) $targets = 'all';
        }

        $request_id = 'req_' . bin2hex(random_bytes(8));
        $request = [
            'staff' => $username,
            'staff_display' => $username,
            'title' => $title,
            'description' => $description,
            'target_students' => $targets,
            'target_class' => $target_class,
            'created_at' => time(),
            'type' => $type,
            'due_date' => $due_date,
        ];

        file_put_contents(
            STAFF_REQUESTS_DIR . '/' . $request_id . '.json',
            json_encode($request, JSON_PRETTY_PRINT),
            LOCK_EX
        );

        // Create inbox directory for this request
        $inbox = STAFF_INBOX_DIR . '/' . $username . '/' . $request_id;
        if (!is_dir($inbox)) {
            mkdir($inbox, 0775, true);
        }

        echo json_encode(['success' => true, 'id' => $request_id]);
        break;

    // ── Delete a request tile ─────────────────────────────────────────────
    case 'delete_request':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $request_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['request_id'] ?? '');
        if (empty($request_id)) {
            echo json_encode(['error' => 'Missing request_id']);
            exit;
        }

        // Verify ownership (unless admin)
        $req_file = STAFF_REQUESTS_DIR . '/' . $request_id . '.json';
        $owner = $username;
        if (file_exists($req_file)) {
            $req_data = json_decode(file_get_contents($req_file), true);
            if (!is_admin() && ($req_data['staff'] ?? '') !== $username) {
                echo json_encode(['error' => 'You can only delete your own requests']);
                exit;
            }
            $owner = $req_data['staff'] ?? $username;
            unlink($req_file);
        }

        // Delete associated inbox files (owned by the request's staff creator)
        $inbox_dir = STAFF_INBOX_DIR . '/' . $owner . '/' . $request_id;
        if (is_dir($inbox_dir)) {
            $files = array_diff(scandir($inbox_dir), ['.', '..']);
            foreach ($files as $f) {
                unlink($inbox_dir . '/' . $f);
            }
            rmdir($inbox_dir);
        }

        echo json_encode(['success' => true]);
        break;

    // ── Delete a specific shared file ─────────────────────────────────────
    case 'delete_file':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $request_id = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['request_id'] ?? '');
        $file = basename($input['filename'] ?? '');
        if (empty($request_id) || empty($file)) {
            echo json_encode(['error' => 'Missing parameters']);
            exit;
        }
        $req_file = STAFF_REQUESTS_DIR . '/' . $request_id . '.json';
        $owner = $username;
        if (file_exists($req_file)) {
            $req_data = json_decode(file_get_contents($req_file), true);
            $req_owner = $req_data['staff'] ?? $username;
            if (is_admin() || $req_owner === $username) {
                $owner = $req_owner;
            }
        }
        $filepath = STAFF_INBOX_DIR . '/' . $owner . '/' . $request_id . '/' . $file;
        if (is_file($filepath)) {
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
