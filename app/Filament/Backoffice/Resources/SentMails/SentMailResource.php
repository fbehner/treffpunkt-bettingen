<?php

namespace App\Filament\Backoffice\Resources\SentMails;

use App\Filament\Shared\SentMails;
use App\Models\SentMail;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

/**
 * E-Mail-Protokoll: Kopien der versendeten E-Mails (nur lesen).
 */
class SentMailResource extends Resource
{
    protected static ?string $model = SentMail::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?string $modelLabel = 'Gesendete E-Mail';

    protected static ?string $pluralModelLabel = 'Gesendete E-Mails';

    protected static string|UnitEnum|null $navigationGroup = 'Mitglieder';

    protected static ?int $navigationSort = 5;

    /** Nur die Kategorien, die in diesen Bereich gehören. */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('category', ['mitglieder']);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->canManageMembers() ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return SentMails::table($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SentMails::infolist($schema);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSentMails::route('/'),
            'view' => Pages\ViewSentMail::route('/{record}'),
        ];
    }
}
