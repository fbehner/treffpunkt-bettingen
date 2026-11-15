<?php

namespace App\Filament\Backoffice\Resources\Memberships\Pages;

use App\Filament\Backoffice\Resources\Memberships\MembershipResource;
use App\Mail\MembershipWelcome;
use App\Models\Membership;
use App\Models\SentMail;
use App\Support\MembershipMails;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditMembership extends EditRecord
{
    protected static string $resource = MembershipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('welcome')
                ->label(fn (Membership $record) => self::lastWelcome($record) ? 'Willkommens-E-Mail erneut senden' : 'Willkommens-E-Mail senden')
                ->icon(Heroicon::OutlinedEnvelope)
                ->color('gray')
                ->disabled(fn (Membership $record) => blank(MembershipMails::welcomeRecipients($record)['to']))
                ->tooltip(fn (Membership $record) => blank(MembershipMails::welcomeRecipients($record)['to']) ? 'Keine E-Mail-Adresse erfasst.' : null)
                ->requiresConfirmation()
                ->modalHeading('Willkommens-E-Mail senden')
                ->modalDescription(function (Membership $record) {
                    $r = MembershipMails::welcomeRecipients($record);
                    $last = self::lastWelcome($record);

                    return 'Aufnahmebestätigung mit Mitgliedernummer und Beitrag an '.$r['to']
                        .($r['cc'] ? ' (Kopie an '.$r['cc'].')' : '').'.'
                        .($last ? ' Zuletzt gesendet am '.$last->sent_at->format('d.m.Y H:i').'.' : '');
                })
                ->action(function (Membership $record) {
                    $ok = MembershipMails::sendWelcome($record);
                    Notification::make()
                        ->title($ok ? 'Willkommens-E-Mail gesendet' : 'E-Mail konnte nicht gesendet werden')
                        ->color($ok ? 'success' : 'danger')
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

    private static function lastWelcome(Membership $membership): ?SentMail
    {
        $to = MembershipMails::welcomeRecipients($membership)['to'];

        return $to ? SentMail::query()
            ->where('mailable', MembershipWelcome::class)
            ->where('to', 'like', $to.'%')
            ->latest('sent_at')
            ->first() : null;
    }
}
