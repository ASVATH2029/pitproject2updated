# PITS Archival System — Walkthrough

## Files Created

| File | Purpose |
|------|---------|
| `config.php` | Constants (quotas, paths, admin list), helper functions (sanitize, path check, dir size) |
| `session.php` | Session guard — `require_login()`, `is_admin()`, `get_username()` |
| `pam_auth_helper.sh` | Shell script to validate Linux credentials via `su` |
| `auth.php` | Login handler — sanitizes input, rate limits (5/10min), calls PAM helper, sets session |
| `logout.php` | Destroys session, clears cookie, redirects to login |
| `file_manager.php` | Returns JSON: file list + quota usage from user's directory |
| `upload.php` | Handles file upload with size/quota validation, filename sanitization |
| `delete.php` | Deletes selected files with path traversal prevention |
| `download.php` | Serves file downloads with `realpath()` safety check |
| `index.php` | Login page (PHP) — preserves original UI, form posts to `auth.php` |
| `dashboard.php` | File manager page — fetches files via JS, upload/delete/download buttons, quota bar |
| `SETUP.md` | Debian server deployment guide |
| `pits-auth-ui.html` | Original static mockup (kept as reference) |

## Request Flow

```mermaid
graph LR
    A[User visits index.php] --> B{Logged in?}
    B -->|Yes| C[Redirect to dashboard.php]
    B -->|No| D[Show login form]
    D --> E[POST to auth.php]
    E --> F[PAM helper validates credentials]
    F -->|Success| G[Session created → dashboard.php]
    F -->|Failure| H[Redirect with error]
    G --> I[fetch file_manager.php → file list]
    G --> J[Upload → upload.php]
    G --> K[Delete → delete.php]
    G --> L[Download → download.php]
```

## Security Features

- **Path traversal blocked**: `realpath()` + `basename()` checks on all file operations
- **Input sanitization**: Username limited to `[a-zA-Z0-9_]{1,32}`, filenames stripped of special chars
- **Rate limiting**: 5 login attempts per IP per 10 minutes
- **Command injection**: `escapeshellarg()` on all shell arguments
- **Session security**: `httponly`, `samesite=Strict`, `session_regenerate_id()` on login

## Deployment

Follow [SETUP.md](file:///Users/adityagiri/Documents/Student%20archival%20system/SETUP.md) to deploy on your Debian VM. Key steps:
1. Copy all files to `/var/www/html/`
2. Set `pam_auth_helper.sh` permissions + sudoers rule
3. Configure PHP `upload_max_filesize` = 50M
4. Set user home directory permissions for `www-data`
