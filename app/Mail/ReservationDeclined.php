<?php

namespace App\Mail;

/**
 * Absage einer Anfrage.
 */
class ReservationDeclined extends ReservationMail
{
    protected function subjectLine(): string
    {
        return 'Eure Reservationsanfrage für den '.$this->reservation->starts_at->format('d.m.Y');
    }

    protected function markdownView(): string
    {
        return 'mail.reservation-declined';
    }
}
