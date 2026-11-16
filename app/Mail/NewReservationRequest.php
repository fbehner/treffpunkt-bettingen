<?php

namespace App\Mail;

/**
 * Meldung an die zuständigen Personen: neue Anfrage.
 */
class NewReservationRequest extends ReservationMail
{
    protected function subjectLine(): string
    {
        return 'Neue Reservationsanfrage: '.$this->reservation->packageInfo()->label().', '.$this->reservation->starts_at->format('d.m.Y');
    }

    protected function markdownView(): string
    {
        return 'mail.new-reservation';
    }
}
