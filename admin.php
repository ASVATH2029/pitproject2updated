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
        $personal_size = dir_size($path);
        $shared_size = shared_dir_size($item);
        $size = $personal_size + $shared_size;

        $users[] = [
            'username' => $item,
            'email' => $data['email'] ?? 'N/A',
            'role' => $data['role'] ?? 'collaborator',
            'created' => $data['created'] ?? 'Unknown',
            'size' => $size,
            'personal_size' => $personal_size,
            'shared_size' => $shared_size
        ];
        $total_storage += $size;
        $total_users++;
    }
}

// Split into students (collaborators) and staff so staff never mix into
// the student roster — they're managed in their own directory instead.
$student_users = array_values(array_filter($users, function ($u) {
    return $u['role'] !== 'admin' && !is_staff_user($u['username']);
}));
$staff_users = array_values(array_filter($users, function ($u) {
    return $u['role'] !== 'admin' && is_staff_user($u['username']);
}));

function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow]; 
}

// "Utilization" is real disk usage on the volume backing PROJECT_DIR, not a
// per-student-quota projection — otherwise a single registered user would
// misleadingly show near-full utilization against their own 200MB cap.
$disk_total = @disk_total_space(PROJECT_DIR) ?: 1;
$disk_free = @disk_free_space(PROJECT_DIR) ?: 0;
$disk_used = $disk_total - $disk_free;
$used_pct = min(100, round(($disk_used / $disk_total) * 100));
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
            position: fixed; top: 0; left: 0; right: 0;
            display: flex; justify-content: space-between; align-items: center;
            padding: 28px 44px; z-index: 100;
            font-family: var(--font-heading);
            font-size: 1rem;
            color: var(--text-cream);
            transition: all 0.45s cubic-bezier(0.16, 1, 0.3, 1);
            animation: topSlide 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .top-bar .greeting { font-size: 1rem; font-weight: 400; transition: opacity 0.3s ease; }
        .top-bar .nav-btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 6px;
            padding: 8px 18px; background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.10); border-radius: 30px;
            color: var(--text-cream); text-decoration: none;
            font-family: var(--font-heading); font-size: 0.82rem; font-weight: 400;
            line-height: 1; white-space: nowrap;
            backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            transition: all 0.25s ease;
        }
        .top-bar .nav-btn:hover { background: rgba(255,255,255,0.12); border-color: rgba(255,255,255,0.20); transform: translateY(-1px); }
        .top-bar .nav-btn-logout { background: rgba(200,90,80,0.14); border-color: rgba(200,90,80,0.25); }
        .top-bar .nav-btn-logout:hover { background: rgba(200,90,80,0.24); border-color: rgba(200,90,80,0.40); }
        .top-bar .nav-links { display: flex; align-items: center; gap: 10px; }

        .top-bar.scrolled {
            top: 12px; left: 50%; right: auto;
            transform: translateX(-50%);
            width: auto; max-width: 720px;
            padding: 12px 28px;
            background: rgba(10, 18, 10, 0.85);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.04);
            font-size: 0.85rem; gap: 14px;
            animation: pillFloat 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .top-bar.scrolled .greeting { font-size: 0.82rem; }
        .top-bar.scrolled .nav-btn { padding: 6px 14px; font-size: 0.78rem; line-height: 1; }

        @keyframes topSlide { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pillFloat {
            from { opacity: 0; transform: translateX(-50%) translateY(-15px) scale(0.9); filter: blur(4px); }
            to   { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); filter: blur(0); }
        }

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
        .stat-card .stat-subvalue { font-size: 0.72rem; color: var(--text-muted); margin-top: 4px; }

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

        /* Shared Folder Viewer Modal (read-only) */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
            z-index: 1000; display: none;
            justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-window {
            background: rgba(15, 25, 15, 0.95);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            padding: 2rem; width: 90%; max-width: 520px;
            max-height: 82vh; overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .shared-file-row {
            display: flex; justify-content: space-between; align-items: center;
            gap: 10px; padding: 10px 14px; border-radius: 8px;
            background: rgba(255,255,255,0.03); margin-bottom: 8px;
        }
        .shared-file-name { font-size: 0.85rem; color: var(--text-cream); word-break: break-all; }
        .shared-file-size { font-size: 0.75rem; color: var(--text-muted); white-space: nowrap; }

        @media(max-width:768px) {
            .top-bar { padding: 16px 20px; }
            .top-bar .topbar-meta { display: none; }
        }

        @media(max-width:600px) {
            .top-bar {
                top: 0;
                left: 0;
                right: 0;
                transform: none;
                width: 100%;
                max-width: none;
                padding: 12px 18px;
                background: rgba(10, 18, 10, 0.9);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: none;
                border-bottom: 1px solid rgba(255, 255, 255, 0.08);
                border-radius: 0;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4);
                font-size: 0.85rem;
                justify-content: space-between;
                gap: 8px;
            }
            .top-bar .greeting { font-size: 0.9rem; white-space: nowrap; }
            .top-bar .nav-links { gap: 6px; flex-shrink: 0; }
            .top-bar .nav-btn { padding: 7px 11px; font-size: 0.72rem; }
            .top-bar.scrolled {
                top: 10px; left: 10px; right: 10px; transform: none;
                width: calc(100% - 20px); max-width: none;
                border-radius: 50px; border: 1px solid rgba(255, 255, 255, 0.08);
            }
            .page-wrapper { padding: 90px 4vw 60px; }
            .stats-grid { grid-template-columns: 1fr; gap: 1rem; }
            .glass-card { padding: 20px 15px; border-radius: 12px; }
            .user-row { flex-direction: column; gap: 15px; text-align: center; }
            .btn-group { width: 100%; justify-content: space-between; flex-wrap: wrap; }
        }

        @media(max-width:420px) {
            .top-bar { padding: 10px 12px; }
            .top-bar .greeting { font-size: 0.8rem; }
            .top-bar .nav-btn { padding: 6px 9px; font-size: 0.68rem; }
        }

        /* BULK UPLOAD CARD */
        .bulk-upload-area {
            background: rgba(15, 22, 14, 0.45);
            border: 2px dashed rgba(255,255,255,0.1);
            border-radius: 12px; padding: 2rem; text-align: center;
            transition: border-color 0.3s, background 0.3s;
            cursor: pointer;
        }
        .bulk-upload-area:hover { border-color: rgba(160,200,140,0.3); background: rgba(15, 22, 14, 0.6); }
        .bulk-upload-area p { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 0.5rem; }
        .bulk-upload-area .hint { font-size: 0.72rem; color: rgba(255,255,255,0.35); }
        .staff-list-display { margin-top: 1rem; font-size: 0.82rem; color: var(--text-muted); }
        .staff-list-display .staff-chip {
            display: inline-block; background: rgba(160,200,140,0.12); color: #a0c88c;
            padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; margin: 3px 4px;
        }
        .staff-list-display .staff-chip.pending {
            background: rgba(230,180,90,0.14); color: #e6b45a;
        }
    </style>
