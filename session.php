<?php
/*
 * session.php — Session bootstrap and helpers
 *
 * Included by every protected page.
 * Enforces: session timeout, user-agent binding, login gate.
 */

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Session timeout: 2 hours of inactivity ─────────────────────────────────
define('SESSION_TIMEOUT', 7200);

if (!empty($_SESSION['username'])) {
    $last = $_SESSION['last_activity'] ?? time();

    if ((time() - $last) > SESSION_TIMEOUT) {
        // Expired — destroy and redirect
        $_SESSION = [];
        session_destroy();
        header('Location: index.php?error=3');
        exit;
    }

    $_SESSION['last_activity'] = time();

    // ── Basic session hijack guard: user-agent fingerprint ─────────────────
    $ua_hash = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
    if (empty($_SESSION['ua_hash'])) {
        $_SESSION['ua_hash'] = $ua_hash;
    } elseif ($_SESSION['ua_hash'] !== $ua_hash) {
        // UA changed mid-session — possible hijack
        $_SESSION = [];
        session_destroy();
        header('Location: index.php?error=1');
        exit;
    }
}

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Redirect to login if not authenticated.
 */
function require_login(): void
{
    if (empty($_SESSION['username'])) {
        header('Location: index.php');
        exit;
    }
}

/**
 * Returns true if the current user has the admin role.
 */
function is_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Returns the sanitised username from session.
 */
function get_username(): string
{
    return $_SESSION['username'] ?? '';
}
