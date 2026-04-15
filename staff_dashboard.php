<?php
require_once __DIR__ . '/session.php';
require_login();
session_write_close();

// Only staff and admin can access
if (!is_staff() && !is_admin()) {
    header('Location: dashboard.php');
    exit;
}

$username = get_username();
$role = is_admin() ? 'Admin' : 'Staff';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS — Staff Portal</title>
    <meta name="description" content="PITS Staff Document Request Portal">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&family=DM+Sans:wght@400;500;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="background.css">
    <style>
        /* ========== DESIGN TOKENS ========== */
        :root {
            --bg-dark: #0a100a;
            --text-primary: #ffffff;
            --text-muted: rgba(255, 255, 255, 0.65);
            --text-cream: #e8e0d0;
            --glass-bg: rgba(55, 70, 50, 0.30);
            --glass-border: rgba(255, 255, 255, 0.06);
            --glass-blur: 20px;
            --input-bg: rgba(25, 35, 22, 0.75);
            --input-border: rgba(255, 255, 255, 0.08);
            --btn-bg: rgba(225, 218, 200, 0.85);
            --btn-text: #2a2a2a;
            --font-heading: 'Libre Baskerville', Georgia, serif;
            --font-label: 'DM Sans', 'Helvetica Neue', sans-serif;
            --radius-card: 16px;
            --radius-inner: 12px;
            --radius-pill: 30px;
            --accent: rgba(160, 200, 140, 0.6);
            --accent-soft: rgba(160, 200, 140, 0.15);
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            min-height: 100vh;
            color: var(--text-primary);
            font-family: var(--font-label);
            overflow-x: hidden;
        }

        /* TOP BAR */
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
        .top-bar .brand { font-size: 1rem; font-weight: 400; transition: opacity 0.3s ease; }
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

        .top-bar.scrolled {
            top: 12px; left: 50%; right: auto;
            transform: translateX(-50%);
            width: auto; max-width: 700px;
            padding: 12px 28px;
            background: rgba(10, 18, 10, 0.85);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.4), 0 0 0 1px rgba(255,255,255,0.04);
            font-size: 0.85rem; gap: 14px;
            animation: pillFloat 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .top-bar.scrolled .brand { font-size: 0.82rem; }
        .top-bar.scrolled .nav-btn { padding: 6px 14px; font-size: 0.78rem; line-height: 1; }

        @keyframes topSlide { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes pillFloat {
            from { opacity: 0; transform: translateX(-50%) translateY(-15px) scale(0.9); filter: blur(4px); }
            to   { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); filter: blur(0); }
        }

        /* LAYOUT */
        .page-wrapper {
            position: relative; z-index: 1;
            display: flex; flex-direction: column; align-items: center;
            padding: 120px 3vw 50px;
        }

        /* STATS GRID */
        .stats-grid {
            display: grid; grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem; width: 100%; max-width: 1100px; margin-bottom: 2rem;
        }
        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            padding: 1.5rem; text-align: left;
            animation: cardFloat 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            transition: transform 0.35s, box-shadow 0.35s;
        }
        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.12s; }
        .stat-card:nth-child(3) { animation-delay: 0.19s; }
        .stat-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.3); }
        .stat-card .label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1.5px; color: var(--text-muted); margin-bottom: 0.5rem; }
        .stat-card .value { font-family: var(--font-heading); font-size: 1.8rem; color: var(--text-primary); }

        /* GLASS CARD */
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(var(--glass-blur));
            -webkit-backdrop-filter: blur(var(--glass-blur));
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            width: 100%; max-width: 1100px; padding: 2.5rem;
            animation: cardFloat 0.6s cubic-bezier(0.16, 1, 0.3, 1) both;
            margin-bottom: 2rem;
        }

        @keyframes cardFloat {
            from { opacity: 0; transform: translateY(40px) scale(0.95); filter: blur(4px); }
            to   { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); }
        }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .card-header h2 { font-family: var(--font-heading); font-weight: 400; font-size: 1.8rem; }
        .divider { border: none; border-top: 1px solid rgba(255,255,255,0.08); margin: 0 0 20px 0; }

        /* BUTTONS */
        .btn-primary {
            background: var(--btn-bg); color: var(--btn-text);
            border: none; border-radius: var(--radius-pill);
            font-family: var(--font-label); font-weight: 600; font-size: 0.85rem;
            padding: 10px 24px; cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 24px rgba(0,0,0,0.35); }
        .btn-primary:active { transform: scale(0.96); }

        .btn-tool {
            background: rgba(255,255,255,0.05); border: 1px solid var(--glass-border);
            color: var(--text-primary); padding: 8px 16px;
            border-radius: var(--radius-inner);
            font-family: var(--font-label); font-size: 0.82rem;
            cursor: pointer; display: flex; align-items: center; gap: 8px;
            transition: all 0.25s;
        }
        .btn-tool:hover { background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); }
        .btn-tool svg { width: 16px; height: 16px; fill: none; stroke: currentColor; stroke-width: 2; }

        .btn-danger { background: rgba(231,76,60,0.2); color: #ff9999; border: 1px solid rgba(231,76,60,0.2); }
        .btn-danger:hover { background: rgba(231,76,60,0.4); }

        .btn-sm { padding: 6px 14px; font-size: 0.78rem; border-radius: 8px; }

        /* TILES GRID */
        .tiles-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(min(320px, 100%), 1fr));
            gap: 1.2rem;
        }

        .request-tile {
            background: rgba(15, 22, 14, 0.5);
            border: 1px solid var(--input-border);
            border-radius: var(--radius-inner);
            padding: 1.5rem;
            transition: border-color 0.3s, transform 0.3s;
            animation: tileSlide 0.5s cubic-bezier(0.16, 1, 0.3, 1) both;
            cursor: default;
        }
        .request-tile:hover { border-color: rgba(160,200,140,0.25); transform: translateY(-2px); }

        @keyframes tileSlide { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

        .tile-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.8rem; }
        .tile-title { font-family: var(--font-heading); font-size: 1.1rem; font-weight: 400; color: var(--text-cream); }
        .tile-date { font-size: 0.7rem; color: var(--text-muted); white-space: nowrap; }
        .tile-desc { font-size: 0.82rem; color: var(--text-muted); line-height: 1.5; margin-bottom: 1rem; }
        .tile-meta { display: flex; gap: 12px; font-size: 0.75rem; color: var(--text-muted); margin-bottom: 1rem; flex-wrap: wrap; }
        .tile-badge {
            background: var(--accent-soft); color: rgba(160,200,140,0.9);
            padding: 3px 10px; border-radius: 20px; font-size: 0.72rem;
        }
        .tile-badge.files { background: rgba(144,202,249,0.15); color: #90caf9; }
        .tile-badge.students { background: rgba(255,213,79,0.15); color: #ffd54f; }
        .tile-actions { display: flex; gap: 8px; flex-wrap: wrap; }

        .empty-state {
            text-align: center; padding: 60px 20px; color: var(--text-muted);
        }
        .empty-state svg { width: 48px; height: 48px; stroke: var(--text-muted); fill: none; margin-bottom: 16px; opacity: 0.4; }
        .empty-state p { font-size: 0.9rem; }

        /* FILE VIEWER */
        .file-viewer { display: none; }
        .file-viewer.active { display: block; }

        .file-viewer-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
        .file-viewer-header h3 { font-family: var(--font-heading); font-weight: 400; font-size: 1.2rem; }

        .file-row {
            display: flex; align-items: center;
            padding: 14px 20px; border-bottom: 1px solid rgba(255,255,255,0.04);
            transition: background 0.15s; animation: rowSlide 0.4s cubic-bezier(0.16, 1, 0.3, 1) both;
        }
        .file-row:last-child { border-bottom: none; }
        .file-row:hover { background: rgba(160,200,140,0.04); box-shadow: inset 2px 0 0 var(--accent); }
        @keyframes rowSlide { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

        .file-student { font-size: 0.78rem; color: #a0c88c; min-width: 100px; margin-right: 12px; }
        .file-name { font-size: 0.88rem; color: var(--text-primary); flex: 1; word-break: break-all; }
        .file-size { font-size: 0.72rem; color: var(--text-muted); margin-left: 16px; white-space: nowrap; }
        .file-actions { display: flex; gap: 8px; margin-left: 12px; }

        .file-list-box {
            background: rgba(15, 22, 14, 0.45);
            border: 1px solid var(--input-border);
            border-radius: 10px; min-height: 100px;
            overflow-y: auto; max-height: 400px;
        }
        .file-list-box::-webkit-scrollbar { width: 5px; }
        .file-list-box::-webkit-scrollbar-track { background: transparent; }
        .file-list-box::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.12); border-radius: 3px; }

        /* MODAL */
        .modal-overlay {
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); backdrop-filter: blur(8px);
            z-index: 1000; display: none;
            justify-content: center; align-items: center;
            opacity: 0; transition: opacity 0.3s;
        }
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-overlay.active .modal-window { animation: modalPop 0.4s cubic-bezier(0.16, 1, 0.3, 1) both; }

        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.85) translateY(20px); filter: blur(8px); }
            to   { opacity: 1; transform: scale(1) translateY(0); filter: blur(0); }
        }

        .modal-window {
            background: rgba(15, 25, 15, 0.95);
            border: 1px solid var(--glass-border);
            border-radius: var(--radius-card);
            padding: 2rem; width: 90%; max-width: 520px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .modal-window h2 { font-family: var(--font-heading); font-size: 1.5rem; margin-bottom: 1.5rem; }

        .input-group { margin-bottom: 1.25rem; text-align: left; }
        .input-group label {
            display: block; font-family: var(--font-label);
            font-size: 0.8rem; font-weight: 500; letter-spacing: 2.2px;
            text-transform: uppercase; color: var(--text-cream); margin-bottom: 10px;
        }
        .input-group input, .input-group textarea {
            width: 100%; padding: 14px 18px;
            background: var(--input-bg); border: 1px solid var(--input-border);
            border-radius: var(--radius-inner);
            font-family: var(--font-label); font-size: 0.92rem;
            color: var(--text-primary); outline: none;
            transition: border-color 0.25s, background 0.25s;
        }
        .input-group textarea { resize: vertical; min-height: 80px; }
        .input-group input:focus, .input-group textarea:focus {
            border-color: rgba(160,200,140,0.4);
            background: rgba(25, 35, 22, 0.9);
            box-shadow: 0 0 0 3px rgba(160,200,140,0.08);
        }
        .input-hint { font-size: 0.72rem; color: var(--text-muted); margin-top: 6px; }

        .toggle-row { display: flex; align-items: center; gap: 12px; margin-bottom: 1rem; }
        .toggle-switch {
            position: relative; width: 42px; height: 22px;
            background: rgba(255,255,255,0.1); border-radius: 11px;
            cursor: pointer; transition: background 0.3s;
        }
        .toggle-switch.active { background: var(--accent); }
        .toggle-switch::after {
            content: ''; position: absolute; width: 18px; height: 18px;
            background: white; border-radius: 50%; top: 2px; left: 2px;
            transition: transform 0.3s;
        }
        .toggle-switch.active::after { transform: translateX(20px); }
        .toggle-label { font-size: 0.85rem; color: var(--text-cream); }

        /* TOAST */
        .toast {
            position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%);
            background: rgba(40,55,40,0.9); padding: 12px 24px; border-radius: 30px;
            color: white; font-size: 0.9rem; border: 1px solid rgba(160,200,140,0.3);
            opacity: 0; transition: opacity 0.3s; pointer-events: none; z-index: 1000;
        }
        .toast.show { opacity: 1; }

        /* RESPONSIVE */
        @media(max-width:768px) {
            .top-bar { padding: 16px 20px; }
            .stats-grid { grid-template-columns: 1fr; }
            .glass-card { padding: 1.5rem; }
            .tiles-grid { grid-template-columns: 1fr; }
        }

        @media(max-width:600px) {
            .top-bar {
                top: 12px; left: 50%; right: auto; transform: translateX(-50%);
                width: 90%; max-width: 400px; padding: 10px 20px;
                background: rgba(10,18,10,0.85); backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border: 1px solid rgba(255,255,255,0.08); border-radius: 50px;
                box-shadow: 0 8px 32px rgba(0,0,0,0.4);
                font-size: 0.85rem; flex-wrap: wrap; gap: 8px;
            }
            .page-wrapper { padding: 100px 4vw 60px; }
            .glass-card { padding: 1.2rem; border-radius: 12px; }
            .card-header h2 { font-size: 1.3rem; }
            .card-header { flex-direction: column; gap: 12px; align-items: flex-start; }
            .file-row { flex-wrap: wrap; padding: 12px 14px; }
            .file-student { min-width: auto; width: 100%; margin-bottom: 4px; }
        }
    </style>
