# PITS Archival System — Deployment Record

**Document Version:** 1.0  
**Date:** 2026-02-28  
**Status:** Pre-Network Exposure  
**Author:** Aditya Giri  

---

## Table of Contents

1. [Environment Setup](#1-environment-setup)
2. [Project Migration](#2-project-migration)
3. [File Structure Finalization](#3-file-structure-finalization)
4. [Repository Setup](#4-repository-setup)
5. [Server Deployment Steps Executed](#5-server-deployment-steps-executed)
6. [Application Verification](#6-application-verification)
7. [Current Status](#7-current-status)

---

## 1. Environment Setup

A Debian-based virtual machine was provisioned as the production environment for the PITS Archival System.

| Component              | Detail                                  |
|------------------------|-----------------------------------------|
| Operating System       | Debian 12 (Bookworm)                    |
| Virtualization Platform| UTM (macOS host)                        |
| Allocated Storage      | 250 GB                                  |
| Remote Access          | XRDP configured for remote desktop GUI  |
| Web Server             | Apache2 (`apache2`)                     |
| Server-Side Language   | PHP (`php`, `libapache2-mod-php`)       |

### Packages Installed

```bash
sudo apt install apache2 php libapache2-mod-php xrdp openssh-server ufw -y
sudo systemctl enable apache2
sudo systemctl start apache2
```

Apache2 was verified as active and serving the default page on `http://localhost`.

---

## 2. Project Migration

The system underwent a full architectural migration from a static front-end prototype to a functional PHP-based backend application.

### 2.1 Architecture Conversion

- **Original state:** Static HTML UI (`pits-auth-ui.html`) — client-side only, no server logic.
- **Target state:** PHP-driven multi-file backend system with session management, file operations, and OS-level authentication.
- **WinSCP-based ideology removed:** The initial concept of relying on WinSCP for file transfers was eliminated. All file operations are now handled through the web-based file manager.
- **Legacy system concept integrated:** Student archival paradigm retained from the original project vision.

### 2.2 Authentication System

| Feature                     | Implementation                                                 |
|-----------------------------|----------------------------------------------------------------|
| Authentication Method       | PAM (Pluggable Authentication Modules) via helper script       |
| Login Mechanism             | HTML form → `auth.php` → `pam_auth_helper.sh` via `proc_open` |
| Session Management          | PHP native sessions with `session_regenerate_id()` on login    |
| Rate Limiting               | 5 attempts per 10 minutes per source IP                        |
| Rate Limit Storage          | JSON files in `sys_get_temp_dir()`, keyed by `md5($ip)`       |
| Session Cookie Hardening    | `httponly=1`, `samesite=Strict`, `use_strict_mode=1`           |

### 2.3 Security Measures

| Measure                    | Implementation                                                           |
|----------------------------|--------------------------------------------------------------------------|
| Path Traversal Protection  | `realpath()` comparison + `basename()` extraction via `is_safe_path()`   |
| Filename Sanitization      | `preg_replace('/[^a-zA-Z0-9._-]/', '_', ...)` + leading dot removal     |
| Input Validation           | Username regex: `/^[a-zA-Z0-9_]{1,32}$/`                                |
| XSS Prevention             | `htmlspecialchars()` on all user-facing output                           |
| CSRF Mitigation            | `samesite=Strict` cookie policy                                         |

### 2.4 Quota System

| Parameter          | Value   |
|--------------------|---------|
| Max Upload Size    | 50 MB   |
| User Total Quota   | 200 MB  |
| Quota Calculation  | Recursive directory size scan via `RecursiveDirectoryIterator`          |

### 2.5 JSON-Based File Manager API

| Endpoint          | Method | Purpose                                    |
|-------------------|--------|--------------------------------------------|
| `file_manager.php`| GET    | List files with name, size, modified date  |
| `upload.php`      | POST   | Accept multipart file upload               |
| `delete.php`      | POST   | Delete files by JSON array of filenames    |
| `download.php`    | GET    | Serve file download by filename parameter  |

---

## 3. File Structure Finalization

All project files reside in `/var/www/html/` on the target server.

| File                   | Purpose                                                                       |
|------------------------|-------------------------------------------------------------------------------|
| `config.php`           | Core configuration: directory paths, quota constants, admin list, helper functions (`get_role`, `get_user_dir`, `dir_size`, `sanitize_filename`, `is_safe_path`) |
| `session.php`          | Session bootstrap: starts PHP session, provides `require_login()`, `is_admin()`, `get_username()` helpers |
| `pam_auth_helper.sh`   | Bash script that authenticates a Linux user via PAM — accepts username as argument, reads password from stdin, exits 0 on success |
| `auth.php`             | Login handler: validates input, enforces rate limit, invokes PAM helper, creates session on success, redirects on failure |
| `logout.php`           | Destroys session and redirects to `index.php`                                 |
| `file_manager.php`     | JSON API: scans user directory, returns file list with metadata and quota usage|
| `upload.php`           | Upload handler: validates file, checks quota, sanitizes filename, saves to user directory with collision avoidance |
| `delete.php`           | Delete handler: accepts JSON array of filenames, validates paths, removes files, returns results |
| `download.php`         | Download handler: validates requested filename against user directory, streams file with proper Content-Disposition headers |
| `index.php`            | Login page: HTML/CSS/JS login form with error message display                 |
| `dashboard.php`        | Main application page: file list UI, upload/download/delete buttons, quota bar, user info bar |
| `SETUP.md`             | Server setup guide: step-by-step deployment instructions for Debian VM        |
| `pits-auth-ui.html`    | Original static UI prototype — retained for reference, not used in production |

### Role-Based Directory Mapping

| Role          | File Directory     | Defined By                      |
|---------------|--------------------|---------------------------------|
| Admin         | `/srv/project`     | `ADMIN_USERS` array in config   |
| Collaborator  | `/home/<username>` | Default `BASE_DIR` + username   |

---

## 4. Repository Setup

- All project files committed and pushed to a **private Git repository**.
- Repository used as the canonical source for deployment.
- Deployment methodology: `git clone` on the target server, or `scp` from development machine.

---

## 5. Server Deployment Steps Executed

The following commands were executed in sequence on the Debian VM:

### 5.1 Prepare Apache Web Root

```bash
# Remove default Apache content
sudo rm -rf /var/www/html

# Recreate clean directory
sudo mkdir /var/www/html
```

### 5.2 Transfer Project Files

Project files were uploaded via WinSCP into `/var/www/html/`.

**Verification:** Ensured flat structure — all PHP/HTML files are direct children of `/var/www/html/` with no unintended nested subdirectories.

### 5.3 Set Ownership and Permissions

```bash
# Transfer ownership to Apache user
sudo chown -R www-data:www-data /var/www/html

# Set standard web permissions
sudo chmod -R 755 /var/www/html
```

### 5.4 Configure PAM Authentication Helper

```bash
# Set restrictive permissions on the auth helper
sudo chown root:www-data /var/www/html/pam_auth_helper.sh
sudo chmod 750 /var/www/html/pam_auth_helper.sh
```

### 5.5 Configure Sudoers

```bash
sudo visudo -f /etc/sudoers.d/pits-auth
```

Added rule:
```
www-data ALL=(root) NOPASSWD: /var/www/html/pam_auth_helper.sh
```

### 5.6 Restart Apache

```bash
sudo systemctl restart apache2
```

---

## 6. Application Verification

| Test                        | Method                      | Result    |
|-----------------------------|-----------------------------|-----------|
| Apache active               | `systemctl status apache2`  | ✅ Active  |
| PHP execution               | `curl http://localhost`     | ✅ Renders |
| Login page loads             | Browser: `http://localhost` | ✅ Visible |
| User authentication         | Login form submission       | ✅ Works   |
| File listing                | Dashboard file manager      | ✅ Works   |
| File upload                 | Upload button               | ⚠️ See §7 |
| File download               | Download button             | ✅ Works   |
| File deletion               | Delete button               | ✅ Works   |
| Session persistence         | Navigate between pages      | ✅ Maintained |
| Logout                      | Logout link                 | ✅ Clears session |

---

## 7. Current Status

| Aspect                  | Status                                                    |
|-------------------------|-----------------------------------------------------------|
| Application State       | Fully functional on `localhost`                           |
| Network Exposure        | **Not yet configured**                                    |
| VM Network Mode         | Likely NAT (default) — external devices cannot connect    |
| LAN Accessibility       | ❌ Not accessible from other devices on the network       |
| Next Step               | Switch UTM network mode to **Bridged** for LAN access    |
| PDF Upload Issue        | ❗ Under investigation — see troubleshooting notes below  |

### Pending Actions

1. Change UTM VM network from NAT to **Bridged (Advanced)** mode
2. Select correct host interface (e.g., `en0` for Wi-Fi)
3. Obtain VM's bridged IP via `hostname -I`
4. Configure UFW firewall: `sudo ufw allow 80/tcp`
5. Test access from Mac browser and phone browser
6. Resolve PDF upload issue (see below)

---

## Appendix: PDF Upload Troubleshooting

See the companion troubleshooting section in the main project documentation for the full diagnosis of the PDF upload failure.

---

*End of Deployment Record*
