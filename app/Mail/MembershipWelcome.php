<?php

namespace App\Mail;

use App\Models\Membership;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aufnahmebestätigung an das neue Mitglied.
 */
class MembershipWelcome extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Membership $membership) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Willkommen im Verein Treffpunkt Bettingen');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.membership-welcome');
    }
}
