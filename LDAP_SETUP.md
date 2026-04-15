# PITS — LDAP & Kerberos SSO Setup Guide

This document covers how to enable Active Directory (LDAP) authentication and transparent Kerberos Single Sign-On (SSO) for the PITS Archival System.

---

## Table of Contents

1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Step 1: Install php-ldap](#step-1-install-php-ldap)
4. [Step 2: Configure LDAP Connection](#step-2-configure-ldap-connection)
5. [Step 3: Enable LDAP Authentication](#step-3-enable-ldap-authentication)
6. [Step 4: Test LDAP Login](#step-4-test-ldap-login)
7. [Step 5: Set Up Kerberos SSO (Optional)](#step-5-set-up-kerberos-sso-optional)
8. [Step 6: Generate the Keytab](#step-6-generate-the-keytab)
9. [Step 7: Configure Apache for SSO](#step-7-configure-apache-for-sso)
10. [Step 8: Test SSO](#step-8-test-sso)
11. [Authentication Flow](#authentication-flow)
12. [Troubleshooting](#troubleshooting)

---

## Overview

PITS supports three authentication methods, tried in order:

| Priority | Method | Use Case |
|:---------|:-------|:---------|
| 1 | **PAM** (`pam_auth_helper.sh`) | Linux system users on the server |
| 2 | **LDAP Bind** (`ldap_config.php`) | School Active Directory domain accounts |
| 3 | **File-based bcrypt** (`.user` files) | Local/dev accounts, self-registered users |

**Kerberos SSO** is a separate, optional layer that allows domain-joined Windows PCs to bypass the login page entirely.

---

## Prerequisites

- Ubuntu/Debian server with Apache + PHP 8.x
- Network access to your Active Directory Domain Controller (DC)
- The DC hostname or IP address
- Your AD domain name (e.g., `school.local`)
- An admin account that can generate keytabs (for SSO only)

---

## Step 1: Install php-ldap

```bash
sudo apt update
sudo apt install php-ldap
sudo systemctl restart apache2
```

Verify the extension is loaded:

```bash
php -m | grep ldap
```

You should see `ldap` in the output.

---

## Step 2: Configure LDAP Connection

Edit `ldap_config.php` on your server:

```php
// ── Connection settings ───────────────────────────────────────────────
// Use ldap:// for plain (port 389) or ldaps:// for SSL (port 636).
define('LDAP_HOST', 'ldap://dc1.school.local');  // ← Your DC hostname
define('LDAP_PORT', 389);                         // ← 389 or 636

// ── Domain details ────────────────────────────────────────────────────
define('LDAP_DOMAIN',  'school.local');            // ← Your AD domain
define('LDAP_BASE_DN', 'DC=school,DC=local');      // ← Your base DN

// ── Security ──────────────────────────────────────────────────────────
define('LDAP_USE_TLS', false);  // Set to true for STARTTLS on port 389

// ── Timeouts ──────────────────────────────────────────────────────────
define('LDAP_TIMEOUT', 5);     // Seconds before falling to next method
```

### Common configurations:

| School Setup | LDAP_HOST | LDAP_PORT | LDAP_USE_TLS |
|:-------------|:----------|:----------|:-------------|
| Plain LDAP | `ldap://dc1.school.local` | 389 | `false` |
| LDAP + STARTTLS | `ldap://dc1.school.local` | 389 | `true` |
| LDAPS (SSL) | `ldaps://dc1.school.local` | 636 | `false` |

---

## Step 3: Enable LDAP Authentication

In `ldap_config.php`, flip the master toggle:

```php
define('LDAP_ENABLED', true);   // ← Change from false to true
```

That's it. The authentication cascade in `auth.php` will now attempt LDAP bind after PAM.

---

## Step 4: Test LDAP Login

1. Navigate to your PITS login page
2. Enter a valid Active Directory username and password
3. If LDAP bind succeeds, you'll be redirected to the dashboard

**How it works internally:**

```
User submits: username + password
    ↓
auth.php tries PAM → fails (not a Linux user)
    ↓
auth.php tries LDAP bind: username@school.local + password
    ↓
DC validates credentials → SUCCESS
    ↓
Session created, user redirected to dashboard
```

The user's directory (`/srv/project/<username>/`) is automatically created on first login.

---

## Step 5: Set Up Kerberos SSO (Optional)

Kerberos SSO allows domain-joined Windows PCs to log in automatically — no username/password needed. The browser sends a Kerberos ticket that Apache validates.

### 5a. Join the server to the domain

```bash
# Install required packages
sudo apt install realmd sssd sssd-tools adcli packagekit

# Discover the domain
sudo realm discover school.local

# Join the domain (you'll be prompted for an AD admin password)
sudo realm join school.local -U Administrator

# Verify
sudo realm list
```

### 5b. Install mod_auth_gssapi

```bash
sudo apt install libapache2-mod-auth-gssapi
sudo a2enmod auth_gssapi
```

---

## Step 6: Generate the Keytab

On the Domain Controller (or using `ktpass` remotely):

```powershell
# Run on the Windows Domain Controller (PowerShell as Admin)
ktpass -princ HTTP/pits-server.school.local@SCHOOL.LOCAL ^
       -mapuser PITS_SVC ^
       -pass <service_account_password> ^
       -crypto AES256-SHA1 ^
       -ptype KRB5_NT_PRINCIPAL ^
       -out pits.keytab
```

Then copy it to the server:

```bash
# Copy the keytab to the server
scp pits.keytab admin@pits-server:/etc/apache2/pits.keytab

# Set permissions
sudo chown www-data:www-data /etc/apache2/pits.keytab
sudo chmod 600 /etc/apache2/pits.keytab
```

---

## Step 7: Configure Apache for SSO

Copy the included Apache config:

```bash
sudo cp apache/pits-sso.conf /etc/apache2/conf-available/
sudo a2enconf pits-sso
sudo systemctl reload apache2
```

The config creates a `/sso` endpoint that:
- Requires a valid Kerberos ticket (no password prompt)
- Passes `REMOTE_USER` to `sso_auth.php`
- Creates a PHP session and redirects to the appropriate dashboard

---

## Step 8: Test SSO

From a **domain-joined Windows PC** on the school network:

1. Open a browser
2. Navigate to `http://pits-server.school.local/sso`
3. You should be automatically logged in and redirected to your dashboard

> **Note:** SSO only works from domain-joined machines with valid Kerberos tickets. Mobile devices and external machines should use the normal login page at `http://pits-server.school.local/`.

---

## Authentication Flow

```
┌─────────────────────────────────────────────────────┐
│                   User Access                        │
├──────────────────────┬──────────────────────────────┤
│  Domain-joined PC    │  Other device / Mobile        │
│  → /sso endpoint     │  → /index.php login page     │
│                      │                               │
│  Apache validates    │  User submits username +      │
│  Kerberos ticket     │  password via POST            │
│  ↓                   │  ↓                            │
│  sso_auth.php        │  auth.php                     │
│  creates session     │  tries: PAM → LDAP → File    │
│  ↓                   │  ↓                            │
│  Redirect to         │  Redirect to                  │
│  dashboard           │  dashboard                    │
└──────────────────────┴──────────────────────────────┘
```

---

## Troubleshooting

### LDAP bind fails ("Invalid credentials" even with correct AD password)

1. **Check DNS:** Can the server resolve the DC?
   ```bash
   nslookup dc1.school.local
   ```

2. **Check connectivity:**
   ```bash
   ldapsearch -x -H ldap://dc1.school.local -D "testuser@school.local" -W
   ```

3. **Check php-ldap is loaded:**
   ```bash
   php -m | grep ldap
   ```

4. **Check LDAP_ENABLED is true** in `ldap_config.php`

### SSO returns 401 Unauthorized

1. **Keytab permissions:**
   ```bash
   ls -la /etc/apache2/pits.keytab
   # Should be: -rw------- www-data www-data
   ```

2. **Verify the keytab:**
   ```bash
   sudo klist -kt /etc/apache2/pits.keytab
   ```

3. **Check Apache error log:**
   ```bash
   sudo tail -20 /var/log/apache2/error.log
   ```

4. **Browser config:** Ensure the browser trusts the server's domain for Negotiate auth. In Firefox, check `network.negotiate-auth.trusted-uris`.

### Users land on wrong dashboard

- **Admin role:** Set in the `ADMIN_USERS` constant in `config.php`
- **Staff role:** Managed via the admin panel or `.staff_users.json`
- **Student (Collaborator):** Default role for all other users

---

## File Reference

| File | Purpose |
|:-----|:--------|
| `ldap_config.php` | LDAP connection settings and master toggle |
| `auth.php` | Login handler — PAM → LDAP → file-based cascade |
| `sso_auth.php` | Kerberos SSO session bootstrap |
| `apache/pits-sso.conf` | Apache config for the `/sso` endpoint |
| `pam_auth_helper.sh` | PAM authentication helper script |
| `config.php` | Core config — admin users, storage paths, staff helpers |
