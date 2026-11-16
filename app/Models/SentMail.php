<?php

namespace App\Models;

use App\Mail\AccessGranted;
use App\Mail\GuardianConfirmation;
use App\Mail\MembershipReceived;
use App\Mail\MembershipWelcome;
use App\Mail\NewMembershipApplication;
use App\Mail\NewReservationRequest;
use App\Mail\NewUserRegistered;
use App\Mail\ReservationCancelled;
use App\Mail\ReservationConfirmed;
use App\Mail\ReservationDeclined;
use App\Mail\ReservationReceived;
use App\Mail\UserInvitation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Kopie einer versendeten E-Mail. Links mit Zugangsschlüsseln (Einladung,
 * Bestätigung, Passwort) werden vor dem Speichern unkenntlich gemacht.
 * Einträge werden nach 12 Monaten automatisch gelöscht.
 */
class SentMail extends Model
{
    use MassPrunable;

    public const KEEP_MONTHS = 12;

    /** Kategorie bestimmt, wer die Kopie sehen darf. */
    public const CATEGORIES = [
        'konto' => 'Konten & Einladungen',
        'mitglieder' => 'Mitglieder',
        'reservationen' => 'Reservationen',
        'sonstige' => 'Sonstige',
    ];

    private const CATEGORY_BY_MAILABLE = [
        ReservationReceived::class => 'reservationen',
        NewReservationRequest::class => 'reservationen',
        ReservationConfirmed::class => 'reservationen',
        ReservationDeclined::class => 'reservationen',
        ReservationCancelled::class => 'reservationen',
        UserInvitation::class => 'konto',
        NewUserRegistered::class => 'konto',
        AccessGranted::class => 'konto',
        MembershipReceived::class => 'mitglieder',
        GuardianConfirmation::class => 'mitglieder',
        NewMembershipApplication::class => 'mitglieder',
        MembershipWelcome::class => 'mitglieder',
    ];

    public const LABELS = [
        UserInvitation::class => 'Einladung',
        NewUserRegistered::class => 'Meldung: neues Konto',
        AccessGranted::class => 'Zugang freigeschaltet',
        MembershipReceived::class => 'Eingangsbestätigung Gesuch',
        GuardianConfirmation::class => 'Bestätigung Erziehungsberechtigte',
        NewMembershipApplication::class => 'Meldung: neues Gesuch',
        MembershipWelcome::class => 'Aufnahmebestätigung',
        ReservationReceived::class => 'Eingangsbestätigung Reservation',
        NewReservationRequest::class => 'Meldung: neue Reservation',
        ReservationConfirmed::class => 'Reservation bestätigt',
        ReservationDeclined::class => 'Reservation abgelehnt',
        ReservationCancelled::class => 'Reservation storniert',
    ];

    protected $fillable = ['category', 'mailable', 'to', 'subject', 'html', 'sent_at'];

    protected $casts = ['sent_at' => 'datetime'];

    public static function categoryFor(?string $mailable, ?string $notification = null): string
    {
        if ($notification !== null) {
            return 'konto'; // z. B. Passwort zurücksetzen
        }

        return self::CATEGORY_BY_MAILABLE[$mailable] ?? 'sonstige';
    }

    public function kindLabel(): string
    {
        if ($this->mailable && str_contains($this->mailable, 'ResetPassword')) {
            return 'Passwort zurücksetzen';
        }

        return self::LABELS[$this->mailable] ?? 'E-Mail';
    }

    /** Zugangsschlüssel in Links unkenntlich machen. */
    public static function redact(?string $html): ?string
    {
        if ($html === null) {
            return null;
        }

        return preg_replace(
            [
                '~(/einladung/)(?!fertig)[A-Za-z0-9]{20,}~',
                '~((?:\?|&|&amp;)(?:signature|token)=)[^&"\'\s<]+~i',
                '~(/password-reset/reset/?)[^?"\'\s<]+~i',
            ],
            ['$1[ausgeblendet]', '$1[ausgeblendet]', '$1[ausgeblendet]'],
            $html
        );
    }

    public function prunable(): Builder
    {
        return static::query()->where('created_at', '<', now()->subMonths(self::KEEP_MONTHS));
    }
}
