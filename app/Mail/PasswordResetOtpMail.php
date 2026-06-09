<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.password_reset_otp.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.password-reset-otp',
        );
    }

    /**
     * @return array<string, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
