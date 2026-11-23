# Deploying HyperBit Social to Hostinger Shared Hosting

This guide explains how to deploy HyperBit Social to Hostinger Premium shared web hosting.

## Requirements

- Hostinger Premium, Business, or Cloud hosting plan
- PHP 8.2 or 8.3
- MySQL or MariaDB database
- SSH access (available on Premium and above)
- Composer (available via SSH)

## Step 1: Prepare Your Files

Before uploading, make sure you have:

1. Built the frontend assets locally:
   ```bash
   npm install
   npm run build
   ```

2. The `public/build` directory is committed to git (or will be uploaded)

3. Created your Meta App and have the App Secret ready

## Step 2: Create Database

1. Log into hPanel
2. Go to **Databases** → **MySQL Databases**
3. Create a new database, e.g., `hyperbit_social`
4. Note the database name, username, password, and host

## Step 3: Upload Files

### Option A: SSH + Git (Recommended)

1. SSH into your hosting:
   ```bash
   ssh u123456789@your-server.hostinger.com
   ```

2. Navigate to the public_html parent:
   ```bash
   cd ~/domains/social.hyperbitit.com
   ```

3. Clone the repository:
   ```bash
   git clone https://github.com/kaiser5474/hyperbit-social.git .
   ```

4. Set the document root to `public` using one of these methods:

   **Method 1: Symbolic link (preferred)**
   ```bash
   rm -rf public_html
   ln -s hyperbit-social/public public_html
   ```

   **Method 2: PHP symlink fallback**
   If shell `ln -s` fails (some hosts restrict symlinks), create the link with PHP:
   ```bash
   php -r "symlink('/home/u123456789/domains/social.hyperbitit.com/public', '/home/u123456789/domains/social.hyperbitit.com/public_html');"
   ```

   **Method 3: Root .htaccess rewrite**
   If you cannot change the document root or create symlinks, use the root `.htaccess` approach:
   1. Place all project files inside `public_html/` (or the web root)
   2. Copy the included `.htaccess` file to the web root:
      ```bash
      cp .htaccess public_html/.htaccess
      ```
   3. This `.htaccess` will:
      - Rewrite all requests to the `public/` subdirectory
      - Deny access to sensitive files (`.env`, `composer.json`, etc.)
      - Block direct access to `app/`, `config/`, `vendor/`, `storage/` (except public files)

### Option B: File Manager/FTP

1. Upload all files except `vendor/` and `node_modules/`
2. Set the document root to point to the `public` directory using Method 1, 2, or 3 above

## Step 4: Configure Environment

1. SSH into your hosting
2. Copy the environment file:
   ```bash
   cd ~/domains/social.hyperbitit.com
   cp .env.example .env
   ```

3. Edit `.env` with your settings:
   ```bash
   nano .env
   ```

   Update these values:
   ```env
   APP_NAME="HyperBit Social"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://social.hyperbitit.com

   DB_CONNECTION=mysql
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=your_database_name
   DB_USERNAME=your_database_user
   DB_PASSWORD=your_database_password

   META_APP_ID=1127758050142770
   META_APP_SECRET=your_actual_app_secret
   META_REDIRECT_URI=https://social.hyperbitit.com/auth/facebook/callback
   ```

## Step 5: Install Dependencies & Setup

```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate --force

# Create storage link
php artisan storage:link

# Clear and cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Step 6: Set Up Cron Job

1. In hPanel, go to **Advanced** → **Cron Jobs**
2. Add a new cron job:
   - Command: `cd /home/u123456789/domains/social.hyperbitit.com && php artisan schedule:run >> /dev/null 2>&1`
   - Frequency: Every minute (`* * * * *`)

Alternatively via SSH:
```bash
crontab -e
```

Add this line:
```
* * * * * cd /home/u123456789/domains/social.hyperbitit.com && php artisan schedule:run >> /dev/null 2>&1
```

## Step 7: Configure Meta App

In the [Meta for Developers](https://developers.facebook.com/apps/) console:

1. **Settings** → **Basic**:
   - App Domains: `social.hyperbitit.com`
   - Privacy Policy URL: `https://social.hyperbitit.com/privacy`
   - Terms of Service URL: `https://social.hyperbitit.com/terms`
   - Data Deletion Callback URL: `https://social.hyperbitit.com/api/meta/data-deletion`

2. **Facebook Login** → **Settings**:
   - Valid OAuth Redirect URIs: `https://social.hyperbitit.com/auth/facebook/callback`

3. Add testers in **Roles** → **Test Users** or **Roles** → **Testers**

## Step 8: Verify Deployment

1. Visit `https://social.hyperbitit.com` - you should see the landing page
2. Try logging in with Facebook (as a tester)
3. Connect a Facebook Page
4. Create and schedule a test post
5. Verify the cron is running by checking if scheduled posts are published

## Troubleshooting

### 500 Error

Check Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

Common causes:
- Missing `.env` file
- Wrong file permissions
- Database connection issues

### File Permissions

```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Storage Link Issues

If `php artisan storage:link` fails (e.g., `exec()` disabled on shared hosting), manually create the link:

**Option 1: Shell symlink**
```bash
cd public
ln -s ../storage/app/public storage
```

**Option 2: PHP symlink fallback**
If shell symlinks are restricted, use PHP:
```bash
php -r "symlink('/home/u123456789/domains/social.hyperbitit.com/storage/app/public', '/home/u123456789/domains/social.hyperbitit.com/public/storage');"
```

Replace the paths with your actual installation path.

### Cron Not Running

Test the scheduler manually:
```bash
php artisan schedule:run
```

Check cron logs:
```bash
grep CRON /var/log/syslog
```

## Updating

To update to a new version:

```bash
cd ~/domains/social.hyperbitit.com

# Pull latest changes
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Shared Hosting Limitations

HyperBit Social is designed to work within shared hosting restrictions:

- **No `exec()`/`proc_open()`**: The scheduler runs commands directly without background processes
- **No Redis**: Uses database driver for sessions, cache, and queue
- **No long-running workers**: Scheduled posts are published via cron, not queue workers
- **No Node.js runtime**: Frontend assets are pre-built and committed to the repository

## Security Notes

- Never commit `.env` with real secrets
- Keep `APP_DEBUG=false` in production
- Regularly update dependencies
- Monitor Laravel logs for errors
- Keep your Meta App Secret secure
- If using the root `.htaccess` approach, verify sensitive directories are blocked by visiting `/vendor/autoload.php` (should return 403 Forbidden)
