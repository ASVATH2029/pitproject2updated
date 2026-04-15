# PITS Archival System — Debian Server Setup (UTM VM on macOS)

## 0. Create the Debian VM in UTM

1. Download the **Debian 12 (Bookworm)** ISO:
   - **Apple Silicon Mac**: download the `arm64` net-install ISO from https://www.debian.org/distrib/
   - **Intel Mac**: download the `amd64` ISO

2. Open UTM → **Create a New Virtual Machine** → **Virtualize** (recommended for Apple Silicon)

3. Configure the VM:
   - **RAM**: 2 GB minimum (4 GB recommended)
   - **Disk**: 20 GB minimum
   - **CPU**: 2 cores

4. **Network Configuration** (CRITICAL for phone access):
   - Go to VM Settings → **Network**
   - Change **Network Mode** to **Bridged (Advanced)**
   - Select your Mac's active network interface (usually `en0` for Wi-Fi)
   - This gives the VM its own IP address on your local network

5. Mount the ISO and install Debian:
   - Set a root password
   - Create your regular user (e.g., `aditya`)
   - Select **SSH server** and **standard system utilities** during package selection
   - Skip desktop environment (not needed for a server)

---

## 1. Install Dependencies

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install apache2 php libapache2-mod-php openssh-server ufw -y
sudo systemctl enable apache2
sudo systemctl start apache2
```

Verify Apache is running:
```bash
systemctl status apache2
```

---

## 2. Find the VM's IP Address

```bash
hostname -I
```

Note this IP (e.g., `192.168.1.105`) — you'll need it for accessing the site from your Mac and phone.

---

## 3. Transfer Files from Mac to VM

From your **Mac terminal**:

```bash
scp -r "/Users/adityagiri/Documents/Student archival system/"* aditya@<VM_IP>:/tmp/pits/
```

> Replace `<VM_IP>` with the IP from step 2. You'll need to create the `/tmp/pits/` directory first by SSH-ing into the VM: `ssh aditya@<VM_IP>` then `mkdir -p /tmp/pits`

---

## 4. Deploy Files

Inside the VM:

```bash
# Remove the default Apache page
sudo rm -f /var/www/html/index.html

# Copy project files
sudo cp -r /tmp/pits/* /var/www/html/
sudo chown -R www-data:www-data /var/www/html/
sudo chmod 644 /var/www/html/*.php
sudo chmod 644 /var/www/html/*.html
sudo chmod 644 "/var/www/html/pits logo.png"
```

---

## 5. Setup PAM Auth Helper

The helper script validates Linux credentials. It needs special permissions:

```bash
sudo chown root:www-data /var/www/html/pam_auth_helper.sh
sudo chmod 750 /var/www/html/pam_auth_helper.sh
```

Add a sudoers rule so `www-data` can run the helper without a password:

```bash
sudo visudo -f /etc/sudoers.d/pits-auth
```

Add this single line:
```
www-data ALL=(root) NOPASSWD: /var/www/html/pam_auth_helper.sh
```

Save and exit (`Ctrl+X`, `Y`, `Enter` in nano).

Test that the helper works:
```bash
echo 'your_password' | sudo /var/www/html/pam_auth_helper.sh aditya
echo $?
# Should output: 0 (success)
```

---

## 6. PHP Configuration

Find your PHP version:
```bash
php -v
```

Edit the Apache PHP config:
```bash
sudo nano /etc/php/*/apache2/php.ini
```

Find and update these values (use `Ctrl+W` to search in nano):
```ini
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 120
session.cookie_httponly = 1
session.cookie_samesite = Strict
```

Restart Apache:
```bash
sudo systemctl restart apache2
```

---

## 7. User Directories

All user files are stored under `/srv/project/<username>/`. Each user gets their own isolated folder, automatically created on registration or first login.

```bash
# Create the base project directory
sudo mkdir -p /srv/project
sudo chown www-data:www-data /srv/project
sudo chmod 755 /srv/project
```

> **Note:** Individual user folders (`/srv/project/aditya/`, `/srv/project/student1/`, etc.) are created automatically by the application when users register or log in. No manual action needed.

### Sudoers for User Registration

The sign-up system creates Linux users via `useradd` and `chpasswd`. Add a sudoers rule:

```bash
sudo visudo -f /etc/sudoers.d/pits-register
```

Add this line:
```
www-data ALL=(root) NOPASSWD: /usr/sbin/useradd, /usr/sbin/chpasswd, /bin/chown, /bin/chmod, /bin/mkdir
```

---

## 8. Firewall

```bash
sudo ufw allow 80/tcp    # HTTP
sudo ufw allow 22/tcp    # SSH (for management)
sudo ufw enable
sudo ufw status
```

---

## 9. Verify Everything Works

### From the VM itself:
```bash
curl http://localhost/index.php
```
You should see HTML with the login form.

### From your Mac:
Open a browser and go to:
```
http://<VM_IP>/index.php
```

### From your Phone:
1. Make sure your phone is connected to the **same Wi-Fi network** as your Mac
2. Open your phone's browser
3. Navigate to: `http://<VM_IP>/index.php`
4. You should see the PITS login page
5. Log in with any Linux user account on the VM

