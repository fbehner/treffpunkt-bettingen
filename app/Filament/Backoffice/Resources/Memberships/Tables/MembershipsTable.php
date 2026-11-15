<?php

namespace App\Filament\Backoffice\Resources\Memberships\Tables;

use App\Filament\Backoffice\Forms\SwissDate;
use App\Models\Membership;
use App\Models\MembershipPerson;
use App\Support\Csv;
use App\Support\MembershipType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MembershipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['people', 'fees']))
            ->defaultSort('number')
            ->columns([
                TextColumn::make('number')
                    ->label('Nr.')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label('Mitglied')
                    ->state(fn (Membership $record) => $record->displayName())
                    ->description(fn (Membership $record) => $record->people->where('relation', 'kind')->map->first_name->join(', ') ?: null)
                    ->searchable(query: fn (Builder $query, string $search) => $query->where(fn (Builder $q) => $q
                        ->where('organisation', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('people', fn (Builder $p) => $p->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Art')
                    ->formatStateUsing(fn (string $state) => MembershipType::from($state)->label())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('fee')
                    ->label('Beitrag')
                    ->prefix('CHF ')
                    ->alignEnd()
                    ->sortable(),
                TextColumn::make('zip_city')
                    ->label('Ort')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('joined_at')
                    ->label('Eintritt')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('left_at')
                    ->label('Austritt')
                    ->date('d.m.Y')
                    ->placeholder('aktiv')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('voting')
                    ->label('Stimmen')
                    ->tooltip('Stimmberechtigte Personen an der GV (ab 16)')
                    ->state(fn (Membership $record) => $record->votingCount())
                    ->alignCenter(),
                TextColumn::make('transition')
                    ->label('Hinweis')
                    ->state(fn (Membership $record) => $record->transitionHint() ? 'Wechsel' : null)
                    ->tooltip(fn (Membership $record) => $record->transitionHint() ? 'Wechsel zu '.$record->transitionHint() : null)
                    ->badge()
                    ->color('warning')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->placeholder(''),
                TextColumn::make('open_fees')
                    ->label('Offen')
                    ->tooltip('Unbezahlte Jahresbeiträge')
                    ->state(fn (Membership $record) => $record->fees->whereNull('paid_at')->pluck('year')->sort()->join(', ') ?: null)
                    ->badge()
                    ->color('danger')
                    ->placeholder('–'),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('Alle')
                    ->trueLabel('Aktive Mitglieder')
                    ->falseLabel('Ausgetretene')
                    ->default(true)
                    ->queries(
                        true: fn (Builder $q) => $q->active(),
                        false: fn (Builder $q) => $q->whereNotNull('left_at')->where('left_at', '<=', now()->toDateString()),
                        blank: fn (Builder $q) => $q,
                    ),
                SelectFilter::make('type')
                    ->label('Art')
                    ->options(MembershipType::options()),
                Filter::make('transition')
                    ->label('Altersübergang fällig')
                    ->query(fn (Builder $q) => $q->where(fn (Builder $w) => $w
                        ->where(fn (Builder $k) => $k->where('type', 'kind')->whereHas('people', fn (Builder $p) => self::olderThan($p, 16)))
                        ->orWhere(fn (Builder $j) => $j->where('type', 'jugend')->whereHas('people', fn (Builder $p) => self::olderThan($p, 18))))),
                Filter::make('open_fees')
                    ->label('Mit offenen Beiträgen')
                    ->query(fn (Builder $q) => $q->whereHas('fees', fn (Builder $f) => $f->whereNull('paid_at'))),
            ])
            ->headerActions([
                ActionGroup::make([
                    Action::make('exportMembers')
                        ->label('Mitgliederliste (CSV)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->action(fn () => self::exportMembers(Membership::query()->active()->with('people')->orderBy('number')->get())),
                    Action::make('exportVoters')
                        ->label('Stimmberechtigte für die GV (CSV)')
                        ->icon(Heroicon::OutlinedClipboardDocumentList)
                        ->action(fn () => self::exportVoters()),
                ])
                    ->label('Exportieren')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->button()
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('leave')
                    ->label('Austritt')
                    ->icon(Heroicon::OutlinedArrowRightStartOnRectangle)
                    ->color('gray')
                    ->visible(fn (Membership $record) => $record->left_at === null)
                    ->modalHeading(fn (Membership $record) => 'Austritt erfassen: '.$record->displayName())
                    ->modalDescription('Laut Statuten ist ein Austritt auf Ende Vereinsjahr möglich. Der Beitrag für das laufende Jahr bleibt geschuldet.')
                    ->schema([
                        SwissDate::make('left_at')
                            ->label('Austritt per')
                            ->default(now()->endOfYear()->format('d.m.Y'))
                            ->required(),
                        Select::make('exit_reason')
                            ->label('Grund')
                            ->options(Membership::EXIT_REASONS)
                            ->default('austritt')
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (Membership $record, array $data) {
                        $record->update($data);
                        Notification::make()->title('Austritt erfasst')->success()->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportSelected')
                        ->label('Auswahl exportieren (CSV)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->action(fn (Collection $records) => self::exportMembers($records->load('people'))),
                ]),
            ]);
    }

    /** Personen, die älter als $years sind (Geburtsdatum oder Jahrgang). */
    private static function olderThan(Builder $people, int $years): Builder
    {
        return $people->where('relation', 'haupt')->where(fn (Builder $q) => $q
            ->whereDate('birthdate', '<=', now()->subYears($years)->toDateString())
            ->orWhere(fn (Builder $y) => $y->whereNull('birthdate')->where('birth_year', '<=', now()->year - $years)));
    }

    public static function exportMembers(Collection $memberships)
    {
        $rows = $memberships->map(function (Membership $m) {
            $contact = $m->contact();

            return [
                'Nr.' => $m->number,
                'Art' => $m->typeInfo()->label(),
                'Beitrag CHF' => $m->fee,
                'Verein/Institution' => $m->organisation,
                'Anrede/Name' => $m->displayName(),
                'Vorname' => $contact?->first_name,
                'Name' => $contact?->last_name,
                'Strasse' => $m->street,
                'PLZ/Ort' => $m->zip_city,
                'E-Mail' => $m->email,
                'Telefon' => $m->phone,
                'Personen' => $m->people->count(),
                'Stimmberechtigt' => $m->votingCount(),
                'Eintritt' => $m->joined_at?->format('d.m.Y'),
                'Austritt' => $m->left_at?->format('d.m.Y'),
            ];
        });

        return Csv::download($rows, 'mitglieder-'.now()->format('Y-m-d').'.csv');
    }

    public static function exportVoters()
    {
        $rows = Membership::query()->active()->with('people')->orderBy('number')->get()
            ->flatMap(function (Membership $m) {
                if ($m->typeInfo()->isOrganisation()) {
                    $p = $m->contact();

                    return [[
                        'Nr.' => $m->number,
                        'Vorname' => $p?->first_name,
                        'Name' => $p?->last_name,
                        'Für' => $m->organisation,
                        'Alter' => '',
                        'Unterschrift' => '',
                    ]];
                }

                return $m->people
                    ->filter(fn (MembershipPerson $p) => ($p->age() ?? ($p->relation === 'kind' ? 0 : 99)) >= 16)
                    ->map(fn (MembershipPerson $p) => [
                        'Nr.' => $m->number,
                        'Vorname' => $p->first_name,
                        'Name' => $p->last_name,
                        'Für' => $m->typeInfo()->label(),
                        'Alter' => $p->age() ?? '',
                        'Unterschrift' => '',
                    ])->values()->all();
            })
            ->sortBy([['Name', 'asc'], ['Vorname', 'asc']])
            ->values();

        return Csv::download($rows, 'stimmberechtigte-gv-'.now()->format('Y-m-d').'.csv');
    }
}
