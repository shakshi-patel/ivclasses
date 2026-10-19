<?php

namespace App\Mail;

use App\Models\ProjectInquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DemoClientReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ProjectInquiry $inquiry,
        public string $dateLabel,
        public string $timeLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We received your Class Flow demo request',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.demo.client-received',
        );
    }
}
