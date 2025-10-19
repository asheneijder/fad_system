<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PendingRequestsAlertEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $pendingRequests;

    public $admin;

    /**
     * Create a new message instance.
     */
    public function __construct($pendingRequests, User $admin)
    {
        $this->pendingRequests = $pendingRequests;
        $this->admin = $admin;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $count = $this->pendingRequests->count();

        return new Envelope(
            subject: "{$count} Pending Stationary Requests Need Your Attention - ".config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.pending-requests-alert',
            with: [
                'pendingRequests' => $this->pendingRequests,
                'admin' => $this->admin,
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
