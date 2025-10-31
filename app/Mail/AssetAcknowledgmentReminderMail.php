<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AssetAcknowledgmentReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    public $pendingAssignments;
    public $pendingCount;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, $pendingAssignments)
    {
        $this->user = $user;
        $this->pendingAssignments = $pendingAssignments;
        $this->pendingCount = $pendingAssignments->count();
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reminder: Pending Asset Acknowledgment Required',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.assets.acknowledgment-reminder',
            with: [
                'user' => $this->user,
                'pendingAssignments' => $this->pendingAssignments,
                'pendingCount' => $this->pendingCount,
                'acknowledgmentUrl' => route('user.asset-registry.index'),
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
}