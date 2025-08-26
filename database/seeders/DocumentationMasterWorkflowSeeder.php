<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdvancedWorkflow;
use App\Models\WorkflowStepDefinition;
use App\Models\WorkflowStepTemplate;
use App\Models\EmailTemplate;

class DocumentationMasterWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        echo "🚀 Creando Master Workflow para Documentation...\n";
        
        // Verificar si ya existe
        $existing = AdvancedWorkflow::where('target_model', 'App\\Models\\Documentation')
            ->where('is_master_workflow', true)
            ->first();
            
        if ($existing) {
            echo "⚠️ Ya existe un Master Workflow para Documentation. Eliminando...\n";
            $existing->stepDefinitions()->delete();
            $existing->delete();
        }
        
        \DB::beginTransaction();
        
        try {
            // Crear el Master Workflow para Documentation
            $masterWorkflow = AdvancedWorkflow::create([
            'name' => 'Documentation Master Workflow',
            'description' => 'Workflow maestro que maneja todo el ciclo de vida de los documentos',
            'target_model' => 'App\\Models\\Documentation',
            'trigger_conditions' => [
                'events' => [], // No eventos específicos - se evalúa siempre
            ],
            'is_active' => true,
            'is_master_workflow' => true,
            'version' => 1,
            'global_variables' => [
                'app_name' => '{{app_name}}',
                'admin_email' => 'admin@saashelpdesk.com',
            ],
        ]);

        echo "✅ Master Workflow creado: {$masterWorkflow->name}\n";

        // PASO 1: Notificación de Creación
        $step1 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $masterWorkflow->id,
            'step_name' => 'Notificar Creación',
            'description' => 'Notificar cuando se crea un nuevo documento',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 1,
            'conditions' => [
                'trigger_events' => ['created'],
            ],
            'step_config' => [],
            'is_active' => true,
        ]);

        // PASO 2: Notificación Envío para Aprobación  
        $step2 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $masterWorkflow->id,
            'step_name' => 'Notificar Envío para Aprobación',
            'description' => 'Notificar cuando un documento se envía para aprobación',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 2,
            'conditions' => [
                'trigger_events' => ['state_changed'],
                'state_conditions' => [
                    'to_state' => 'pending_approval'
                ],
            ],
            'step_config' => [],
            'is_active' => true,
        ]);

        // PASO 3: Proceso de Aprobación
        $step3 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $masterWorkflow->id,
            'step_name' => 'Proceso de Aprobación',
            'description' => 'Solicitar aprobación a usuarios con rol manager',
            'step_type' => WorkflowStepDefinition::TYPE_APPROVAL,
            'step_order' => 3,
            'conditions' => [
                'trigger_events' => ['state_changed'],
                'state_conditions' => [
                    'to_state' => 'pending_approval'
                ],
            ],
            'step_config' => [
                'approval' => [
                    'type' => 'single', // single, multiple, unanimous
                    'approvers' => [
                        [
                            'type' => 'role',
                            'value' => 'manager',
                        ],
                    ],
                    'timeout_hours' => 72,
                    'auto_approve' => false,
                ],
            ],
            'is_active' => true,
        ]);

        // PASO 4: Notificación de Aprobación
        $step4 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $masterWorkflow->id,
            'step_name' => 'Notificar Aprobación',
            'description' => 'Notificar cuando un documento es aprobado',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 4,
            'conditions' => [
                'trigger_events' => ['state_changed'],
                'state_conditions' => [
                    'transition_name' => 'approve'
                ],
            ],
            'step_config' => [],
            'is_active' => true,
        ]);

        // PASO 5: Notificación de Rechazo
        $step5 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $masterWorkflow->id,
            'step_name' => 'Notificar Rechazo',
            'description' => 'Notificar cuando un documento es rechazado',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 5,
            'conditions' => [
                'trigger_events' => ['state_changed'],
                'state_conditions' => [
                    'transition_name' => 'reject'
                ],
            ],
            'step_config' => [],
            'is_active' => true,
        ]);

        // PASO 6: Notificación de Publicación
        $step6 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $masterWorkflow->id,
            'step_name' => 'Notificar Publicación',
            'description' => 'Notificar cuando un documento es publicado',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 6,
            'conditions' => [
                'trigger_events' => ['state_changed'],
                'state_conditions' => [
                    'to_state' => 'published'
                ],
            ],
            'step_config' => [],
            'is_active' => true,
        ]);

        // PASO 7: Limpieza de Archivos
        $step7 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $masterWorkflow->id,
            'step_name' => 'Limpieza de Archivos',
            'description' => 'Limpiar documentos archivados antiguos',
            'step_type' => WorkflowStepDefinition::TYPE_ACTION,
            'step_order' => 7,
            'conditions' => [
                'trigger_events' => ['state_changed'],
                'state_conditions' => [
                    'to_state' => 'archived'
                ],
                'field_conditions' => [
                    [
                        'field' => 'created_at',
                        'operator' => '<',
                        'value' => '1 year ago'
                    ]
                ]
            ],
            'step_config' => [
                'action' => [
                    'type' => 'call_method',
                    'method' => 'cleanupOldData',
                    'parameters' => []
                ]
            ],
            'is_active' => true,
        ]);

        echo "✅ Creados 7 pasos para el Master Workflow\n";

        // Crear templates de email básicos (reutilizar existentes si están disponibles)
        $this->createEmailTemplates($masterWorkflow, [
            $step1, $step2, $step4, $step5, $step6
        ]);

            \DB::commit();
            
            echo "🎉 Master Workflow completado!\n";
            echo "📍 Pasos creados:\n";
            echo "   1. Notificar Creación (created)\n";
            echo "   2. Notificar Envío para Aprobación (to_state: pending_approval)\n"; 
            echo "   3. Proceso de Aprobación (to_state: pending_approval)\n";
            echo "   4. Notificar Aprobación (transition: approve)\n";
            echo "   5. Notificar Rechazo (transition: reject)\n";
            echo "   6. Notificar Publicación (to_state: published)\n";
            echo "   7. Limpieza de Archivos (to_state: archived + created < 1 year)\n";
            
        } catch (\Exception $e) {
            \DB::rollback();
            echo "❌ Error creando Master Workflow: " . $e->getMessage() . "\n";
            throw $e;
        }
    }

    private function createEmailTemplates(AdvancedWorkflow $workflow, array $steps): void
    {
        // Template para creación
        $creationTemplate = EmailTemplate::firstOrCreate([
            'key' => 'documentation_created'
        ], [
            'name' => 'Documento Creado',
            'subject' => '📄 Nuevo documento creado: "{{document_title}}"',
            'content' => '
                <h2>Nuevo Documento Creado</h2>
                <p>Se ha creado un nuevo documento en el sistema:</p>
                <ul>
                    <li><strong>Título:</strong> {{document_title}}</li>
                    <li><strong>Creado por:</strong> {{creator_name}}</li>
                    <li><strong>Fecha:</strong> {{created_date}}</li>
                </ul>
                <p>Puedes ver el documento <a href="{{document_url}}">aquí</a>.</p>
            ',
            'category' => 'workflow',
            'is_active' => true,
        ]);

        // Template para envío aprobación
        $approvalRequestTemplate = EmailTemplate::firstOrCreate([
            'key' => 'documentation_approval_request'
        ], [
            'name' => 'Solicitud de Aprobación',
            'subject' => '⏳ Aprobación requerida: "{{document_title}}"',
            'content' => '
                <h2>Solicitud de Aprobación</h2>
                <p>El siguiente documento requiere tu aprobación:</p>
                <ul>
                    <li><strong>Título:</strong> {{document_title}}</li>
                    <li><strong>Creado por:</strong> {{creator_name}}</li>
                    <li><strong>Descripción:</strong> {{document_description}}</li>
                </ul>
                <p>Por favor, revisa y aprueba el documento <a href="{{document_url}}">aquí</a>.</p>
            ',
            'category' => 'workflow',
            'is_active' => true,
        ]);

        // Crear WorkflowStepTemplates para conectar pasos con templates
        foreach ($steps as $step) {
            $templateKey = match($step->step_name) {
                'Notificar Creación' => 'documentation_created',
                'Notificar Envío para Aprobación' => 'documentation_approval_request',
                'Proceso de Aprobación' => 'documentation_approval_request',
                default => 'documentation_created'
            };

            WorkflowStepTemplate::create([
                'workflow_step_definition_id' => $step->id,
                'email_template_key' => $templateKey,
                'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_CREATOR,
                'recipient_config' => [],
                'template_variables' => [],
            ]);
        }

        echo "✅ Templates de email creados y conectados\n";
    }
}