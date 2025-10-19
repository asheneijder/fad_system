<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LowStockAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $items;

    public $customSubject;

    public $customMessage;

    /**
     * Create a new message instance.
     */
    public function __construct($items, $subject = null, $message = null)
    {
        $this->items = $items;
        $this->customSubject = $subject ?? 'Low Stock Alert';
        $this->customMessage = $message ?? 'The following stationary items are running low on stock.';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->customSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.low-stock',
            with: [
                'items' => $this->items,
                'subject' => $this->customSubject,
                'message' => $this->customMessage,
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
