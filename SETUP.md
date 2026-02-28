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
| Admin user in config | `aditya` |
| All user files | `/srv/project/<username>/` |
| Upload limit | 50 MB per file |
| User quota | 200 MB total |
| Rate limit | 5 login attempts per 10 min per IP |
| Session cookie | httponly, samesite=Strict |
