<?php

use App\Console\Commands\SendPendingRequestsAlert;
use Illuminate\Support\Facades\Artisan;

Artisan::command('alerts:pending-requests', function () {
    $this->call(SendPendingRequestsAlert::class);
})->purpose('Send daily pending requests alert to admins')
    ->weekdays() // Monday to Friday only
    ->at('09:00')
    ->withoutOverlapping();
