<?php

namespace App\Support;

use App\Mail\MembershipWelcome;
use App\Models\Membership;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * E-Mails an Mitglieder aus dem Backoffice.
 */
class MembershipMails
{
    /** Empfänger der Willkommens-E-Mail: Mitglied, bei Minderjährigen zusätzlich die Eltern (Kopie). */
    public static function welcomeRecipients(Membership $membership): array
    {
        $to = $membership->email ?: $membership->contact()?->email;
        $cc = $membership->application?->guardian_email;

        return ['to' => $to, 'cc' => $cc && $cc !== $to ? $cc : null];
    }

    /** Gibt false zurück, wenn keine Adresse vorhanden ist oder der Versand scheitert. */
    public static function sendWelcome(Membership $membership): bool
    {
        $recipients = self::welcomeRecipients($membership);
        if (blank($recipients['to'])) {
            return false;
        }

        try {
            $mail = Mail::to($recipients['to']);
            if ($recipients['cc']) {
                $mail->cc($recipients['cc']);
            }
            $mail->send(new MembershipWelcome($membership->loadMissing('people')));

            return true;
        } catch (\Throwable $e) {
            Log::error('Willkommens-E-Mail konnte nicht gesendet werden', ['membership' => $membership->id, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
