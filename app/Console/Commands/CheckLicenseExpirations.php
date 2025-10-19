<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLicenseExpirations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:check-licenses {--days=30 : Check licenses expiring within this many days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expiring licenses and send notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService)
    {
        $days = (int) $this->option('days');

        $this->info("Checking for licenses expiring within {$days} days...");

        $sentCount = $notificationService->checkExpiringLicenses($days);

        if ($sentCount > 0) {
            $this->info("Successfully sent {$sentCount} license expiration notification(s).");
            Log::info("License expiration check completed. Notifications sent: {$sentCount}");
        } else {
            $this->info('No license expiration notifications were sent.');
            Log::info('License expiration check completed. No notifications sent.');
        }

        return Command::SUCCESS;
    }
}
