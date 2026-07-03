<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountRestoreOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('mail.account_restore_otp.subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.otp',
            with: [
                'otp' => $this->otp,
                'intro' => __('mail.account_restore_otp.intro'),
                'headerTitle' => __('mail.account_restore_otp.title'),
            ],
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
