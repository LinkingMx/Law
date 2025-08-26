<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;
    
    protected static ?string $title = 'Crear Documento';
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->icon('heroicon-o-document-plus')
            ->title('Documento creado exitosamente')
            ->body('El documento legal ha sido registrado correctamente.')
            ->duration(5000)
            ->send();
    }
}
