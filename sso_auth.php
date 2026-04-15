<?php
/*
 * sso_auth.php — Kerberos SSO session bootstrap
 *
 * This script is called when a domain-joined Windows PC accesses /sso/.
 * Apache (mod_auth_gssapi) validates the Kerberos ticket and sets
 * $_SERVER['REMOTE_USER'] before PHP executes.
 *
 * If REMOTE_USER is present and valid:
 *   → Creates a PHP session (same as a normal login)
 *   → Redirects to dashboard.php or admin.php
 *
 * If REMOTE_USER is missing (mobile, external device, no ticket):
 *   → Redirects to index.php (the normal login page)
 *
 * Prerequisites:
 *   1. Server joined to the Active Directory domain (realmd / sssd)
 *   2. mod_auth_gssapi installed and configured (see apache/pits-sso.conf)
 *   3. Keytab file generated and placed at /etc/apache2/pits.keytab
 *
 * See SETUP.md sections 11-12 for full instructions.
 */

require_once __DIR__ . '/config.php';
session_start();

// ── Already authenticated — skip straight to dashboard ────────────────────
if (!empty($_SESSION['username'])) {
    if (get_role($_SESSION['username']) === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

// ── Read the Kerberos principal from Apache ───────────────────────────────
$remote_user = $_SERVER['REMOTE_USER'] ?? '';

if (empty($remote_user)) {
    // No Kerberos ticket available — fall back to manual login
    header('Location: index.php');
    exit;
}

// ── Extract username from principal ───────────────────────────────────────
// Kerberos principals look like: aditya@SCHOOL.LOCAL
// We need just the username part, lowercased and sanitised.
$username = strtolower(explode('@', $remote_user)[0]);
$username = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);

if (empty($username)) {
    header('Location: index.php');
    exit;
}

// ── Create session ────────────────────────────────────────────────────────
session_regenerate_id(true); // Prevent session fixation

$_SESSION['username']    = $username;
$_SESSION['role']        = get_role($username);
$_SESSION['login_time']  = time();
$_SESSION['auth_method'] = 'kerberos_sso';

ensure_user_dir($username); // Create /srv/project/<user>/ if not exists

// ── Redirect ──────────────────────────────────────────────────────────────
if ($_SESSION['role'] === 'staff') {
    header('Location: staff_dashboard.php');
} elseif ($_SESSION['role'] === 'admin') {
    header('Location: admin.php');
} else {
    header('Location: dashboard.php');
}
exit;
