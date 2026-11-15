<?php

namespace App\Filament\Resources\Invitations\Tables;

use App\Filament\Resources\SentMails\SentMailResource;
use App\Mail\UserInvitation;
use App\Models\Invitation;
use App\Models\SentMail;
use App\Support\Invitations;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvitationsTable
{
    /** Zuletzt versendete Einladungs-E-Mail an diese Adresse. */
    private static function lastMail(Invitation $invitation): ?SentMail
    {
        return SentMail::query()
            ->where('mailable', UserInvitation::class)
            ->where('to', $invitation->email)
            ->latest('sent_at')
            ->first();
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('email')->label('E-Mail')->searchable()->weight('bold'),
                TextColumn::make('name')->label('Name')->placeholder('–'),
                TextColumn::make('inviter.name')->label('Eingeladen von')->placeholder('–'),
                TextColumn::make('sent_at')->label('Gesendet')->dateTime('d.m.Y H:i')->placeholder('nicht gesendet'),
                TextColumn::make('expires_at')->label('Gültig bis')->date('d.m.Y'),
                TextColumn::make('status')
                    ->label('Status')
                    ->state(fn (Invitation $record) => $record->status())
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'angenommen' => 'success',
                        'abgelaufen' => 'gray',
                        default => 'warning',
                    }),
            ])
            ->recordActions([
                Action::make('mail')
                    ->label('E-Mail ansehen')
                    ->icon(Heroicon::OutlinedEnvelopeOpen)
                    ->color('gray')
                    ->url(fn (Invitation $record) => ($mail = self::lastMail($record))
                        ? SentMailResource::getUrl('view', ['record' => $mail])
                        : null)
                    ->visible(fn (Invitation $record) => self::lastMail($record) !== null),
                Action::make('resend')
                    ->label('Erneut senden')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->visible(fn (Invitation $record) => $record->accepted_at === null)
                    ->requiresConfirmation()
                    ->modalDescription('Es wird ein neuer Link verschickt, der alte wird ungültig.')
                    ->action(function (Invitation $record) {
                        $ok = Invitations::send($record);
                        Notification::make()
                            ->title($ok ? 'Einladung gesendet' : 'E-Mail konnte nicht gesendet werden')
                            ->body($ok ? null : 'Bitte die E-Mail-Einstellungen in der .env prüfen.')
                            ->color($ok ? 'success' : 'danger')
                            ->send();
                    }),
                DeleteAction::make()
                    ->label(fn (Invitation $record) => $record->accepted_at ? 'Löschen' : 'Widerrufen')
                    ->modalHeading('Einladung entfernen')
                    ->modalDescription('Ein offener Link wird damit ungültig. Ein bereits angelegtes Konto bleibt bestehen.'),
            ]);
    }
}
