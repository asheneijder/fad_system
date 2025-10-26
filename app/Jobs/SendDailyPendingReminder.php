<?php

namespace App\Jobs;

use App\Mail\DailyPendingReminderMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyPendingReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $approver;

    public $pendingClaims;

    /**
     * Create a new job instance.
     */
    public function __construct(User $approver, $pendingClaims)
    {
        $this->approver = $approver;
        $this->pendingClaims = $pendingClaims;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            if ($this->pendingClaims->isEmpty()) {
                return;
            }

            // Sort claims by days pending (oldest first)
            $sortedClaims = $this->pendingClaims->sortByDesc('days_pending');

            // Send daily reminder email
            Mail::to($this->approver->email)->send(
                new DailyPendingReminderMail($this->approver, $sortedClaims)
            );

            Log::info("Daily pending reminder sent to {$this->approver->email} with {$this->pendingClaims->count()} claims");

        } catch (\Exception $e) {
            Log::error("Failed to send daily reminder to {$this->approver->email}. Error: ".$e->getMessage());
        }
    }
}
