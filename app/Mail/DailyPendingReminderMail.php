<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyPendingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public $approver;

    public $pendingClaims;

    public $totalAmount;

    public $oldestClaimTime;

    /**
     * Create a new message instance.
     */
    public function __construct(User $approver, $pendingClaims)
    {
        $this->approver = $approver;
        $this->pendingClaims = $pendingClaims;
        $this->totalAmount = $pendingClaims->sum('amount');
        $this->oldestClaimTime = $this->getOldestClaimTime($pendingClaims);
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = "Daily Reminder: {$this->pendingClaims->count()} Pending Claims Awaiting Approval";

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
            markdown: 'emails.daily-pending-reminder',
            with: [
                'approver' => $this->approver,
                'pendingClaims' => $this->pendingClaims,
                'totalAmount' => $this->totalAmount,
                'oldestClaimTime' => $this->oldestClaimTime,
                'totalClaims' => $this->pendingClaims->count(),
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
     * Get the oldest claim time in human-readable format
     */
    private function getOldestClaimTime($pendingClaims)
    {
        $oldestClaim = $pendingClaims->sortByDesc('days_pending')->first();

        if (! $oldestClaim) {
            return 'N/A';
        }

        return $oldestClaim['pending_display'];
    }
}
