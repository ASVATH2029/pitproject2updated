<?php
require_once __DIR__ . '/session.php';
require_login();
$username = get_username();
$role = is_admin() ? 'Admin' : 'Collaborator';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PITS - File Manager</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&family=Poppins:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <style>
        :root {
            --bg-color: #ffffff;
            --primary-color: #545454;
            --font-heading: 'Poppins', sans-serif;
            --font-label: 'DM Sans', sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--primary-color);
            font-family: var(--font-heading);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3rem;
            padding: 2rem;
            position: relative;
        }

        .logo-section {
            position: absolute;
            left: 2rem;
            top: 50%;
            transform: translateY(-50%);
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .file-manager-wrapper {
            width: 100%;
            max-width: 550px;
        }

        .user-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            font-family: var(--font-label);
            font-size: 0.85rem;
        }

        .user-info span {
            font-weight: 700;
        }

        .logout-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
        }

        .logout-link:hover {
            opacity: 0.7;
        }

        .quota-bar {
            width: 100%;
            height: 8px;
            background: #ddd;
            margin-bottom: 1rem;
            overflow: hidden;
        }

        .quota-fill {
            height: 100%;
            background: #545454;
            transition: width 0.3s;
        }

        .quota-text {
            font-family: var(--font-label);
            font-size: 0.7rem;
            margin-bottom: 0.8rem;
            color: #888;
        }

        .file-list-container {
            background-color: #BFBFBF;
            height: 320px;
            overflow-y: auto;
            border: none;
        }

        .file-list-container::-webkit-scrollbar {
            width: 8px;
        }

        .file-list-container::-webkit-scrollbar-track {
            background: #BFBFBF;
        }

        .file-list-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }

        .file-row {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            border-bottom: 1px solid #545454;
        }

        .file-row:last-child {
            border-bottom: none;
        }

        .file-checkbox {
            width: 18px;
            height: 18px;
            margin-right: 15px;
            cursor: pointer;
            border: 1px solid #545454;
            background: transparent;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            position: relative;
        }

        .file-checkbox:checked {
            background-color: #545454;
            border-color: #545454;
        }

        .file-checkbox:checked::after {
            content: '✓';
            position: absolute;
            color: white;
            font-size: 14px;
            font-weight: bold;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .file-name {
            font-family: var(--font-label);
            font-size: 1rem;
            color: #333;
            font-weight: 400;
            flex: 1;
        }

        .file-size {
            font-family: var(--font-label);
            font-size: 0.75rem;
            color: #777;
        }

        .action-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            gap: 1.5rem;
        }

        .btn-action {
            flex: 1;
            background-color: #A6A6A6;
            color: #444444;
            border: none;
            padding: 12px 0;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-action:hover {
            opacity: 0.8;
        }

        .empty-msg {
            padding: 40px 20px;
            text-align: center;
            font-family: var(--font-label);
            color: #666;
        }

        .status-msg {
            font-family: var(--font-label);
            font-size: 0.8rem;
            margin-top: 0.8rem;
            min-height: 1.2em;
        }

        .status-msg.error {
            color: #c0392b;
        }

        .status-msg.success {
            color: #27ae60;
        }

        @media (max-width: 768px) {
            .logo-section {
                position: static;
                transform: none;
                margin-bottom: 2rem;
            }

            .container {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="logo-section">
            <img src="pits logo.png" alt="PITS Logo" style="width: 120px; height: auto;">
        </div>
        <div class="file-manager-wrapper">
            <div class="user-bar">
                <div class="user-info">
                    <span>
                        <?php echo htmlspecialchars($username); ?>
                    </span> (
                    <?php echo $role; ?>)
                </div>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>
            <div class="quota-bar">
                <div class="quota-fill" id="quotaFill"></div>
            </div>
            <div class="quota-text" id="quotaText">Loading...</div>

            <div class="file-list-container" id="fileList">
                <div class="empty-msg">Loading files...</div>
            </div>

            <div class="action-buttons">
                <button class="btn-action" onclick="triggerUpload()">Upload</button>
                <button class="btn-action" onclick="deleteSelected()">Delete</button>
                <button class="btn-action" onclick="downloadSelected()">Download</button>
            </div>
            <div class="status-msg" id="statusMsg"></div>
            <input type="file" id="fileInput" style="display:none" onchange="uploadFile(this)">
        </div>
    </div>
    <script>
        function loadFiles() {
            fetch('file_manager.php')
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showStatus(data.error, 'error');
                        return;
                    }
                    var list = document.getElementById('fileList');
                    list.innerHTML = '';
                    if (data.files.length === 0) {
                        list.innerHTML = '<div class="empty-msg">No files found</div>';
                    } else {
                        data.files.forEach(function (f) {
                            var row = document.createElement('div');
                            row.className = 'file-row';
                            row.innerHTML = '<input type="checkbox" class="file-checkbox" data-name="' + escapeHtml(f.name) + '">'
                                + '<span class="file-name">' + escapeHtml(f.name) + '</span>'
                                + '<span class="file-size">' + formatSize(f.size) + '</span>';
                            list.appendChild(row);
                        });
                    }
                    document.getElementById('quotaFill').style.width = data.usage_percent + '%';
                    document.getElementById('quotaText').textContent = formatSize(data.usage) + ' / ' + formatSize(data.quota) + ' used (' + data.usage_percent + '%)';
                })
                .catch(function () { showStatus('Failed to load files', 'error'); });
        }

        function triggerUpload() {
            document.getElementById('fileInput').click();
        }

        function uploadFile(input) {
            if (!input.files[0]) return;
            var fd = new FormData();
            fd.append('file', input.files[0]);
            showStatus('Uploading...', '');
            fetch('upload.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (data.error) {
                        showStatus(data.error, 'error');
                    } else {
                        showStatus('Uploaded: ' + data.filename, 'success');
                        loadFiles();
                    }
                    input.value = '';
                })
                .catch(function () { showStatus('Upload failed', 'error'); });
        }

        function deleteSelected() {
            var checked = document.querySelectorAll('.file-checkbox:checked');
            if (checked.length === 0) { showStatus('Select files to delete', 'error'); return; }
            if (!confirm('Delete ' + checked.length + ' file(s)?')) return;
            var names = [];
            checked.forEach(function (cb) { names.push(cb.dataset.name); });
            fetch('delete.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ files: names })
            })
                .then(r => r.json())
                .then(data => {
                    if (data.deleted && data.deleted.length > 0) {
                        showStatus('Deleted ' + data.deleted.length + ' file(s)', 'success');
                    }
                    if (data.failed && data.failed.length > 0) {
                        showStatus('Failed to delete: ' + data.failed.join(', '), 'error');
                    }
                    loadFiles();
                })
                .catch(function () { showStatus('Delete failed', 'error'); });
        }

        function downloadSelected() {
            var checked = document.querySelectorAll('.file-checkbox:checked');
            if (checked.length === 0) { showStatus('Select a file to download', 'error'); return; }
            checked.forEach(function (cb) {
                window.open('download.php?file=' + encodeURIComponent(cb.dataset.name), '_blank');
            });
        }

        function formatSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
            return (bytes / 1024 / 1024).toFixed(1) + ' MB';
        }

        function escapeHtml(str) {
            var d = document.createElement('div');
            d.textContent = str;
            return d.innerHTML;
        }

        function showStatus(msg, type) {
            var el = document.getElementById('statusMsg');
            el.textContent = msg;
            el.className = 'status-msg' + (type ? ' ' + type : '');
        }

        loadFiles();
    </script>
</body>

</html>