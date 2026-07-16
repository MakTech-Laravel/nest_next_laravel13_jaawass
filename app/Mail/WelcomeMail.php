<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $firstName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.welcome.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.auth.welcome',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<string, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
