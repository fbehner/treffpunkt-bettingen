<?php

namespace App\Mail;

/**
 * Stornierung einer bestätigten Reservation durch den Verein.
 */
class ReservationCancelled extends ReservationMail
{
    protected function subjectLine(): string
    {
        return 'Reservation storniert: '.$this->reservation->starts_at->format('d.m.Y');
    }

    protected function markdownView(): string
    {
        return 'mail.reservation-cancelled';
    }
}