</head>
<body>
    <div class="top-bar" id="topBar">
        <span id="adminDate" class="topbar-meta" style="font-size:0.88rem; color:var(--text-cream); opacity:0.85;"></span>
        <span class="greeting">Pitsnas - Admin</span>
        <nav class="nav-links">
            <a href="staff_dashboard.php" class="nav-btn">Staff Portal</a>
            <a href="dashboard.php" class="nav-btn">My Files</a>
            <a href="logout.php" class="nav-btn nav-btn-logout">Logout</a>
        </nav>
        <span id="adminTime" class="topbar-meta" style="font-size:0.88rem; color:var(--text-cream); opacity:0.85;"></span>
    </div>

    <div class="page-wrapper">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Users</div>
                <div class="value"><?= $total_users ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Disk Space Used</div>
                <div class="value"><?= formatBytes($disk_used) ?></div>
                <div class="stat-subvalue">of <?= formatBytes($disk_total) ?> total (<?= formatBytes($total_storage) ?> in user files)</div>
            </div>
            <div class="stat-card">
                <div class="label">Utilization</div>
                <div class="value"><?= $used_pct ?>%</div>
                <div class="stat-subvalue">of entire hard disk</div>
            </div>
        </div>

        <div class="glass-card">
            <div class="dash-header">
                <h2>Student Directory</h2>
                <div style="position:relative;">
                    <input type="text" id="userSearch" placeholder="Search students..." oninput="filterUsers()"
                        style="padding:10px 18px 10px 38px; background:rgba(25,35,22,0.75); border:1px solid rgba(255,255,255,0.08); border-radius:30px; color:var(--text-cream); font-family:var(--font-heading); font-size:0.85rem; outline:none; width:220px; transition:border-color 0.25s, width 0.3s;"
                        onfocus="this.style.borderColor='rgba(160,200,140,0.4)'; this.style.width='280px';"
                        onblur="this.style.borderColor='rgba(255,255,255,0.08)'; this.style.width='220px';">
                    <svg style="position:absolute; left:12px; top:50%; transform:translateY(-50%); width:16px; height:16px; stroke:var(--text-muted); fill:none; stroke-width:2; pointer-events:none;" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                </div>
            </div>
            <hr class="divider">
            <div id="noResults" style="display:none; text-align:center; padding:30px 0; color:var(--text-muted); font-family:var(--font-heading); font-size:0.9rem;">No users found.</div>

            <div class="user-list" id="studentList">
                <?php foreach ($student_users as $u): ?>
                    <div class="user-row">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($u['username']) ?></span>
                            <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                            <div class="user-meta">
                                <span><?= formatBytes($u['size']) ?> used <span style="color:var(--text-muted); font-size:0.72rem;">(<?= formatBytes($u['personal_size']) ?> personal &middot; <?= formatBytes($u['shared_size']) ?> shared)</span></span>
                                <span>Joined: <?= explode(' ', $u['created'])[0] ?></span>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button class="btn-action" onclick="viewSharedFolder('<?= htmlspecialchars(addslashes($u['username'])) ?>')">View Shared Folder</button>
                            <button class="btn-action btn-danger" onclick="deleteUser('<?= htmlspecialchars(addslashes($u['username'])) ?>')">Delete User</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($student_users)): ?>
                    <div style="text-align:center; padding: 40px; color: var(--text-muted);">No students found on the server.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- STAFF DIRECTORY CARD (separate from students) -->
        <div class="glass-card">
            <div class="dash-header">
                <h2>Staff Directory</h2>
            </div>
            <hr class="divider">

            <div class="user-list">
                <?php foreach ($staff_users as $u): ?>
                    <div class="user-row">
                        <div class="user-info">
                            <span class="user-name"><?= htmlspecialchars($u['username']) ?></span>
                            <span class="user-email"><?= htmlspecialchars($u['email']) ?></span>
                            <div class="user-meta">
                                <span class="user-badge" style="background:rgba(160,200,140,0.15); color:#a0c88c;">Staff</span>
                                <span><?= formatBytes($u['size']) ?> used <span style="color:var(--text-muted); font-size:0.72rem;">(<?= formatBytes($u['personal_size']) ?> personal &middot; <?= formatBytes($u['shared_size']) ?> shared)</span></span>
                                <span>Joined: <?= explode(' ', $u['created'])[0] ?></span>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button class="btn-action" onclick="viewSharedFolder('<?= htmlspecialchars(addslashes($u['username'])) ?>')">View Shared Folder</button>
                            <button class="btn-action btn-danger" onclick="deleteUser('<?= htmlspecialchars(addslashes($u['username'])) ?>')">Delete User</button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($staff_users)): ?>
                    <div style="text-align:center; padding: 40px; color: var(--text-muted);">No staff accounts yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- STAFF MANAGEMENT CARD -->
        <div class="glass-card">
            <div class="dash-header">
                <h2>Staff Management</h2>
            </div>
            <hr class="divider">

            <div class="bulk-upload-area" onclick="triggerBulkUpload()">
                <p>Upload Staff Roster</p>
                <p class="hint">Drop a .txt or .csv file with one username per line. This file is the full staff roster — it REPLACES the current list. Usernames on it are staff (registered instantly if they already have an account, or automatically the moment they sign up); usernames left off lose staff access.</p>
                <input type="file" id="bulkFileInput" accept=".txt,.csv" style="display:none" onchange="handleBulkUpload(this)">
            </div>

            <?php
            $staff_list = get_staff_list();
            $registered_usernames = array_column($users, 'username');
            if (!empty($staff_list)):
            ?>
            <div class="staff-list-display">
                <strong style="color:var(--text-cream);">Staff Roster (<?= count($staff_list) ?>):</strong>
                <span class="hint" style="display:block; margin-bottom:8px;">Green = registered &amp; active staff. Amber = pre-approved, awaiting signup.</span>
                <?php foreach ($staff_list as $s): ?>
                    <?php $registered = in_array($s, $registered_usernames, true); ?>
                    <span class="staff-chip<?= $registered ? '' : ' pending' ?>" title="<?= $registered ? 'Registered' : 'Awaiting signup' ?>"><?= htmlspecialchars($s) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- SHARED FOLDER VIEWER (read-only: view/download only, no upload/delete/rename) -->
    <div id="sharedModal" class="modal-overlay" onclick="closeSharedModal()">
        <div class="modal-window" onclick="event.stopPropagation()" style="max-width:640px;">
            <div class="dash-header" style="margin-bottom:1rem;">
                <h2 id="sharedModalTitle" style="font-size:1.3rem;">Shared Folder</h2>
                <button class="btn-action" onclick="closeSharedModal()">Close</button>
            </div>
            <hr class="divider">
            <div id="sharedModalBody">
                <div style="text-align:center; padding:30px 0; color:var(--text-muted);">Loading…</div>
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

        function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
        function fmtBytes(b) { if (b < 1024) return b + ' B'; if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'; return (b / 1048576).toFixed(1) + ' MB'; }

        // ── Shared Folder Viewer (view/download only — no upload, delete, or
        // access to personal files; the "Override Files" concept is gone) ────
        function viewSharedFolder(username) {
            var modal = document.getElementById('sharedModal');
            var body = document.getElementById('sharedModalBody');
            document.getElementById('sharedModalTitle').textContent = 'Shared Folder — ' + username;
            body.innerHTML = '<div style="text-align:center; padding:30px 0; color:var(--text-muted);">Loading…</div>';
            modal.classList.add('active');

            fetch('shared_api.php?action=list&target=' + encodeURIComponent(username))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.error) { body.innerHTML = '<div style="text-align:center; padding:20px 0; color:var(--text-muted);">' + esc(data.error) + '</div>'; return; }
                    var files = data.files || [];
                    if (!files.length) {
                        body.innerHTML = '<div style="text-align:center; padding:20px 0; color:var(--text-muted);">No shared files for ' + esc(data.owner) + '.</div>';
                        return;
                    }
                    var html = '';
                    files.forEach(function(f) {
                        html += '<div class="shared-file-row">';
                        html += '<span class="shared-file-name">' + esc(f.name) + '</span>';
                        html += '<span class="shared-file-size">' + fmtBytes(f.size) + '</span>';
                        html += '<button class="btn-action" onclick="window.open(\'shared_api.php?action=download&target=' + encodeURIComponent(data.owner) + '&file=' + encodeURIComponent(f.name) + '\', \'_blank\')">Download</button>';
                        html += '</div>';
                    });
                    body.innerHTML = html;
                })
                .catch(function() { body.innerHTML = '<div style="text-align:center; padding:20px 0; color:var(--text-muted);">Failed to load shared folder.</div>'; });
        }

        function closeSharedModal() {
            document.getElementById('sharedModal').classList.remove('active');
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

        // ── Staff Management ──────────────────────────────────────────────────
        // Staff status is driven entirely by the uploaded roster (below) --
        // there is no manual promote/demote button. To remove someone's staff
        // access, re-upload a roster file that omits their username.
        function triggerBulkUpload() {
            document.getElementById('bulkFileInput').click();
        }

        function handleBulkUpload(input) {
            if (!input.files[0]) return;
            if (!confirm('This replaces the entire staff roster. Anyone currently staffed but not in this file will lose staff access. Continue?')) {
                input.value = '';
                return;
            }
            var fd = new FormData();
            fd.append('staff_file', input.files[0]);

            fetch('staff_manage.php?action=bulk_upload', {
                method: 'POST',
                body: fd
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast('Roster updated: +' + data.count + ' added, -' + data.removed + ' removed (' + data.total + ' total staff)');
                    setTimeout(function(){ window.location.reload(); }, 1500);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(function() { alert('Upload failed'); });
            input.value = '';
        }

        // ── User Search ──────────────────────────────────────────────────────
        function filterUsers() {
            var query = document.getElementById('userSearch').value.toLowerCase().trim();
            var rows = document.querySelectorAll('#studentList .user-row');
            var visible = 0;
            rows.forEach(function(row) {
                var name = row.querySelector('.user-name');
                var email = row.querySelector('.user-email');
                var text = (name ? name.textContent : '') + ' ' + (email ? email.textContent : '');
                if (text.toLowerCase().indexOf(query) !== -1) {
                    row.style.display = '';
                    visible++;
                } else {
                    row.style.display = 'none';
                }
            });
            document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
        }
    </script>
    <script>
        // ── Clock ─────────────────────────────────────────────────────────────
        function updateAdminClock() {
            var now = new Date();
            var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            var dateEl = document.getElementById('adminDate');
            var timeEl = document.getElementById('adminTime');
            if (dateEl) dateEl.textContent = months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();
            if (timeEl) {
                var h = now.getHours(), m = now.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                timeEl.textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
            }
        }
        updateAdminClock(); setInterval(updateAdminClock, 1000);

        // ── Scroll-triggered floating pill nav ─────────────────────────────
        (function(){
            var topBar = document.getElementById('topBar');
            if (!topBar) return;
            window.addEventListener('scroll', function() {
                if (window.scrollY > 60) { topBar.classList.add('scrolled'); }
                else { topBar.classList.remove('scrolled'); }
            });
        })();
    </script>
</body>
</html>
