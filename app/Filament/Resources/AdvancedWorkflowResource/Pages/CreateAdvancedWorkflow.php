<?php

namespace App\Filament\Resources\AdvancedWorkflowResource\Pages;

use App\Filament\Resources\AdvancedWorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAdvancedWorkflow extends CreateRecord
{
    protected static string $resource = AdvancedWorkflowResource::class;
    
    public function getTitle(): string
    {
        return 'Crear Workflow Avanzado';
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Asegurar que trigger_conditions sea un array válido
        if (!isset($data['trigger_conditions']) || !is_array($data['trigger_conditions'])) {
            $data['trigger_conditions'] = [];
        }
        
        // Asegurar que global_variables sea un array válido
        if (!isset($data['global_variables']) || !is_array($data['global_variables'])) {
            $data['global_variables'] = [];
        }
        
        return $data;
    }
}