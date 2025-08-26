<?php

use Illuminate\Database\Migrations\Migration;
use App\Models\AdvancedWorkflow;

return new class extends Migration
{
    public function up(): void
    {
        // Mapeo de eventos obsoletos a eventos unificados
        $eventMapping = [
            // Eventos específicos → Evento unificado
            'published' => 'state_changed',
            'approved' => 'state_changed', 
            'rejected' => 'state_changed',
            'archived' => 'state_changed',
            'status_changed' => 'state_changed',
            
            // Eventos específicos de estado → Eventos unificados específicos
            'status_changed_to_draft' => 'changed_to_state_draft',
            'status_changed_to_pending_approval' => 'changed_to_state_pending_approval',
            'status_changed_to_rejected' => 'changed_to_state_rejected',
            'status_changed_to_published' => 'changed_to_state_published',
            'status_changed_to_archived' => 'changed_to_state_archived',
        ];
        
        // Actualizar workflows existentes
        $workflows = AdvancedWorkflow::all();
        
        foreach ($workflows as $workflow) {
            $originalEvent = $workflow->trigger_event;
            
            if (isset($eventMapping[$originalEvent])) {
                $newEvent = $eventMapping[$originalEvent];
                
                // Actualizar el evento principal
                $workflow->update(['trigger_event' => $newEvent]);
                
                // Si era un evento específico que ahora es genérico, agregar condiciones
                if ($newEvent === 'state_changed' && $originalEvent !== 'state_changed') {
                    $conditions = $workflow->trigger_conditions ?? [];
                    
                    // Agregar condición específica basada en el evento original
                    switch ($originalEvent) {
                        case 'published':
                            $conditions['to_state_name'] = 'published';
                            break;
                        case 'approved':
                            $conditions['to_state_name'] = 'approved';
                            break;
                        case 'rejected':
                            $conditions['to_state_name'] = 'rejected';
                            break;
                        case 'archived':
                            $conditions['to_state_name'] = 'archived';
                            break;
                    }
                    
                    if (!empty($conditions)) {
                        $workflow->update(['trigger_conditions' => $conditions]);
                    }
                }
                
                echo "✅ Workflow '{$workflow->name}' migrado: {$originalEvent} → {$newEvent}\n";
            }
        }
        
        echo "🎉 Migración de workflows completada\n";
    }

    public function down(): void
    {
        // No revertir - mantener la migración
        echo "⚠️  La migración no se puede revertir automáticamente\n";
    }
};