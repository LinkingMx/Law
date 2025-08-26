<?php

namespace App\Filament\Resources\DocumentCategoryResource\Pages;

use App\Filament\Resources\DocumentCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocumentCategories extends ListRecords
{
    protected static string $resource = DocumentCategoryResource::class;
    
    protected static ?string $title = 'Categorías de Documentos';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nueva Categoría')
                ->icon('heroicon-o-plus'),
        ];
    }
}
