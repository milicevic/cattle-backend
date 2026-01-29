# Automated Notifications Setup

## Overview
The system automatically checks for notifications about upcoming calvings and insemination needs.

## Scheduled Tasks

The following tasks are configured to run automatically:

1. **Daily Check** - Runs every day at 8:00 AM
   - Checks all farms for notifications
   - Logs high-priority notifications

2. **Frequent Check** - Runs every 6 hours
   - Checks for urgent notifications
   - Ensures time-sensitive alerts are caught quickly

## Setting Up the Scheduler

To enable automatic notifications, you need to add a single cron entry to your server:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### For Docker/Development:

If you're using Docker, you can add this to your `compose.yaml` or run it manually:

```bash
# In your docker container or local environment
php artisan schedule:work
```

This will run the scheduler continuously (useful for development).

### For Production:

Add this cron entry to your server's crontab:

```bash
crontab -e
```

Then add:
```
* * * * * cd /path-to-your-project/cattle-backend && php artisan schedule:run >> /dev/null 2>&1
```

## Manual Execution

You can also run the notification check manually:

```bash
# Check all farms
php artisan notifications:check

# Check specific farm
php artisan notifications:check --farm-id=1
```

## Notification Types

The system checks for:

1. **Calving Due Soon** - Cows due to calve within 15 days
   - High priority: ≤5 days remaining
   - Medium priority: 6-15 days remaining

2. **Insemination Due** - Cows needing insemination
   - High priority: Overdue (>90 days) or within 5 days of ideal window
   - Medium priority: Approaching ideal window (45-50 days)

## Logs

High-priority notifications are automatically logged to:
- `storage/logs/laravel.log`

Check logs for notification history:
```bash
tail -f storage/logs/laravel.log | grep "High priority notifications"
```
