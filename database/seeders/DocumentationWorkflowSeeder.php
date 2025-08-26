<?php

namespace Database\Seeders;

use App\Models\AdvancedWorkflow;
use App\Models\WorkflowStepDefinition;
use App\Models\WorkflowStepTemplate;
use App\Models\EmailTemplate;
use App\Models\Documentation;
use Illuminate\Database\Seeder;

class DocumentationWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar workflows existentes para Documentation
        AdvancedWorkflow::where('target_model', Documentation::class)->delete();

        // Crear workflow principal para Documentation
        $workflow = AdvancedWorkflow::create([
            'name' => 'Flujo de Aprobación de Documentación',
            'description' => 'Workflow completo para notificaciones, aprobación por 2 usuarios y autorización final por super-admin',
            'target_model' => Documentation::class,
            'trigger_conditions' => [
                'events' => ['created', 'updated', 'submitted_for_approval', 'approval_level_1_received', 'approval_level_2_received', 'approval_rejected']
            ],
            'is_active' => true,
            'version' => 1,
            'global_variables' => []
        ]);

        // PASO 1: Notificación de creación
        $step1 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $workflow->id,
            'step_name' => 'Notificación de Creación',
            'description' => 'Notificar al creador sobre la creación exitosa del documento',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 1,
            'conditions' => [
                'trigger_events' => ['created'],
                'field_conditions' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => Documentation::STATUS_DRAFT
                    ]
                ]
            ],
            'step_config' => [
                'priority' => 'normal'
            ],
            'is_active' => true
        ]);

        // Template para notificación de creación
        $template1 = WorkflowStepTemplate::create([
            'workflow_step_definition_id' => $step1->id,
            'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_CREATOR,
            'recipient_config' => [
                'dynamic_type' => 'creator'
            ],
            'email_template_key' => 'documentation_created',
            'template_variables' => [
                'send_conditions' => [
                    'trigger_events' => ['created']
                ]
            ]
        ]);

        // PASO 2: Notificación de edición
        $step2 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $workflow->id,
            'step_name' => 'Notificación de Edición',
            'description' => 'Notificar al creador y editor sobre cambios en el documento',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 2,
            'conditions' => [
                'trigger_events' => ['updated'],
                'field_conditions' => [
                    [
                        'field' => 'last_edited_at',
                        'operator' => 'changed',
                        'value' => null
                    ]
                ]
            ],
            'step_config' => [
                'priority' => 'normal'
            ],
            'is_active' => true
        ]);

        // Template para notificación de edición al creador
        $template2a = WorkflowStepTemplate::create([
            'workflow_step_definition_id' => $step2->id,
            'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_CREATOR,
            'recipient_config' => [
                'dynamic_type' => 'creator'
            ],
            'email_template_key' => 'documentation_edited',
            'template_variables' => [
                'send_conditions' => [
                    'trigger_events' => ['updated']
                ]
            ]
        ]);

        // Template para notificación de edición al editor
        $template2b = WorkflowStepTemplate::create([
            'workflow_step_definition_id' => $step2->id,
            'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_DYNAMIC,
            'recipient_config' => [
                'dynamic_type' => 'last_editor'
            ],
            'email_template_key' => 'documentation_edited',
            'template_variables' => [
                'send_conditions' => [
                    'trigger_events' => ['updated']
                ]
            ]
        ]);

        // PASO 3: Solicitud de Aprobación (Primer Nivel)
        $step3 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $workflow->id,
            'step_name' => 'Aprobación Nivel 1',
            'description' => 'Solicitar aprobación al primer aprobador',
            'step_type' => WorkflowStepDefinition::TYPE_APPROVAL,
            'step_order' => 3,
            'conditions' => [
                'trigger_events' => ['submitted_for_approval'],
                'field_conditions' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => Documentation::STATUS_PENDING_APPROVAL
                    ],
                    [
                        'field' => 'approval_level',
                        'operator' => '=',
                        'value' => Documentation::APPROVAL_LEVEL_NONE
                    ]
                ]
            ],
            'step_config' => [
                'approval_type' => 'single',
                'approvers' => [
                    [
                        'type' => 'role',
                        'role' => 'panel_user'
                    ]
                ],
                'timeout_hours' => 48
            ],
            'is_active' => true
        ]);

        // Template para solicitud de aprobación nivel 1
        $template3 = WorkflowStepTemplate::create([
            'workflow_step_definition_id' => $step3->id,
            'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_ROLE,
            'recipient_config' => [
                'role_names' => ['panel_user']
            ],
            'email_template_key' => 'documentation_approval_request_level_1',
            'template_variables' => [
                'send_conditions' => [
                    'trigger_events' => ['submitted_for_approval']
                ]
            ]
        ]);

        // PASO 4: Aprobación Nivel 2
        $step4 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $workflow->id,
            'step_name' => 'Aprobación Nivel 2',
            'description' => 'Solicitar aprobación al segundo aprobador',
            'step_type' => WorkflowStepDefinition::TYPE_APPROVAL,
            'step_order' => 4,
            'conditions' => [
                'trigger_events' => ['approval_level_1_received'],
                'field_conditions' => [
                    [
                        'field' => 'approval_level',
                        'operator' => '=',
                        'value' => Documentation::APPROVAL_LEVEL_FIRST
                    ]
                ]
            ],
            'step_config' => [
                'approval_type' => 'single',
                'approvers' => [
                    [
                        'type' => 'role',
                        'role' => 'panel_user'
                    ]
                ],
                'timeout_hours' => 48
            ],
            'is_active' => true
        ]);

        // Template para solicitud de aprobación nivel 2
        $template4 = WorkflowStepTemplate::create([
            'workflow_step_definition_id' => $step4->id,
            'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_ROLE,
            'recipient_config' => [
                'role_names' => ['panel_user']
            ],
            'email_template_key' => 'documentation_approval_request_level_2',
            'template_variables' => [
                'send_conditions' => [
                    'trigger_events' => ['approval_level_1_received']
                ]
            ]
        ]);

        // PASO 5: Aprobación Final (Super Admin)
        $step5 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $workflow->id,
            'step_name' => 'Aprobación Final - Super Admin',
            'description' => 'Autorización final por super-admin',
            'step_type' => WorkflowStepDefinition::TYPE_APPROVAL,
            'step_order' => 5,
            'conditions' => [
                'trigger_events' => ['approval_level_2_received'],
                'field_conditions' => [
                    [
                        'field' => 'approval_level',
                        'operator' => '=',
                        'value' => Documentation::APPROVAL_LEVEL_SECOND
                    ]
                ]
            ],
            'step_config' => [
                'approval_type' => 'single',
                'approvers' => [
                    [
                        'type' => 'role',
                        'role' => 'super_admin'
                    ]
                ],
                'timeout_hours' => 24
            ],
            'is_active' => true
        ]);

        // Template para solicitud de aprobación final
        $template5 = WorkflowStepTemplate::create([
            'workflow_step_definition_id' => $step5->id,
            'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_ROLE,
            'recipient_config' => [
                'role_names' => ['super_admin']
            ],
            'email_template_key' => 'documentation_approval_request_final',
            'template_variables' => [
                'send_conditions' => [
                    'trigger_events' => ['approval_level_2_received']
                ]
            ]
        ]);

        // PASO 6: Notificación de Rechazo
        $step6 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $workflow->id,
            'step_name' => 'Notificación de Rechazo',
            'description' => 'Notificar al creador sobre el rechazo del documento',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 6,
            'conditions' => [
                'trigger_events' => ['approval_rejected'],
                'field_conditions' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => Documentation::STATUS_REJECTED
                    ]
                ]
            ],
            'step_config' => [
                'priority' => 'high',
                'immediate' => true
            ],
            'is_active' => true
        ]);

        // Template para rechazo
        $template6 = WorkflowStepTemplate::create([
            'workflow_step_definition_id' => $step6->id,
            'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_CREATOR,
            'recipient_config' => [
                'dynamic_type' => 'creator'
            ],
            'email_template_key' => 'documentation_rejected',
            'template_variables' => [
                'send_conditions' => [
                    'trigger_events' => ['approval_rejected']
                ]
            ]
        ]);

        // PASO 7: Notificación de Publicación Exitosa
        $step7 = WorkflowStepDefinition::create([
            'advanced_workflow_id' => $workflow->id,
            'step_name' => 'Notificación de Publicación',
            'description' => 'Notificar al creador sobre la publicación exitosa del documento',
            'step_type' => WorkflowStepDefinition::TYPE_NOTIFICATION,
            'step_order' => 7,
            'conditions' => [
                'trigger_events' => ['documentation_published'],
                'field_conditions' => [
                    [
                        'field' => 'status',
                        'operator' => '=',
                        'value' => Documentation::STATUS_PUBLISHED
                    ]
                ]
            ],
            'step_config' => [
                'priority' => 'normal',
                'celebration' => true
            ],
            'is_active' => true
        ]);

        // Template para publicación
        $template7 = WorkflowStepTemplate::create([
            'workflow_step_definition_id' => $step7->id,
            'recipient_type' => WorkflowStepTemplate::RECIPIENT_TYPE_CREATOR,
            'recipient_config' => [
                'dynamic_type' => 'creator'
            ],
            'email_template_key' => 'documentation_published',
            'template_variables' => [
                'send_conditions' => [
                    'trigger_events' => ['documentation_published']
                ]
            ]
        ]);

        // CREAR TEMPLATES DE EMAIL
        $this->createEmailTemplates();

        $this->command->info('✅ Workflow de Documentación creado exitosamente');
        $this->command->info("   - Workflow ID: {$workflow->id}");
        $this->command->info("   - Pasos creados: 7");
        $this->command->info("   - Templates creados: 8");
        $this->command->info("   - Templates de email: 7");
    }

    private function createEmailTemplates(): void
    {
        // Limpiar templates existentes
        EmailTemplate::whereIn('key', [
            'documentation_created',
            'documentation_edited', 
            'documentation_approval_request_level_1',
            'documentation_approval_request_level_2',
            'documentation_approval_request_final',
            'documentation_rejected',
            'documentation_published'
        ])->delete();

        // Template 1: Documento Creado
        EmailTemplate::create([
            'key' => 'documentation_created',
            'name' => 'Documento Creado - Confirmación',
            'subject' => '✅ Documento "{{document_title}}" creado exitosamente',
            'content' => '
                <h2>Documento Creado Exitosamente</h2>
                
                <p>Hola <strong>{{creator_name}}</strong>,</p>
                
                <p>Tu documento ha sido creado exitosamente:</p>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>Título:</strong> {{document_title}}<br>
                    <strong>Estado:</strong> {{document_status}}<br>
                    <strong>Fecha de creación:</strong> {{created_date}}<br>
                    <strong>ID:</strong> #{{document_id}}
                </div>
                
                <p>Puedes ver y editar tu documento haciendo clic en el siguiente enlace:</p>
                <p><a href="{{document_url}}" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ver Documento</a></p>
                
                <p>Saludos,<br>Sistema de Documentación</p>
            ',
            'language' => 'es',
            'category' => 'documentation',
            'description' => 'Confirmación de creación de documento',
            'is_active' => true
        ]);

        // Template 2: Documento Editado
        EmailTemplate::create([
            'key' => 'documentation_edited',
            'name' => 'Documento Editado - Notificación',
            'subject' => '📝 Documento "{{document_title}}" ha sido editado',
            'content' => '
                <h2>Documento Editado</h2>
                
                <p>El documento <strong>"{{document_title}}"</strong> ha sido editado:</p>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>Editado por:</strong> {{editor_name}}<br>
                    <strong>Fecha de edición:</strong> {{edited_date}}<br>
                    <strong>Estado actual:</strong> {{document_status}}
                </div>
                
                <p><a href="{{document_url}}" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Ver Cambios</a></p>
                
                <p>Saludos,<br>Sistema de Documentación</p>
            ',
            'language' => 'es',
            'category' => 'documentation',
            'description' => 'Notificación de edición de documento',
            'is_active' => true
        ]);

        // Template 3: Solicitud Aprobación Nivel 1
        EmailTemplate::create([
            'key' => 'documentation_approval_request_level_1',
            'name' => 'Solicitud Aprobación Nivel 1',
            'subject' => '⏳ Documento pendiente de aprobación: "{{document_title}}"',
            'content' => '
                <h2>Solicitud de Aprobación - Nivel 1</h2>
                
                <p>Se requiere tu aprobación para el siguiente documento:</p>
                
                <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>Título:</strong> {{document_title}}<br>
                    <strong>Creado por:</strong> {{creator_name}}<br>
                    <strong>Fecha de creación:</strong> {{created_date}}<br>
                    <strong>Nivel de aprobación:</strong> 1 de 3
                </div>
                
                <p><a href="{{document_url}}" style="background: #ff9800; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Revisar y Aprobar</a></p>
                
                <p>Por favor, revisa el documento y procede con la aprobación o rechazo.</p>
                
                <p>Saludos,<br>Sistema de Documentación</p>
            ',
            'language' => 'es',
            'category' => 'documentation',
            'description' => 'Solicitud de aprobación nivel 1',
            'is_active' => true
        ]);

        // Template 4: Solicitud Aprobación Nivel 2
        EmailTemplate::create([
            'key' => 'documentation_approval_request_level_2',
            'name' => 'Solicitud Aprobación Nivel 2',
            'subject' => '⏳ Aprobación Nivel 2 requerida: "{{document_title}}"',
            'content' => '
                <h2>Solicitud de Aprobación - Nivel 2</h2>
                
                <p>El documento ha pasado la primera aprobación y ahora requiere tu revisión:</p>
                
                <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0;">
                    <strong>Título:</strong> {{document_title}}<br>
                    <strong>Creado por:</strong> {{creator_name}}<br>
                    <strong>Primera aprobación:</strong> ✅ Completada<br>
                    <strong>Nivel actual:</strong> 2 de 3
                </div>
                
                <p><a href="{{document_url}}" style="background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Revisar y Aprobar</a></p>
                
                <p>Por favor, realiza la segunda revisión del documento.</p>
                
                <p>Saludos,<br>Sistema de Documentación</p>
            ',
            'language' => 'es',
            'category' => 'documentation',
            'description' => 'Solicitud de aprobación nivel 2',
            'is_active' => true
        ]);

        // Template 5: Solicitud Aprobación Final
        EmailTemplate::create([
            'key' => 'documentation_approval_request_final',
            'name' => 'Solicitud Aprobación Final - Super Admin',
            'subject' => '🔥 Aprobación FINAL requerida: "{{document_title}}"',
            'content' => '
                <h2>Aprobación Final Requerida</h2>
                
                <p>Como <strong>Super Administrador</strong>, se requiere tu autorización final:</p>
                
                <div style="background: #fff8e1; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ff9800;">
                    <strong>Título:</strong> {{document_title}}<br>
                    <strong>Creado por:</strong> {{creator_name}}<br>
                    <strong>Aprobaciones previas:</strong> ✅ Nivel 1 y 2 completadas<br>
                    <strong>Estado:</strong> <span style="color: #ff9800;">⏳ Pendiente autorización final</span>
                </div>
                
                <p><a href="{{document_url}}" style="background: #d32f2f; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">AUTORIZAR PUBLICACIÓN</a></p>
                
                <p><strong>Importante:</strong> Esta es la aprobación final. Una vez autorizado, el documento será publicado.</p>
                
                <p>Saludos,<br>Sistema de Documentación</p>
            ',
            'language' => 'es',
            'category' => 'documentation',
            'description' => 'Solicitud de aprobación final super-admin',
            'is_active' => true
        ]);

        // Template 6: Documento Rechazado
        EmailTemplate::create([
            'key' => 'documentation_rejected',
            'name' => 'Documento Rechazado',
            'subject' => '❌ Documento "{{document_title}}" ha sido rechazado',
            'content' => '
                <h2>Documento Rechazado</h2>
                
                <p>Hola <strong>{{creator_name}}</strong>,</p>
                
                <p>Lamentamos informarte que tu documento ha sido rechazado:</p>
                
                <div style="background: #ffebee; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #f44336;">
                    <strong>Título:</strong> {{document_title}}<br>
                    <strong>Rechazado por:</strong> {{documentation_rejected_by}}<br>
                    <strong>Fecha de rechazo:</strong> {{documentation_rejected_at}}<br>
                    <strong>Razón:</strong> {{documentation_rejection_reason}}
                </div>
                
                <p>Puedes revisar los comentarios y realizar las correcciones necesarias:</p>
                <p><a href="{{document_url}}" style="background: #f44336; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Revisar Documento</a></p>
                
                <p>Una vez realizadas las correcciones, puedes volver a enviar el documento para aprobación.</p>
                
                <p>Saludos,<br>Sistema de Documentación</p>
            ',
            'language' => 'es',
            'category' => 'documentation',
            'description' => 'Notificación de documento rechazado',
            'is_active' => true
        ]);

        // Template 7: Documento Publicado
        EmailTemplate::create([
            'key' => 'documentation_published',
            'name' => 'Documento Publicado Exitosamente',
            'subject' => '🎉 ¡Tu documento "{{document_title}}" ha sido publicado!',
            'content' => '
                <h2>🎉 ¡Documento Publicado Exitosamente!</h2>
                
                <p>¡Excelentes noticias, <strong>{{creator_name}}</strong>!</p>
                
                <p>Tu documento ha completado todo el proceso de aprobación y ha sido <strong>publicado exitosamente</strong>:</p>
                
                <div style="background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #4caf50;">
                    <strong>Título:</strong> {{document_title}}<br>
                    <strong>Estado:</strong> <span style="color: #4caf50;">✅ PUBLICADO</span><br>
                    <strong>Fecha de publicación:</strong> {{documentation_approved_at}}<br>
                    <strong>Proceso completado:</strong> ✅ Todos los niveles de aprobación
                </div>
                
                <p>Tu documento ahora está disponible y accesible en el sistema:</p>
                <p><a href="{{document_url}}" style="background: #4caf50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🎯 Ver Documento Publicado</a></p>
                
                <hr style="margin: 20px 0; border: 1px solid #ddd;">
                
                <h3>📊 Resumen del Proceso:</h3>
                <ul>
                    <li>✅ Creación completada</li>
                    <li>✅ Primera aprobación</li>
                    <li>✅ Segunda aprobación</li>
                    <li>✅ Autorización final</li>
                    <li>🎉 <strong>Publicación exitosa</strong></li>
                </ul>
                
                <p><strong>¡Felicitaciones por completar todo el proceso de aprobación!</strong></p>
                
                <p>Saludos,<br>Sistema de Documentación</p>
            ',
            'language' => 'es',
            'category' => 'documentation',
            'description' => 'Celebración de documento publicado exitosamente',
            'is_active' => true
        ]);
    }
}