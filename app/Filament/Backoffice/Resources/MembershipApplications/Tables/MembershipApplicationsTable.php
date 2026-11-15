<?php

namespace App\Filament\Backoffice\Resources\MembershipApplications\Tables;

use App\Filament\Backoffice\Actions\AcceptApplicationAction;
use App\Models\MembershipApplication;
use App\Support\MembershipType;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembershipApplicationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn ($query) => $query->orderByRaw('COALESCE(received_at, created_at) desc'))
            ->columns([
                TextColumn::make('received_at')
                    ->label('Eingang')
                    ->state(fn (MembershipApplication $record) => $record->receivedDate())
                    ->date('d.m.Y')
                    ->sortable(query: fn ($query, string $direction) => $query->orderByRaw('COALESCE(received_at, created_at) '.($direction === 'asc' ? 'asc' : 'desc'))),
                TextColumn::make('source')
                    ->label('Per')
                    ->formatStateUsing(fn (string $state) => MembershipApplication::SOURCES[$state] ?? $state)
                    ->toggleable(),
                TextColumn::make('last_name')
                    ->label('Name')
                    ->formatStateUsing(fn (MembershipApplication $record) => $record->displayName())
                    ->searchable(['first_name', 'last_name', 'organisation', 'partner_last_name', 'email'])
                    ->weight('bold'),
                TextColumn::make('type')
                    ->label('Mitgliedschaft')
                    ->formatStateUsing(fn (string $state) => MembershipType::from($state)->label())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('fee')
                    ->label('Beitrag')
                    ->prefix('CHF ')
                    ->alignEnd(),
                TextColumn::make('email')
                    ->label('E-Mail')
                    ->toggleable(),
                TextColumn::make('duplicate_hint')
                    ->label('')
                    ->state(fn (MembershipApplication $record) => in_array($record->status, ['eingegangen', 'bestaetigung'], true) && $record->hasPossibleDuplicates() ? 'Doppelt?' : null)
                    ->badge()
                    ->color('warning')
                    ->icon(Heroicon::OutlinedDocumentDuplicate)
                    ->tooltip('Gleiche E-Mail oder gleicher Name wie ein anderes Gesuch oder Mitglied'),
                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state) => MembershipApplication::STATUSES[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'aufgenommen' => 'success',
                        'eingegangen' => 'warning',
                        'bestaetigung' => 'info',
                        'doppelt' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(MembershipApplication::STATUSES),
                SelectFilter::make('type')
                    ->label('Mitgliedschaft')
                    ->options(MembershipType::options()),
                Filter::make('duplicates')
                    ->label('Mögliche Duplikate (offen)')
                    ->query(fn (Builder $query) => $query->whereIn('status', ['eingegangen', 'bestaetigung'])
                        ->whereExists(fn ($sub) => $sub->selectRaw('1')
                            ->from('membership_applications as other')
                            ->whereColumn('other.id', '!=', 'membership_applications.id')
                            ->where('other.status', '!=', 'doppelt')
                            ->where(fn ($w) => $w
                                ->whereRaw('LOWER(other.email) = LOWER(membership_applications.email)')
                                ->orWhere(fn ($n) => $n
                                    ->whereRaw('LOWER(other.first_name) = LOWER(membership_applications.first_name)')
                                    ->whereRaw('LOWER(other.last_name) = LOWER(membership_applications.last_name)'))))),
                SelectFilter::make('source')
                    ->label('Eingegangen per')
                    ->options(MembershipApplication::SOURCES),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Als Liste exportieren (CSV)')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->action(fn () => self::csv(MembershipApplication::query()->orderBy('last_name')->get())),
            ])
            ->recordActions([
                AcceptApplicationAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('exportSelected')
                        ->label('Auswahl exportieren (CSV)')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->action(fn (Collection $records) => self::csv($records)),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    /** CSV für Excel (Semikolon, UTF-8 mit BOM). */
    public static function csv(Collection $records): StreamedResponse
    {
        $columns = [
            'Eingang' => fn ($r) => $r->receivedDate()?->format('d.m.Y'),
            'Per' => fn ($r) => MembershipApplication::SOURCES[$r->source] ?? $r->source,
            'Status' => fn ($r) => MembershipApplication::STATUSES[$r->status] ?? $r->status,
            'Mitgliedschaft' => fn ($r) => MembershipType::from($r->type)->label(),
            'Beitrag CHF' => fn ($r) => $r->fee,
            'Gönnerbeitrag CHF' => fn ($r) => $r->donation,
            'Verein/Institution' => fn ($r) => $r->organisation,
            'Vorname' => fn ($r) => $r->first_name,
            'Name' => fn ($r) => $r->last_name,
            'Geburtsdatum' => fn ($r) => $r->birthdate?->format('d.m.Y'),
            'Vorname 2. Person' => fn ($r) => $r->partner_first_name,
            'Name 2. Person' => fn ($r) => $r->partner_last_name,
            'Kinder' => fn ($r) => $r->children,
            'Strasse' => fn ($r) => $r->street,
            'PLZ/Ort' => fn ($r) => $r->zip_city,
            'Telefon' => fn ($r) => $r->phone,
            'E-Mail' => fn ($r) => $r->email,
            'Erziehungsberechtigte/r' => fn ($r) => $r->guardian_name,
            'E-Mail Erziehungsberechtigte/r' => fn ($r) => $r->guardian_email,
            'Bestätigt am' => fn ($r) => $r->guardian_confirmed_at?->format('d.m.Y'),
        ];

        return response()->streamDownload(function () use ($records, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_keys($columns), ';');
            foreach ($records as $record) {
                fputcsv($out, array_map(fn ($get) => self::safe($get($record)), $columns), ';');
            }
            fclose($out);
        }, 'beitrittsgesuche-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Verhindert, dass Excel Eingaben als Formel ausführt. */
    private static function safe(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[=+\-@\t\r]/', $value) ? "'".$value : $value;
    }
}
