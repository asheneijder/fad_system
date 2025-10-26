<?php

use App\Console\Commands\SendPendingRequestsAlert;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Low stock notifications - run daily at 8:00 AM on weekdays
Artisan::command('notifications:check-stock', function () {
    $this->call(\App\Console\Commands\CheckLowStock::class);
})->purpose('Check for low stock items and send notifications')
    ->weekdays() // Monday to Friday only
    ->at('08:00')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping();

// Claims reminders at 8:30 AM
Artisan::command('claims:send-reminders', function () {
    $this->call(\App\Console\Commands\SendPendingClaimsReminder::class);
})->purpose('Send daily pending claims reminders to approvers')
    ->weekdays() // Monday to Friday only
    ->at('08:30')
    ->timezone('Asia/Kuala_Lumpur')
    ->withoutOverlapping();

// Other alerts at 9:00 AM
Artisan::command('alerts:pending-requests', function () {
    $this->call(SendPendingRequestsAlert::class);
})->purpose('Send daily pending requests alert to admins')
    ->weekdays() // Monday to Friday only
    ->at('09:00')
    ->withoutOverlapping();

// Backup command
Schedule::command('backup:run')->dailyAt('02:00')->withoutOverlapping();
