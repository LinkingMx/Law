<?php

namespace App\Filament\Resources\AdvancedWorkflowResource\Pages;

use App\Filament\Resources\AdvancedWorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvancedWorkflows extends ListRecords
{
    protected static string $resource = AdvancedWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    public function getTitle(): string
    {
        return 'Workflows Avanzados';
    }
}