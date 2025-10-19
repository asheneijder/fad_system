<?php

namespace App\Mail;

use App\Models\RequestItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestRejectedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestItem;

    public $rejectionReason;

    /**
     * Create a new message instance.
     */
    public function __construct(RequestItem $requestItem, string $rejectionReason)
    {
        $this->requestItem = $requestItem;
        $this->rejectionReason = $rejectionReason;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Update on Your Stationary Request - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.request-rejected',
            with: [
                'requestItem' => $this->requestItem,
                'rejectionReason' => $this->rejectionReason,
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
