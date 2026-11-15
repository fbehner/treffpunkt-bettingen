<?php

namespace App\Mail;

use App\Models\MembershipApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Eingangsbestätigung an die antragstellende Person.
 */
class MembershipReceived extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public MembershipApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Euer Beitrittsgesuch beim Treffpunkt Bettingen',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.membership-received',
        );
    }
}
