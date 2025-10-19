<?php

namespace App\Jobs;

use App\Mail\RequestRejectedEmail;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRequestRejectedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requestItem;

    public $rejectionReason;

    /**
     * Create a new job instance.
     */
    public function __construct(RequestItem $requestItem, string $rejectionReason)
    {
        $this->requestItem = $requestItem;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send email to the user who made the request
        $user = $this->requestItem->user;

        Mail::to($user->email)->send(
            new RequestRejectedEmail($this->requestItem, $this->rejectionReason)
        );
    }
}
