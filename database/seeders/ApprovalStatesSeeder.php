<?php

namespace Database\Seeders;

use App\Models\ApprovalState;
use App\Models\StateTransition;
use Illuminate\Database\Seeder;

class ApprovalStatesSeeder extends Seeder
{
    public function run(): void
    {
        // Estados para Documentation
        $docStates = $this->createDocumentationStates();
        $this->createDocumentationTransitions($docStates);
    }

    private function createDocumentationStates(): array
    {
        $states = [
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'draft',
                'label' => 'Borrador',
                'description' => 'Documento en borrador, puede ser editado libremente',
                'color' => 'warning',
                'icon' => 'heroicon-o-document-text',
                'is_initial' => true,
                'is_final' => false,
                'requires_approval' => false,
                'sort_order' => 1,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'pending_approval',
                'label' => 'Pendiente de Aprobación',
                'description' => 'Documento enviado para aprobación, esperando revisión',
                'color' => 'info',
                'icon' => 'heroicon-o-clock',
                'is_initial' => false,
                'is_final' => false,
                'requires_approval' => true,
                'sort_order' => 2,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'approved',
                'label' => 'Aprobado',
                'description' => 'Documento aprobado, listo para publicación',
                'color' => 'success',
                'icon' => 'heroicon-o-check-circle',
                'is_initial' => false,
                'is_final' => false,
                'requires_approval' => false,
                'sort_order' => 3,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'rejected',
                'label' => 'Rechazado',
                'description' => 'Documento rechazado, requiere modificaciones',
                'color' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'is_initial' => false,
                'is_final' => false,
                'requires_approval' => false,
                'sort_order' => 4,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'published',
                'label' => 'Publicado',
                'description' => 'Documento publicado y visible para todos',
                'color' => 'success',
                'icon' => 'heroicon-o-eye',
                'is_initial' => false,
                'is_final' => true,
                'requires_approval' => false,
                'sort_order' => 5,
            ],
            [
                'model_type' => 'App\\Models\\Documentation',
                'name' => 'archived',
                'label' => 'Archivado',
                'description' => 'Documento archivado, no está activo',
                'color' => 'gray',
                'icon' => 'heroicon-o-archive-box',
                'is_initial' => false,
                'is_final' => true,
                'requires_approval' => false,
                'sort_order' => 6,
            ],
        ];

        $createdStates = [];
        foreach ($states as $stateData) {
            $createdStates[$stateData['name']] = ApprovalState::firstOrCreate(
                [
                    'model_type' => $stateData['model_type'],
                    'name' => $stateData['name'],
                ],
                $stateData
            );
        }

        return $createdStates;
    }

    private function createDocumentationTransitions(array $states): void
    {
        $transitions = [
            // Draft -> Pending Approval
            [
                'from_state_id' => $states['draft']->id,
                'to_state_id' => $states['pending_approval']->id,
                'name' => 'submit_for_approval',
                'label' => 'Enviar para Aprobación',
                'description' => 'Enviar el documento para revisión y aprobación',
                'requires_permission' => false,
                'requires_role' => false,
                'requires_approval' => false,
                'success_message' => 'Documento enviado para aprobación exitosamente',
                'sort_order' => 1,
            ],
            // Pending Approval -> Approved
            [
                'from_state_id' => $states['pending_approval']->id,
                'to_state_id' => $states['approved']->id,
                'name' => 'approve',
                'label' => 'Aprobar',
                'description' => 'Aprobar el documento para publicación',
                'requires_permission' => true,
                'permission_name' => 'approve_documentation',
                'requires_role' => true,
                'role_names' => ['super_admin', 'admin'],
                'requires_approval' => false,
                'success_message' => 'Documento aprobado exitosamente',
                'sort_order' => 1,
            ],
            // Pending Approval -> Rejected
            [
                'from_state_id' => $states['pending_approval']->id,
                'to_state_id' => $states['rejected']->id,
                'name' => 'reject',
                'label' => 'Rechazar',
                'description' => 'Rechazar el documento con comentarios',
                'requires_permission' => true,
                'permission_name' => 'approve_documentation',
                'requires_role' => true,
                'role_names' => ['super_admin', 'admin'],
                'requires_approval' => false,
                'success_message' => 'Documento rechazado',
                'sort_order' => 2,
            ],
            // Approved -> Published
            [
                'from_state_id' => $states['approved']->id,
                'to_state_id' => $states['published']->id,
                'name' => 'publish',
                'label' => 'Publicar',
                'description' => 'Publicar el documento aprobado',
                'requires_permission' => true,
                'permission_name' => 'publish_documentation',
                'requires_role' => true,
                'role_names' => ['super_admin', 'admin'],
                'requires_approval' => false,
                'success_message' => 'Documento publicado exitosamente',
                'sort_order' => 1,
            ],
            // Rejected -> Draft
            [
                'from_state_id' => $states['rejected']->id,
                'to_state_id' => $states['draft']->id,
                'name' => 'revise',
                'label' => 'Revisar',
                'description' => 'Volver a borrador para hacer correcciones',
                'requires_permission' => false,
                'requires_role' => false,
                'requires_approval' => false,
                'success_message' => 'Documento devuelto a borrador para revisión',
                'sort_order' => 1,
            ],
            // Published -> Archived
            [
                'from_state_id' => $states['published']->id,
                'to_state_id' => $states['archived']->id,
                'name' => 'archive',
                'label' => 'Archivar',
                'description' => 'Archivar el documento publicado',
                'requires_permission' => true,
                'permission_name' => 'archive_documentation',
                'requires_role' => true,
                'role_names' => ['super_admin', 'admin'],
                'requires_approval' => false,
                'success_message' => 'Documento archivado exitosamente',
                'sort_order' => 1,
            ],
        ];

        foreach ($transitions as $transitionData) {
            StateTransition::firstOrCreate(
                [
                    'from_state_id' => $transitionData['from_state_id'],
                    'to_state_id' => $transitionData['to_state_id'],
                    'name' => $transitionData['name'],
                ],
                $transitionData
            );
        }
    }
}