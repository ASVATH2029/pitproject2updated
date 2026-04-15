<?php
/*
 * ldap_config.php — LDAP / Active Directory configuration
 *
 * Set LDAP_ENABLED = true once the server can reach your AD Domain Controller.
 * This file is required by auth.php to add LDAP bind as an authentication method.
 *
 * For Kerberos SSO (transparent auto-login from domain PCs), see sso_auth.php
 * and the Apache config in apache/pits-sso.conf.
 */

// ── Master toggle ─────────────────────────────────────────────────────────
// Set to true on the production server once php-ldap is installed and the
// Domain Controller is reachable. Leave false on dev to avoid connection errors.
define('LDAP_ENABLED', false);

// ── Connection settings ───────────────────────────────────────────────────
// Use ldap:// for plain (port 389) or ldaps:// for SSL (port 636).
define('LDAP_HOST', 'ldap://dc1.school.local');
define('LDAP_PORT', 389);

// ── Domain details ────────────────────────────────────────────────────────
// LDAP_DOMAIN is appended as user@domain for the bind attempt.
// LDAP_BASE_DN is used if you later add group/attribute lookups.
define('LDAP_DOMAIN',  'school.local');
define('LDAP_BASE_DN', 'DC=school,DC=local');

// ── Security options ──────────────────────────────────────────────────────
// STARTTLS upgrades a plain ldap:// connection to encrypted.
// Only relevant when using ldap:// on port 389, ignored for ldaps://.
define('LDAP_USE_TLS', false);

// ── Timeouts ──────────────────────────────────────────────────────────────
// How long to wait for the LDAP server before falling through to the next
// authentication method (in seconds). Keep this low so logins stay fast
// even if the DC is unreachable.
define('LDAP_TIMEOUT', 5);
