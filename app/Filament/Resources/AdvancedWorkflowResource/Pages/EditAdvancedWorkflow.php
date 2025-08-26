<?php

namespace App\Filament\Resources\AdvancedWorkflowResource\Pages;

use App\Filament\Resources\AdvancedWorkflowResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditAdvancedWorkflow extends EditRecord
{
    protected static string $resource = AdvancedWorkflowResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            
            Actions\Action::make('test_workflow')
                ->label('Probar Workflow')
                ->icon('heroicon-o-play')
                ->color('info')
                ->action(function () {
                    $workflow = $this->getRecord();
                    
                    // Verificar que el workflow esté configurado correctamente
                    $totalSteps = $workflow->stepDefinitions()->active()->count();
                    
                    if ($totalSteps === 0) {
                        Notification::make()
                            ->warning()
                            ->title('Workflow sin pasos')
                            ->body('El workflow debe tener al menos un paso activo para poder probarse.')
                            ->send();
                        return;
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Workflow válido')
                        ->body("El workflow tiene {$totalSteps} pasos configurados y está listo para usarse.")
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Probar Configuración del Workflow')
                ->modalDescription('Esto verificará que el workflow esté configurado correctamente.'),
                
            Actions\Action::make('duplicate')
                ->label('Duplicar')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->action(function () {
                    $record = $this->getRecord();
                    $newWorkflow = $record->replicate([
                        'name',
                    ]);
                    $newWorkflow->name = $record->name . ' (Copia)';
                    $newWorkflow->is_active = false;
                    $newWorkflow->version = 1;
                    $newWorkflow->save();
                    
                    // Duplicar pasos también
                    foreach ($record->stepDefinitions as $step) {
                        $newStep = $step->replicate();
                        $newStep->advanced_workflow_id = $newWorkflow->id;
                        $newStep->save();
                        
                        // Duplicar templates del paso
                        foreach ($step->templates as $template) {
                            $newTemplate = $template->replicate();
                            $newTemplate->workflow_step_definition_id = $newStep->id;
                            $newTemplate->save();
                        }
                    }
                    
                    Notification::make()
                        ->success()
                        ->title('Workflow duplicado')
                        ->body('Se ha creado una copia completa del workflow.')
                        ->send();
                        
                    return redirect()->to(static::getResource()::getUrl('edit', ['record' => $newWorkflow->id]));
                })
                ->requiresConfirmation()
                ->modalHeading('Duplicar Workflow')
                ->modalDescription('Se creará una copia completa del workflow con todos sus pasos y templates.'),
        ];
    }
    
    public function getTitle(): string
    {
        $record = $this->getRecord();
        return 'Editar Workflow: ' . $record->name;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
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
    
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Workflow actualizado')
            ->body('Los cambios han sido guardados correctamente.');
    }
}