<?php

namespace App\Mail;

use App\Models\RoomReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Gemeinsame Grundlage der E-Mails zu Raumreservationen.
 */
abstract class ReservationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RoomReservation $reservation) {}

    abstract protected function subjectLine(): string;

    abstract protected function markdownView(): string;

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine());
    }

    public function content(): Content
    {
        return new Content(markdown: $this->markdownView());
    }
}