---

## 10. HTTPS (Optional but Recommended)

For local-only use, HTTP is fine. For production or external access:

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| **Login fails** | Check that `pam_auth_helper.sh` is executable and the sudoers rule is in place. Test manually: `echo 'password' \| sudo /var/www/html/pam_auth_helper.sh username` |
| **Files not showing** | Verify directory permissions — `www-data` must have read access to user home directories |
| **Upload fails** | Check PHP `upload_max_filesize` in `/etc/php/*/apache2/php.ini` (NOT cli/php.ini) and directory write permissions |
| **Session issues** | Ensure `/var/lib/php/sessions/` is writable by `www-data`: `sudo chown www-data:www-data /var/lib/php/sessions/` |
| **Can't access from phone** | Verify UTM is using **Bridged networking** (not NAT). Run `hostname -I` in the VM — your phone must be able to reach that IP |
| **VM has no IP** | In UTM, double-check the network mode is set to **Bridged** and the correct interface is selected. Run `sudo dhclient` in the VM to request an IP |
| **Apache not starting** | Check logs: `sudo journalctl -u apache2 --no-pager -n 50` and `sudo cat /var/log/apache2/error.log` |
| **Permission denied errors** | Run `sudo chown -R www-data:www-data /var/www/html/` again, and check that user home dirs have `www-data` as group |

---

## Quick Reference

| Item | Value |
|------|-------|
| Web root | `/var/www/html/` |
| Login page | `http://<VM_IP>/index.php` |
| SSO entry point | `http://<VM_IP>/sso/` |
| Admin user in config | `aditya` |
| All user files | `/srv/project/<username>/` |
| Upload limit | 50 MB per file |
| User quota | 200 MB total |
| Rate limit | 5 login attempts per 10 min per IP |
| Session cookie | httponly, samesite=Strict |

---

## 11. LDAP Authentication Setup (Active Directory)

This enables students to log in using their **school Active Directory credentials** via the normal login page (`index.php`). Useful for mobile devices or PCs that are not domain-joined.

### 11.1 Install PHP LDAP Extension

```bash
sudo apt install php-ldap -y
sudo systemctl restart apache2
```

Verify it's loaded:
```bash
php -m | grep ldap
# Should output: ldap
```

### 11.2 Configure LDAP Settings

Edit `ldap_config.php` in your web root:

```bash
sudo nano /var/www/html/ldap_config.php
```

Update these values to match your school's Active Directory:

```php
define('LDAP_ENABLED', true);                          // ← flip to true
define('LDAP_HOST',    'ldap://dc1.yourschool.local'); // ← your DC hostname or IP
define('LDAP_PORT',    389);                           // ← 636 for LDAPS
define('LDAP_DOMAIN',  'yourschool.local');             // ← your AD domain
define('LDAP_BASE_DN', 'DC=yourschool,DC=local');       // ← your base DN
define('LDAP_USE_TLS', false);                         // ← true for STARTTLS
```

> **Tip:** If using LDAPS (port 636), change LDAP_HOST to `ldaps://dc1.yourschool.local` and LDAP_PORT to `636`. If the DC uses a self-signed certificate, add `TLS_REQCERT never` to `/etc/ldap/ldap.conf`.

### 11.3 Test LDAP Connectivity

From the server, verify you can reach the Domain Controller:

```bash
# Test network connectivity
nc -zv dc1.yourschool.local 389

# Test LDAP bind with a known account
ldapsearch -x -H ldap://dc1.yourschool.local -D "testuser@yourschool.local" -W -b "DC=yourschool,DC=local" "(sAMAccountName=testuser)"
```

### 11.4 How It Works

The authentication chain in `auth.php` now has three methods, tried in order:

1. **PAM** — Linux system user credentials (primary on server)
2. **LDAP** — Active Directory bind (`user@domain`) — **NEW**
3. **File-based** — bcrypt `.user` files (fallback / local dev)

If any method succeeds, the user is logged in. If all fail, they see "Invalid credentials."

---

## 12. Kerberos SSO Setup (Auto-Login for Domain PCs)

This enables **transparent single sign-on** — users on domain-joined Windows PCs navigate to `http://<server>/sso/` and are automatically logged in using their existing Windows session. No password prompt.

> **Note:** This section requires Domain Admin access and is typically performed by the school's IT administrator.

### 12.1 Prerequisites

- The Debian server must be **joined to the Active Directory domain**
- Time must be synchronised with the Domain Controller (within 5 minutes)
- Apache must have `mod_auth_gssapi` installed

### 12.2 Join the Domain

```bash
# Install required packages
sudo apt install realmd sssd sssd-tools adcli krb5-user libpam-sss libnss-sss -y

# During krb5-user installation, enter your domain: YOURSCHOOL.LOCAL

# Discover and join the domain
sudo realm discover yourschool.local
sudo realm join -U administrator yourschool.local
# You'll be prompted for the Domain Admin password

# Verify
realm list
# Should show your domain as 'configured'
```

