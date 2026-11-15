<?php

namespace App\Filament\Backoffice\Resources\MembershipFees\Tables;

use App\Filament\Backoffice\Forms\SwissDate;
use App\Models\MembershipFee;
use App\Support\Csv;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class MembershipFeesTable
{
    public static function configure(Table $table, bool $withMembership = true): Table
    {
        $columns = [
            TextColumn::make('year')
                ->label('Jahr')
                ->sortable(),
        ];

        if ($withMembership) {
            $columns[] = TextColumn::make('membership.number')
                ->label('Nr.')
                ->sortable();
            $columns[] = TextColumn::make('member')
                ->label('Mitglied')
                ->state(fn (MembershipFee $record) => $record->membership?->displayName())
                ->searchable(query: fn (Builder $query, string $search) => $query->whereHas('membership', fn (Builder $m) => $m
                    ->where('organisation', 'like', "%{$search}%")
                    ->orWhere('number', $search)
                    ->orWhereHas('people', fn (Builder $p) => $p->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%"))))
                ->weight('bold');
        }

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('membership.people'))
            ->defaultSort('year', 'desc')
            ->columns([
                ...$columns,
                TextColumn::make('amount')
                    ->label('Beitrag')
                    ->prefix('CHF ')
                    ->alignEnd(),
                TextColumn::make('donation')
                    ->label('Gönner')
                    ->prefix('CHF ')
                    ->placeholder('–')
                    ->alignEnd(),
                TextColumn::make('paid_at')
                    ->label('Bezahlt')
                    ->date('d.m.Y')
                    ->placeholder('offen')
                    ->badge()
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Bemerkung')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('year')
                    ->label('Jahr')
                    ->options(fn () => MembershipFee::query()->distinct()->orderByDesc('year')->pluck('year', 'year')->all()),
                TernaryFilter::make('paid')
                    ->label('Zahlung')
                    ->placeholder('Alle')
                    ->trueLabel('Bezahlt')
                    ->falseLabel('Offen')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('paid_at'),
                        false: fn (Builder $q) => $q->whereNull('paid_at'),
                        blank: fn (Builder $q) => $q,
                    ),
            ])
            ->recordActions([
                self::markPaidAction(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('markPaid')
                        ->label('Als bezahlt markieren')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->schema([
                            SwissDate::make('paid_at')->label('Bezahlt am')->default(now()->format('d.m.Y'))->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $records->each->update(['paid_at' => $data['paid_at']]);
                            Notification::make()->title($records->count().' Beiträge als bezahlt markiert')->success()->send();
                        }),
                    BulkAction::make('export')
                        ->label('Auswahl exportieren (CSV)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->action(fn (Collection $records) => self::export($records->load('membership.people'))),
                ]),
            ]);
    }

    public static function markPaidAction(): Action
    {
        return Action::make('pay')
            ->label('Bezahlt')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (MembershipFee $record) => $record->paid_at === null)
            ->schema([
                SwissDate::make('paid_at')->label('Bezahlt am')->default(now()->format('d.m.Y'))->required(),
                TextInput::make('donation')->label('Davon Gönnerbeitrag (CHF)')->numeric(),
            ])
            ->fillForm(fn (MembershipFee $record) => ['paid_at' => now()->format('d.m.Y'), 'donation' => $record->donation])
            ->action(function (MembershipFee $record, array $data) {
                $record->update($data);
                Notification::make()->title('Zahlung erfasst')->success()->send();
            });
    }

    /** Header-Aktion: Beiträge eines Jahres für alle Mitglieder anlegen. */
    public static function generateAction(): Action
    {
        return Action::make('generate')
            ->label('Jahresbeiträge erzeugen')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->modalDescription('Legt für jede Mitgliedschaft, die im gewählten Jahr Mitglied ist, einen offenen Beitrag an. Bestehende Einträge bleiben unverändert – ihr könnt das gefahrlos wiederholen, z. B. nach neuen Aufnahmen.')
            ->schema([
                TextInput::make('year')
                    ->label('Jahr')
                    ->numeric()
                    ->minValue(2026)
                    ->maxValue(2100)
                    ->default((int) now()->year + (now()->month >= 11 ? 1 : 0))
                    ->required(),
            ])
            ->action(function (array $data) {
                $count = MembershipFee::generateFor((int) $data['year']);
                Notification::make()
                    ->title($count > 0 ? "{$count} Beiträge für {$data['year']} angelegt" : "Für {$data['year']} waren schon alle Beiträge erfasst")
                    ->success()
                    ->send();
            });
    }

    public static function export(Collection $fees)
    {
        $rows = $fees->map(fn (MembershipFee $f) => [
            'Jahr' => $f->year,
            'Nr.' => $f->membership?->number,
            'Mitglied' => $f->membership?->displayName(),
            'Art' => $f->membership?->typeInfo()->label(),
            'Strasse' => $f->membership?->street,
            'PLZ/Ort' => $f->membership?->zip_city,
            'E-Mail' => $f->membership?->email,
            'Beitrag CHF' => $f->amount,
            'Gönnerbeitrag CHF' => $f->donation,
            'Total CHF' => $f->total(),
            'Bezahlt am' => $f->paid_at?->format('d.m.Y'),
            'Bemerkung' => $f->notes,
        ]);

        return Csv::download($rows, 'beitraege-'.now()->format('Y-m-d').'.csv');
    }

    /** Alle Beiträge eines Jahres (für die Rechnungsstellung). */
    public static function exportYearAction(): Action
    {
        return Action::make('exportYear')
            ->label('Exportieren (CSV)')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->schema([
                TextInput::make('year')->label('Jahr')->numeric()->default((int) now()->year)->required(),
                Toggle::make('open_only')->label('Nur offene')->default(false),
            ])
            ->action(fn (array $data) => self::export(
                MembershipFee::query()
                    ->where('year', $data['year'])
                    ->when($data['open_only'], fn (Builder $q) => $q->whereNull('paid_at'))
                    ->with('membership.people')
                    ->get()
                    ->sortBy(fn (MembershipFee $f) => $f->membership?->number)
            ));
    }
}
