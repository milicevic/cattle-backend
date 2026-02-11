# Neon PostgreSQL Setup Guide

## What is Neon?

Neon is a serverless PostgreSQL platform that provides:
- Automatic scaling
- Branching (database branching like Git)
- Pay-as-you-go pricing
- Built-in connection pooling
- Free tier available

## Setup Steps

### 1. Create Neon Account

1. Go to https://neon.tech
2. Sign up for a free account
3. Create a new project

### 2. Create Database

1. In Neon dashboard, create a new project
2. Note your connection details:
   - Host: `your-project.neon.tech`
   - Database name: Usually `neondb` or your project name
   - Username: Your Neon username
   - Password: Generated password (save it securely!)

### 3. Get Connection String

Neon provides a connection string in this format:
```
postgresql://username:password@host.neon.tech/neondb?sslmode=require
```

### 4. Configure Laravel

**Option A: Using Individual Variables (Recommended)**

In your `.env` or Laravel Cloud environment variables:

```bash
DB_CONNECTION=pgsql
DB_HOST=your-project.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=your_neon_username
DB_PASSWORD=your_neon_password
```

**Option B: Using Connection String**

Laravel supports `DB_URL` environment variable:

```bash
DB_URL=postgresql://username:password@host.neon.tech/neondb?sslmode=require
```

### 5. Test Connection

```bash
php artisan migrate:status
```

If successful, you should see your migration status.

### 6. Run Migrations

```bash
php artisan migrate --force
```

## Connection Pooling

Neon provides connection pooling for better performance. You can use:

- **Session mode** (default): Direct connection
- **Transaction mode**: Pooled connection (better for serverless)

To use transaction mode, append `?sslmode=require&pgbouncer=true` to your connection string.

## Security Best Practices

1. **Use SSL**: Always use `sslmode=require` in production
2. **Rotate Passwords**: Regularly update database passwords
3. **IP Allowlisting**: Configure IP restrictions in Neon dashboard if possible
4. **Environment Variables**: Never commit credentials to Git

## Troubleshooting

### Connection Timeout

- Check firewall settings
- Verify host and port are correct
- Ensure SSL is enabled (`sslmode=require`)

### Authentication Failed

- Verify username and password
- Check if user has proper permissions
- Ensure database name is correct

### SSL Required Error

- Add `?sslmode=require` to connection string
- Or set `DB_SSLMODE=require` in environment variables

## Migration from Local Database

If migrating from local PostgreSQL to Neon:

1. Export local database:
   ```bash
   pg_dump -h localhost -U your_user -d cattle_backend > backup.sql
   ```

2. Import to Neon:
   ```bash
   psql "postgresql://user:password@host.neon.tech/neondb?sslmode=require" < backup.sql
   ```

## Free Tier Limits

Neon free tier includes:
- 0.5 GB storage
- Limited compute time
- Perfect for development and small projects

For production, consider upgrading to a paid plan.

## Resources

- Neon Documentation: https://neon.tech/docs
- Neon Dashboard: https://console.neon.tech
- Laravel Database Docs: https://laravel.com/docs/database
