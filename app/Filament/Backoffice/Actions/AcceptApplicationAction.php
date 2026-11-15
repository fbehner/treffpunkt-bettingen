<?php

namespace App\Filament\Backoffice\Actions;

use App\Filament\Backoffice\Resources\Memberships\MembershipResource;
use App\Models\Membership;
use App\Models\MembershipApplication;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
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
                DatePicker::make('joined_at')
                    ->label('Eintritt (Beschluss des Vorstands)')
                    ->default(now())
                    ->required()
                    ->native(false)
                    ->displayFormat('d.m.Y'),
            ])
            ->action(function (MembershipApplication $record, array $data, Action $action) {
                $membership = $record->accept(Carbon::parse($data['joined_at']), (int) $data['number']);

                Notification::make()
                    ->title('Aufgenommen als Mitglied Nr. '.$membership->number)
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
