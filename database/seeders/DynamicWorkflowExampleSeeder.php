<?php

namespace Database\Seeders;

use App\Models\AdvancedWorkflow;
use App\Models\WorkflowStepDefinition;
use App\Models\EmailTemplate;
use App\Models\Documentation;
use Illuminate\Database\Seeder;

class DynamicWorkflowExampleSeeder extends Seeder
{
    /**
     * Ejemplo de cómo crear workflows con email templates dinámicos
     */
    public function run(): void
    {
        $this->command->info('🚀 Creando ejemplo de Workflow Dinámico con Email Templates...');

        // PASO 1: Crear algunos email templates de ejemplo
        $this->createExampleEmailTemplates();

        // PASO 2: Crear workflow con configuración dinámica
        $this->createDynamicWorkflow();

        $this->command->info('✅ Workflow dinámico de ejemplo creado exitosamente');
    }

    private function createExampleEmailTemplates(): void
    {
        // Limpiar templates existentes de ejemplo
        EmailTemplate::whereIn('key', [
            'doc_creation_notification',
            'doc_approval_request',
            'doc_approval_granted',
            'doc_rejected_notice'
        ])->delete();

        // Template 1: Notificación de creación
        EmailTemplate::create([
            'key' => 'doc_creation_notification',
            'name' => '📄 Documento Creado - Notificación',
            'subject' => '✅ Nuevo documento creado: "{{document_title}}"',
            'content' => '<div style="padding: 40px 20px; text-align: center;">
                <h2 style="color: #10b981; margin-bottom: 20px;">📄 Documento Creado</h2>
                <p style="font-size: 16px; margin-bottom: 15px;">
                    Se ha creado un nuevo documento en el sistema:
                </p>
                <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #374151; margin-bottom: 10px;">{{document_title}}</h3>
                    <p style="color: #6b7280; margin-bottom: 5px;">
                        <strong>Creado por:</strong> {{creator_name}}
                    </p>
                    <p style="color: #6b7280;">
                        <strong>Fecha:</strong> {{created_at|date:d/m/Y H:i}}
                    </p>
                </div>
                <p style="color: #6b7280; font-size: 14px;">
                    Este es un mensaje automático del sistema de gestión documental.
                </p>
            </div>',
            'variables' => ['document_title', 'creator_name', 'created_at'],
            'model_type' => Documentation::class,
            'language' => 'es',
            'is_active' => true,
            'description' => 'Notificación enviada cuando se crea un nuevo documento'
        ]);

        // Template 2: Solicitud de aprobación
        EmailTemplate::create([
            'key' => 'doc_approval_request',
            'name' => '⏳ Solicitud de Aprobación de Documento',
            'subject' => '⏳ Documento pendiente de tu aprobación: "{{document_title}}"',
            'content' => '<div style="padding: 40px 20px; text-align: center;">
                <h2 style="color: #f59e0b; margin-bottom: 20px;">⏳ Solicitud de Aprobación</h2>
                <p style="font-size: 16px; margin-bottom: 15px;">
                    Un documento requiere tu aprobación:
                </p>
                <div style="background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #92400e; margin-bottom: 10px;">{{document_title}}</h3>
                    <p style="color: #92400e; margin-bottom: 5px;">
                        <strong>Autor:</strong> {{creator_name}}
                    </p>
                    <p style="color: #92400e; margin-bottom: 15px;">
                        <strong>Fecha límite:</strong> {{approval_deadline|date:d/m/Y}}
                    </p>
                    <a href="{{approval_url}}" style="display: inline-block; background: #f59e0b; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold;">
                        🔍 Revisar y Aprobar
                    </a>
                </div>
                <p style="color: #6b7280; font-size: 14px;">
                    Por favor, revisa el documento y toma una decisión lo antes posible.
                </p>
            </div>',
            'variables' => ['document_title', 'creator_name', 'approval_deadline', 'approval_url'],
            'model_type' => Documentation::class,
            'language' => 'es',
            'is_active' => true,
            'description' => 'Email enviado a los aprobadores cuando se solicita aprobación'
        ]);

        // Template 3: Aprobación concedida
        EmailTemplate::create([
            'key' => 'doc_approval_granted',
            'name' => '✅ Documento Aprobado',
            'subject' => '🎉 Tu documento "{{document_title}}" ha sido aprobado',
            'content' => '<div style="padding: 40px 20px; text-align: center;">
                <h2 style="color: #10b981; margin-bottom: 20px;">🎉 ¡Documento Aprobado!</h2>
                <p style="font-size: 16px; margin-bottom: 15px;">
                    Excelente noticia, tu documento ha sido aprobado:
                </p>
                <div style="background: #d1fae5; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #065f46; margin-bottom: 10px;">{{document_title}}</h3>
                    <p style="color: #065f46; margin-bottom: 5px;">
                        <strong>Aprobado por:</strong> {{approver_name}}
                    </p>
                    <p style="color: #065f46; margin-bottom: 15px;">
                        <strong>Fecha de aprobación:</strong> {{approved_at|date:d/m/Y H:i}}
                    </p>
                    {{#approval_comments}}
                    <div style="background: white; padding: 15px; border-radius: 6px; margin: 10px 0;">
                        <p style="color: #374151; font-style: italic;">"{{approval_comments}}"</p>
                    </div>
                    {{/approval_comments}}
                </div>
                <p style="color: #6b7280; font-size: 14px;">
                    El documento ya puede ser publicado o continuar con el siguiente paso del proceso.
                </p>
            </div>',
            'variables' => ['document_title', 'approver_name', 'approved_at', 'approval_comments'],
            'model_type' => Documentation::class,
            'language' => 'es',
            'is_active' => true,
            'description' => 'Email enviado cuando un documento es aprobado'
        ]);

        // Template 4: Documento rechazado
        EmailTemplate::create([
            'key' => 'doc_rejected_notice',
            'name' => '❌ Documento Rechazado',
            'subject' => '❌ Tu documento "{{document_title}}" requiere revisiones',
            'content' => '<div style="padding: 40px 20px; text-align: center;">
                <h2 style="color: #ef4444; margin-bottom: 20px;">❌ Documento Rechazado</h2>
                <p style="font-size: 16px; margin-bottom: 15px;">
                    Tu documento requiere algunas revisiones antes de ser aprobado:
                </p>
                <div style="background: #fee2e2; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h3 style="color: #dc2626; margin-bottom: 10px;">{{document_title}}</h3>
                    <p style="color: #dc2626; margin-bottom: 5px;">
                        <strong>Revisado por:</strong> {{reviewer_name}}
                    </p>
                    <p style="color: #dc2626; margin-bottom: 15px;">
                        <strong>Fecha de revisión:</strong> {{reviewed_at|date:d/m/Y H:i}}
                    </p>
                    {{#rejection_reason}}
                    <div style="background: white; padding: 15px; border-radius: 6px; margin: 10px 0;">
                        <h4 style="color: #dc2626; margin-bottom: 8px;">Comentarios del revisor:</h4>
                        <p style="color: #374151;">"{{rejection_reason}}"</p>
                    </div>
                    {{/rejection_reason}}
                </div>
                <p style="color: #6b7280; font-size: 14px;">
                    Realiza las correcciones necesarias y vuelve a enviar el documento para aprobación.
                </p>
            </div>',
            'variables' => ['document_title', 'reviewer_name', 'reviewed_at', 'rejection_reason'],
            'model_type' => Documentation::class,
            'language' => 'es',
            'is_active' => true,
            'description' => 'Email enviado cuando un documento es rechazado'
        ]);

        $this->command->info('   ✅ Email templates de ejemplo creados');
    }

    private function createDynamicWorkflow(): void
    {
        // Crear el workflow principal
        $workflow = AdvancedWorkflow::create([
            'name' => 'Workflow Dinámico de Documentación (Ejemplo)',
            'description' => 'Ejemplo de workflow con email templates dinámicos y configurables',
            'target_model' => Documentation::class,
            'version' => 1,
            'is_active' => true,
            'is_master_workflow' => true,
            'trigger_conditions' => [
                'events' => ['created', 'updated', 'state_changed'],
                'state_transitions' => [
                    'submit_for_approval',
                    'approve',
                    'reject',
                    'publish'
                ]
            ],
        ]);

        // PASO 1: Notificación de creación (dinámico)
        WorkflowStepDefinition::create([
            'workflow_id' => $workflow->id,
            'step_name' => 'Notificar Creación de Documento',
            'description' => 'Envía notificación cuando se crea un documento',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 1,
            'is_required' => false,
            'is_active' => true,
            'step_config' => [
                // ✨ EMAIL TEMPLATE DINÁMICO - SE PUEDE CAMBIAR EN LA UI
                'email_template_key' => 'doc_creation_notification',
                
                // Configuración de destinatarios
                'recipient_config' => [
                    'type' => 'dynamic',
                    'dynamic_type' => 'creator'
                ],
                
                // Variables adicionales para el template
                'template_variables' => [
                    'notification_type' => 'creation',
                    'system_name' => 'Sistema de Gestión Documental'
                ],
                
                'notifications' => [
                    'priority' => 'normal'
                ]
            ],
            'conditions' => [
                'trigger_events' => ['created']
            ]
        ]);

        // PASO 2: Solicitud de aprobación (dinámico)
        WorkflowStepDefinition::create([
            'workflow_id' => $workflow->id,
            'step_name' => 'Solicitar Aprobación',
            'description' => 'Solicita aprobación a usuarios con rol específico',
            'step_type' => WorkflowStepDefinition::TYPE_APPROVAL,
            'step_order' => 2,
            'is_required' => true,
            'is_active' => true,
            'step_config' => [
                // Configuración de aprobadores
                'approvers' => [
                    'type' => 'roles',
                    'roles' => ['panel_user'] // Se puede cambiar dinámicamente
                ],
                'timeout_hours' => 48,
                
                // ✨ EMAIL TEMPLATES DINÁMICOS - SE PUEDEN CAMBIAR EN LA UI
                'approval_email_template_key' => 'doc_approval_request',
                'approval_response_email_template_key' => 'doc_approval_granted',
                
                // Variables para los templates de aprobación
                'approval_template_variables' => [
                    'approval_url' => '{{app_url}}/admin/documentations/{{document_id}}/edit',
                    'approval_deadline' => '{{date_add:2 days}}'
                ]
            ],
            'conditions' => [
                'trigger_events' => ['state_transition_submit_for_approval'],
                'state_conditions' => [
                    'to_state' => 'pending_approval'
                ]
            ]
        ]);

        // PASO 3: Notificación de rechazo (dinámico)
        WorkflowStepDefinition::create([
            'workflow_id' => $workflow->id,
            'step_name' => 'Notificar Rechazo',
            'description' => 'Notifica al autor cuando se rechaza el documento',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 3,
            'is_required' => false,
            'is_active' => true,
            'step_config' => [
                // ✨ EMAIL TEMPLATE DINÁMICO - SE PUEDE CAMBIAR EN LA UI
                'email_template_key' => 'doc_rejected_notice',
                
                'recipient_config' => [
                    'type' => 'dynamic',
                    'dynamic_type' => 'creator'
                ],
                
                'template_variables' => [
                    'action_required' => 'revision',
                    'next_step' => 'Realizar correcciones y reenviar'
                ]
            ],
            'conditions' => [
                'trigger_events' => ['state_transition_reject']
            ]
        ]);

        $this->command->info("   ✅ Workflow dinámico creado (ID: {$workflow->id})");
        $this->command->info('   📧 Con 4 email templates configurables dinámicamente');
        $this->command->info('   🔧 Los templates se pueden cambiar desde la interfaz de Filament');

        $this->command->line('');
        $this->command->info('🎯 CÓMO USAR:');
        $this->command->info('   1. Ve a Admin -> Workflows Avanzados');
        $this->command->info('   2. Edita el workflow creado');
        $this->command->info('   3. En cada paso, puedes cambiar el "Template de Email"');
        $this->command->info('   4. Los templates se filtran automáticamente por modelo');
        $this->command->info('   5. Puedes agregar variables personalizadas por paso');
    }
}