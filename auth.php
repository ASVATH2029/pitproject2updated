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
        // Always resolve role via get_role() rather than the .user file's
        // stale 'role' snapshot — get_role() checks ADMIN_USERS and the
        // live .staff_users.json list, so staff promotions/demotions take
        // effect on next login instead of being stuck at signup-time role.
        $role = get_role($username);
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

if ($role === 'staff') {
    header('Location: staff_dashboard.php');
} elseif ($role === 'admin') {
    header('Location: admin.php');
} else {
    header('Location: dashboard.php');
}
exit;
