<?php

namespace App\Listeners;

use App\Models\SentMail;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Legt nach jedem erfolgreichen Versand eine Kopie im E-Mail-Protokoll ab.
 * Ein Fehler hier darf den Versand nie stören.
 * Wird von Laravel automatisch registriert (Event-Discovery in app/Listeners).
 */
class LogSentMail
{
    public function handle(MessageSent $event): void
    {
        try {
            $message = $event->sent->getOriginalMessage();
            if (! $message instanceof Email) {
                return;
            }

            $mailable = $event->data['__laravel_mailable'] ?? null;
            $notification = $event->data['__laravel_notification'] ?? null;

            $html = $message->getHtmlBody();
            if (is_resource($html)) {
                $html = stream_get_contents($html);
            }
            if ($html === null && $message->getTextBody() !== null) {
                $html = '<pre style="white-space:pre-wrap;font-family:inherit">'.e((string) $message->getTextBody()).'</pre>';
            }

            SentMail::create([
                'category' => SentMail::categoryFor($mailable, $notification),
                'mailable' => $mailable ?? $notification,
                'to' => collect($message->getTo())->map(fn (Address $a) => $a->getAddress())->join(', '),
                'subject' => $message->getSubject(),
                'html' => SentMail::redact($html),
                'sent_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('E-Mail-Protokoll konnte nicht geschrieben werden', ['error' => $e->getMessage()]);
        }
    }
}
