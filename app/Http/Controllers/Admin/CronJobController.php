<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CronJobController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index()
    {
        $cronJobs = [
            [
                'name' => 'License Expiration Check (30 days)',
                'command' => 'notifications:check-licenses --days=30',
                'schedule' => 'Daily at 08:00',
                'description' => 'Checks for licenses expiring within 30 days and sends email notifications to admins',
                'last_run' => $this->getLastRunTime('notifications:check-licenses --days=30'),
            ],
            [
                'name' => 'License Expiration Check (7 days)',
                'command' => 'notifications:check-licenses --days=7',
                'schedule' => 'Daily at 08:30',
                'description' => 'Checks for critical licenses expiring within 7 days and sends urgent notifications',
                'last_run' => $this->getLastRunTime('notifications:check-licenses --days=7'),
            ],
            [
                'name' => 'Low Stock Check',
                'command' => 'notifications:check-stock',
                'schedule' => 'Weekly on Monday at 09:00',
                'description' => 'Checks for stationary items running low on stock and sends notifications',
                'last_run' => $this->getLastRunTime('notifications:check-stock'),
            ],
        ];

        return Inertia::render('Admin/Cron/Index', [
            'cronJobs' => $cronJobs,
            'stats' => $this->getNotificationStats(),
        ]);
    }

    public function runCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string',
        ]);

        try {
            $exitCode = Artisan::call($request->command);

            $output = Artisan::output();

            Log::info("Manual cron job executed: {$request->command}", [
                'exit_code' => $exitCode,
                'output' => $output,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Command executed successfully',
                'output' => $output,
                'exit_code' => $exitCode,
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to execute cron command: {$request->command}", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to execute command: '.$e->getMessage(),
            ], 500);
        }
    }

    public function testNotifications(Request $request)
    {
        $type = $request->get('type', 'licenses');

        try {
            if ($type === 'licenses') {
                $sentCount = $this->notificationService->checkExpiringLicenses(30);
                $message = "Test license expiration notifications sent: {$sentCount} emails";
            } else {
                $sentCount = $this->notificationService->checkLowStock();
                $message = "Test low stock notifications sent: {$sentCount} emails";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'sent_count' => $sentCount,
            ]);

        } catch (\Exception $e) {
            Log::error("Test notification failed for type: {$type}", [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Test failed: '.$e->getMessage(),
            ], 500);
        }
    }

    private function getLastRunTime($command)
    {
        // You can implement a more sophisticated way to track last run times
        // For now, we'll return a placeholder
        return 'Not tracked';
    }

    private function getNotificationStats()
    {
        return [
            'total_licenses' => \App\Models\License::where('status', true)->count(),
            'expiring_soon' => \App\Models\License::where('status', true)
                ->whereNotNull('expiration_date')
                ->where('expiration_date', '>', now())
                ->where('expiration_date', '<=', now()->addDays(30))
                ->count(),
            'total_stationary' => \App\Models\StationaryItem::where('status', true)->count(),
            'low_stock' => \App\Models\StationaryItem::where('status', true)
                ->where('current_stock', '<=', \DB::raw('min_stock'))
                ->count(),
        ];
    }
}
