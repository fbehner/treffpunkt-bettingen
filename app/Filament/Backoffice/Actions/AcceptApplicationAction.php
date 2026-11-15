<?php

namespace App\Filament\Backoffice\Actions;

use App\Filament\Backoffice\Forms\SwissDate;
use App\Filament\Backoffice\Resources\Memberships\MembershipResource;
use App\Models\Membership;
use App\Models\MembershipApplication;
use App\Support\MembershipMails;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

/**
 * „Aufnehmen“: aus einem Gesuch eine Mitgliedschaft machen.
 */
class AcceptApplicationAction
{
    public static function make(): Action
    {
        return Action::make('accept')
            ->label('Aufnehmen')
            ->icon(Heroicon::OutlinedUserPlus)
            ->color('success')
            ->visible(fn (MembershipApplication $record) => $record->membership_id === null
                && in_array($record->status, ['eingegangen', 'bestaetigung'], true))
            ->disabled(fn (MembershipApplication $record) => ! $record->canBeAccepted())
            ->tooltip(fn (MembershipApplication $record) => $record->canBeAccepted() ? null : 'Die erziehungsberechtigte Person hat noch nicht bestätigt.')
            ->modalHeading(fn (MembershipApplication $record) => 'Aufnehmen: '.$record->displayName())
            ->modalDescription('Erstellt die Mitgliedschaft mit allen Personen aus dem Gesuch. Das Gesuch bleibt als Beleg erhalten.')
            ->modalSubmitActionLabel('Mitglied aufnehmen')
            ->schema([
                TextInput::make('number')
                    ->label('Mitgliedernummer')
                    ->numeric()
                    ->required()
                    ->default(fn () => Membership::nextNumber())
                    ->rule(Rule::unique('memberships', 'number'))
                    ->validationMessages(['unique' => 'Diese Nummer ist schon vergeben.']),
                Toggle::make('send_welcome')
                    ->label('Willkommens-E-Mail senden')
                    ->helperText(fn (MembershipApplication $record) => 'An '.$record->email.($record->guardian_email ? ', Kopie an '.$record->guardian_email : '').' – mit Mitgliedernummer und Beitrag.')
                    ->default(true),
                SwissDate::make('joined_at')
                    ->label('Eintritt (Beschluss des Vorstands)')
                    ->default(now()->format('d.m.Y'))
                    ->required(),
            ])
            ->action(function (MembershipApplication $record, array $data, Action $action) {
                $membership = $record->accept(Carbon::parse($data['joined_at']), (int) $data['number']);

                $mailNote = null;
                if ($data['send_welcome'] ?? false) {
                    $mailNote = MembershipMails::sendWelcome($membership)
                        ? 'Willkommens-E-Mail gesendet.'
                        : 'Die Willkommens-E-Mail konnte nicht gesendet werden – bitte später beim Mitglied erneut versuchen.';
                }

                Notification::make()
                    ->title('Aufgenommen als Mitglied Nr. '.$membership->number)
                    ->body($mailNote)
                    ->success()
                    ->send();

                $action->redirect(MembershipResource::getUrl('edit', ['record' => $membership]));
            });
    }

    /** Link vom Gesuch zur entstandenen Mitgliedschaft. */
    public static function openMembership(): Action
    {
        return Action::make('openMembership')
            ->label(fn (MembershipApplication $record) => 'Mitglied Nr. '.$record->membership?->number)
            ->icon(Heroicon::OutlinedUserGroup)
            ->color('gray')
            ->visible(fn (MembershipApplication $record) => $record->membership_id !== null)
            ->url(fn (MembershipApplication $record) => MembershipResource::getUrl('edit', ['record' => $record->membership_id]));
    }
}
