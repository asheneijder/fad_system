<?php

namespace App\Jobs;

use App\Mail\RequestApprovedEmail;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendRequestApprovedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $requestItem;

    /**
     * Create a new job instance.
     */
    public function __construct(RequestItem $requestItem)
    {
        $this->requestItem = $requestItem;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Send email to the user who made the request
        $user = $this->requestItem->user;

        Mail::to($user->email)->send(
            new RequestApprovedEmail($this->requestItem)
        );
    }
}
