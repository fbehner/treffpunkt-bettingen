<?php

namespace App\Mail;

use App\Models\Invitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Einladung, ein Konto für die Verwaltung anzulegen.
 */
class UserInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Invitation $invitation, public string $link) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Einladung: Zugang zur Verwaltung des Treffpunkt Bettingen');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.user-invitation');
    }
}
