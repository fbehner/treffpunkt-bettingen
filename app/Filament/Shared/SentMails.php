<?php

namespace App\Filament\Shared;

use App\Models\SentMail;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Tabelle und Ansicht des E-Mail-Protokolls – für Website-Verwaltung und Backoffice.
 */
class SentMails
{
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sent_at', 'desc')
            ->columns([
                TextColumn::make('sent_at')
                    ->label('Gesendet')
                    ->dateTime('d.m.Y H:i')
                    ->sortable(),
                TextColumn::make('kind')
                    ->label('Art')
                    ->state(fn (SentMail $record) => $record->kindLabel())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('to')
                    ->label('An')
                    ->searchable(),
                TextColumn::make('subject')
                    ->label('Betreff')
                    ->searchable()
                    ->limit(60)
                    ->weight('bold'),
            ])
            ->filters([
                SelectFilter::make('mailable')
                    ->label('Art')
                    ->options(SentMail::LABELS),
            ])
            ->recordActions([
                ViewAction::make()->label('Ansehen'),
            ])
            ->recordUrl(null)
            ->emptyStateHeading('Noch keine E-Mails versendet')
            ->emptyStateDescription('Hier erscheint eine Kopie jeder E-Mail, die die Website verschickt.');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(4)
                    ->schema([
                        TextEntry::make('sent_at')->label('Gesendet')->dateTime('d.m.Y H:i'),
                        TextEntry::make('kind')->label('Art')->state(fn (SentMail $record) => $record->kindLabel()),
                        TextEntry::make('to')->label('An'),
                        TextEntry::make('subject')->label('Betreff'),
                    ]),
                Section::make('Inhalt')
                    ->description('Links mit Zugangsschlüsseln (Einladung, Bestätigung, Passwort) sind aus Sicherheitsgründen ausgeblendet.')
                    ->schema([
                        ViewEntry::make('html')
                            ->hiddenLabel()
                            ->view('filament.sent-mail-preview'),
                    ]),
            ]);
    }
}
