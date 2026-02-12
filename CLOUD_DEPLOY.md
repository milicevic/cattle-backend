# Laravel Cloud Deployment Guide

## Prerequisites

1. Laravel Cloud account (https://cloud.laravel.com) or compatible hosting (Laravel Forge, DigitalOcean, etc.)
2. PostgreSQL or MySQL database
3. Git repository (GitHub, GitLab, or Bitbucket)
4. Domain name (optional)

## Laravel Cloud Specific

Laravel Cloud automatically detects Laravel applications and handles deployment automatically. No special configuration files (like Procfile) are needed.

## Environment Variables

Set the following environment variables in your cloud hosting platform:

### Required Variables

```bash
APP_NAME="Cattle Management"
APP_ENV=production
APP_KEY=base64:your-generated-key-here
APP_DEBUG=false
APP_URL=https://your-backend-domain.com

# Database Configuration
# For Neon PostgreSQL (recommended)
# DB_CONNECTION=pgsql
# DB_HOST=your-project.neon.tech
# DB_PORT=5432
# DB_DATABASE=neondb
# DB_USERNAME=your_neon_user
# DB_PASSWORD=your_neon_password
# Or use connection string:
DB_URL=postgresql://neondb_owner:npg_J5GnkCA3VhlX@ep-wandering-paper-ag9vhwij-pooler.c-2.eu-central-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require
# For Laravel Cloud database (alternative)
# DB_CONNECTION=pgsql
# DB_HOST=your-cloud-db-host
# DB_PORT=5432
# DB_DATABASE=your_database_name
# DB_USERNAME=your_db_user
# DB_PASSWORD=your_db_password

# Frontend URL for CORS
# Primary frontend URL
FRONTEND_URL=https://your-frontend-domain.vercel.app

# Additional frontend URLs (comma-separated, optional)
# For multiple domains or preview deployments
# FRONTEND_URLS=https://www.yourdomain.com,https://yourdomain.com,https://staging.yourdomain.com

# Frontend domain pattern for wildcard matching (optional)
# Example: /^https:\/\/.*\.yourdomain\.com$/
# FRONTEND_DOMAIN_PATTERN=

# Session Configuration
SESSION_DRIVER=database
SESSION_ENCRYPT=true

# Cache & Queue
CACHE_STORE=database
QUEUE_CONNECTION=database

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### Optional Variables

```bash
# Redis (for better performance)
REDIS_HOST=your-redis-host
REDIS_PASSWORD=your-redis-password
REDIS_PORT=6379

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mail-username
MAIL_PASSWORD=your-mail-password
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# AWS S3 (for file storage)
AWS_ACCESS_KEY_ID=your-aws-key
AWS_SECRET_ACCESS_KEY=your-aws-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
```

## Deployment Steps

### 1. Generate Application Key

```bash
php artisan key:generate
```

### 2. Run Migrations

```bash
php artisan migrate --force
```

### 3. Optimize for Production

```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev
```

### 4. Set Storage Permissions

```bash
# Make sure storage and bootstrap/cache are writable
chmod -R 775 storage bootstrap/cache
```

### 5. Queue Worker Setup

If using queues, set up a queue worker:

```bash
php artisan queue:work --tries=3 --timeout=90
```

For production, use a process manager like Supervisor or Laravel Horizon.

## Platform-Specific Instructions

### Laravel Cloud

1. **Sign up** at https://cloud.laravel.com
2. **Create a new project** and connect your Git repository
3. **Set environment variables** in the dashboard (see Required Variables section)
4. **Configure resources:**
   - Add a database (MySQL or PostgreSQL)
   - Add Redis (optional, for better performance)
   - Configure file storage (local or S3)
5. **Deploy:** Laravel Cloud automatically deploys on every push to your main branch
6. **Run migrations:** Use the Laravel Cloud dashboard or CLI to run migrations:
   ```bash
   php artisan migrate --force
   ```

**Note:** Laravel Cloud automatically handles:
- PHP version detection
- Composer dependencies
- Build process
- Zero-downtime deployments
- SSL certificates

### Laravel Forge

1. Connect your Git repository
2. Set environment variables in the dashboard
3. Configure deployment script:
   ```bash
   cd /home/forge/your-site
   git pull origin main
   composer install --no-interaction --prefer-dist --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

### Heroku

**Note:** For Heroku, you'll need a `Procfile` in the root directory:
```
web: vendor/bin/heroku-php-apache2 public/
```

1. Install Heroku CLI
2. Create app: `heroku create your-app-name`
3. Add PostgreSQL: `heroku addons:create heroku-postgresql:hobby-dev`
4. Set environment variables: `heroku config:set APP_KEY=your-key FRONTEND_URL=your-frontend-url`
5. Deploy: `git push heroku main`
6. Run migrations: `heroku run php artisan migrate`

### DigitalOcean App Platform

1. Connect Git repository
2. Select Laravel as framework
3. Configure environment variables
4. Set build command: `composer install --optimize-autoloader --no-dev && php artisan config:cache && php artisan route:cache && php artisan view:cache`
5. Set run command: `php artisan serve --host=0.0.0.0 --port=8080`

## Post-Deployment Checklist

- [ ] Verify `APP_KEY` is set
- [ ] Run database migrations
- [ ] Test API endpoints
- [ ] Verify CORS is working with frontend
- [ ] Test authentication flow
- [ ] Check queue workers are running (if using queues)
- [ ] Verify file storage permissions
- [ ] Test email sending (if configured)
- [ ] Monitor logs for errors
- [ ] Set up SSL certificate (HTTPS)

## Troubleshooting

### Database Connection Issues

- Verify database credentials are correct
- Check database host allows connections from your server IP
- Ensure database exists and user has proper permissions

### CORS Issues

- Verify `FRONTEND_URL` environment variable is set correctly
- Check `config/cors.php` configuration
- Ensure frontend URL matches exactly (including protocol and port)

### Session Issues

- Verify `SESSION_DRIVER` is set to `database`
- Run `php artisan session:table` migration if not already done
- Check `SESSION_ENCRYPT` is set to `true` in production

### Storage Issues

- Ensure `storage` and `bootstrap/cache` directories are writable
- Check file permissions: `chmod -R 775 storage bootstrap/cache`
- Consider using S3 for file storage in production

### Performance Optimization

- Enable OPcache in PHP configuration
- Use Redis for cache and sessions
- Set up queue workers for background jobs
- Enable HTTP/2 and compression
- Use CDN for static assets

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] Strong database passwords
- [ ] HTTPS enabled
- [ ] CORS properly configured
- [ ] Session encryption enabled
- [ ] CSRF protection enabled
- [ ] Rate limiting configured
- [ ] Regular security updates
