<?php

namespace Database\Seeders;

use App\Models\AdvancedWorkflow;
use App\Models\WorkflowStepDefinition;
use App\Models\EmailTemplate;
use App\Models\Documentation;
use Illuminate\Database\Seeder;

class SimpleNotificationWorkflowSeeder extends Seeder
{
    /**
     * Workflow simple: Solo notificar al creador cuando se crea un documento
     */
    public function run(): void
    {
        $this->command->info('🚀 Creando workflow simple de notificación...');

        // 1. Crear el email template
        $this->createSimpleEmailTemplate();

        // 2. Crear el workflow simple
        $this->createSimpleWorkflow();

        $this->command->info('✅ Workflow simple creado exitosamente');
        $this->command->info('🎯 Prueba: Crea un documento y revisa tu email');
    }

    private function createSimpleEmailTemplate(): void
    {
        // Limpiar template existente si existe
        EmailTemplate::where('key', 'simple_doc_created')->delete();

        EmailTemplate::create([
            'key' => 'simple_doc_created',
            'name' => '📝 Documento Creado - Confirmación Simple',
            'subject' => '✅ Has creado el documento: "{{document.title}}"',
            'content' => '<div style="padding: 40px 20px; text-align: center; font-family: Arial, sans-serif;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 30px;">
                    <h1 style="margin: 0; font-size: 24px;">📝 ¡Documento Creado!</h1>
                </div>
                
                <div style="background: #f8fafc; padding: 30px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #10b981;">
                    <h2 style="color: #1f2937; margin-top: 0;">{{document.title}}</h2>
                    
                    <div style="text-align: left; margin: 20px 0;">
                        <p style="margin: 8px 0; color: #4b5563;">
                            <strong>📅 Fecha de creación:</strong> {{document.created_at|date:d/m/Y H:i}}
                        </p>
                        <p style="margin: 8px 0; color: #4b5563;">
                            <strong>👤 Creado por:</strong> {{user.name}}
                        </p>
                        <p style="margin: 8px 0; color: #4b5563;">
                            <strong>📧 Email:</strong> {{user.email}}
                        </p>
                        <p style="margin: 8px 0; color: #4b5563;">
                            <strong>🏷️ Estado actual:</strong> <span style="background: #dbeafe; color: #1e40af; padding: 4px 8px; border-radius: 4px;">{{document.state}}</span>
                        </p>
                    </div>
                </div>
                
                <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p style="color: #065f46; margin: 0; font-size: 16px;">
                        🎉 <strong>¡Felicidades!</strong> Tu documento ha sido registrado exitosamente en el sistema.
                    </p>
                </div>
                
                <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 8px;">
                    <p style="color: #6b7280; font-size: 14px; margin: 0;">
                        Este es un mensaje automático del sistema. <br>
                        <strong>{{app_name}}</strong> - Sistema de Gestión Documental
                    </p>
                </div>
            </div>',
            'variables' => [
                'document.title',
                'document.created_at',
                'document.state',
                'user.name',
                'user.email',
                'app_name'
            ],
            'model_type' => Documentation::class,
            'model_variables' => [
                'document' => [
                    'title' => 'Título del documento',
                    'created_at' => 'Fecha de creación',
                    'state' => 'Estado actual'
                ],
                'user' => [
                    'name' => 'Nombre del usuario',
                    'email' => 'Email del usuario'
                ]
            ],
            'language' => 'es',
            'is_active' => true,
            'description' => 'Notificación simple enviada al creador cuando crea un documento'
        ]);

        $this->command->info('   📧 Email template "simple_doc_created" creado');
    }

    private function createSimpleWorkflow(): void
    {
        // Limpiar workflow existente si existe
        AdvancedWorkflow::where('name', 'Notificación Simple - Documento Creado')->delete();

        // Crear el workflow
        $workflow = AdvancedWorkflow::create([
            'name' => 'Notificación Simple - Documento Creado',
            'description' => 'Workflow súper simple: solo notifica al creador cuando se crea un documento',
            'target_model' => Documentation::class,
            'version' => 1,
            'is_active' => true,
            'trigger_conditions' => [
                'events' => ['created']
            ],
        ]);

        // Crear el único paso: Notificación
        WorkflowStepDefinition::create([
            'advanced_workflow_id' => $workflow->id,
            'step_name' => 'Notificar al Creador',
            'description' => 'Envía confirmación por email al usuario que creó el documento',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 1,
            'is_required' => false,
            'is_active' => true,
            'step_config' => [
                // Email template dinámico
                'email_template_key' => 'simple_doc_created',
                
                // Destinatario: el creador del documento
                'recipient_config' => [
                    'type' => 'dynamic',
                    'dynamic_type' => 'creator'
                ],
                
                // Variables adicionales
                'template_variables' => [
                    'notification_type' => 'creation_confirmation',
                    'workflow_name' => 'Simple Notification'
                ],
                
                'notifications' => [
                    'priority' => 'normal'
                ]
            ],
            'conditions' => [
                'trigger_events' => ['created']
            ]
        ]);

        $this->command->info("   🔄 Workflow simple creado (ID: {$workflow->id})");
        $this->command->info('   📝 1 paso: Notificar al creador');
        $this->command->info('   🎯 Se dispara solo al crear documentos');
    }
}