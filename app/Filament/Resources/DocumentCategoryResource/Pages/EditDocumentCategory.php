<?php

namespace App\Filament\Resources\DocumentCategoryResource\Pages;

use App\Filament\Resources\DocumentCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDocumentCategory extends EditRecord
{
    protected static string $resource = DocumentCategoryResource::class;
    
    protected static ?string $title = 'Editar Categoría';

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->icon('heroicon-o-trash')
                        ->title('Categoría eliminada')
                        ->body('La categoría ha sido eliminada del sistema.')
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
            ->title('Categoría actualizada')
            ->body('Los cambios han sido guardados exitosamente.')
            ->duration(5000)
            ->send();
    }
}
