<?php

namespace App\Filament\Resources\IncidentResource\Pages;

use App\Filament\Resources\IncidentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateIncident extends CreateRecord
{
    protected static string $resource = IncidentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Incidencia creada exitosamente';
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->icon('heroicon-o-exclamation-triangle')
            ->title('Incidencia Reportada')
            ->body('Tu incidencia ha sido registrada exitosamente. El equipo legal la revisará pronto.')
            ->duration(5000);
    }
}
