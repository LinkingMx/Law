<?php

namespace App\Filament\Resources\ModelVariableMappingResource\Pages;

use App\Filament\Resources\ModelVariableMappingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateModelVariableMapping extends CreateRecord
{
    protected static string $resource = ModelVariableMappingResource::class;
    
    public function getTitle(): string
    {
        return 'Crear Variable de Modelo';
    }
}