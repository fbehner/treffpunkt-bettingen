<?php

namespace App\Mail;

use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bitte um Bestätigung an die erziehungsberechtigte Person.
 */
class GuardianConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MembershipApplication $application, public string $link) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bitte bestätigen: Beitrittsgesuch von '.$this->application->first_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.guardian-confirmation',
        );
    }
}
