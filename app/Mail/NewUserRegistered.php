<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Meldung an die Webadmins: neues Konto wartet auf Rollen.
 */
class NewUserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Neues Konto wartet auf Freigabe: '.$this->user->name);
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.new-user-registered');
    }
}
