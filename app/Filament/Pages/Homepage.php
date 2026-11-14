<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
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
 * Texte der Startseite und allgemeine Angaben (Kontakt, Fusszeile).
 *
 * @property-read Schema $form
 */
class Homepage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static ?string $navigationLabel = 'Startseite & Kontakt';

    protected static ?string $title = 'Startseite & Kontakt';

    protected static string|UnitEnum|null $navigationGroup = 'Inhalte';

    protected static ?int $navigationSort = 0;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(Setting::values());
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Kopfbereich')
                    ->description('Der grosse Titel oben auf der Startseite.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('kicker')
                            ->label('Kleine Überschrift')
                            ->maxLength(60),
                        TextInput::make('site_name')
                            ->label('Name des Vereins')
                            ->required()
                            ->maxLength(80),
                        TextInput::make('headline_dark')
                            ->label('Titel – dunkler Teil')
                            ->maxLength(60),
                        Textarea::make('headline_accent')
                            ->label('Titel – roter Teil')
                            ->helperText('Zeilenumbrüche werden übernommen.')
                            ->rows(2)
                            ->maxLength(80),
                        Textarea::make('intro')
                            ->label('Einleitung')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),
                    ]),
                Section::make('Abschluss')
                    ->description('„Auch für eure Ideen.“ unter den Kacheln.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('closing_dark')
                            ->label('Satz – dunkler Teil')
                            ->maxLength(60),
                        TextInput::make('closing_accent')
                            ->label('Satz – roter Teil')
                            ->maxLength(40),
                        Textarea::make('closing_text')
                            ->label('Text darunter')
                            ->rows(2)
                            ->maxLength(300)
                            ->columnSpanFull(),
                    ]),
                Section::make('Kontakt & Fusszeile')
                    ->columns(2)
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Kontakt-E-Mail')
                            ->email(),
                        Textarea::make('address')
                            ->label('Adresse')
                            ->rows(3),
                        TextInput::make('footer_text')
                            ->label('Kurzer Text in der Fusszeile')
                            ->maxLength(160)
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
                        Action::make('preview')
                            ->label('Website ansehen')
                            ->color('gray')
                            ->url(url('/'))
                            ->openUrlInNewTab(),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();
        Setting::putMany(array_intersect_key($state, Setting::DEFAULTS));

        Notification::make()
            ->title('Gespeichert')
            ->success()
            ->send();
    }
}
