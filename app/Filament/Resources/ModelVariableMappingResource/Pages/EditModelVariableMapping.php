<?php

namespace App\Filament\Resources\ModelVariableMappingResource\Pages;

use App\Filament\Resources\ModelVariableMappingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditModelVariableMapping extends EditRecord
{
    protected static string $resource = ModelVariableMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    public function getTitle(): string
    {
        $record = $this->getRecord();
        return 'Editar Variable: ' . $record->variable_name;
    }
}