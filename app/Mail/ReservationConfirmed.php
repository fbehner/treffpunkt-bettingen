<?php

namespace App\Mail;

/**
 * Bestätigung der Reservation.
 */
class ReservationConfirmed extends ReservationMail
{
    protected function subjectLine(): string
    {
        return 'Reservation bestätigt: '.$this->reservation->packageInfo()->label().', '.$this->reservation->starts_at->format('d.m.Y');
    }

    protected function markdownView(): string
    {
        return 'mail.reservation-confirmed';
    }
}
