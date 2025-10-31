<?php

namespace App\Jobs;

use App\Mail\AssetAcknowledgmentReminderMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAcknowledgmentReminder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $pendingAssignments;
    public $sentBy;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, $pendingAssignments, User $sentBy)
    {
        $this->user = $user;
        $this->pendingAssignments = $pendingAssignments;
        $this->sentBy = $sentBy;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Send email using the Mail class
            Mail::to($this->user->email)
                ->send(new AssetAcknowledgmentReminderMail($this->user, $this->pendingAssignments));

            // Log the activity
            activity()
                ->causedBy($this->sentBy)
                ->performedOn($this->user)
                ->withProperties([
                    'pending_count' => $this->pendingAssignments->count(),
                    'asset_ids' => $this->pendingAssignments->pluck('asset_id')->toArray()
                ])
                ->log('sent acknowledgment reminder');

        } catch (\Exception $e) {
            // Log the error but don't fail the job to prevent queue blocking
            \Log::error('Failed to send acknowledgment reminder: ' . $e->getMessage(), [
                'user_id' => $this->user->id,
                'sent_by' => $this->sentBy->id
            ]);
            
            // You might want to implement a retry mechanism here
            if ($this->attempts() < 3) {
                $this->release(60); // Retry after 60 seconds
            }
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        \Log::error('Acknowledgment reminder job failed: ' . $exception->getMessage(), [
            'user_id' => $this->user->id,
            'sent_by' => $this->sentBy->id
        ]);
    }
}