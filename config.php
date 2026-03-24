<?php
/*
 * config.php — Global constants, path helpers, and utility functions
 *
 * Storage layout:
 *   /srv/project/                  BASE_DIR (outside web root)
 *   /srv/project/<username>/       per-user isolated directory
 *   /srv/project/<username>/.user  credential JSON (excluded from quota)
 */

// ── Storage constants ──────────────────────────────────────────────────────
define('PROJECT_DIR', '/srv/project');
define('BASE_DIR', PROJECT_DIR . '/');
define('UPLOAD_QUOTA', 200 * 1024 * 1024); // 200 MB per user
define('MAX_UPLOAD_SIZE', 200 * 1024 * 1024); // 200 MB single-file limit

// ── Security configuration ─────────────────────────────────────────────────
define('BLOCKED_EXTENSIONS', [
    'php', 'php3', 'php4', 'php5', 'phtml', 'cgi',
    'sh', 'bash', 'zsh', 'bin',
    'exe', 'bat', 'cmd', 'msi', 'ps1',
    'pl', 'py', 'rb', 'js'
]);

// ── Admin users ────────────────────────────────────────────────────────────
define('ADMIN_USERS', ['aditya', 'pitsnas']);

// ── Mail / SMTP configuration ──────────────────────────────────────────────
// Fill in your outgoing mail server details here.
// For Gmail: host=smtp.gmail.com, port=587, use an App Password (not your real password)
//   https://support.google.com/accounts/answer/185833
define('MAIL_HOST', 'smtp.gmail.com'); // SMTP server
define('MAIL_PORT', 587); // 587 = TLS, 465 = SSL
define('MAIL_USER', 'pitsnas0@gmail.com'); // ← your sender email
define('MAIL_PASS', 'momqtnvjuskieplv'); // ← App Password (16 chars, no spaces)
define('MAIL_FROM', 'pitsnas0@gmail.com'); // Same as MAIL_USER for Gmail
define('MAIL_FROM_NAME', 'PITS Archival System');
define('OTP_EXPIRY_SECS', 600); // 10 minutes

// ── Session hardening (applied before session_start() by session.php) ─────
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 7200); // match SESSION_TIMEOUT in session.php

// ── PHP upload limits ──────────────────────────────────────────────────────
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '55M');

// ══════════════════════════════════════════════════════════════════════════
// PATH HELPERS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Returns the absolute path to the user's storage directory.
 * Strips everything except alphanumerics, underscores, and hyphens.
 */
function get_user_dir(string $username): string
{
    $safe = preg_replace('/[^a-zA-Z0-9_-]/', '', $username);
    return PROJECT_DIR . '/' . $safe;
}

/**
 * Creates the user's storage directory if it does not exist.
 */
function ensure_user_dir(string $username): string
{
    $dir = get_user_dir($username);
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
    return $dir;
}

/**
 * Returns true if a .user credential file exists for this username.
 */
function user_exists(string $username): bool
{
    return file_exists(get_user_dir($username) . '/.user');
}

/**
 * Reads and decodes the user's .user credential JSON file.
 * Returns null if the file does not exist or is malformed.
 */
function get_user_data(string $username): ?array
{
    $file = get_user_dir($username) . '/.user';
    if (!file_exists($file)) {
        return null;
    }
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

/**
 * Determines the user's role.
 * Priority: .user file → ADMIN_USERS constant → 'collaborator'
 */
function get_role(string $username): string
{
    $data = get_user_data($username);
    if ($data && isset($data['role'])) {
        return $data['role'];
    }
    return in_array($username, ADMIN_USERS, true) ? 'admin' : 'collaborator';
}

// ══════════════════════════════════════════════════════════════════════════
// QUOTA HELPERS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Recursively sums the size of all non-hidden files in a directory.
 * Hidden files (starting with '.', including .user) are excluded.
 *
 * Uses a quota-cache file (.quota_cache) to avoid full traversal on hot paths.
 * Cache is invalidated by upload.php and delete.php via invalidate_quota_cache().
 */
function dir_size(string $dir): int
{
    if (!is_dir($dir)) {
        return 0;
    }

    // Return cached value if fresh (< 60 seconds old)
    $cache_file = $dir . '/.quota_cache';
    if (file_exists($cache_file) && (time() - filemtime($cache_file)) < 60) {
        $cached = (int)file_get_contents($cache_file);
        if ($cached > 0) {
            return $cached;
        }
    }

    $size = 0;
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
        );

    foreach ($iter as $file) {
        if ($file->getFilename()[0] === '.') {
            continue; // skip .user, .quota_cache, etc.
        }
        $size += $file->getSize();
    }

    // Write fresh cache
    file_put_contents($cache_file, (string)$size, LOCK_EX);
    return $size;
}

/**
 * Deletes the quota cache file to force recalculation on next request.
 * Call this after every upload or delete operation.
 */
function invalidate_quota_cache(string $user_dir): void
{
    $cache_file = $user_dir . '/.quota_cache';
    if (file_exists($cache_file)) {
        unlink($cache_file);
    }
}

