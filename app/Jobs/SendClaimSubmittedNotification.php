<?php

namespace App\Jobs;

use App\Mail\ClaimSubmittedMail;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClaimSubmittedNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $claim;

    public $claimType;

    public $submitter;

    /**
     * Create a new job instance.
     */
    public function __construct($claim, string $claimType, User $submitter)
    {
        $this->claim = $claim;
        $this->claimType = $claimType;
        $this->submitter = $submitter;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $approver = $this->getClaimApprover();

            if (! $approver) {
                Log::error("Approver not found for claim ID: {$this->claim->id}, Type: {$this->claimType}");

                return;
            }

            $claimData = $this->getClaimData();

            // Send email to approver using Mailable class
            Mail::to($approver->email)->send(new ClaimSubmittedMail($approver, $this->claim, $this->claimType, $claimData, $this->submitter));

            Log::info("Submission notification email sent successfully for {$this->claimType} claim ID: {$this->claim->id} to approver: {$approver->email}");

        } catch (\Exception $e) {
            Log::error("Failed to send submission notification email for claim ID: {$this->claim->id}. Error: ".$e->getMessage());
        }
    }

    /**
     * Get the approver for the claim
     */
    private function getClaimApprover()
    {
        return User::find($this->claim->approver_id);
    }

    /**
     * Get claim-specific data for the email
     */
    private function getClaimData()
    {
        $baseData = [
            'claim_id' => $this->claim->id,
            'claim_date' => $this->claim->claim_date ?? $this->claim->created_at,
            'amount' => $this->getClaimAmount(),
            'purpose' => $this->claim->purpose ?? 'N/A',
            'submitter_name' => $this->submitter->name,
            'submitter_email' => $this->submitter->email,
        ];

        // Add type-specific data
        switch ($this->claimType) {
            case 'travel':
                return array_merge($baseData, [
                    'vehicle_type' => $this->claim->vehicle_type,
                    'total_distance' => $this->claim->total_distance,
                    'total_cost' => $this->claim->total_cost,
                    'travel_from' => $this->claim->travel_from,
                    'travel_to' => $this->claim->travel_to,
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
                    'check_in_date' => $this->claim->check_in_date,
                    'check_out_date' => $this->claim->check_out_date,
                    'total_amount' => $this->claim->total_amount,
                    'destination_city' => $this->claim->destination_city,
                    'destination_country' => $this->claim->destination_country,
                ]);

            case 'transportation':
                return array_merge($baseData, [
                    'transport_type' => $this->claim->transport_type,
                    'from_location' => $this->claim->from_location,
                    'to_location' => $this->claim->to_location,
                    'amount' => $this->claim->amount,
                    'trip_type' => $this->claim->trip_type,
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
