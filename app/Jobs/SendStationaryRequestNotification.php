<?php

namespace App\Jobs;

use App\Mail\StationaryItemRequestEmail;
use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendStationaryRequestNotification implements ShouldQueue
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
        $getSuperAdmins = User::role('fad-approver')->get();

        foreach ($getSuperAdmins as $admin) {
            Mail::to($admin->email)->send(
                new StationaryItemRequestEmail($this->requestItem, $admin)
            );
        }
    }
}
