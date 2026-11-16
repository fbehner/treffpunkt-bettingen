<?php

namespace App\Mail;

/**
 * Eingangsbestätigung an die anfragende Person.
 */
class ReservationReceived extends ReservationMail
{
    protected function subjectLine(): string
    {
        return 'Eure Reservationsanfrage beim Treffpunkt Bettingen';
    }

    protected function markdownView(): string
    {
        return 'mail.reservation-received';
    }
}
