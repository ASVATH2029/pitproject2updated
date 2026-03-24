<?php
require_once __DIR__ . '/session.php';
require_login();

// 1. Only allow admins to run the cleanup
if (!is_admin()) {
    http_response_code(403);
    exit("Permission Denied: Only administrators can run the duplicate cleanup tool.");
}

echo "<h2>Duplicate Username Cleanup Tool</h2>";
echo "<pre>";

// 2. Scan all directories in BASE_DIR and group by lowercase
$groups = [];
$items = scandir(BASE_DIR);

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = BASE_DIR . $item;
    
    // We only care about directories that contain a .user file
    if (is_dir($path) && file_exists($path . '/.user')) {
        $lower = strtolower($item);
        if (!isset($groups[$lower])) {
            $groups[$lower] = [];
        }
        $groups[$lower][] = $path;
    }
}

$duplicates_found = 0;
$cleaned = 0;

// 3. Process each group
foreach ($groups as $lower_name => $paths) {
    if (count($paths) > 1) {
        $duplicates_found++;
        echo "Found duplicates for username: <strong>{$lower_name}</strong>\n";
        
        $profiles = [];
        foreach ($paths as $p) {
            $data = json_decode(file_get_contents($p . '/.user'), true);
            $created_time = isset($data['created']) ? strtotime($data['created']) : filemtime($p . '/.user');
            $profiles[] = [
                'path' => $p,
                'time' => $created_time,
                'basename' => basename($p),
                'data' => $data
            ];
        }
        
        // Sort ascending by time (oldest first)
        usort($profiles, function($a, $b) {
            return $a['time'] <=> $b['time'];
        });
        
        // The first one is the oldest and gets kept
        $keeper = $profiles[0];
        $target_path = BASE_DIR . $lower_name;
        
        echo "  -> Keeping oldest profile: {$keeper['basename']} (created: " . date('Y-m-d H:i:s', $keeper['time']) . ")\n";
        
        // Delete the newer ones
        for ($i = 1; $i < count($profiles); $i++) {
            $dupe = $profiles[$i];
            echo "  -> Deleting newer duplicate: {$dupe['basename']} (created: " . date('Y-m-d H:i:s', $dupe['time']) . ")\n";
            
            // Recursively delete directory
            $it = new RecursiveDirectoryIterator($dupe['path'], RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
                if ($file->isDir()){
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            rmdir($dupe['path']);
            $cleaned++;
        }
        
        // If the surviving directory is not strictly lowercase, rename it
        if ($keeper['basename'] !== $lower_name) {
            echo "  -> Renaming {$keeper['basename']} to strictly lowercase {$lower_name}\n";
            rename($keeper['path'], $target_path);
            
            // Also update the .user file to ensure the username string matches exactly
            if (isset($keeper['data']['username'])) {
                $keeper['data']['username'] = $lower_name;
                file_put_contents($target_path . '/.user', json_encode($keeper['data']));
            }
        }
        echo "\n";
    } else {
        // Even if there are no duplicates, we want to enforce standard casing across the board
        $p = $paths[0];
        $basename = basename($p);
        if ($basename !== $lower_name) {
            echo "Standardizing casing for: {$basename} -> {$lower_name}\n";
            $target_path = BASE_DIR . $lower_name;
            rename($p, $target_path);
            
            // Also update the .user file if needed
            $data = json_decode(file_get_contents($target_path . '/.user'), true);
            if (isset($data['username']) && $data['username'] !== $lower_name) {
                $data['username'] = $lower_name;
                file_put_contents($target_path . '/.user', json_encode($data));
            }
        }
    }
}

if ($duplicates_found === 0) {
    echo "No duplicates found to clean up.\n";
} else {
    echo "\nCleanup Complete! Removed {$cleaned} duplicate(s).\n";
}
echo "</pre>";
?>
