<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use App\Support\MembershipType;
use App\Support\RoomPackage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Allgemeine Angaben: Kontakt, Hinweisband, Statuten, Mitgliederbeiträge.
 *
 * @property-read Schema $form
 */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Einstellungen';

    protected static ?string $title = 'Einstellungen';

    protected static ?string $slug = 'einstellungen';

    protected static string|UnitEnum|null $navigationGroup = 'Verwaltung';

    protected static ?int $navigationSort = 20;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::values());
    }

    public function form(Schema $schema): Schema
    {
        $fees = collect(MembershipType::cases())->map(
            fn (MembershipType $type) => TextInput::make('fee_'.$type->value)
                ->label($type->label())
                ->prefix('CHF')
                ->numeric()
                ->minValue(0)
                ->required()
        )->all();

        $tariffs = collect(RoomPackage::cases())->flatMap(fn (RoomPackage $p) => [
            TextInput::make('tariff_'.$p->value.'_mitglied')
                ->label($p->label().' – Mitglieder')
                ->prefix('CHF')->suffix('/ Std.')
                ->numeric()->minValue(0)->required(),
            TextInput::make('tariff_'.$p->value.'_gast')
                ->label($p->label().' – Nichtmitglieder')
                ->prefix('CHF')->suffix('/ Std.')
                ->numeric()->minValue(0)->required(),
        ])->all();

        return $schema
            ->statePath('data')
            ->components([
                Section::make('Hinweisband')
                    ->description('Der grüne Streifen ganz oben auf jeder Seite. Text leer lassen, um ihn auszublenden.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('banner_label')->label('Etikett')->maxLength(30),
                        TextInput::make('banner_link')->label('Link „Mehr erfahren“')->helperText('z. B. /projekt#stand'),
                        TextInput::make('banner_text')->label('Text')->maxLength(160)->columnSpanFull(),
                    ]),
                Section::make('Kontakt & Fusszeile')
                    ->columns(2)
                    ->schema([
                        TextInput::make('site_name')->label('Name des Vereins')->required()->maxLength(80),
                        TextInput::make('contact_email')
                            ->label('Kontakt-E-Mail')
                            ->helperText('Erhält auch die Meldungen zu neuen Beitrittsgesuchen.')
                            ->email(),
                        Textarea::make('address')->label('Adresse')->rows(3),
                        TextInput::make('footer_text')->label('Kurzer Text in der Fusszeile')->maxLength(160),
                        Textarea::make('meta_description')
                            ->label('Beschreibung für Suchmaschinen')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),
                        FileUpload::make('statutes_file')
                            ->label('Statuten (PDF)')
                            ->helperText('Verlinkt in der Fusszeile und im Beitrittsgesuch.')
                            ->disk('public')
                            ->directory('dokumente')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf'])
                            ->preserveFilenames()
                            ->columnSpanFull(),
                    ]),
                Section::make('Raumreservation')
                    ->description('Gilt für Anfragen über die Website. Im Backoffice erfasste Reservationen sind davon nicht betroffen.')
                    ->columns(3)
                    ->collapsible()
                    ->schema([
                        TextInput::make('reservation_email')
                            ->label('E-Mail für neue Anfragen')
                            ->helperText('Leer = Kontakt-E-Mail.')
                            ->email()
                            ->columnSpan(2),
                        TextInput::make('reservation_lead_days')
                            ->label('Frühestens in … Tagen')
                            ->numeric()->minValue(0)->maxValue(60)->required(),
                        TextInput::make('reservation_from')
                            ->label('Frühester Beginn')
                            ->placeholder('07:00')->regex('/^([01]\d|2[0-3]):(00|30)$/')->required()
                            ->validationMessages(['regex' => 'Bitte als HH:MM in halben Stunden, z. B. 07:00.']),
                        TextInput::make('reservation_until')
                            ->label('Spätestes Ende')
                            ->placeholder('23:00')->regex('/^([01]\d|2[0-4]):(00|30)$/')->required()
                            ->validationMessages(['regex' => 'Bitte als HH:MM in halben Stunden, z. B. 23:00.']),
                        TextInput::make('cafe_from')
                            ->label('Café frei ab')
                            ->helperText('Nach Betriebsschluss. Leer = ganzer Tag.')
                            ->placeholder('18:00')->regex('/^([01]\d|2[0-3]):(00|30)$/')
                            ->validationMessages(['regex' => 'Bitte als HH:MM in halben Stunden, z. B. 18:00.']),
                        TextInput::make('reservation_max_months')
                            ->label('Höchstens … Monate im Voraus')
                            ->numeric()->minValue(1)->maxValue(36)->required(),
                        FileUpload::make('house_rules_file')
                            ->label('Hausordnung (PDF)')
                            ->helperText('Wird im Reservationsformular verlinkt.')
                            ->disk('public')
                            ->directory('dokumente')
                            ->visibility('public')
                            ->acceptedFileTypes(['application/pdf'])
                            ->preserveFilenames()
                            ->columnSpan(2),
                        Textarea::make('reservation_note')
                            ->label('Hinweis im Formular')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
                Section::make('Raumtarife')
                    ->description('Preis pro Stunde in CHF. 0 = „Tarif auf Anfrage“. Bereits eingegangene Reservationen behalten ihren Betrag.')
                    ->columns(2)
                    ->collapsible()
                    ->schema($tariffs),
                Section::make('Mitgliederbeiträge')
                    ->description('Jahresbeiträge für das Beitrittsgesuch. Bereits eingegangene Gesuche behalten ihren Betrag.')
                    ->columns(3)
                    ->schema([
                        ...$fees,
                        Textarea::make('membership_note')
                            ->label('Hinweis zu Fälligkeit')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')
                            ->label('Speichern')
                            ->submit('save'),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $values = array_intersect_key($state, Setting::DEFAULTS);

        // Leere Felder bewusst speichern (z. B. Hinweisband ausblenden).
        foreach ($values as $key => $value) {
            $values[$key] = $value ?? '';
        }

        Setting::putMany($values);

        Notification::make()->title('Gespeichert')->success()->send();
    }
}
