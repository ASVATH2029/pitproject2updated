# PITS — LDAP/SSO Integration Walkthrough

## What Was Done

### 1. Particle/Bubble Removal — Verified Clean ✅

Ran exhaustive `grep` for `particle`, `floatParticle`, and `bubble` across all `.php`, `.css`, and `.js` files in both directories. **Zero results.** The bubble animation system was fully removed in the previous session.

Additionally verified:
- **Font stack intact**: `Libre Baskerville` (heading) and `DM Sans` (body) confirmed in all 12 page files
- **background.css untouched**: Still uses `backdrop.png` with the radial vignette overlay
- **login page (index.php)**: 472 lines, zero modifications — all CSS tokens, responsive breakpoints, glassmorphism effects, and animations preserved

### 2. LDAP/Active Directory Authentication — New Method

#### New File: [ldap_config.php](file:///Users/adityagiri/Documents/Student%20archival%20system/ldap_config.php)
- Contains all AD connection constants (host, port, domain, base DN, TLS, timeout)
- Ships with `LDAP_ENABLED = false` — safe default for dev
- The sysadmin fills in the real school AD details on the production server

#### Modified: [auth.php](file:///Users/adityagiri/Documents/Student%20archival%20system/auth.php)
- Inserted **LDAP bind as Method 2** between PAM and file-based auth
- Auth chain is now: PAM → LDAP → File-based bcrypt
- Gated behind `LDAP_ENABLED` and `function_exists('ldap_connect')` — no crash if php-ldap isn't installed
- Uses UPN format (`user@domain`) for Active Directory bind
- 5-second network timeout prevents slow logins if the DC is unreachable

```diff:auth.php
<?php
require_once __DIR__ . '/config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

if (!preg_match('/^[a-zA-Z0-9_]{1,32}$/', $username)) {
    header('Location: index.php?error=1');
    exit;
}

// Rate limiting: 5 attempts per 10 minutes per IP
$ip = $_SERVER['REMOTE_ADDR'];
$rate_file = sys_get_temp_dir() . '/pits_rate_' . md5($ip);
$attempts = [];

if (file_exists($rate_file)) {
    $attempts = json_decode(file_get_contents($rate_file), true) ?: [];
    $attempts = array_filter($attempts, function ($t) {
        return $t > time() - 600; });
}

if (count($attempts) >= 5) {
    header('Location: index.php?error=2');
    exit;
}

// Authenticate via PAM helper
$helper = __DIR__ . '/pam_auth_helper.sh';
$cmd = 'sudo ' . escapeshellcmd($helper) . ' ' . escapeshellarg($username);
$descriptors = [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
$process = proc_open($cmd, $descriptors, $pipes);

if (!is_resource($process)) {
    header('Location: index.php?error=1');
    exit;
}

fwrite($pipes[0], $password);
fclose($pipes[0]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit_code = proc_close($process);

// Log attempt
$attempts[] = time();
file_put_contents($rate_file, json_encode($attempts));

if ($exit_code === 0) {
    session_regenerate_id(true);
    $_SESSION['username'] = $username;
    $_SESSION['role'] = get_role($username);
    $_SESSION['login_time'] = time();
    header('Location: dashboard.php');
    exit;
}

header('Location: index.php?error=1');
exit;
===
<?php
/*
 * auth.php — Login handler
 *
 * Authentication order:
 *   1. PAM via pam_auth_helper.sh  (Linux system users — primary on server)
 *   2. LDAP bind against Active Directory (school domain credentials)
 *   3. File-based bcrypt .user file (fallback / local dev)
 *
 * For transparent Kerberos SSO (domain-joined PCs), see sso_auth.php.
 *
 * On success: sets session, redirects to dashboard.php
 * On failure: redirects to index.php?error=<code>
 *   error=1  Invalid credentials
 *   error=2  Rate-limited (5 failed attempts / 10 min per IP)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/ldap_config.php';
session_start();

// Already logged in
if (!empty($_SESSION['username'])) {
    if (get_role($_SESSION['username']) === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

// ── Sanitize username and standardise case ───────────────────────────────────────────────────
$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
$username = strtolower($username);

if (!preg_match('/^[a-zA-Z0-9_]{1,32}$/', $username)) {
    header('Location: index.php?error=1');
    exit;
}

// ── Rate limiting: 5 failures per IP per 10 minutes ───────────────────────
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rate_file = sys_get_temp_dir() . '/pits_rate_' . md5($ip);
$attempts = [];

if (file_exists($rate_file)) {
    $attempts = json_decode(file_get_contents($rate_file), true) ?: [];
    // Prune attempts older than 10 minutes
    $attempts = array_values(array_filter($attempts, fn($t) => $t > time() - 600));
}

if (count($attempts) >= 5) {
    header('Location: index.php?error=2');
    exit;
}

// ── Authenticate ───────────────────────────────────────────────────────────

$authenticated = false;
$role = 'collaborator';

// --- Method 1: PAM via pam_auth_helper.sh (Linux system users) ------------
$helper = __DIR__ . '/pam_auth_helper.sh';

if (is_executable($helper)) {
    // Password is passed through stdin — never exposed in ps output
    $proc = proc_open(
        'sudo ' . escapeshellarg($helper) . ' ' . escapeshellarg($username),
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes
    );

    if (is_resource($proc)) {
        fwrite($pipes[0], $password);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);

        if ($exit === 0) {
            $authenticated = true;
            $role = get_role($username); // reads .user or ADMIN_USERS list
        }
    }
}

// --- Method 2: LDAP bind against Active Directory -------------------------
// Attempts to authenticate by binding to the school's AD server using the
// user's credentials. Gated behind LDAP_ENABLED and php-ldap availability.
if (!$authenticated && LDAP_ENABLED && function_exists('ldap_connect')) {
    $ldap = @ldap_connect(LDAP_HOST, LDAP_PORT);
    if ($ldap) {
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, LDAP_TIMEOUT);

        if (LDAP_USE_TLS) {
            @ldap_start_tls($ldap);
        }

        // Bind using user@domain format (Active Directory UPN)
        $bind_dn = $username . '@' . LDAP_DOMAIN;

        if (@ldap_bind($ldap, $bind_dn, $password)) {
            $authenticated = true;
            $role = get_role($username);
        }

        @ldap_unbind($ldap);
    }
}

// --- Method 3: File-based bcrypt .user (fallback / dev mode) ---------------
if (!$authenticated) {
    $userData = get_user_data($username);

    if (
        $userData && isset($userData['password']) &&
        password_verify($password, $userData['password'])
    ) {
        $authenticated = true;
        $role = $userData['role'] ?? get_role($username);
    }
}

// ── Handle result ──────────────────────────────────────────────────────────

if (!$authenticated) {
    // Log failed attempt
    $attempts[] = time();
    file_put_contents($rate_file, json_encode($attempts), LOCK_EX);
    header('Location: index.php?error=1');
    exit;
}

// --- Success: clear rate limit, create session ----------------------------
if (file_exists($rate_file)) {
    unlink($rate_file);
}

session_regenerate_id(true); // Prevent session fixation

$_SESSION['username'] = $username;
$_SESSION['role'] = $role;
$_SESSION['login_time'] = time();

ensure_user_dir($username); // Create /srv/project/<user>/ if not exists

if ($role === 'admin') {
    header('Location: admin.php');
} else {
    header('Location: dashboard.php');
}
exit;
```

