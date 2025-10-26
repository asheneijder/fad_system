<?php

namespace App\Jobs;

use App\Mail\AssetAssignmentMail;
use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendAssetAssignmentNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $asset;

    public $user;

    public $assignment;

    public $assignedBy;

    /**
     * Create a new job instance.
     */
    public function __construct(Asset $asset, User $user, AssetAssignment $assignment, User $assignedBy)
    {
        $this->asset = $asset;
        $this->user = $user;
        $this->assignment = $assignment;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Mail::to($this->user->email)->send(
            new AssetAssignmentMail(
                $this->asset,
                $this->user,
                $this->assignment,
                $this->assignedBy
            )
        );
    }
}
