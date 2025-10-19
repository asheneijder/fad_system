<?php

namespace App\Console\Commands;

use App\Mail\PendingRequestsAlertEmail;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPendingRequestsAlert extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'alerts:pending-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send pending requests alert to admins';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting pending requests alert...');

        // Get pending requests with their items and users
        $pendingRequests = RequestItem::with(['user', 'items.stationaryItem'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        // If no pending requests, don't send email
        if ($pendingRequests->isEmpty()) {
            $this->info('No pending requests found. No email sent.');
            Log::info('No pending requests found for daily alert.');

            return Command::SUCCESS;
        }

        $this->info("Found {$pendingRequests->count()} pending requests.");

        // Get admin users using Spatie Permission
        $admins = User::role('fad-approver')->get();

        $this->info("Sending to {$admins->count()} admin(s).");

        $sentCount = 0;
        foreach ($admins as $admin) {
            try {
                Mail::to($admin->email)->send(
                    new PendingRequestsAlertEmail($pendingRequests, $admin)
                );
                $this->line("✅ Sent to: {$admin->email} ({$admin->name})");
                $sentCount++;

            } catch (\Exception $e) {
                $this->error("❌ Failed to send to {$admin->email}: ".$e->getMessage());
                Log::error("Failed to send pending requests alert to {$admin->email}: ".$e->getMessage());
            }
        }

        $this->info("📧 Successfully sent {$sentCount} out of {$admins->count()} email(s).");
        Log::info("Pending requests alert sent to {$sentCount} admin(s). {$pendingRequests->count()} pending requests found.");

        return $sentCount > 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
