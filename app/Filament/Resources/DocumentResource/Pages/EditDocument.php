<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;
    
    protected static ?string $title = 'Editar Documento';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->icon('heroicon-o-trash')
                        ->title('Documento eliminado')
                        ->body('El documento ha sido eliminado del sistema.')
                        ->duration(5000)
                ),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->icon('heroicon-o-check-circle')
            ->title('Documento actualizado')
            ->body('Los cambios han sido guardados exitosamente.')
            ->duration(5000)
            ->send();
    }
}
