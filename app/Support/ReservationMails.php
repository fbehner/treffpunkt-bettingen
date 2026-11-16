<?php

namespace App\Support;

use App\Mail\NewReservationRequest;
use App\Mail\ReservationCancelled;
use App\Mail\ReservationConfirmed;
use App\Mail\ReservationDeclined;
use App\Mail\ReservationReceived;
use App\Models\RoomReservation;
use App\Models\Setting;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * E-Mails zu Raumreservationen. Versandfehler werden geloggt und nie
 * weitergereicht – die Reservation bleibt gespeichert.
 */
class ReservationMails
{
    public static function received(RoomReservation $r): bool
    {
        return self::send($r->email, new ReservationReceived($r));
    }

    public static function notifyTeam(RoomReservation $r): bool
    {
        return self::send(Setting::reservationEmail(), new NewReservationRequest($r));
    }

    public static function confirmed(RoomReservation $r): bool
    {
        return self::send($r->email, new ReservationConfirmed($r));
    }

    public static function declined(RoomReservation $r): bool
    {
        return self::send($r->email, new ReservationDeclined($r));
    }

    public static function cancelled(RoomReservation $r): bool
    {
        return self::send($r->email, new ReservationCancelled($r));
    }

    /** false, wenn keine Adresse vorhanden ist oder der Versand scheitert. */
    private static function send(?string $to, Mailable $mail): bool
    {
        if (blank($to)) {
            return false;
        }

        try {
            Mail::to($to)->send($mail);

            return true;
        } catch (\Throwable $e) {
            Log::error('E-Mail zur Raumreservation konnte nicht gesendet werden', [
                'mail' => $mail::class,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
