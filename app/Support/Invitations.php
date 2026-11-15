<?php

namespace App\Support;

use App\Mail\AccessGranted;
use App\Mail\NewUserRegistered;
use App\Mail\UserInvitation;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Ablauf: Einladen → Registrieren (ohne Rollen) → Webadmin vergibt Rollen.
 */
class Invitations
{
    /** Einladung (neu oder erneut) per E-Mail senden. Gibt false zurück, wenn der Versand scheitert. */
    public static function send(Invitation $invitation): bool
    {
        $token = $invitation->issueToken();
        $sent = self::mail($invitation->email, new UserInvitation($invitation, route('invitation.show', $token)));

        if ($sent) {
            $invitation->forceFill(['sent_at' => now()])->save();
        }

        return $sent;
    }

    /** Nach der Registrierung alle aktiven Webadmins informieren. */
    public static function notifyAdmins(User $user): void
    {
        User::query()->where('is_active', true)->get()
            ->filter(fn (User $admin) => $admin->isAdmin() && ! $admin->is($user))
            ->each(fn (User $admin) => self::mail($admin->email, new NewUserRegistered($user)));
    }

    /** Erste Rollen vergeben → Person per E-Mail informieren. */
    public static function notifyAccessGranted(User $user): void
    {
        self::mail($user->email, new AccessGranted($user));
    }

    private static function mail(string $to, Mailable $mail): bool
    {
        try {
            Mail::to($to)->send($mail);

            return true;
        } catch (\Throwable $e) {
            Log::error('E-Mail konnte nicht gesendet werden', ['mail' => $mail::class, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
