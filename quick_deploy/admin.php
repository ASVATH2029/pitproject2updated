<?php
require_once __DIR__ . '/session.php';
require_login();
session_write_close();

if (!is_admin()) {
    header('Location: dashboard.php');
    exit;
}

$username = get_username();
$role = 'Super Admin';

$users = [];
$items = scandir(PROJECT_DIR);
$total_storage = 0;
$total_users = 0;

foreach ($items as $item) {
    if ($item === '.' || $item === '..') continue;
    $path = PROJECT_DIR . '/' . $item;
    if (is_dir($path) && file_exists($path . '/.user')) {
        $data = json_decode(file_get_contents($path . '/.user'), true) ?: [];
        $size = dir_size($path);
        
        $users[] = [
            'username' => $item,
            'email' => $data['email'] ?? 'N/A',
            'role' => $data['role'] ?? 'collaborator',
            'created' => $data['created'] ?? 'Unknown',
            'size' => $size
        ];
        $total_storage += $size;
        $total_users++;
    }
}

function formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}

$system_quota = max(1, $total_users * UPLOAD_QUOTA);
$used_pct = min(100, round(($total_storage / $system_quota) * 100));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS — Admin Portal</title>
    <meta name="description" content="PITS Admin Portal">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="background.css">
    <style>
        :root {
            --bg-dark: #0a100a;
            --text-primary: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.65);
            --text-cream: #e8e0d0;
            --glass-bg: rgba(55, 70, 50, 0.30);
            --glass-border: rgba(255, 255, 255, 0.06);
            --glass-blur: 20px;
            --btn-bg: rgba(225, 218, 200, 0.85);
            --btn-text: #2a2a2a;
            --font-heading: 'Libre Baskerville', Georgia, serif;
            --font-label: 'DM Sans', 'Helvetica Neue', sans-serif;
            --radius-card: 16px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body { min-height: 100vh; color: var(--text-primary); font-family: var(--font-label); overflow-x: hidden; }

        .top-bar {
            position: fixed; top: 0; left: 0; right: 0; display: flex; justify-content: space-between; align-items: center;
            padding: 28px 44px; z-index: 100; font-family: var(--font-heading); background: rgba(10, 18, 10, 0.6); backdrop-filter: blur(15px); border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .top-bar a { color: var(--text-cream); text-decoration: none; margin-left: 20px; }
        .greeting { font-size: 1rem; color: #a0c88c; }

        .page-wrapper {
            position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center; padding: 120px 3vw 50px;
        }

        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; width: 100%; max-width: 1100px; margin-bottom: 2rem; }
        .stat-card {
            background: var(--glass-bg); backdrop-filter: blur(var(--glass-blur)); border: 1px solid var(--glass-border); border-radius: var(--radius-card);
            padding: 1.5rem; text-align: left; animation: cardFloat 0.6s both;
        }

        .stat-card .label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 0.5rem; }
        .stat-card .value { font-family: var(--font-heading); font-size: 1.8rem; color: var(--text-primary); }

        .glass-card {
            background: var(--glass-bg); backdrop-filter: blur(var(--glass-blur)); border: 1px solid var(--glass-border); border-radius: 16px;
            width: 100%; max-width: 1100px; padding: 2.5rem; animation: cardFloat 0.6s both; margin-bottom: 30px;
        }

        .dash-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .dash-header h2 { font-family: var(--font-heading); font-weight: 400; font-size: 1.8rem; }

        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 0 0 20px 0; }

        .user-list { width: 100%; display: flex; flex-direction: column; gap: 8px; }
        .user-row {
            display: flex; justify-content: space-between; align-items: center;
            background: rgba(45, 60, 40, 0.4); border: 1px solid rgba(255, 255, 255, 0.04); border-radius: 8px;
            padding: 12px 20px; transition: background 0.2s;
        }
        .user-row:hover { background: rgba(55, 75, 50, 0.6); }

        .user-info { display: flex; flex-direction: column; gap: 4px; }
        .user-name { font-size: 1.05rem; font-weight: 700; color: #e8e0d0; }
        .user-email { font-size: 0.8rem; color: var(--text-muted); }
        .user-meta { display: flex; gap: 15px; font-size: 0.8rem; color: #a0c88c; }
        .user-badge { background: rgba(160, 200, 140, 0.15); padding: 2px 6px; border-radius: 4px; }

        .btn-group { display: flex; gap: 10px; }
        .btn-action {
            background: rgba(255, 255, 255, 0.1); border: none; color: white; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-family: var(--font-label); transition: 0.2s;
        }
        .btn-action:hover { background: rgba(255, 255, 255, 0.2); }
        .btn-danger { background: rgba(231, 76, 60, 0.2); color: #ff9999; }
        .btn-danger:hover { background: rgba(231, 76, 60, 0.4); }

        @keyframes cardFloat { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Notification Toast */
        .toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); background: rgba(40,55,40,0.9); padding: 12px 24px; border-radius: 30px; color: white; font-size: 0.9rem; border: 1px solid rgba(160,200,140,0.3); opacity: 0; transition: opacity 0.3s; pointer-events: none; z-index: 1000;}
        .toast.show { opacity: 1; }

        @media(max-width:600px) {
            .top-bar {
                top: 12px;
                left: 50%;
                right: auto;
                transform: translateX(-50%);
                width: 90%;
                max-width: 400px;
                padding: 10px 20px;
                background: rgba(10, 18, 10, 0.85);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 50px;
                box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4), 0 0 0 1px rgba(255, 255, 255, 0.04);
                font-size: 0.85rem;
                justify-content: space-between;
                flex-wrap: wrap;
                flex-direction: column;
                gap: 10px;
            }
            .page-wrapper { padding: 130px 4vw 60px; }
            .stats-grid { grid-template-columns: 1fr; gap: 1rem; }
            .glass-card { padding: 20px 15px; border-radius: 12px; }
            .user-row { flex-direction: column; gap: 15px; text-align: center; }
            .btn-group { width: 100%; justify-content: space-between; }
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="greeting">COMMAND CENTER ⚡️ PITS</div>
        <div>
            <span style="color:var(--text-muted); font-size: 0.8rem; margin-right:15px;"><?= htmlspecialchars($username) ?> (<?= $role ?>)</span>
            <a href="dashboard.php" style="color:#a0c88c;">Personal Dashboard</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="page-wrapper">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Users</div>
                <div class="value"><?= $total_users ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Server Storage Used</div>
                <div class="value"><?= formatBytes($total_storage) ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Utilization</div>
                <div class="value"><?= $used_pct ?>%</div>
            </div>
        </div>

        <div class="glass-card">
            <div class="dash-header">
                <h2>User Directory</h2>
            </div>
            <hr class="divider">

            <div class="user-list">
                <?php foreach ($users as $u): ?>
                    <div class="user-row">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($u['username']) ?></span>
                            <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                            <div class="user-meta">
                                <?php if ($u['role'] === 'admin'): ?><span class="user-badge" style="background:rgba(230,150,150,0.15); color:#e69696;">Admin</span><?php endif; ?>
                                <span><?= formatBytes($u['size']) ?> used</span>
                                <span>Joined: <?= explode(' ', $u['created'])[0] ?></span>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button class="btn-action" onclick="window.open('dashboard.php?target=<?= urlencode($u['username']) ?>', '_blank')">Override Files</button>
                            <button class="btn-action btn-danger" onclick="deleteUser('<?= htmlspecialchars(addslashes($u['username'])) ?>')">Delete User</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($users)): ?>
                    <div style="text-align:center; padding: 40px; color: var(--text-muted);">No users found on the server.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="toast" class="toast">Action completed.</div>

    <script>
        function showToast(msg) {
            var t = document.getElementById('toast');
            t.innerText = msg;
            t.classList.add('show');
            setTimeout(function(){ t.classList.remove('show'); }, 3000);
        }

        function deleteUser(username) {
            if (!confirm('EXTREME DANGER: Are you absolutely sure you want to delete ' + username + ' and physically wipe ALL their uploaded files? This action is unrecoverable.')) return;
            
            fetch('delete_user.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ target: username })
            })
            .then(function(r){ return r.json(); })
            .then(function(data) {
                if(data.success) {
                    showToast('User ' + username + ' obliterated.');
                    setTimeout(function(){ window.location.reload(); }, 1500);
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(function(){ alert('Server error occurred while attempting to delete user.'); });
        }
    </script>
</body>
</html>
