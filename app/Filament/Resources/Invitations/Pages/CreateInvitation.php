<?php

namespace App\Filament\Resources\Invitations\Pages;

use App\Filament\Resources\Invitations\InvitationResource;
use App\Support\Invitations;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateInvitation extends CreateRecord
{
    protected static string $resource = InvitationResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['email'] = Str::lower(trim($data['email']));
        $data['invited_by'] = auth()->id();
        // Vorläufig; beim Senden wird ein neues Token erzeugt.
        $data['token_hash'] = hash('sha256', Str::random(48));
        $data['expires_at'] = now();

        return $data;
    }

    protected function afterCreate(): void
    {
        $ok = Invitations::send($this->record);

        if (! $ok) {
            Notification::make()
                ->title('Einladung gespeichert, aber die E-Mail konnte nicht gesendet werden')
                ->body('Bitte die E-Mail-Einstellungen prüfen und dann „Erneut senden“ wählen.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Einladung an '.$this->record->email.' gesendet';
    }

    protected function getRedirectUrl(): string
    {
        return InvitationResource::getUrl('index');
    }
}
