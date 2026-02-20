# PITS Archival System — Debian Server Setup

## 1. Install Dependencies

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php -y
sudo systemctl enable apache2
```

## 2. Deploy Files

```bash
sudo cp -r "/path/to/Student archival system/"* /var/www/html/
sudo chown -R www-data:www-data /var/www/html/
sudo chmod 644 /var/www/html/*.php
sudo chmod 644 /var/www/html/*.html
sudo chmod 644 "/var/www/html/pits logo.png"
```

## 3. Setup PAM Auth Helper

The helper script needs special permissions to validate Linux credentials:

```bash
sudo chown root:www-data /var/www/html/pam_auth_helper.sh
sudo chmod 750 /var/www/html/pam_auth_helper.sh
```

Add a sudoers rule so `www-data` can run the helper:

```bash
sudo visudo -f /etc/sudoers.d/pits-auth
```

Add this line:
```
www-data ALL=(root) NOPASSWD: /var/www/html/pam_auth_helper.sh
```

Then update `auth.php` to call the helper with sudo:
```php
$cmd = 'sudo ' . escapeshellcmd($helper) . ' ' . escapeshellarg($username);
```

## 4. PHP Configuration

Edit `/etc/php/*/apache2/php.ini`:

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

## 5. User Directories

Each Linux user's home directory needs to be readable by Apache:

```bash
sudo chmod 750 /home/username
sudo chown username:www-data /home/username
```

For the shared project directory:
```bash
sudo mkdir -p /srv/project
sudo chown root:www-data /srv/project
sudo chmod 775 /srv/project
```

## 6. Firewall

```bash
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

## 7. HTTPS (Optional but Recommended)

```bash
sudo apt install certbot python3-certbot-apache -y
sudo certbot --apache -d yourdomain.com
```

## 8. Verify

1. Open `http://your-server-ip/index.php`
2. Login with a valid Linux user account
3. You should see the file manager with real files from that user's home directory
4. Test upload, download, and delete operations

## Troubleshooting

- **Login fails**: Check that `pam_auth_helper.sh` is executable and the sudoers rule is in place. Test manually: `echo 'password' | sudo /var/www/html/pam_auth_helper.sh username`
- **Files not showing**: Verify directory permissions — `www-data` must have read access to user home dirs
- **Upload fails**: Check PHP `upload_max_filesize` and directory write permissions
- **Session issues**: Ensure `/var/lib/php/sessions/` is writable by `www-data`
