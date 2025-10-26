<?php

namespace App\Jobs;

use App\Mail\ClaimRejectedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClaimRejectionNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $claim;

    public $claimType;

    public $approver;

    public $rejectionReason;

    /**
     * Create a new job instance.
     */
    public function __construct($claim, string $claimType, User $approver, string $rejectionReason)
    {
        $this->claim = $claim;
        $this->claimType = $claimType;
        $this->approver = $approver;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $user = $this->getClaimUser();

            if (! $user) {
                Log::error("User not found for claim ID: {$this->claim->id}, Type: {$this->claimType}");

                return;
            }

            $claimData = $this->getClaimData();

            // Send email to user using Mailable class
            Mail::to($user->email)->send(new ClaimRejectedMail($user, $this->claim, $this->claimType, $claimData, $this->approver, $this->rejectionReason));

            Log::info("Rejection email sent successfully for {$this->claimType} claim ID: {$this->claim->id} to user: {$user->email}");

        } catch (\Exception $e) {
            Log::error("Failed to send rejection email for claim ID: {$this->claim->id}. Error: ".$e->getMessage());
        }
    }

    /**
     * Get the user who submitted the claim
     */
    private function getClaimUser()
    {
        return User::find($this->claim->user_id);
    }

    /**
     * Get claim-specific data for the email
     */
    private function getClaimData()
    {
        $baseData = [
            'claim_id' => $this->claim->id,
            'claim_date' => $this->claim->formatted_claim_date ?? $this->claim->created_at?->format('M d, Y'),
            'amount' => $this->getClaimAmount(),
            'purpose' => $this->claim->purpose ?? 'N/A',
        ];

        // Add type-specific data
        switch ($this->claimType) {
            case 'travel':
                return array_merge($baseData, [
                    'vehicle_type' => $this->claim->vehicle_type,
                    'total_distance' => $this->claim->total_distance,
                    'total_cost' => $this->claim->total_cost,
                ]);

            case 'daily':
                return array_merge($baseData, [
                    'allowance_type' => $this->claim->allowance_type,
                    'daily_rate' => $this->claim->daily_rate,
                    'claim_percentage' => $this->claim->claim_percentage,
                    'destination' => $this->claim->destination,
                ]);

            case 'accommodation':
                return array_merge($baseData, [
                    'hotel_name' => $this->claim->hotel_name,
                    'check_in_date' => $this->claim->formatted_check_in_date ?? $this->claim->check_in_date?->format('M d, Y'),
                    'check_out_date' => $this->claim->formatted_check_out_date ?? $this->claim->check_out_date?->format('M d, Y'),
                    'total_amount' => $this->claim->total_amount,
                ]);

            case 'transportation':
                return array_merge($baseData, [
                    'transport_type' => $this->claim->transport_type,
                    'from_location' => $this->claim->from_location,
                    'to_location' => $this->claim->to_location,
                    'amount' => $this->claim->amount,
                ]);

            default:
                return $baseData;
        }
    }

    /**
     * Get the claim amount based on type
     */
    private function getClaimAmount()
    {
        return match ($this->claimType) {
            'travel' => $this->claim->total_cost,
            'daily' => $this->claim->claim_amount,
            'accommodation' => $this->claim->total_amount,
            'transportation' => $this->claim->amount,
            default => 0,
        };
    }
}
