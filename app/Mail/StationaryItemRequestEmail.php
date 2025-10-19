<?php

namespace App\Mail;

use App\Models\RequestItem;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StationaryItemRequestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestItem;

    public $admin;

    /**
     * Create a new message instance.
     */
    public function __construct(RequestItem $requestItem, User $admin)
    {
        $this->requestItem = $requestItem;
        $this->admin = $admin;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Stationary Item Request - '.config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.stationary-item-request',
            with: [
                'requestItem' => $this->requestItem,
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
