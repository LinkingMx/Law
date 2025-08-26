<?php

namespace App\Filament\Resources\ModelVariableMappingResource\Pages;

use App\Filament\Resources\ModelVariableMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModelVariableMappings extends ListRecords
{
    protected static string $resource = ModelVariableMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Crear Variable Manual'),
                
            Actions\Action::make('generator')
                ->label('Generador de Variables')
                ->icon('heroicon-o-sparkles')
                ->color('info')
                ->url(static::getResource()::getUrl('generator'))
                ->tooltip('Usa el asistente para crear variables de forma fácil'),
        ];
    }
    
    public function getTitle(): string
    {
        return 'Variables de Modelos';
    }
}