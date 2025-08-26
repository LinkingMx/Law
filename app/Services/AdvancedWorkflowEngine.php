<?php

namespace App\Services;

use App\Models\AdvancedWorkflow;
use App\Models\AdvancedWorkflowExecution;
use App\Models\WorkflowStepDefinition;
use App\Models\WorkflowStepExecutionAdvanced;
use App\Models\WorkflowStepTemplate;
use App\Models\User;
use App\Services\EmailTemplateService;
use App\Services\ModelIntrospectionService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdvancedWorkflowEngine
{
    public function __construct(
        private EmailTemplateService $emailTemplateService,
        private ModelIntrospectionService $modelIntrospectionService
    ) {}

    /**
     * Disparar workflows avanzados para un modelo y evento específico
     * NUEVO ENFOQUE: Master Workflow - Evaluar todos los pasos en cada evento
     */
    public function trigger(Model $model, string $event, array $context = []): array
    {
        $modelClass = get_class($model);
        
        Log::info("🚀 AdvancedWorkflowEngine::trigger called", [
            'model_class' => $modelClass,
            'model_id' => $model->getKey(),
            'event' => $event,
            'context' => $context
        ]);
        
        // Buscar el workflow maestro para este modelo
        $masterWorkflow = AdvancedWorkflow::getMasterWorkflowForModel($modelClass);
        
        Log::info("🔍 Master workflow search result", [
            'master_workflow_found' => $masterWorkflow ? true : false,
            'workflow_id' => $masterWorkflow?->id,
            'workflow_name' => $masterWorkflow?->name
        ]);
        
        if (!$masterWorkflow) {
            // Fallback al sistema anterior si no hay workflow maestro
            Log::info("⚠️ No master workflow found, trying legacy workflows");
            return $this->triggerLegacyWorkflows($model, $event, $context);
        }
        
        // Enfoque Master Workflow: Evaluar todos los pasos
        return $this->processMasterWorkflow($masterWorkflow, $model, $event, $context);
    }
    
    /**
     * Procesar Master Workflow - Evaluar todos los pasos para el evento actual
     */
    protected function processMasterWorkflow(AdvancedWorkflow $workflow, Model $model, string $event, array $context = []): array
    {
        $executions = [];
        
        try {
            // Obtener todos los pasos activos del workflow
            $steps = $workflow->stepDefinitions()
                ->where('is_active', true)
                ->orderBy('step_order')
                ->get();
                
            Log::info('Processing master workflow', [
                'workflow' => $workflow->name,
                'model' => get_class($model),
                'model_id' => $model->getKey(),
                'event' => $event,
                'total_steps' => $steps->count()
            ]);
            
            $executedSteps = [];
            
            // Evaluar cada paso para ver si debe ejecutarse con el evento/contexto actual
            foreach ($steps as $step) {
                if ($step->shouldExecute($model, array_merge($context, ['trigger_event' => $event]))) {
                    Log::info('Step matches conditions, executing', [
                        'step' => $step->step_name,
                        'step_type' => $step->step_type
                    ]);
                    
                    // Crear ejecución para este paso específico
                    $execution = $this->createMasterStepExecution($workflow, $step, $model, $event, $context);
                    
                    if ($execution) {
                        $executions[] = $execution;
                        $executedSteps[] = $step->step_name;
                        
                        // Ejecutar el paso inmediatamente (no secuencial)
                        $this->executeIndependentStep($execution, $step);
                    }
                }
            }
            
            Log::info('Master workflow processing completed', [
                'workflow' => $workflow->name,
                'executed_steps' => $executedSteps,
                'total_executions' => count($executions)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error processing master workflow', [
                'workflow_id' => $workflow->id,
                'model' => get_class($model),
                'model_id' => $model->getKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        return $executions;
    }

    /**
     * Fallback al sistema anterior de workflows múltiples
     */
    protected function triggerLegacyWorkflows(Model $model, string $event, array $context = []): array
    {
        $modelClass = get_class($model);
        $workflows = AdvancedWorkflow::getTriggeredWorkflows($model, $event, $context);
        $executions = [];

        foreach ($workflows as $workflow) {
            try {
                $execution = $this->createExecution($workflow, $model, $event, $context);
                $executions[] = $execution;
                
                // Inicializar y procesar primera etapa
                if ($execution->initialize()) {
                    $this->processExecution($execution);
                }
                
            } catch (\Exception $e) {
                Log::error('Error creating legacy workflow execution', [
                    'workflow_id' => $workflow->id,
                    'model' => $modelClass,
                    'model_id' => $model->getKey(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $executions;
    }
    
    /**
     * Crear ejecución para un paso específico del master workflow
     */
    protected function createMasterStepExecution(
        AdvancedWorkflow $workflow,
        WorkflowStepDefinition $step,
        Model $model,
        string $event,
        array $context = []
    ): ?AdvancedWorkflowExecution {
        try {
            // Preparar contexto enriquecido
            $enrichedContext = $this->enrichContext($model, $event, $context);
            $enrichedContext['master_workflow_step_id'] = $step->id;
            $enrichedContext['master_workflow_step_name'] = $step->step_name;
            
            return AdvancedWorkflowExecution::create([
                'advanced_workflow_id' => $workflow->id,
                'target_model' => get_class($model),
                'target_id' => $model->getKey(),
                'status' => AdvancedWorkflowExecution::STATUS_IN_PROGRESS,
                'context_data' => $enrichedContext,
                'initiated_by' => Auth::id(),
                'current_step_id' => $step->id,
                'current_step_order' => $step->step_order,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating master step execution', [
                'workflow_id' => $workflow->id,
                'step_id' => $step->id,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }
    
    /**
     * Ejecutar un paso independiente (no secuencial)
     */
    protected function executeIndependentStep(AdvancedWorkflowExecution $execution, WorkflowStepDefinition $step): bool
    {
        try {
            $targetModel = $execution->getTargetModel();
            if (!$targetModel) {
                $execution->markAsFailed('Modelo objetivo no encontrado');
                return false;
            }
            
            // Crear ejecución del paso
            $stepExecution = WorkflowStepExecutionAdvanced::create([
                'workflow_execution_id' => $execution->id,
                'step_definition_id' => $step->id,
                'status' => WorkflowStepExecutionAdvanced::STATUS_PENDING,
                'started_at' => now(),
            ]);
            
            // Marcar como iniciado
            $stepExecution->markAsStarted();
            
            // Ejecutar según el tipo de paso
            $success = $this->executeStepByType($execution, $step, $stepExecution);
            
            if ($success) {
                // Para pasos independientes, completar inmediatamente
                if (!$step->requiresManualIntervention()) {
                    $stepExecution->markAsCompleted();
                    $execution->markAsCompleted('Paso independiente completado');
                }
                
                Log::info('Independent step executed successfully', [
                    'execution_id' => $execution->id,
                    'step_name' => $step->step_name,
                    'step_type' => $step->step_type
                ]);
            } else {
                $stepExecution->markAsFailed('Error ejecutando paso independiente');
                $execution->markAsFailed('Error en paso independiente');
            }
            
            return $success;
            
        } catch (\Exception $e) {
            Log::error('Error executing independent step', [
                'execution_id' => $execution->id,
                'step_id' => $step->id,
                'error' => $e->getMessage()
            ]);
            
            $execution->markAsFailed('Error ejecutando paso: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear una nueva ejecución de workflow avanzado (método legacy)
     */
    protected function createExecution(
        AdvancedWorkflow $workflow,
        Model $model,
        string $event,
        array $context = []
    ): AdvancedWorkflowExecution {
        
        // Preparar contexto enriquecido
        $enrichedContext = $this->enrichContext($model, $event, $context);
        
        return AdvancedWorkflowExecution::create([
            'advanced_workflow_id' => $workflow->id,
            'target_model' => get_class($model),
            'target_id' => $model->getKey(),
            'status' => AdvancedWorkflowExecution::STATUS_PENDING,
            'context_data' => $enrichedContext,
            'initiated_by' => Auth::id(),
        ]);
    }

    /**
     * Enriquecer contexto con información del modelo
     */
    protected function enrichContext(Model $model, string $event, array $context): array
    {
        return array_merge($context, [
            'trigger_event' => $event,
            'triggered_at' => now()->toISOString(),
            'trigger_user_id' => Auth::id(),
            'trigger_user_name' => Auth::user()?->name ?? 'Sistema',
            'model_changes' => $this->modelIntrospectionService->detectFieldChanges($model),
            'model_data' => $this->extractModelData($model),
        ]);
    }

    /**
     * Extraer datos relevantes del modelo
     */
    protected function extractModelData(Model $model): array
    {
        $data = [];
        
        // Campos básicos
        $fillableFields = $model->getFillable();
        foreach ($fillableFields as $field) {
            $data[$field] = $model->getAttribute($field);
        }

        // Información de relaciones comunes
        $relationFields = ['creator', 'lastEditor', 'assignedUser'];
        foreach ($relationFields as $relation) {
            if (method_exists($model, $relation)) {
                try {
                    $relatedModel = $model->$relation;
                    if ($relatedModel) {
                        $data["{$relation}_id"] = $relatedModel->getKey();
                        $data["{$relation}_name"] = $relatedModel->name ?? '';
                        if (isset($relatedModel->email)) {
                            $data["{$relation}_email"] = $relatedModel->email;
                        }
                    }
                } catch (\Exception $e) {
                    // Ignorar errores de relaciones
                }
            }
        }

        return $data;
    }

    /**
     * Procesar una ejecución de workflow
     */
    public function processExecution(AdvancedWorkflowExecution $execution): bool
    {
        try {
            if (!$execution->isInProgress()) {
                return false;
            }

            // Procesar pasos en un bucle para evitar recursión infinita
            $maxSteps = 10; // Límite de seguridad
            $stepsProcessed = 0;
            
            while ($execution->isInProgress() && $stepsProcessed < $maxSteps) {
                $currentStep = $execution->currentStep;
                if (!$currentStep) {
                    // No hay más pasos, el workflow está completado
                    return true;
                }

                $result = $this->processSingleStep($execution, $currentStep);
                
                if (!$result) {
                    // Si el paso falló, detener el procesamiento
                    return false;
                }
                
                // Si el paso requiere intervención manual, detener aquí
                if ($currentStep->requiresManualIntervention()) {
                    return true;
                }
                
                $stepsProcessed++;
            }
            
            if ($stepsProcessed >= $maxSteps) {
                $execution->markAsFailed('Se alcanzó el límite máximo de pasos procesados');
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error processing advanced workflow execution', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);

            $execution->markAsFailed($e->getMessage());
            return false;
        }
    }

    /**
     * Procesar un paso específico (sin recursión)
     */
    protected function processSingleStep(AdvancedWorkflowExecution $execution, WorkflowStepDefinition $step): bool
    {
        $targetModel = $execution->getTargetModel();
        
        if (!$targetModel) {
            $execution->markAsFailed('Modelo objetivo no encontrado');
            return false;
        }

        // Este método asume que el paso ya fue validado para ejecutarse
        $context = $execution->getContext();

        // Obtener o crear ejecución del paso
        $stepExecution = $execution->getCurrentStepExecution();
        if (!$stepExecution) {
            $stepExecution = $execution->createCurrentStepExecution();
        }

        // Marcar como iniciado
        $stepExecution->markAsStarted();

        // Procesar según el tipo de paso
        $result = $this->executeStepByType($execution, $step, $stepExecution);

        if ($result) {
            // Si el paso no requiere intervención manual, completarlo automáticamente
            if (!$step->requiresManualIntervention()) {
                $stepExecution->markAsCompleted();
                $execution->markCurrentStepCompleted();
                $execution->advanceToNextStep(); // Solo avanzar, no procesar recursivamente
            }
        }

        return $result;
    }

    /**
     * Ejecutar paso según su tipo
     */
    protected function executeStepByType(
        AdvancedWorkflowExecution $execution,
        WorkflowStepDefinition $step,
        WorkflowStepExecutionAdvanced $stepExecution
    ): bool {
        return match ($step->step_type) {
            WorkflowStepDefinition::TYPE_NOTIFICATION => $this->executeNotificationStep($execution, $step, $stepExecution),
            WorkflowStepDefinition::TYPE_APPROVAL => $this->executeApprovalStep($execution, $step, $stepExecution),
            WorkflowStepDefinition::TYPE_ACTION => $this->executeActionStep($execution, $step, $stepExecution),
            WorkflowStepDefinition::TYPE_CONDITION => $this->executeConditionStep($execution, $step, $stepExecution),
            WorkflowStepDefinition::TYPE_WAIT => $this->executeWaitStep($execution, $step, $stepExecution),
            default => false,
        };
    }

    /**
     * Ejecutar paso de notificación
     */
    protected function executeNotificationStep(
        AdvancedWorkflowExecution $execution,
        WorkflowStepDefinition $step,
        WorkflowStepExecutionAdvanced $stepExecution
    ): bool {
        try {
            $targetModel = $execution->getTargetModel();
            $context = $execution->getContext();
            
            Log::info('🔔 Executing notification step', [
                'step_name' => $step->step_name,
                'step_config' => $step->step_config,
                'target_model' => get_class($targetModel),
                'model_id' => $targetModel->getKey()
            ]);
            
            $notificationsSent = 0;
            
            // MÉTODO 1: Master workflows con step_config (configuración simple)
            if (isset($step->step_config['email_template_key'])) {
                Log::info('📧 Using step_config email template method');
                
                $templateKey = $step->step_config['email_template_key'];
                $emailTemplate = \App\Models\EmailTemplate::where('key', $templateKey)
                    ->where('is_active', true)
                    ->first();
                    
                if (!$emailTemplate) {
                    Log::error('❌ Email template not found', ['template_key' => $templateKey]);
                    $stepExecution->markAsFailed("Template de email no encontrado: {$templateKey}");
                    return false;
                }
                
                // Obtener destinatarios según configuración
                $recipients = $this->getRecipientsFromStepConfig($step, $targetModel, $context);
                
                if (empty($recipients)) {
                    Log::warning('⚠️ No recipients found for step config notification');
                    $stepExecution->markAsFailed('No se encontraron destinatarios');
                    return false;
                }
                
                Log::info('👥 Recipients found', [
                    'count' => count($recipients),
                    'recipients' => $recipients
                ]);
                
                // Preparar variables del modelo para el template
                $templateVariables = $this->prepareModelVariables($targetModel, $context);
                
                Log::info('📋 Template variables prepared', [
                    'variables' => $templateVariables
                ]);
                
                // Procesar template con EmailTemplateService
                $processedTemplate = $this->emailTemplateService->processTemplate(
                    $templateKey, 
                    $templateVariables
                );
                
                Log::info('📝 Template processed', [
                    'subject' => $processedTemplate['subject'],
                    'from_email' => $processedTemplate['from_email']
                ]);
                
                // Enviar emails
                $this->sendEmails($recipients, $processedTemplate);
                $notificationsSent = count($recipients);
                
            } else {
                // MÉTODO 2: Workflows avanzados con relaciones de templates
                Log::info('🔗 Using template relations method');
                
                $templates = $step->templates;
                
                if ($templates->isEmpty()) {
                    Log::warning('⚠️ No template relations found');
                    $stepExecution->markAsFailed('No se encontraron templates de notificación');
                    return false;
                }

                foreach ($templates as $template) {
                    if (!$template->shouldSend($targetModel, $context)) {
                        continue;
                    }
                    
                    $recipients = $template->getRecipients($targetModel, $context);
                    
                    if (empty($recipients)) {
                        Log::warning('No recipients found for template', [
                            'template_id' => $template->id,
                            'step_id' => $step->id,
                        ]);
                        continue;
                    }
                    
                    // Preparar variables para el template
                    $variables = $this->prepareStepVariables($execution, $step, $template);
                    
                    // Enviar notificaciones
                    $this->sendNotifications($template, $recipients, $variables, $stepExecution);
                    $notificationsSent += count($recipients);
                }
            }

            if ($notificationsSent > 0) {
                $stepExecution->setOutputData('notifications_sent_count', $notificationsSent);
                Log::info('✅ Notification step completed successfully', [
                    'notifications_sent' => $notificationsSent
                ]);
                return true;
            } else {
                Log::warning('⚠️ No notifications were sent');
                $stepExecution->markAsFailed('No se pudieron enviar notificaciones');
                return false;
            }

        } catch (\Exception $e) {
            Log::error('❌ Error in notification step', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $stepExecution->markAsFailed('Error enviando notificaciones: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecutar paso de aprobación
     */
    protected function executeApprovalStep(
        AdvancedWorkflowExecution $execution,
        WorkflowStepDefinition $step,
        WorkflowStepExecutionAdvanced $stepExecution
    ): bool {
        try {
            $approvers = $step->getApprovers();
            
            if (empty($approvers)) {
                $stepExecution->markAsFailed('No se encontraron aprobadores configurados');
                return false;
            }

            // Resolver aprobadores dinámicos
            $resolvedApprovers = $this->resolveApprovers($approvers, $execution->getTargetModel());
            
            if (empty($resolvedApprovers)) {
                $stepExecution->markAsFailed('No se pudieron resolver los aprobadores');
                return false;
            }

            // Asignar primer aprobador (o todos según configuración)
            $firstApprover = $resolvedApprovers[0];
            $stepExecution->update(['assigned_to' => $firstApprover['id']]);
            
            // Establecer fecha límite si está configurada
            $stepExecution->setDueDate();
            
            // Enviar notificaciones de aprobación
            $this->sendApprovalNotifications($execution, $step, $stepExecution, $resolvedApprovers);
            
            return true;

        } catch (\Exception $e) {
            $stepExecution->markAsFailed('Error procesando aprobación: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecutar paso de acción
     */
    protected function executeActionStep(
        AdvancedWorkflowExecution $execution,
        WorkflowStepDefinition $step,
        WorkflowStepExecutionAdvanced $stepExecution
    ): bool {
        try {
            $actionConfig = $step->getActionConfig();
            $targetModel = $execution->getTargetModel();
            
            $result = $this->executeAction($targetModel, $actionConfig, $execution);
            
            if ($result) {
                $stepExecution->setOutputData('action_result', 'success');
                return true;
            } else {
                $stepExecution->markAsFailed('Error ejecutando la acción');
                return false;
            }

        } catch (\Exception $e) {
            $stepExecution->markAsFailed('Error en paso de acción: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecutar paso de condición
     */
    protected function executeConditionStep(
        AdvancedWorkflowExecution $execution,
        WorkflowStepDefinition $step,
        WorkflowStepExecutionAdvanced $stepExecution
    ): bool {
        // Los pasos de condición se evalúan en shouldExecute()
        // Si llegamos aquí es que la condición se cumplió
        $stepExecution->setOutputData('condition_result', 'passed');
        return true;
    }

    /**
     * Ejecutar paso de espera
     */
    protected function executeWaitStep(
        AdvancedWorkflowExecution $execution,
        WorkflowStepDefinition $step,
        WorkflowStepExecutionAdvanced $stepExecution
    ): bool {
        $waitConfig = $step->step_config['wait'] ?? [];
        $waitType = $waitConfig['type'] ?? 'time';
        
        switch ($waitType) {
            case 'time':
                $waitMinutes = $waitConfig['minutes'] ?? 60;
                $stepExecution->setDueDate(now()->addMinutes($waitMinutes));
                $stepExecution->setOutputData('wait_until', now()->addMinutes($waitMinutes)->toISOString());
                // Este paso se completará automáticamente cuando se procesen los timeouts
                return true;
                
            case 'condition':
                // Esperar hasta que se cumpla una condición específica
                $stepExecution->setOutputData('waiting_for_condition', $waitConfig['condition']);
                return true;
                
            case 'manual':
                // Esperar intervención manual
                $stepExecution->setOutputData('waiting_for_manual_trigger', true);
                return true;
                
            default:
                $stepExecution->markAsFailed('Tipo de espera no válido: ' . $waitType);
                return false;
        }
    }

    /**
     * Resolver aprobadores dinámicos
     */
    protected function resolveApprovers(array $approvers, ?Model $targetModel): array
    {
        $resolved = [];
        
        foreach ($approvers as $approver) {
            if ($approver['type'] === 'dynamic' && $targetModel) {
                $dynamicApprovers = $this->resolveDynamicApprovers($approver['config'], $targetModel);
                $resolved = array_merge($resolved, $dynamicApprovers);
            } else {
                $resolved[] = $approver;
            }
        }
        
        return array_unique($resolved, SORT_REGULAR);
    }

    /**
     * Resolver aprobadores dinámicos basados en el modelo
     */
    protected function resolveDynamicApprovers(array $config, Model $model): array
    {
        $approvers = [];
        $dynamicType = $config['type'] ?? '';
        
        switch ($dynamicType) {
            case 'creator_manager':
                if (method_exists($model, 'creator') && $model->creator) {
                    $creator = $model->creator;
                    if (method_exists($creator, 'manager') && $creator->manager) {
                        $approvers[] = [
                            'id' => $creator->manager->id,
                            'name' => $creator->manager->name,
                            'email' => $creator->manager->email,
                            'type' => 'dynamic'
                        ];
                    }
                }
                break;
                
            case 'department_head':
                if (method_exists($model, 'creator') && $model->creator) {
                    $creator = $model->creator;
                    if (method_exists($creator, 'department') && $creator->department) {
                        $department = $creator->department;
                        if (method_exists($department, 'head') && $department->head) {
                            $approvers[] = [
                                'id' => $department->head->id,
                                'name' => $department->head->name,
                                'email' => $department->head->email,
                                'type' => 'dynamic'
                            ];
                        }
                    }
                }
                break;
                
            case 'role_in_department':
                // Usuarios con rol específico en el departamento del creador
                $roleName = $config['role'] ?? '';
                if ($roleName && method_exists($model, 'creator') && $model->creator) {
                    $creator = $model->creator;
                    if (method_exists($creator, 'department') && $creator->department) {
                        $department = $creator->department;
                        $users = User::whereHas('roles', function ($query) use ($roleName) {
                                $query->where('name', $roleName);
                            })
                            ->whereHas('department', function ($query) use ($department) {
                                $query->where('id', $department->id);
                            })
                            ->get();
                            
                        foreach ($users as $user) {
                            $approvers[] = [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                                'type' => 'dynamic'
                            ];
                        }
                    }
                }
                break;
        }
        
        return $approvers;
    }

    /**
     * Enviar notificaciones de aprobación
     */
    protected function sendApprovalNotifications(
        AdvancedWorkflowExecution $execution,
        WorkflowStepDefinition $step,
        WorkflowStepExecutionAdvanced $stepExecution,
        array $approvers
    ): void {
        foreach ($step->templates as $template) {
            if ($template->recipient_type === WorkflowStepTemplate::RECIPIENT_TYPE_APPROVER) {
                $recipients = [];
                foreach ($approvers as $approver) {
                    $recipients[] = $approver['email'];
                }
                
                $variables = $this->prepareStepVariables($execution, $step, $template);
                $variables['approvers'] = $approvers;
                $variables['assigned_approver'] = $approvers[0] ?? null;
                
                $this->sendNotifications($template, $recipients, $variables, $stepExecution);
            }
        }
    }

    // Métodos eliminados para evitar recursión infinita

    /**
     * Preparar variables para el paso
     */
    protected function prepareStepVariables(
        AdvancedWorkflowExecution $execution,
        WorkflowStepDefinition $step,
        ?WorkflowStepTemplate $template = null
    ): array {
        $targetModel = $execution->getTargetModel();
        $workflow = $execution->workflow;
        
        $baseVariables = [
            // Variables del workflow
            'workflow_name' => $workflow->name,
            'workflow_description' => $workflow->description,
            'workflow_version' => $workflow->version,
            
            // Variables del paso
            'step_name' => $step->step_name,
            'step_description' => $step->description ?? '',
            'step_type' => $step->getTypeDescription(),
            'step_order' => $step->step_order,
            'current_step_order' => $execution->current_step_order,
            'total_steps' => $workflow->stepDefinitions()->active()->count(),
            
            // Variables de la ejecución
            'execution_id' => $execution->id,
            'execution_status' => $execution->getStatusDescription(),
            'execution_progress' => $execution->getProgress() . '%',
            'completed_steps' => $execution->getCompletedStepsCount(),
            
            // Variables del modelo objetivo
            'target_model' => class_basename($execution->target_model),
            'target_title' => $execution->getTargetTitle(),
            'target_id' => $execution->target_id,
            
            // Variables de contexto
            'trigger_event' => $execution->getContext('trigger_event'),
            'trigger_user_name' => $execution->getContext('trigger_user_name'),
            'triggered_at' => $execution->getContext('triggered_at'),
            
            // Variables del usuario iniciador
            'initiator_name' => $execution->initiator?->name ?? 'Sistema',
            'initiator_email' => $execution->initiator?->email ?? '',
        ];

        // Agregar variables específicas del modelo
        if ($targetModel) {
            $modelVariables = $this->getModelSpecificVariables($targetModel);
            $baseVariables = array_merge($baseVariables, $modelVariables);
        }

        // Procesar variables específicas del template
        if ($template) {
            $templateVariables = $template->processTemplateVariables($baseVariables);
            $baseVariables = array_merge($baseVariables, $templateVariables);
        }

        return $baseVariables;
    }

    /**
     * Obtener variables específicas del modelo
     */
    protected function getModelSpecificVariables(Model $model): array
    {
        $variables = [];
        $modelName = strtolower(class_basename($model));
        
        // Variables específicas para documentación
        if ($model instanceof \App\Models\Documentation) {
            $variables = array_merge($variables, [
                'document_title' => $model->title ?? '',
                'document_id' => $model->id,
                'document_status' => $model->getStatusDescription() ?? 'Desconocido',
                'document_url' => route('filament.admin.resources.documentations.edit', $model->id),
                'creator_name' => $model->creator?->name ?? 'Usuario',
                'created_date' => $model->created_at?->format('d/m/Y H:i') ?? '',
                'editor_name' => $model->lastEditor?->name ?? 'Sistema',
                'edited_date' => $model->last_edited_at?->format('d/m/Y H:i') ?? '',
            ]);
        }

        // Variables generales del modelo
        $fillableFields = $model->getFillable();
        foreach ($fillableFields as $field) {
            $value = $model->getAttribute($field);
            if (is_scalar($value) || is_null($value)) {
                $variables["{$modelName}_{$field}"] = (string) ($value ?? '');
            } elseif (is_array($value)) {
                // Convertir arrays a JSON string
                $variables["{$modelName}_{$field}"] = json_encode($value);
            }
        }

        return $variables;
    }

    /**
     * Ejecutar una acción
     */
    protected function executeAction(Model $targetModel, array $actionConfig, AdvancedWorkflowExecution $execution): bool
    {
        $actionType = $actionConfig['type'] ?? null;
        
        return match ($actionType) {
            'update_model' => $this->executeUpdateModelAction($targetModel, $actionConfig),
            'send_email' => $this->executeSendEmailAction($actionConfig, $execution),
            'create_record' => $this->executeCreateRecordAction($actionConfig),
            'call_method' => $this->executeCallMethodAction($targetModel, $actionConfig),
            default => $this->executeCustomAction($targetModel, $actionConfig, $execution),
        };
    }

    /**
     * Ejecutar acción de actualizar modelo
     */
    protected function executeUpdateModelAction(Model $targetModel, array $actionConfig): bool
    {
        try {
            $updates = $actionConfig['updates'] ?? [];
            
            if (empty($updates)) {
                return false;
            }
            
            $targetModel->update($updates);
            
            Log::info('Model updated by workflow action', [
                'model' => get_class($targetModel),
                'model_id' => $targetModel->getKey(),
                'updates' => $updates
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error updating model in workflow action', [
                'error' => $e->getMessage(),
                'model' => get_class($targetModel),
                'model_id' => $targetModel->getKey()
            ]);
            return false;
        }
    }

    /**
     * Ejecutar acción de enviar email
     */
    protected function executeSendEmailAction(array $actionConfig, AdvancedWorkflowExecution $execution): bool
    {
        try {
            $templateKey = $actionConfig['template_key'] ?? null;
            $recipients = $actionConfig['recipients'] ?? [];
            
            if (!$templateKey || empty($recipients)) {
                return false;
            }
            
            if (!$this->emailTemplateService->templateExists($templateKey)) {
                Log::warning("Email template not found for action: {$templateKey}");
                return false;
            }
            
            $variables = $actionConfig['variables'] ?? [];
            $processedTemplate = $this->emailTemplateService->processTemplate($templateKey, $variables);
            
            $this->sendEmails($recipients, $processedTemplate);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error sending email in workflow action', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Ejecutar acción de crear registro
     */
    protected function executeCreateRecordAction(array $actionConfig): bool
    {
        try {
            $modelClass = $actionConfig['model'] ?? null;
            $data = $actionConfig['data'] ?? [];
            
            if (!$modelClass || empty($data) || !class_exists($modelClass)) {
                return false;
            }
            
            $modelClass::create($data);
            
            Log::info('Record created by workflow action', [
                'model' => $modelClass,
                'data' => $data
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error creating record in workflow action', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Ejecutar acción de llamar método
     */
    protected function executeCallMethodAction(Model $targetModel, array $actionConfig): bool
    {
        try {
            $method = $actionConfig['method'] ?? null;
            $parameters = $actionConfig['parameters'] ?? [];
            
            if (!$method || !method_exists($targetModel, $method)) {
                return false;
            }
            
            $result = $targetModel->$method(...$parameters);
            
            Log::info('Method called by workflow action', [
                'model' => get_class($targetModel),
                'model_id' => $targetModel->getKey(),
                'method' => $method,
                'result' => $result
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error calling method in workflow action', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Ejecutar acción personalizada
     */
    protected function executeCustomAction(Model $targetModel, array $actionConfig, AdvancedWorkflowExecution $execution): bool
    {
        Log::info('Custom workflow action executed', [
            'action_config' => $actionConfig,
            'target_model' => get_class($targetModel),
            'execution_id' => $execution->id
        ]);
        
        return true;
    }

    /**
     * Enviar notificaciones usando templates
     */
    protected function sendNotifications(
        WorkflowStepTemplate $template,
        array $recipients,
        array $variables,
        WorkflowStepExecutionAdvanced $stepExecution
    ): void {
        try {
            if (!$this->emailTemplateService->templateExists($template->email_template_key)) {
                Log::warning("Email template not found: {$template->email_template_key}");
                return;
            }
            
            $processedTemplate = $this->emailTemplateService->processTemplate(
                $template->email_template_key,
                $variables
            );
            
            $this->sendEmails($recipients, $processedTemplate);
            
            // Registrar notificaciones enviadas
            foreach ($recipients as $recipient) {
                $stepExecution->addNotificationSent($recipient, $template->email_template_key, [
                    'template_id' => $template->id,
                    'variables_count' => count($variables)
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Error sending workflow notifications', [
                'template_id' => $template->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Enviar emails a destinatarios
     */
    protected function sendEmails(array $recipients, array $processedTemplate): void
    {
        Log::info('📧 sendEmails() called', [
            'recipients_count' => count($recipients),
            'recipients' => $recipients,
            'template_subject' => $processedTemplate['subject'] ?? 'NO_SUBJECT',
            'template_from' => $processedTemplate['from_email'] ?? 'NO_FROM_EMAIL',
        ]);

        if (empty($recipients)) {
            Log::warning('⚠️ No recipients to send emails to');
            return;
        }

        $content = $this->emailTemplateService->getWrappedContent($processedTemplate['content']);
        
        Log::info('📝 Email content prepared', [
            'content_length' => strlen($content),
            'wrapped_content_preview' => substr($content, 0, 200) . '...'
        ]);

        foreach ($recipients as $email) {
            Log::info("📤 Queueing email to: {$email}");
            
            try {
                // Debug: Log template values
                Log::info("🔍 Debug template values", [
                    'from_email' => $processedTemplate['from_email'] ?? 'NOT_SET',
                    'from_name' => $processedTemplate['from_name'] ?? 'NOT_SET',
                    'from_email_type' => gettype($processedTemplate['from_email'] ?? null),
                    'from_name_type' => gettype($processedTemplate['from_name'] ?? null),
                ]);
                
                $fromEmail = is_array($processedTemplate['from_email'] ?? null) ? 'noreply@saashelpdesk.test' : ($processedTemplate['from_email'] ?? 'noreply@saashelpdesk.test');
                $fromName = is_array($processedTemplate['from_name'] ?? null) ? 'Sistema' : ($processedTemplate['from_name'] ?? 'Sistema');
                
                // Temporal: Usar envío directo en lugar de queue para evitar errores de serialización
                Mail::send([], [], function ($message) use ($email, $processedTemplate, $content, $fromEmail, $fromName) {
                    $message->to($email)
                        ->subject($processedTemplate['subject'])
                        ->html($content)
                        ->from($fromEmail, $fromName);
                });
                
                // TODO: Investigar problema de serialización en WorkflowNotificationMail
                // $mailable = new \App\Mail\WorkflowNotificationMail(
                //     $processedTemplate['subject'],
                //     $content,
                //     $fromEmail,
                //     $fromName
                // );
                // Mail::to($email)->queue($mailable);
                
                Log::info("✅ Email queued successfully to: {$email}");
                
            } catch (\Exception $e) {
                Log::error('❌ Failed to queue workflow email', [
                    'recipient' => $email,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        Log::info('📧 sendEmails() completed');
    }

    /**
     * Aprobar una ejecución de workflow avanzado
     */
    public function approve(AdvancedWorkflowExecution $execution, ?string $comments = null): bool
    {
        if (!$execution->isInProgress()) {
            return false;
        }

        try {
            $currentStepExecution = $execution->getCurrentStepExecution();
            
            if (!$currentStepExecution || !$currentStepExecution->requiresUserAction()) {
                return false;
            }

            // Marcar paso como completado
            $currentStepExecution->markAsCompleted($comments, Auth::id());
            $execution->markCurrentStepCompleted($comments, Auth::id());

            Log::info('Advanced workflow step approved', [
                'execution_id' => $execution->id,
                'step_id' => $execution->current_step_id,
                'approver' => Auth::user()?->name,
                'comments' => $comments,
            ]);

            // Avanzar al siguiente paso
            return $this->advanceToNextStep($execution);

        } catch (\Exception $e) {
            Log::error('Error approving advanced workflow execution', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Rechazar una ejecución de workflow avanzado
     */
    public function reject(AdvancedWorkflowExecution $execution, string $reason): bool
    {
        if (!$execution->isInProgress()) {
            return false;
        }

        try {
            $currentStepExecution = $execution->getCurrentStepExecution();
            
            if ($currentStepExecution) {
                $currentStepExecution->markAsFailed($reason, Auth::id());
            }

            $execution->markAsFailed($reason);

            Log::info('Advanced workflow execution rejected', [
                'execution_id' => $execution->id,
                'approver' => Auth::user()?->name,
                'reason' => $reason,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error rejecting advanced workflow execution', [
                'execution_id' => $execution->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Procesar timeouts de pasos
     */
    public function processTimeouts(): int
    {
        $overdueSteps = WorkflowStepExecutionAdvanced::overdue()->get();
        $processed = 0;

        foreach ($overdueSteps as $stepExecution) {
            try {
                $step = $stepExecution->stepDefinition;
                
                if ($step->step_type === WorkflowStepDefinition::TYPE_WAIT) {
                    // Los pasos de espera se completan automáticamente
                    $stepExecution->markAsCompleted('Timeout alcanzado');
                    $stepExecution->workflowExecution->markCurrentStepCompleted();
                    $stepExecution->workflowExecution->advanceToNextStep();
                } else {
                    // Otros tipos de pasos fallan por timeout
                    $stepExecution->markAsFailed('Timeout alcanzado');
                    $stepExecution->workflowExecution->markAsFailed('Paso vencido por timeout');
                }
                
                $processed++;
                
            } catch (\Exception $e) {
                Log::error('Error processing timeout for step execution', [
                    'step_execution_id' => $stepExecution->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $processed;
    }
    
    /**
     * Obtener destinatarios desde la configuración del paso
     */
    protected function getRecipientsFromStepConfig(WorkflowStepDefinition $step, Model $targetModel, array $context): array
    {
        $stepConfig = $step->step_config ?? [];
        $recipientConfig = $stepConfig['recipient_config'] ?? [];
        
        Log::info('🎯 Getting recipients from step config', [
            'recipient_config' => $recipientConfig,
            'target_model' => get_class($targetModel)
        ]);
        
        $recipients = [];
        
        switch ($recipientConfig['type'] ?? 'static') {
            case 'dynamic':
                $dynamicType = $recipientConfig['dynamic_type'] ?? null;
                
                switch ($dynamicType) {
                    case 'creator':
                        // El usuario que creó el registro
                        if (method_exists($targetModel, 'user') && $targetModel->user) {
                            $recipients[] = $targetModel->user->email;
                            Log::info('👤 Found creator recipient', ['email' => $targetModel->user->email]);
                        } elseif (isset($targetModel->created_by)) {
                            $user = \App\Models\User::find($targetModel->created_by);
                            if ($user) {
                                $recipients[] = $user->email;
                                Log::info('👤 Found creator by created_by field', ['email' => $user->email]);
                            }
                        } else {
                            Log::warning('⚠️ Could not find creator for dynamic recipient');
                        }
                        break;
                        
                    case 'owner':
                        // El propietario actual del registro
                        if (method_exists($targetModel, 'owner') && $targetModel->owner) {
                            $recipients[] = $targetModel->owner->email;
                        } elseif (isset($targetModel->user_id)) {
                            $user = \App\Models\User::find($targetModel->user_id);
                            if ($user) {
                                $recipients[] = $user->email;
                            }
                        }
                        break;
                        
                    case 'assigned':
                        // Usuario asignado
                        if (isset($targetModel->assigned_to)) {
                            $user = \App\Models\User::find($targetModel->assigned_to);
                            if ($user) {
                                $recipients[] = $user->email;
                            }
                        }
                        break;
                        
                    default:
                        Log::warning('⚠️ Unknown dynamic recipient type', ['type' => $dynamicType]);
                }
                break;
                
            case 'static':
                // Emails estáticos configurados
                $staticEmails = $recipientConfig['emails'] ?? [];
                if (is_array($staticEmails)) {
                    $recipients = array_merge($recipients, $staticEmails);
                } elseif (is_string($staticEmails)) {
                    $recipients[] = $staticEmails;
                }
                break;
                
            case 'role':
                // Usuarios con un rol específico
                $roleName = $recipientConfig['role_name'] ?? null;
                if ($roleName) {
                    $users = \App\Models\User::role($roleName)->get();
                    foreach ($users as $user) {
                        $recipients[] = $user->email;
                    }
                }
                break;
                
            default:
                Log::warning('⚠️ Unknown recipient type', ['type' => $recipientConfig['type']]);
        }
        
        // Limpiar y validar emails
        $recipients = array_filter(array_unique($recipients), function($email) {
            return filter_var($email, FILTER_VALIDATE_EMAIL);
        });
        
        Log::info('📧 Final recipients list', [
            'count' => count($recipients),
            'recipients' => $recipients
        ]);
        
        return array_values($recipients);
    }
    
    /**
     * Preparar variables del modelo para usar en templates
     */
    protected function prepareModelVariables(Model $model, array $context = []): array
    {
        $variables = [];
        
        // Variables del modelo principal
        $modelClass = class_basename($model);
        $modelKey = strtolower($modelClass);
        
        // Crear también un alias más amigable para algunos modelos comunes
        $friendlyKeys = [
            'documentation' => 'document',
            'user' => 'user',
            'ticket' => 'ticket',
            'order' => 'order',
            'invoice' => 'invoice',
        ];
        
        $primaryKey = $friendlyKeys[$modelKey] ?? $modelKey;
        
        // Información básica del modelo
        $modelData = [
            'id' => $model->getKey(),
            'created_at' => $model->created_at,
            'updated_at' => $model->updated_at,
        ];
        
        // Agregar campos específicos del modelo
        $fillableFields = $model->getFillable();
        foreach ($fillableFields as $field) {
            if (isset($model->$field)) {
                $modelData[$field] = $model->$field;
            }
        }
        
        // Agregar campos adicionales importantes que pueden no estar en fillable
        $additionalFields = ['state', 'status'];
        foreach ($additionalFields as $field) {
            if (isset($model->$field)) {
                $value = $model->$field;
                
                // Si es un objeto State, extraer información útil
                if (is_object($value) && method_exists($value, '__toString')) {
                    // Priorizar descripción amigable sobre clase completa
                    if (method_exists($value, 'getDescription')) {
                        $modelData[$field] = $value->getDescription();
                        $modelData[$field . '_description'] = $value->getDescription();
                    } else {
                        $modelData[$field] = (string) $value;
                    }
                    
                    $modelData[$field . '_class'] = class_basename($value);
                    
                    // Agregar información adicional del state si está disponible
                    if (method_exists($value, 'getStateName')) {
                        $modelData[$field . '_name'] = $value->getStateName();
                    }
                } else {
                    $modelData[$field] = $value;
                }
            }
        }
        
        // Establecer tanto la clave original como la amigable
        $variables[$modelKey] = $modelData;
        if ($primaryKey !== $modelKey) {
            $variables[$primaryKey] = $modelData;
        }
        
        // Variables del usuario creador (si existe)
        if (isset($model->created_by)) {
            $creator = \App\Models\User::find($model->created_by);
            if ($creator) {
                $variables['user'] = [
                    'id' => $creator->id,
                    'name' => $creator->name,
                    'email' => $creator->email,
                ];
            }
        } elseif (method_exists($model, 'user') && $model->user) {
            $variables['user'] = [
                'id' => $model->user->id,
                'name' => $model->user->name,
                'email' => $model->user->email,
            ];
        }
        
        // Agregar contexto adicional
        if (!empty($context)) {
            $variables = array_merge($variables, $context);
        }
        
        Log::info('🔧 Model variables prepared', [
            'model_class' => get_class($model),
            'model_key' => $modelKey,
            'variables_keys' => array_keys($variables)
        ]);
        
        return $variables;
    }
}