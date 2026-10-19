<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InstituteRegistrationCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     institute_name: string,
     *     branch_name?: string,
     *     admin_name: string,
     *     admin_email: string,
     *     admin_password: string,
     *     manager_name?: string,
     *     manager_email?: string,
     *     manager_password?: string,
     *     branches?: list<array{branch_name: string, manager_name: string, manager_email: string, manager_password: string}>,
     *     has_separate_branch_login?: bool,
     *     institute_login_url: string,
     *     branch_login_url: string
     * }  $credentials
     */
    public function __construct(
        public array $credentials
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Class Flow login details — '.$this->credentials['institute_name'],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.registration.credentials',
        );
    }
}
