<?php

namespace App\Mail;

use App\Models\ProjectInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoAdminActionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectInquiry $inquiry,
        public string $dateLabel,
        public string $timeLabel,
        public string $confirmUrl,
        public string $declineUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirm demo? — ' . $this->inquiry->inquiry_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo.admin-action',
        );
    }
}
