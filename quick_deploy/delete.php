<?php
require_once __DIR__ . '/session.php';
require_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$filenames = $input['files'] ?? [];

if (empty($filenames) || !is_array($filenames)) {
    echo json_encode(['error' => 'No files selected']);
    exit;
}

$user_dir = get_user_dir(get_username());
$deleted = [];
$failed = [];

foreach ($filenames as $name) {
    $safe = basename($name);
    if ($safe !== $name || strpos($name, '..') !== false) {
        $failed[] = $name;
        continue;
    }

    $filepath = $user_dir . '/' . $safe;
    if (!is_safe_path($filepath, $user_dir)) {
        $failed[] = $name;
        continue;
    }

    if (is_file($filepath) && unlink($filepath)) {
        $deleted[] = $safe;
    } else {
        $failed[] = $name;
    }
}

echo json_encode([
    'success' => true,
    'deleted' => $deleted,
    'failed' => $failed
]);
