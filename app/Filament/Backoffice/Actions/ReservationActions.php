<?php

namespace App\Filament\Backoffice\Actions;

use App\Models\RoomReservation;
use App\Support\ReservationMails;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Bestätigen, Ablehnen und Stornieren – jeweils mit E-Mail an die Person.
 */
class ReservationActions
{
    public static function confirm(): Action
    {
        return Action::make('confirm')
            ->label('Bestätigen')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (RoomReservation $record) => $record->status === 'angefragt')
            ->disabled(fn (RoomReservation $record) => ! $record->canBeConfirmed())
            ->tooltip(fn (RoomReservation $record) => $record->canBeConfirmed() ? null : 'Der Raum ist zu dieser Zeit schon bestätigt vergeben.')
            ->modalHeading(fn (RoomReservation $record) => 'Bestätigen: '.$record->packageInfo()->label().', '.$record->timeLabel())
            ->modalDescription(fn (RoomReservation $record) => $record->competingRequests()->isNotEmpty()
                ? 'Für diese Zeit gibt es weitere Anfragen. Sie bleiben offen – bitte danach absagen.'
                : null)
            ->modalSubmitActionLabel('Reservation bestätigen')
            ->schema(self::mailFields('Zum Beispiel: Schlüssel bei … abholen.'))
            ->action(function (RoomReservation $record, array $data) {
                // Erneut prüfen – zwischen Öffnen und Bestätigen könnte jemand anderes bestätigt haben.
                if (! $record->canBeConfirmed()) {
                    Notification::make()->title('Nicht möglich: Der Raum ist inzwischen belegt.')->danger()->send();

                    return;
                }

                self::decide($record, 'bestaetigt', $data, fn () => ReservationMails::confirmed($record), 'Reservation bestätigt');
            });
    }

    public static function decline(): Action
    {
        return Action::make('decline')
            ->label('Ablehnen')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (RoomReservation $record) => $record->status === 'angefragt')
            ->modalHeading('Anfrage ablehnen')
            ->modalSubmitActionLabel('Ablehnen')
            ->schema(self::mailFields('Zum Beispiel: Der Raum ist an diesem Abend schon vergeben.'))
            ->action(fn (RoomReservation $record, array $data) => self::decide($record, 'abgelehnt', $data, fn () => ReservationMails::declined($record), 'Anfrage abgelehnt'));
    }

    public static function cancel(): Action
    {
        return Action::make('cancel')
            ->label('Stornieren')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('gray')
            ->visible(fn (RoomReservation $record) => $record->status === 'bestaetigt')
            ->modalHeading('Reservation stornieren')
            ->modalDescription('Der Raum wird wieder frei.')
            ->modalSubmitActionLabel('Stornieren')
            ->schema(self::mailFields('Zum Beispiel: Auf Wunsch abgesagt am …'))
            ->action(fn (RoomReservation $record, array $data) => self::decide($record, 'storniert', $data, fn () => ReservationMails::cancelled($record), 'Reservation storniert'));
    }

    private static function mailFields(string $placeholder): array
    {
        return [
            Toggle::make('send_mail')
                ->label('E-Mail an die Person senden')
                ->helperText(fn (RoomReservation $record) => $record->email ? 'An '.$record->email : 'Keine E-Mail-Adresse erfasst.')
                ->default(fn (RoomReservation $record) => filled($record->email))
                ->disabled(fn (RoomReservation $record) => blank($record->email)),
            Textarea::make('decision_note')
                ->label('Hinweis in der E-Mail')
                ->placeholder($placeholder)
                ->default(fn (RoomReservation $record) => $record->decision_note)
                ->rows(3),
        ];
    }

    private static function decide(RoomReservation $record, string $status, array $data, \Closure $mail, string $title): void
    {
        $record->update([
            'status' => $status,
            'decision_note' => $data['decision_note'] ?? null,
            'decided_at' => now(),
        ]);

        $note = null;
        if ($data['send_mail'] ?? false) {
            $note = $mail() ? 'E-Mail gesendet.' : 'Die E-Mail konnte nicht gesendet werden – bitte die Person direkt informieren.';
        }

        Notification::make()->title($title)->body($note)->success()->send();
    }
}
