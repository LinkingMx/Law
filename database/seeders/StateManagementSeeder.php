<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalState;
use App\Models\StateTransition;

class StateManagementSeeder extends Seeder
{
    public function run(): void
    {
        // Estados para Documentation
        $states = [
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'draft',
                'label' => 'Borrador',
                'description' => 'Documento en estado de borrador',
                'color' => 'gray',
                'icon' => 'heroicon-o-document-text',
                'is_initial' => true,
                'is_final' => false,
                'requires_approval' => false,
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'pending_approval',
                'label' => 'Pendiente de Aprobación',
                'description' => 'Documento enviado para aprobación',
                'color' => 'warning',
                'icon' => 'heroicon-o-clock',
                'is_initial' => false,
                'is_final' => false,
                'requires_approval' => true,
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'approved',
                'label' => 'Aprobado',
                'description' => 'Documento aprobado',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'is_initial' => false,
                'is_final' => false,
                'requires_approval' => false,
                'sort_order' => 3,
                'is_active' => true,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'rejected',
                'label' => 'Rechazado',
                'description' => 'Documento rechazado',
                'color' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'is_initial' => false,
                'is_final' => true,
                'requires_approval' => false,
                'sort_order' => 4,
                'is_active' => true,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'published',
                'label' => 'Publicado',
                'description' => 'Documento publicado',
                'color' => 'success',
                'icon' => 'heroicon-o-eye',
                'is_initial' => false,
                'is_final' => false,
                'requires_approval' => false,
                'sort_order' => 5,
                'is_active' => true,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'archived',
                'label' => 'Archivado',
                'description' => 'Documento archivado',
                'color' => 'gray',
                'icon' => 'heroicon-o-archive-box',
                'is_initial' => false,
                'is_final' => true,
                'requires_approval' => false,
                'sort_order' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($states as $stateData) {
            ApprovalState::firstOrCreate([
                'model_type' => $stateData['model_type'],
                'name' => $stateData['name']
            ], $stateData);
        }

        // Obtener los estados creados
        $draftState = ApprovalState::where('name', 'draft')->first();
        $pendingState = ApprovalState::where('name', 'pending_approval')->first();
        $approvedState = ApprovalState::where('name', 'approved')->first();
        $rejectedState = ApprovalState::where('name', 'rejected')->first();
        $publishedState = ApprovalState::where('name', 'published')->first();
        $archivedState = ApprovalState::where('name', 'archived')->first();

        // Transiciones
        $transitions = [
            [
                'from_state_id' => $draftState->id,
                'to_state_id' => $pendingState->id,
                'name' => 'submit_for_approval',
                'label' => 'Enviar para Aprobación',
                'description' => 'Enviar documento para proceso de aprobación',
                'requires_permission' => false,
                'requires_role' => false,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'from_state_id' => $pendingState->id,
                'to_state_id' => $approvedState->id,
                'name' => 'approve',
                'label' => 'Aprobar',
                'description' => 'Aprobar el documento',
                'requires_permission' => true,
                'permission_name' => 'approve_documentation',
                'requires_role' => false,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'from_state_id' => $pendingState->id,
                'to_state_id' => $rejectedState->id,
                'name' => 'reject',
                'label' => 'Rechazar',
                'description' => 'Rechazar el documento',
                'requires_permission' => true,
                'permission_name' => 'reject_documentation',
                'requires_role' => false,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'from_state_id' => $approvedState->id,
                'to_state_id' => $publishedState->id,
                'name' => 'publish',
                'label' => 'Publicar',
                'description' => 'Publicar el documento aprobado',
                'requires_permission' => false,
                'requires_role' => false,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'from_state_id' => $publishedState->id,
                'to_state_id' => $archivedState->id,
                'name' => 'archive',
                'label' => 'Archivar',
                'description' => 'Archivar el documento',
                'requires_permission' => false,
                'requires_role' => false,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'from_state_id' => $rejectedState->id,
                'to_state_id' => $draftState->id,
                'name' => 'back_to_draft',
                'label' => 'Volver a Borrador',
                'description' => 'Devolver documento rechazado a borrador para correcciones',
                'requires_permission' => false,
                'requires_role' => false,
                'requires_approval' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
        ];

        foreach ($transitions as $transitionData) {
            StateTransition::firstOrCreate([
                'from_state_id' => $transitionData['from_state_id'],
                'to_state_id' => $transitionData['to_state_id'],
                'name' => $transitionData['name']
            ], $transitionData);
        }
    }
}