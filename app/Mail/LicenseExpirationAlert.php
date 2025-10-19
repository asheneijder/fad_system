<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LicenseExpirationAlert extends Mailable
{
    use Queueable, SerializesModels;

    public $licenses;

    public $customSubject;

    public $customMessage;

    public $alertLevel;

    public $days;

    /**
     * Create a new message instance.
     */
    public function __construct($licenses, $subject = null, $message = null, $days = 30)
    {
        $this->licenses = $licenses;
        $this->customSubject = $subject ?? 'License Expiration Alert';
        $this->customMessage = $message ?? 'The following licenses are expiring soon.';
        $this->days = $days;

        // Determine alert level based on closest expiration
        $criticalCount = $licenses->filter(function ($license) {
            return $license->expiration_date->diffInDays(now()) <= 7;
        })->count();

        $this->alertLevel = $criticalCount > 0 ? 'critical' : 'warning';
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
            view: 'emails.license-expiration',
            with: [
                'licenses' => $this->licenses,
                'subject' => $this->customSubject,
                'message' => $this->customMessage,
                'alertLevel' => $this->alertLevel,
                'days' => $this->days,
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
