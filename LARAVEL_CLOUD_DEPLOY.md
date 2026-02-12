# Laravel Cloud Deployment Guide

## Quick Start

Laravel Cloud is the official hosting platform for Laravel applications. It's designed to work seamlessly with Laravel without requiring any special configuration files.

## Step-by-Step Deployment

### 1. Create Laravel Cloud Account

1. Go to https://cloud.laravel.com
2. Sign up or log in
3. Create a new project

### 2. Connect Git Repository

1. In Laravel Cloud dashboard, click "Connect Repository"
2. Authorize Laravel Cloud to access your Git provider (GitHub, GitLab, Bitbucket)
3. Select your `cattle-backend` repository
4. Choose the branch to deploy (usually `main` or `master`)

### 3. Configure Environment Variables

In Laravel Cloud dashboard, go to **Environment** and set:

**Required:**
```bash
APP_NAME="Cattle Management"
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=https://your-app-name.cloud.laravel.com

FRONTEND_URL=https://your-frontend-domain.vercel.app

# Additional frontend URLs (comma-separated, optional)
# FRONTEND_URLS=https://www.yourdomain.com,https://yourdomain.com

# Frontend domain pattern for wildcard matching (optional)
# FRONTEND_DOMAIN_PATTERN=/^https:\/\/.*\.yourdomain\.com$/

# Neon PostgreSQL Configuration
DB_CONNECTION=pgsql
DB_HOST=your-project.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=your_neon_username
DB_PASSWORD=your_neon_password
DB_SSLMODE=require
# Or use connection string (alternative):
# DB_URL=postgresql://username:password@host.neon.tech/neondb?sslmode=require

SESSION_DRIVER=database
SESSION_ENCRYPT=true
CACHE_STORE=database
QUEUE_CONNECTION=database
```

**To generate APP_KEY:**
```bash
php artisan key:generate --show
```

### 4. Configure Database (Neon PostgreSQL)

**Option A: Use Neon Database (Recommended)**

1. Create a Neon account at https://neon.tech
2. Create a new project and database
3. Copy the connection string from Neon dashboard
4. In Laravel Cloud, set these environment variables:
   ```bash
   DB_CONNECTION=pgsql
   DB_HOST=your-project.neon.tech
   DB_PORT=5432
   DB_DATABASE=neondb
   DB_USERNAME=your_neon_user
   DB_PASSWORD=your_neon_password
   ```
   Or use the connection string format:
   ```bash
   DB_URL=postgresql://user:password@host.neon.tech/neondb?sslmode=require
   ```

**Option B: Use Laravel Cloud Database**

1. In Laravel Cloud dashboard → Resources
2. Click "Add Resource" → Select PostgreSQL or MySQL
3. Laravel Cloud will automatically set DB_* environment variables

### 5. Deploy

Laravel Cloud automatically deploys when you push to your connected branch:

```bash
git push origin main
```

Or manually trigger deployment from the dashboard.

### 6. Run Migrations

After first deployment, run migrations:

**Via Dashboard:**
- Go to your project → Click "Run Command"
- Enter: `php artisan migrate --force`

**Via CLI (if available):**
```bash
php artisan migrate --force
```

## Build & Deploy Commands

Laravel Cloud automatically runs these commands during deployment:

**Build Commands (optional, can be configured):**
```bash
composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**Deploy Commands (optional):**
```bash
php artisan migrate --force
php artisan storage:link  # If using public storage
```

## Environment-Specific Configuration

### Production Environment

- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_LEVEL=error`
- `SESSION_ENCRYPT=true`

### Staging Environment

- `APP_ENV=staging`
- `APP_DEBUG=true`
- `LOG_LEVEL=debug`

## Post-Deployment Checklist

- [ ] Verify `APP_KEY` is set
- [ ] Run database migrations
- [ ] Test API endpoints
- [ ] Verify CORS is working with frontend
- [ ] Test authentication flow
- [ ] Check application logs
- [ ] Verify SSL certificate is active
- [ ] Test file uploads (if applicable)
- [ ] Configure queue workers (if using queues)
- [ ] Set up scheduled tasks (cron jobs)

## Queue Workers

If your application uses queues, configure a worker:

1. In Laravel Cloud dashboard → Resources
2. Add a "Worker" resource
3. Set command: `php artisan queue:work --tries=3 --timeout=90`

## Scheduled Tasks

For scheduled tasks (like notification checks):

1. In Laravel Cloud dashboard → Scheduled Tasks
2. Add cron expression: `* * * * *`
3. Command: `php artisan schedule:run`

## Troubleshooting

### Deployment Fails

- Check build logs in Laravel Cloud dashboard
- Verify all environment variables are set
- Ensure `composer.json` is valid
- Check PHP version compatibility

### Database Connection Issues

- Verify database credentials
- Check database host allows connections
- Ensure database exists
- Verify user permissions

### CORS Issues

- Verify `FRONTEND_URL` environment variable matches your frontend domain exactly
- Check `config/cors.php` configuration
- Ensure frontend URL includes protocol (https://)

### Storage Issues

- Verify storage directory permissions
- Check `storage` and `bootstrap/cache` are writable
- Consider using S3 for production file storage

## Support

- Laravel Cloud Documentation: https://cloud.laravel.com/docs
- Laravel Cloud Support: Available in dashboard
