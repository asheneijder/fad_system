<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-stock';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for low stock items and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $this->info('Checking for low stock items...');

        $sentCount = $notificationService->checkLowStock();

        if ($sentCount > 0) {
            $this->info("Successfully sent {$sentCount} low stock notification(s).");
            Log::info("Low stock check completed. Notifications sent: {$sentCount}");
        } else {
            $this->info('No low stock notifications were sent.');
            Log::info('Low stock check completed. No notifications sent.');
        }

        return Command::SUCCESS;
    }
}
