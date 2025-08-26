<?php

namespace App\Filament\Resources\DocumentCategoryResource\Pages;

use App\Filament\Resources\DocumentCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocumentCategory extends ViewRecord
{
    protected static string $resource = DocumentCategoryResource::class;
    
    protected static ?string $title = 'Ver Categoría';
    
    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->icon('heroicon-o-pencil-square'),
            Actions\DeleteAction::make()
                ->label('Eliminar')
                ->icon('heroicon-o-trash')
                ->successRedirectUrl(DocumentCategoryResource::getUrl('index'))
                ->successNotification(
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->icon('heroicon-o-trash')
                        ->title('Categoría eliminada')
                        ->body('La categoría ha sido eliminada exitosamente.')
                        ->duration(5000)
                ),
        ];
    }
}