### 12.3 Create a Service Principal & Keytab

On the **Domain Controller** (Windows), run:

```powershell
# Create a service account for the web server
New-ADUser -Name "pits-svc" -SamAccountName "pits-svc" -UserPrincipalName "HTTP/pitsserver.yourschool.local@YOURSCHOOL.LOCAL" -PasswordNeverExpires $true -Enabled $true

# Set the SPN
setspn -A HTTP/pitsserver.yourschool.local pits-svc

# Generate the keytab
ktpass -princ HTTP/pitsserver.yourschool.local@YOURSCHOOL.LOCAL -mapuser pits-svc -pass YourServicePassword -crypto AES256-SHA1 -ptype KRB5_NT_PRINCIPAL -out C:\pits.keytab
```

Copy `pits.keytab` to the Debian server:

```bash
# From a machine that can reach both Windows DC and Linux server
scp pits.keytab aditya@<DEBIAN_IP>:/tmp/
sudo mv /tmp/pits.keytab /etc/apache2/pits.keytab
sudo chown www-data:www-data /etc/apache2/pits.keytab
sudo chmod 600 /etc/apache2/pits.keytab
```

### 12.4 Install and Configure Apache

```bash
# Install mod_auth_gssapi
sudo apt install libapache2-mod-auth-gssapi -y
sudo a2enmod auth_gssapi

# Copy the PITS SSO config
sudo cp /var/www/html/apache/pits-sso.conf /etc/apache2/conf-available/
sudo a2enconf pits-sso

# Reload Apache
sudo systemctl reload apache2
```

### 12.5 Configure Client Browsers

For SSO to work, browsers must be configured to send Kerberos tickets to your server. This is usually handled via **Group Policy** on the school's domain:

- **Internet Explorer / Edge:** Add `http://pitsserver.yourschool.local` to the **Local Intranet** zone
- **Chrome:** Set the `AuthServerAllowlist` policy to `*.yourschool.local`
- **Firefox:** Set `network.negotiate-auth.trusted-uris` to `.yourschool.local`

### 12.6 Usage

| Access method | URL | What happens |
|---------------|-----|---------------|
| Domain PC (auto-login) | `http://<server>/sso/` | Kerberos ticket validated → instant dashboard access |
| Mobile / External | `http://<server>/` | Normal login page → enter AD username + password |

### 12.7 Test SSO

From a domain-joined Windows PC:

1. Open Chrome or Edge
2. Navigate to `http://pitsserver.yourschool.local/sso/`
3. You should be redirected to the dashboard without any login prompt
4. If prompted for credentials, check the browser's intranet zone settings

From a mobile device or non-domain PC:

1. Navigate to `http://<server>/index.php`
2. Enter your school AD username and password
3. You should be logged in via the LDAP authentication method

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| **Login fails** | Check that `pam_auth_helper.sh` is executable and the sudoers rule is in place. Test manually: `echo 'password' \| sudo /var/www/html/pam_auth_helper.sh username` |
| **Files not showing** | Verify directory permissions — `www-data` must have read access to user home directories |
| **Upload fails** | Check PHP `upload_max_filesize` in `/etc/php/*/apache2/php.ini` (NOT cli/php.ini) and directory write permissions |
| **Session issues** | Ensure `/var/lib/php/sessions/` is writable by `www-data`: `sudo chown www-data:www-data /var/lib/php/sessions/` |
| **Can't access from phone** | Verify UTM is using **Bridged networking** (not NAT). Run `hostname -I` in the VM — your phone must be able to reach that IP |
| **VM has no IP** | In UTM, double-check the network mode is set to **Bridged** and the correct interface is selected. Run `sudo dhclient` in the VM to request an IP |
| **Apache not starting** | Check logs: `sudo journalctl -u apache2 --no-pager -n 50` and `sudo cat /var/log/apache2/error.log` |
| **Permission denied errors** | Run `sudo chown -R www-data:www-data /var/www/html/` again, and check that user home dirs have `www-data` as group |
| **LDAP login fails** | Verify `php-ldap` is installed (`php -m \| grep ldap`), check `LDAP_ENABLED = true` in `ldap_config.php`, test connectivity: `nc -zv <DC_HOST> 389` |
| **LDAP slow / hangs** | The DC may be unreachable. Check `LDAP_TIMEOUT` in `ldap_config.php` (default 5s). Ensure DNS resolves the DC hostname. |
| **SSO not working** | Verify: (1) server is domain-joined (`realm list`), (2) keytab exists at `/etc/apache2/pits.keytab`, (3) `mod_auth_gssapi` is loaded (`apache2ctl -M \| grep gssapi`), (4) browser is in the intranet zone |
| **SSO 401 error** | Check Apache error log. Common causes: keytab permissions (must be readable by `www-data`), time skew > 5 minutes with DC, wrong SPN in keytab |