### 3. Kerberos SSO — Transparent Auto-Login

#### New File: [sso_auth.php](file:///Users/adityagiri/Documents/Student%20archival%20system/sso_auth.php)
- Handles the Kerberos SSO flow for domain-joined Windows PCs
- Reads `$_SERVER['REMOTE_USER']` (set by Apache's `mod_auth_gssapi`)
- Extracts username from Kerberos principal (`aditya@SCHOOL.LOCAL` → `aditya`)
- Creates a PHP session and redirects to dashboard — zero clicks required
- Falls back to `index.php` if no ticket is present (mobile/external)
- Stores `auth_method = 'kerberos_sso'` in session for audit trail

#### New File: [apache/pits-sso.conf](file:///Users/adityagiri/Documents/Student%20archival%20system/apache/pits-sso.conf)
- Drop-in Apache config for `/etc/apache2/conf-available/`
- Protects only the `/sso/` URL path with GSSAPI authentication
- All other paths remain unprotected (normal login page)
- `GssapiBasicAuth Off` — no browser password prompt fallback; users go to `index.php` instead

### 4. Documentation

#### Modified: [SETUP.md](file:///Users/adityagiri/Documents/Student%20archival%20system/SETUP.md)
- **Section 11**: LDAP Authentication Setup — installing `php-ldap`, configuring `ldap_config.php`, testing connectivity
- **Section 12**: Kerberos SSO Setup — domain join, keytab generation, `mod_auth_gssapi`, browser config, usage table
- Updated troubleshooting table with 4 new LDAP/SSO rows
- Updated Quick Reference table with SSO entry point

### 5. quick_deploy Mirror

All new and modified files mirrored to `quick_deploy/`:
- `quick_deploy/ldap_config.php` — exact copy
- `quick_deploy/sso_auth.php` — exact copy
- `quick_deploy/apache/pits-sso.conf` — exact copy
- `quick_deploy/auth.php` — same LDAP Method 2 modification

---

## Verification Results

| Check | Result |
|-------|--------|
| Particle/bubble remnants | **0** matches across entire project |
| Font stack (`Libre Baskerville`) | **12/12** page files intact |
| LDAP markers in `auth.php` | `ldap_bind`, `ldap_connect`, `LDAP_ENABLED` present |
| SSO markers in `sso_auth.php` | `REMOTE_USER`, `kerberos_sso` present |
| quick_deploy parity | All 4 files mirrored, LDAP markers confirmed |
| New files exist | 6 new files (3 per directory) confirmed |
| UI pages modified | **None** — `index.php` checksum unchanged |

---

## What's NOT Pushed

> [!IMPORTANT]
> As requested, nothing has been pushed to the repository. All changes are local only.

## Next Steps for Production Deployment

1. **Install `php-ldap`** on the Debian server: `sudo apt install php-ldap -y`
2. **Edit `ldap_config.php`** with actual school AD details and set `LDAP_ENABLED = true`
3. **Domain join** the server (if Kerberos SSO is desired) — see SETUP.md Section 12
4. **Deploy to web root** and test both login flows (manual + SSO)