</head>

<body>
    <div class="top-bar" id="topBar">
        <span id="staffDate" style="font-size:0.88rem; color:var(--text-muted);"></span>
        <div class="brand">Pitsnas - Staff</div>
        <div style="display:flex; align-items:center; gap:10px;">
            <?php if (is_admin()): ?>
                <a href="admin.php" class="nav-btn">Admin Panel</a>
            <?php endif; ?>
            <a href="dashboard.php" class="nav-btn">My Files</a>
            <a href="logout.php" class="nav-btn">Logout</a>
        </div>
        <span id="staffTime" style="font-size:0.88rem; color:var(--text-muted);"></span>
    </div>

    <div class="page-wrapper">

        <!-- STATS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Active Requests</div>
                <div class="value" id="statRequests">—</div>
            </div>
            <div class="stat-card">
                <div class="label">Files Received</div>
                <div class="value" id="statFiles">—</div>
            </div>
            <div class="stat-card">
                <div class="label">Students Responded</div>
                <div class="value" id="statStudents">—</div>
            </div>
        </div>

        <!-- REQUESTS CARD -->
        <div class="glass-card">
            <div class="card-header">
                <h2>Document Requests</h2>
                <button class="btn-primary" onclick="openCreateModal()">
                    + New Request
                </button>
            </div>
            <hr class="divider">
            <div id="tilesContainer">
                <div class="empty-state">
                    <svg viewBox="0 0 24 24" stroke-width="1.5"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>
                    <p>Loading requests...</p>
                </div>
            </div>
        </div>

        <!-- FILE VIEWER CARD -->
        <div class="glass-card file-viewer" id="fileViewerCard">
            <div class="file-viewer-header">
                <h3 id="fileViewerTitle">Files for: —</h3>
                <button class="btn-tool" onclick="closeFileViewer()">
                    <svg viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12"/></svg>
                    Close
                </button>
            </div>
            <hr class="divider">
            <div class="file-list-box" id="fileViewerList">
                <div class="empty-state"><p>No files shared yet.</p></div>
            </div>
        </div>
    </div>

    <!-- CREATE REQUEST MODAL -->
    <div id="createModal" class="modal-overlay" onclick="closeModal()">
        <div class="modal-window" onclick="event.stopPropagation()">
            <h2>New Document Request</h2>
            <div class="input-group">
                <label>Title</label>
                <input type="text" id="reqTitle" placeholder="e.g. Lab Report — Week 5" maxlength="200">
            </div>
            <div class="input-group">
                <label>Description</label>
                <textarea id="reqDesc" placeholder="Describe what you need students to submit..." maxlength="2000"></textarea>
            </div>
            <div class="toggle-row">
                <div class="toggle-switch active" id="allStudentsToggle" onclick="toggleTargetAll()"></div>
                <span class="toggle-label">All Students</span>
            </div>
            <div class="input-group" id="targetGroup" style="display:none;">
                <label>Target Students</label>
                <input type="text" id="reqTargets" placeholder="username1, username2, ...">
                <div class="input-hint">Comma-separated usernames. Only these students will see the request.</div>
            </div>
            <div style="display:flex;gap:1rem;margin-top:1.5rem;">
                <button class="btn-primary" onclick="submitRequest()">Create Request</button>
                <button class="btn-tool" onclick="closeModal()">Cancel</button>
            </div>
        </div>
    </div>

    <div id="toast" class="toast">Action completed.</div>

    <script>
        // ── Helpers ───────────────────────────────────────────────────────────
        function fmtBytes(b) { if (b < 1024) return b + ' B'; if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'; return (b / 1048576).toFixed(1) + ' MB'; }
        function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
        function fmtDate(ts) { var d = new Date(ts * 1000); return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }); }

        function showToast(msg) {
            var t = document.getElementById('toast');
            t.textContent = msg; t.classList.add('show');
            setTimeout(function(){ t.classList.remove('show'); }, 3000);
        }

        // ── Data ──────────────────────────────────────────────────────────────
        var allRequests = [];
        var currentViewId = '';

        // ── Load Requests ─────────────────────────────────────────────────────
        function loadRequests() {
            fetch('staff_api.php?action=list_requests')
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.error) { showToast(data.error); return; }
                    allRequests = data.requests || [];
                    renderStats();
                    renderTiles();
                })
                .catch(function() { showToast('Failed to load requests'); });
        }

        function renderStats() {
            document.getElementById('statRequests').textContent = allRequests.length;
            var totalFiles = 0, allStudents = [];
            allRequests.forEach(function(r) {
                totalFiles += r.file_count || 0;
                (r.students_responded || []).forEach(function(s) {
                    if (allStudents.indexOf(s) === -1) allStudents.push(s);
                });
            });
            document.getElementById('statFiles').textContent = totalFiles;
            document.getElementById('statStudents').textContent = allStudents.length;
        }

        function renderTiles() {
            var container = document.getElementById('tilesContainer');
            if (!allRequests.length) {
                container.innerHTML = '<div class="empty-state">' +
                    '<svg viewBox="0 0 24 24" stroke-width="1.5"><path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5l5 5v11a2 2 0 01-2 2z"/></svg>' +
                    '<p>No requests yet. Click "+ New Request" to create one.</p></div>';
                return;
            }
            var html = '<div class="tiles-grid">';
            allRequests.forEach(function(r, i) {
                var targets = r.target_students;
                var targetLabel = targets === 'all' ? 'All Students' : (Array.isArray(targets) ? targets.length + ' student(s)' : 'All Students');
                html += '<div class="request-tile" style="animation-delay:' + (i * 0.08).toFixed(2) + 's">';
                html += '<div class="tile-header">';
                html += '<span class="tile-title">' + esc(r.title) + '</span>';
                html += '<span class="tile-date">' + fmtDate(r.created_at) + '</span>';
                html += '</div>';
                if (r.description) html += '<div class="tile-desc">' + esc(r.description) + '</div>';
                html += '<div class="tile-meta">';
                html += '<span class="tile-badge">' + targetLabel + '</span>';
                html += '<span class="tile-badge files">' + (r.file_count || 0) + ' file(s)</span>';
                html += '<span class="tile-badge students">' + (r.students_responded || []).length + ' responded</span>';
                html += '</div>';
                html += '<div class="tile-actions">';
                html += '<button class="btn-tool btn-sm" onclick="viewFiles(\'' + esc(r.id) + '\', \'' + esc(r.title) + '\')"><svg viewBox="0 0 24 24"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>View Files</button>';
                html += '<button class="btn-tool btn-sm btn-danger" onclick="deleteRequest(\'' + esc(r.id) + '\')"><svg viewBox="0 0 24 24"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>Remove</button>';
                html += '</div></div>';
            });
            html += '</div>';
            container.innerHTML = html;
        }

        // ── File Viewer ───────────────────────────────────────────────────────
        function viewFiles(requestId, title) {
            currentViewId = requestId;
            document.getElementById('fileViewerTitle').textContent = 'Files for: ' + title;
            document.getElementById('fileViewerCard').classList.add('active');
            document.getElementById('fileViewerCard').style.display = 'block';
            document.getElementById('fileViewerCard').scrollIntoView({ behavior: 'smooth' });

            fetch('staff_api.php?action=list_files&request_id=' + encodeURIComponent(requestId))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.error) { showToast(data.error); return; }
                    var list = document.getElementById('fileViewerList');
                    var files = data.files || [];
                    if (!files.length) {
                        list.innerHTML = '<div class="empty-state"><p>No files shared yet for this request.</p></div>';
                        return;
                    }
                    var html = '';
                    files.forEach(function(f, i) {
                        html += '<div class="file-row" style="animation-delay:' + (i * 0.04).toFixed(2) + 's">';
                        html += '<span class="file-student">' + esc(f.student) + '</span>';
                        html += '<span class="file-name">' + esc(f.display_name) + '</span>';
                        html += '<span class="file-size">' + fmtBytes(f.size) + '</span>';
                        html += '<div class="file-actions">';
                        html += '<button class="btn-tool btn-sm" onclick="downloadFile(\'' + esc(currentViewId) + '\', \'' + esc(f.filename) + '\')">Download</button>';
                        html += '<button class="btn-tool btn-sm btn-danger" onclick="deleteFile(\'' + esc(currentViewId) + '\', \'' + esc(f.filename) + '\')">Delete</button>';
                        html += '</div></div>';
                    });
                    list.innerHTML = html;
                })
                .catch(function() { showToast('Failed to load files'); });
        }

        function closeFileViewer() {
            document.getElementById('fileViewerCard').style.display = 'none';
            currentViewId = '';
        }

        function downloadFile(reqId, filename) {
            window.open('staff_api.php?action=download_file&request_id=' + encodeURIComponent(reqId) + '&file=' + encodeURIComponent(filename), '_blank');
        }

        function deleteFile(reqId, filename) {
            if (!confirm('Delete this file? This cannot be undone.')) return;
            fetch('staff_api.php?action=delete_file', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: reqId, filename: filename })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) { showToast('File deleted'); viewFiles(reqId, document.getElementById('fileViewerTitle').textContent.replace('Files for: ', '')); loadRequests(); }
                else { showToast(d.error || 'Delete failed'); }
            })
            .catch(function() { showToast('Delete failed'); });
        }

        // ── Create Request ────────────────────────────────────────────────────
        var targetAll = true;

        function toggleTargetAll() {
            targetAll = !targetAll;
            var toggle = document.getElementById('allStudentsToggle');
            toggle.classList.toggle('active', targetAll);
            document.getElementById('targetGroup').style.display = targetAll ? 'none' : 'block';
        }

        function openCreateModal() {
            document.getElementById('reqTitle').value = '';
            document.getElementById('reqDesc').value = '';
            document.getElementById('reqTargets').value = '';
            targetAll = true;
            document.getElementById('allStudentsToggle').classList.add('active');
            document.getElementById('targetGroup').style.display = 'none';
            document.getElementById('createModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('createModal').classList.remove('active');
        }

        function submitRequest() {
            var title = document.getElementById('reqTitle').value.trim();
            var desc = document.getElementById('reqDesc').value.trim();
            var targets = targetAll ? 'all' : document.getElementById('reqTargets').value.trim();

            if (!title) { showToast('Please enter a title'); return; }

            fetch('staff_api.php?action=create_request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ title: title, description: desc, target_students: targets })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) {
                    closeModal();
                    showToast('Request created successfully');
                    loadRequests();
                } else {
                    showToast(d.error || 'Failed to create request');
                }
            })
            .catch(function() { showToast('Failed to create request'); });
        }

        // ── Delete Request ────────────────────────────────────────────────────
        function deleteRequest(id) {
            if (!confirm('Remove this request and all its shared files? This cannot be undone.')) return;
            fetch('staff_api.php?action=delete_request', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ request_id: id })
            })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.success) { showToast('Request removed'); closeFileViewer(); loadRequests(); }
                else { showToast(d.error || 'Failed to remove request'); }
            })
            .catch(function() { showToast('Failed to remove request'); });
        }

        // ── Init ────────────────────────────────────────────────────────────────────
        loadRequests();

        // ── Clock ─────────────────────────────────────────────────────────────
        function updateStaffClock() {
            var now = new Date();
            var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
            var dateEl = document.getElementById('staffDate');
            var timeEl = document.getElementById('staffTime');
            if (dateEl) dateEl.textContent = months[now.getMonth()] + ' ' + now.getDate() + ', ' + now.getFullYear();
            if (timeEl) {
                var h = now.getHours(), m = now.getMinutes(), ap = h >= 12 ? 'PM' : 'AM';
                h = h % 12 || 12;
                timeEl.textContent = h + ':' + (m < 10 ? '0' : '') + m + ' ' + ap;
            }
        }
        updateStaffClock(); setInterval(updateStaffClock, 1000);

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
