<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClaimSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $approver;

    public $claim;

    public $claimType;

    public $claimData;

    public $submitter;

    /**
     * Create a new message instance.
     */
    public function __construct(User $approver, $claim, string $claimType, array $claimData, User $submitter)
    {
        $this->approver = $approver;
        $this->claim = $claim;
        $this->claimType = $claimType;
        $this->claimData = $claimData;
        $this->submitter = $submitter;
        $this->claimTypeDisplay = $this->getClaimTypeDisplay();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = "New {$this->claimTypeDisplay} Claim Submitted for Approval";

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
            markdown: 'emails.claim-submitted',
            with: [
                'approver' => $this->approver,
                'claim' => $this->claim,
                'claimType' => $this->claimType,
                'claimData' => $this->claimData,
                'submitter' => $this->submitter,
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
