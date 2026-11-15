<?php

namespace App\Mail;

use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Meldung an den Verein über ein neues Gesuch.
 */
class NewMembershipApplication extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MembershipApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Neues Beitrittsgesuch: '.$this->application->displayName(),
            replyTo: [$this->application->email],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.new-application',
        );
    }
}
