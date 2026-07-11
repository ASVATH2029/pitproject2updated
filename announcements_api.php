<?php
/*
 * announcements_api.php — Backend API for the announcements feature
 *
 * Actions (via ?action= parameter):
 *   GET  list         List announcements. Role-aware:
 *                        admin/HOD → all announcements, with optional filters:
 *                          &by=staff  → only staff-authored
 *                          &by=hod    → only HOD-authored
 *                          &by=all    → everything (default)
 *                          &staff=<username> → only that staff member's
 *                        staff  → HOD announcements + their own
 *                        student → announcements targeted at them (all + their class)
 *   GET  staff_list   (admin only) list distinct staff usernames who have posted,
 *                     for the HOD per-staff drill-down UI
 *   POST create       Create a new announcement (staff or admin only)
 *   POST delete       Delete an announcement (author or admin only)
 */

require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');

$username = get_username();
$action = $_GET['action'] ?? '';

switch ($action) {

    // ── List announcements ─────────────────────────────────────────────────
    case 'list':
        if (is_admin()) {
            $out = get_all_announcements();

            // Optional per-staff drill-down: only that staff member's posts.
            $staff = strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $_GET['staff'] ?? ''));
            if ($staff !== '') {
                $out = array_values(array_filter($out, fn($a) => ($a['author'] ?? '') === $staff));
            } else {
                // Optional author-role filter: staff-authored / HOD-authored / all.
                $by = $_GET['by'] ?? 'all';
                if ($by === 'staff') {
                    $out = array_values(array_filter($out, fn($a) => ($a['author_role'] ?? '') === 'staff'));
                } elseif ($by === 'hod') {
                    $out = array_values(array_filter($out, fn($a) => ($a['author_role'] ?? '') === 'admin'));
                }
            }
        } elseif (is_staff()) {
            // Staff see HOD announcements plus their own.
            $out = get_staff_announcements($username);
        } else {
            $out = get_student_announcements($username);
        }
        echo json_encode(['announcements' => $out, 'classes' => get_all_class_names()]);
        break;

    // ── List distinct staff who have posted announcements (admin only) ──────
    case 'staff_list':
        if (!is_admin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }
        $staff_authors = [];
        foreach (get_all_announcements() as $a) {
            if (($a['author_role'] ?? '') === 'staff') {
                $author = $a['author'] ?? '';
                if ($author !== '' && !in_array($author, $staff_authors, true)) {
                    $staff_authors[] = $author;
                }
            }
        }
        sort($staff_authors);
        echo json_encode(['staff' => $staff_authors]);
        break;

    // ── Create an announcement ─────────────────────────────────────────────
    case 'create':
        if (!is_staff() && !is_admin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Staff role required.']);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $title = trim($input['title'] ?? '');
        $body = trim($input['body'] ?? '');
        $target_type = ($input['target_type'] ?? 'all') === 'class' ? 'class' : 'all';
        $target_class = trim($input['target_class'] ?? '');

        if (empty($title)) {
            echo json_encode(['error' => 'Title is required']);
            exit;
        }
        if (strlen($title) > 200) {
            echo json_encode(['error' => 'Title too long (max 200 chars)']);
            exit;
        }
        if (strlen($body) > 5000) {
            echo json_encode(['error' => 'Body too long (max 5000 chars)']);
            exit;
        }
        if ($target_type === 'class' && !in_array($target_class, get_all_class_names(), true)) {
            echo json_encode(['error' => 'Invalid target class']);
            exit;
        }

        ensure_announcements_dir();
        $id = 'ann_' . bin2hex(random_bytes(8));
        $announcement = [
            'author' => $username,
            'author_display' => $username,
            'author_role' => is_admin() ? 'admin' : 'staff',
            'title' => $title,
            'body' => $body,
            'target_type' => $target_type,
            'target_class' => $target_type === 'class' ? $target_class : '',
            'created_at' => time(),
        ];

        file_put_contents(
            ANNOUNCEMENTS_DIR . '/' . $id . '.json',
            json_encode($announcement, JSON_PRETTY_PRINT),
            LOCK_EX
        );

        echo json_encode(['success' => true, 'id' => $id]);
        break;

    // ── Delete an announcement ─────────────────────────────────────────────
    case 'delete':
        if (!is_staff() && !is_admin()) {
            http_response_code(403);
            echo json_encode(['error' => 'Access denied. Staff role required.']);
            exit;
        }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['error' => 'POST required']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['id'] ?? '');
        if (empty($id)) {
            echo json_encode(['error' => 'Missing id']);
            exit;
        }

        $file = ANNOUNCEMENTS_DIR . '/' . $id . '.json';
        if (!file_exists($file)) {
            echo json_encode(['error' => 'Announcement not found']);
            exit;
        }
        $data = json_decode(file_get_contents($file), true);
        if (!is_admin() && ($data['author'] ?? '') !== $username) {
            echo json_encode(['error' => 'You can only delete your own announcements']);
            exit;
        }
        unlink($file);
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Unknown action: ' . $action]);
        break;
}
