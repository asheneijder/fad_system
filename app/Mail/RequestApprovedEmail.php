<?php

namespace App\Mail;

use App\Models\RequestItem;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestApprovedEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestItem;

    /**
     * Create a new message instance.
     */
    public function __construct(RequestItem $requestItem)
    {
        $this->requestItem = $requestItem;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Stationary Request Has Been Approved - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.request-approved',
            with: [
                'requestItem' => $this->requestItem,
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