// ══════════════════════════════════════════════════════════════════════════
// SECURITY HELPERS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Sanitizes a filename to safe characters only.
 * - basename() strips directory components first
 * - Regex allows: letters, digits, dots, hyphens, underscores
 * - Leading dots removed (no hidden files)
 */
function sanitize_filename(string $name): string
{
    $name = basename($name);
    $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);
    $name = ltrim($name, '.');
    return $name !== '' ? $name : 'unnamed_file';
}

/**
 * Verifies that $filepath resolves (via realpath) to a descendant of $basedir.
 * Prevents path traversal attacks including symlink exploitation.
 *
 * NOTE: realpath() returns false if the file does not exist.
 *       For new files (pre-creation check), use dirname() of the target.
 */
function is_safe_path(string $filepath, string $basedir): bool
{
    $real_base = realpath($basedir);
    $real_file = realpath($filepath);

    if ($real_base === false || $real_file === false) {
        return false;
    }

    // Ensure the resolved file path starts with the resolved base path
    return str_starts_with($real_file, $real_base . DIRECTORY_SEPARATOR);
}

// ══════════════════════════════════════════════════════════════════════════
// OTP HELPERS
// ══════════════════════════════════════════════════════════════════════════

/**
 * Generates a cryptographically random 6-digit OTP string.
 */
function generate_otp(): string
{
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Sends an OTP email using PHPMailer over SMTP.
 *
 * @param string $to      Recipient email address
 * @param string $otp     The 6-digit OTP code
 * @param string $purpose 'verify' (account) or 'reset' (password)
 * @return bool           true on success, false on failure
 */
function send_otp_email(string $to, string $otp, string $purpose = 'verify'): bool
{
    $phpmailer_dir = __DIR__ . '/phpmailer';
    if (!file_exists($phpmailer_dir . '/PHPMailer.php')) {
        error_log('PITS mail: PHPMailer not found at ' . $phpmailer_dir);
        return false;
    }

    require_once $phpmailer_dir . '/Exception.php';
    require_once $phpmailer_dir . '/PHPMailer.php';
    require_once $phpmailer_dir . '/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = MAIL_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = MAIL_USER;
        $mail->Password = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = MAIL_PORT;

        // Sender & recipient
        $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
        $mail->addAddress($to);

        // Content
        if ($purpose === 'reset') {
            $subject = 'PITS — Password Reset OTP';
            $body = otp_email_html($otp, 'Reset Your Password',
                'You requested a password reset. Use the code below to continue.');
        }
        else {
            $subject = 'PITS — Verify Your Email';
            $body = otp_email_html($otp, 'Verify Your Email Address',
                'Thanks for signing up! Use the code below to verify your email.');
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = "Your PITS OTP code is: $otp  (expires in 10 minutes)";

        $mail->send();
        return true;

    }
    catch (Exception $e) {
        error_log('PITS mail error: ' . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Returns a styled HTML email body for the OTP.
 */
function otp_email_html(string $otp, string $heading, string $intro): string
{
    return <<<HTML
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#0a100a;font-family:'Helvetica Neue',Arial,sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#0a100a;padding:40px 0;">
    <tr><td align="center">
      <table width="480" cellpadding="0" cellspacing="0"
             style="background:rgba(55,70,50,0.95);border:1px solid rgba(255,255,255,0.08);
                    border-radius:16px;padding:40px 44px;">
        <tr><td style="text-align:center;padding-bottom:8px;">
          <span style="font-family:Georgia,serif;font-size:1.1rem;color:rgba(255,255,255,0.6);">
            PITS Student Archival System
          </span>
        </td></tr>
        <tr><td style="text-align:center;padding-bottom:24px;border-bottom:1px solid rgba(255,255,255,0.06);">
          <h1 style="font-family:Georgia,serif;font-weight:400;font-size:1.8rem;color:#fff;margin:12px 0 4px;">
            {$heading}
          </h1>
          <p style="color:rgba(255,255,255,0.6);font-size:0.9rem;margin:0;">{$intro}</p>
        </td></tr>
        <tr><td style="padding:32px 0;text-align:center;">
          <div style="display:inline-block;background:rgba(25,35,22,0.75);border:1px solid rgba(160,200,140,0.25);
                      border-radius:12px;padding:18px 36px;">
            <span style="font-size:2.4rem;font-weight:700;letter-spacing:10px;color:#fff;font-family:monospace;">
              {$otp}
            </span>
          </div>
          <p style="color:rgba(255,255,255,0.45);font-size:0.78rem;margin-top:14px;">
            This code expires in <strong style="color:rgba(255,255,255,0.7);">10 minutes</strong>.
            Do not share it with anyone.
          </p>
        </td></tr>
        <tr><td style="border-top:1px solid rgba(255,255,255,0.06);padding-top:20px;text-align:center;">
          <p style="color:rgba(255,255,255,0.3);font-size:0.72rem;margin:0;">
            If you didn't request this, you can safely ignore this email.
          </p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
}
