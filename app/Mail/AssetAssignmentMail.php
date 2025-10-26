<?php

namespace App\Mail;

use App\Models\Asset;
use App\Models\AssetAssignment;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class AssetAssignmentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $asset;

    public $user;

    public $assignment;

    public $assignedBy;

    public $confirmationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Asset $asset, User $user, AssetAssignment $assignment, User $assignedBy)
    {
        $this->asset = $asset;
        $this->user = $user;
        $this->assignment = $assignment;
        $this->assignedBy = $assignedBy;

        // Create temporary signed URL that expires in 7 days
        $this->confirmationUrl = URL::temporarySignedRoute(
            'asset-assignment.confirm',
            now()->addDays(7),
            ['assignment' => $assignment->id]
        );
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ARTB Asset Assignment - Please Confirm',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.asset-assignment',
            with: [
                'asset' => $this->asset,
                'user' => $this->user,
                'assignment' => $this->assignment,
                'assignedBy' => $this->assignedBy,
                'confirmationUrl' => $this->confirmationUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
