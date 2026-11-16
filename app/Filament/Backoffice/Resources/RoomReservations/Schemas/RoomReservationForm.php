<?php

namespace App\Filament\Backoffice\Resources\RoomReservations\Schemas;

use App\Filament\Backoffice\Forms\SwissDate;
use App\Models\RoomReservation;
use App\Support\ReservationRules;
use App\Support\RoomPackage;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Callout;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class RoomReservationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                self::conflictCallout(),
                self::memberCallout(),
                Section::make('Reservation')
                    ->columnSpan(2)
                    ->columns(3)
                    ->schema([
                        Select::make('package')
                            ->label('Raum')
                            ->options(RoomPackage::options())
                            ->required()
                            ->native(false)
                            ->columnSpanFull(),
                        SwissDate::make('date')
                            ->label('Datum')
                            ->required(),
                        self::time('start_time', 'Beginn'),
                        self::time('end_time', 'Ende')
                            ->rule(fn (Get $get, ?RoomReservation $record) => self::periodRule($get, $record)),
                        TextInput::make('purpose')
                            ->label('Anlass')
                            ->required()
                            ->maxLength(200)
                            ->columnSpan(2),
                        TextInput::make('participants')
                            ->label('Personen')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('public_title')
                            ->label('Titel im öffentlichen Kalender')
                            ->helperText('Leer = „Belegt“. Keine Namen von Privatpersonen.')
                            ->maxLength(80)
                            ->columnSpanFull(),
                    ]),
                Section::make('Bearbeitung')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->helperText('Bei Anfragen von der Website besser die Knöpfe oben verwenden – sie schicken die E-Mail.')
                            ->options(RoomReservation::STATUSES)
                            ->default('bestaetigt')
                            ->required()
                            ->native(false),
                        Select::make('source')
                            ->label('Eingegangen per')
                            ->options(RoomReservation::SOURCES)
                            ->default('intern')
                            ->required()
                            ->native(false),
                        Toggle::make('is_member')
                            ->label('Mitgliedertarif'),
                        TextInput::make('member_number')
                            ->label('Mitgliedernummer (Angabe)')
                            ->maxLength(20),
                        TextInput::make('price')
                            ->label('Preis')
                            ->prefix('CHF')
                            ->numeric()
                            ->minValue(0)
                            ->helperText('Leer lassen = nach aktuellem Tarif berechnen.'),
                        Textarea::make('notes')
                            ->label('Interne Notizen')
                            ->helperText('Nur im Backoffice sichtbar.')
                            ->rows(4),
                    ]),
                Section::make('Kontakt')
                    ->columnSpan(2)
                    ->columns(2)
                    ->description('Bei eigenen Einträgen (z. B. Jugendtreff) genügt Verein / Organisation.')
                    ->schema([
                        TextInput::make('organisation')->label('Verein / Organisation')->maxLength(160)->columnSpanFull(),
                        TextInput::make('first_name')->label('Vorname')->maxLength(80),
                        TextInput::make('last_name')->label('Name')->maxLength(80),
                        TextInput::make('street')->label('Strasse und Nr.')->maxLength(120),
                        TextInput::make('zip_city')->label('PLZ und Ort')->maxLength(120),
                        TextInput::make('email')->label('E-Mail')->email()->maxLength(160),
                        TextInput::make('phone')->label('Telefon')->tel()->maxLength(40),
                        Textarea::make('message')->label('Mitteilung der anfragenden Person')->rows(3)->columnSpanFull(),
                        Textarea::make('decision_note')
                            ->label('Hinweis für die E-Mail')
                            ->helperText('Erscheint in der Bestätigung, Absage oder Stornierung.')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function time(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->placeholder('HH:MM')
            ->mask(RawJs::make("'99:99'"))
            ->inputMode('numeric')
            ->required()
            ->regex('/^([01]\d|2[0-3]):[0-5]\d$|^24:00$/')
            ->validationMessages(['regex' => 'Bitte als HH:MM eingeben, z. B. 18:30.']);
    }

    /** Ende nach Beginn; eine bestätigte Reservation darf keine andere überschneiden. */
    private static function periodRule(Get $get, ?RoomReservation $record): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($get, $record) {
            $period = self::period($get('date'), $get('start_time'), $value);
            if ($period === null) {
                return;
            }
            [$start, $end] = $period;

            if ($end->lessThanOrEqualTo($start)) {
                $fail('Das Ende muss nach dem Beginn liegen.');

                return;
            }

            $package = RoomPackage::tryFrom((string) $get('package'));
            if ($package === null || $get('status') !== 'bestaetigt') {
                return;
            }

            $conflict = RoomReservation::query()->confirmed()
                ->overlapping($package, $start, $end)
                ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                ->orderBy('starts_at')
                ->first();

            if ($conflict) {
                $fail('Überschneidet sich mit der bestätigten Reservation „'.$conflict->purpose.'“ ('
                    .$conflict->packageInfo()->label().', '.$conflict->timeLabel().').');
            }
        };
    }

    /** @return array{0: Carbon, 1: Carbon}|null */
    public static function period(mixed $date, mixed $startTime, mixed $endTime): ?array
    {
        $day = SwissDate::parse($date);
        $start = ReservationRules::minutes($startTime);
        $end = ReservationRules::minutes($endTime);

        if ($day === null || $start === null || $end === null) {
            return null;
        }

        return [$day->copy()->addMinutes($start), $day->copy()->addMinutes($end)];
    }

    /** Formularfelder → Spalten (Datum + Zeiten → starts_at/ends_at, Preis berechnen). */
    public static function toModel(array $data): array
    {
        [$start, $end] = self::period($data['date'] ?? null, $data['start_time'] ?? null, $data['end_time'] ?? null);
        unset($data['date'], $data['start_time'], $data['end_time']);

        $data['starts_at'] = $start;
        $data['ends_at'] = $end;

        if (blank($data['price'] ?? null)) {
            $data['price'] = RoomPackage::from($data['package'])->price((int) $start->diffInMinutes($end), (bool) ($data['is_member'] ?? false));
        }

        return $data;
    }

    /** Spalten → Formularfelder. Zeitstempel kommen als UTC (ISO) und werden in Ortszeit umgerechnet. */
    public static function fromModel(array $data): array
    {
        $tz = config('app.timezone');
        $start = Carbon::parse($data['starts_at'])->setTimezone($tz);
        $end = Carbon::parse($data['ends_at'])->setTimezone($tz);

        $data['date'] = $start->toDateString();
        $data['start_time'] = $start->format('H:i');
        $data['end_time'] = $end->isSameDay($start) ? $end->format('H:i') : '24:00';

        return $data;
    }

    /** Hinweis auf Überschneidungen mit bestätigten Reservationen und anderen Anfragen. */
    private static function conflictCallout(): Callout
    {
        return Callout::make(fn (RoomReservation $record) => $record->confirmedConflicts()->isNotEmpty()
                ? 'Raum ist zu dieser Zeit schon belegt'
                : 'Weitere Anfragen für dieselbe Zeit')
            ->warning()
            ->icon(Heroicon::OutlinedExclamationTriangle)
            ->columnSpanFull()
            ->visible(fn (?RoomReservation $record) => $record !== null
                && in_array($record->status, RoomReservation::OPEN, true)
                && ($record->confirmedConflicts()->isNotEmpty() || ($record->status === 'angefragt' && $record->competingRequests()->isNotEmpty())))
            ->description(function (RoomReservation $record) {
                $items = $record->confirmedConflicts()->map(fn (RoomReservation $r) => 'bestätigt: '.self::link($r))
                    ->merge($record->status === 'angefragt'
                        ? $record->competingRequests()->map(fn (RoomReservation $r) => 'angefragt: '.self::link($r).($r->is_member ? ' – Mitglied' : ''))
                        : []);

                return new HtmlString($items->join(' · ').'. Mitglieder haben Vorrang.');
            });
    }

    private static function memberCallout(): Callout
    {
        return Callout::make('Mitgliedertarif prüfen')
            ->info()
            ->icon(Heroicon::OutlinedIdentification)
            ->columnSpanFull()
            ->visible(fn (?RoomReservation $record) => $record !== null && $record->status === 'angefragt' && $record->source === 'web')
            ->description(function (RoomReservation $record) {
                $membership = $record->matchingMembership();
                $claim = $record->is_member ? 'Die Person gibt an, Mitglied zu sein'.($record->member_number ? ' (Nr. '.$record->member_number.')' : '').'.' : 'Die Person gibt an, nicht Mitglied zu sein.';
                $found = $membership ? ' Die E-Mail-Adresse gehört zur aktiven Mitgliedschaft Nr. '.$membership->number.'.' : ' Zur E-Mail-Adresse ist keine aktive Mitgliedschaft erfasst.';

                return $claim.$found;
            });
    }

    private static function link(RoomReservation $r): string
    {
        return '<a class="underline" href="'.e(\App\Filament\Backoffice\Resources\RoomReservations\RoomReservationResource::getUrl('edit', ['record' => $r])).'">'
            .e($r->purpose).' ('.e($r->packageInfo()->label()).', '.e($r->timeLabel()).')</a>';
    }
}
