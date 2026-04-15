<?php
/*
 * student_requests_api.php — Returns active staff requests relevant to the current student
 *
 * Used by the student dashboard to display "Staff Requests" tiles.
 * Returns JSON with request details and whether the student has already shared files.
 */

require_once __DIR__ . '/session.php';
require_login();
session_write_close();

header('Content-Type: application/json');

$username = get_username();

// Students (collaborators) only — staff and admin don't see student request tiles
// (but we allow it for testing)

$requests = get_student_requests($username);

// Check if this student has already shared files for each request
foreach ($requests as &$req) {
    $staff = $req['staff'] ?? '';
    $req_id = $req['id'] ?? '';
    $inbox_dir = STAFF_INBOX_DIR . '/' . $staff . '/' . $req_id;

    $req['already_shared'] = false;
    $req['shared_files'] = [];

    if (is_dir($inbox_dir)) {
        $files = array_diff(scandir($inbox_dir), ['.', '..']);
        foreach ($files as $f) {
            // Files are named: studentname__originalfilename.ext
            if (str_starts_with($f, $username . '__')) {
                $req['already_shared'] = true;
                $req['shared_files'][] = substr($f, strlen($username) + 2);
            }
        }
    }

    // Don't expose internal paths to the client
    unset($req['target_students']);
}
unset($req);

echo json_encode(['requests' => $requests]);
