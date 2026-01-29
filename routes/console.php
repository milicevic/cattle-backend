<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule notification checks
Schedule::command('notifications:check')
    ->dailyAt('08:00') // Check every day at 8 AM
    ->description('Check for upcoming calvings and insemination needs');

// Optional: Check more frequently (every 6 hours) for high-priority notifications
Schedule::command('notifications:check')
    ->everySixHours()
    ->description('Check for urgent notifications every 6 hours');
