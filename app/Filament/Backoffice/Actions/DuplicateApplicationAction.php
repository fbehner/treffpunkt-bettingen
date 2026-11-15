<?php

namespace App\Filament\Backoffice\Actions;

use App\Filament\Backoffice\Resources\MembershipApplications\MembershipApplicationResource;
use App\Filament\Backoffice\Resources\Memberships\MembershipResource;
use App\Models\MembershipApplication;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Callout;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

/**
 * Doppelt eingereichte Gesuche erkennen und still erledigen.
 */
class DuplicateApplicationAction
{
    /** Kopf-Aktion „Als doppelt erledigen“. */
    public static function make(): Action
    {
        return Action::make('markDuplicate')
            ->label('Als doppelt erledigen')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->visible(fn (MembershipApplication $record) => $record->membership_id === null
                && ! in_array($record->status, ['doppelt', 'aufgenommen'], true))
            ->modalHeading('Gesuch als doppelt erledigen')
            ->modalDescription('Das Gesuch wird ohne E-Mail an die Person abgeschlossen und verweist auf das massgebliche Gesuch. Es zählt danach nicht mehr als offen.')
            ->modalSubmitActionLabel('Als doppelt erledigen')
            ->schema([
                Select::make('original_id')
                    ->label('Massgebliches Gesuch')
                    ->options(fn (MembershipApplication $record) => self::originalOptions($record))
                    ->default(fn (MembershipApplication $record) => array_key_first(self::originalOptions($record)))
                    ->required()
                    ->native(false),
            ])
            ->action(function (MembershipApplication $record, array $data, Action $action) {
                $original = MembershipApplication::findOrFail($data['original_id']);
                $record->markAsDuplicateOf($original);

                Notification::make()->title('Als doppelt erledigt')->success()->send();
                $action->redirect(MembershipApplicationResource::getUrl('edit', ['record' => $original]));
            });
    }

    /** Auswahl: andere Gesuche derselben Person, aufgenommene zuerst. */
    private static function originalOptions(MembershipApplication $record): array
    {
        return MembershipApplication::query()
            ->samePersonAs($record)
            ->with('membership')
            ->get()
            ->sortBy(fn (MembershipApplication $a) => [$a->status === 'aufgenommen' ? 0 : 1, $a->created_at])
            ->mapWithKeys(fn (MembershipApplication $a) => [$a->id => sprintf(
                '%s vom %s – %s%s',
                $a->displayName(),
                $a->receivedDate()?->format('d.m.Y'),
                MembershipApplication::STATUSES[$a->status] ?? $a->status,
                $a->membership ? ' (Mitglied Nr. '.$a->membership->number.')' : '',
            )])
            ->all();
    }

    /** Hinweis oben im Gesuch. */
    public static function callout(): Callout
    {
        return Callout::make(fn (MembershipApplication $record) => $record->status === 'doppelt'
                ? 'Dieses Gesuch ist als doppelt erledigt'
                : 'Mögliches Duplikat')
            ->warning()
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->columnSpanFull()
            ->visible(fn (?MembershipApplication $record) => $record !== null
                && ($record->status === 'doppelt' || ($record->membership_id === null && $record->hasPossibleDuplicates())))
            ->description(fn (MembershipApplication $record) => self::describe($record));
    }

    private static function describe(MembershipApplication $record): HtmlString
    {
        if ($record->status === 'doppelt' && $record->duplicateOf) {
            $o = $record->duplicateOf;

            return new HtmlString('Massgeblich ist '.self::link($o).'.');
        }

        $found = $record->possibleDuplicates();
        $items = [];
        foreach ($found['applications'] as $a) {
            $items[] = 'Gesuch '.self::link($a);
        }
        foreach ($found['memberships'] as $m) {
            $items[] = '<a class="underline" href="'.e(MembershipResource::getUrl('edit', ['record' => $m])).'">Mitglied Nr. '.e($m->number).' – '.e($m->displayName()).'</a>';
        }

        return new HtmlString(
            'Gleiche E-Mail-Adresse oder gleicher Name wie: '.implode(' · ', $items)
            .'. Falls es dieselbe Person ist: oben „Als doppelt erledigen“ – die Person erhält keine E-Mail.'
        );
    }

    private static function link(MembershipApplication $a): string
    {
        return '<a class="underline" href="'.e(MembershipApplicationResource::getUrl('edit', ['record' => $a])).'">'
            .e($a->displayName()).' vom '.e($a->receivedDate()?->format('d.m.Y'))
            .' ('.e(MembershipApplication::STATUSES[$a->status] ?? $a->status).')</a>';
    }
}
