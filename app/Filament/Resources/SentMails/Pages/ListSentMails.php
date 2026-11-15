<?php

namespace App\Filament\Resources\SentMails\Pages;

use App\Filament\Resources\SentMails\SentMailResource;
use Filament\Resources\Pages\ListRecords;

class ListSentMails extends ListRecords
{
    protected static string $resource = SentMailResource::class;

    public function getSubheading(): ?string
    {
        return 'Kopien der E-Mails, die die Website verschickt hat. Einträge werden nach 12 Monaten gelöscht.';
    }
}
