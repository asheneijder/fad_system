<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClaimRejectedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public $claim;

    public $claimType;

    public $claimData;

    public $approver;

    public $rejectionReason;

    public $claimTypeDisplay;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $claim, string $claimType, array $claimData, User $approver, string $rejectionReason)
    {
        $this->user = $user;
        $this->claim = $claim;
        $this->claimType = $claimType;
        $this->claimData = $claimData;
        $this->approver = $approver;
        $this->rejectionReason = $rejectionReason;
        $this->claimTypeDisplay = $this->getClaimTypeDisplay();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = "Your {$this->claimTypeDisplay} Claim Has Been Rejected";

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.claim-rejected',
            with: [
                'user' => $this->user,
                'claim' => $this->claim,
                'claimType' => $this->claimType,
                'claimData' => $this->claimData,
                'approver' => $this->approver,
                'rejectionReason' => $this->rejectionReason,
                'claimTypeDisplay' => $this->claimTypeDisplay,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Get display name for claim type
     */
    private function getClaimTypeDisplay()
    {
        return match ($this->claimType) {
            'travel' => 'Travel',
            'daily' => 'Daily Allowance',
            'accommodation' => 'Accommodation',
            'transportation' => 'Transportation',
            default => 'Claim',
        };
    }
}
