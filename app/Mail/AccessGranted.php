<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Bestätigung an die Person: Zugang freigeschaltet.
 */
class AccessGranted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Dein Zugang ist freigeschaltet');
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.access-granted');
    }
}
