<?php

namespace App\Services;

use App\Models\ApprovalState;
use App\Models\StateTransition;
use App\Models\User;
use App\Services\AdvancedWorkflowEngine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Spatie\ModelStates\Exceptions\InvalidConfig;
use Spatie\ModelStates\HasStates;

class StateTransitionService
{
    public function __construct(
        private AdvancedWorkflowEngine $workflowEngine
    ) {}

    /**
     * Obtener transiciones disponibles para un modelo
     */
    public function getAvailableTransitions(Model $model, ?User $user = null): array
    {
        $user = $user ?? Auth::user();
        
        if (!$this->modelHasStates($model)) {
            return [];
        }

        // Verificar si el modelo tiene estado
        if (!$model->state) {
            // Si no tiene estado, intentar inicializarlo
            $this->initializeModelState($model);
            $model->save();
            $model->refresh();
            
            if (!$model->state) {
                return [];
            }
        }

        // Obtener estado actual
        $currentStateName = $model->state->getStateName();
        
        // Buscar ApprovalState correspondiente
        $currentApprovalState = ApprovalState::where('model_type', get_class($model))
            ->where('name', $currentStateName)
            ->where('is_active', true)
            ->first();

        if (!$currentApprovalState) {
            return [];
        }

        // Obtener transiciones disponibles desde este estado
        $transitions = StateTransition::where('from_state_id', $currentApprovalState->id)
            ->where('is_active', true)
            ->with(['toState'])
            ->orderBy('sort_order')
            ->get();

        $availableTransitions = [];
        
        foreach ($transitions as $transition) {
            if ($this->canExecuteTransition($transition, $model, $user)) {
                $availableTransitions[] = [
                    'transition' => $transition,
                    'to_state' => $transition->toState,
                    'can_execute' => true,
                ];
            }
        }

        return $availableTransitions;
    }

    /**
     * Ejecutar una transición de estado
     */
    public function executeTransition(
        Model $model,
        StateTransition $transition,
        ?User $user = null,
        array $data = []
    ): bool {
        $user = $user ?? Auth::user();

        try {
            // Verificar si se puede ejecutar la transición
            if (!$this->canExecuteTransition($transition, $model, $user)) {
                Log::warning('Transition execution denied', [
                    'transition_id' => $transition->id,
                    'model' => get_class($model),
                    'model_id' => $model->getKey(),
                    'user_id' => $user?->id,
                ]);
                return false;
            }

            // Verificar condiciones
            if (!$transition->conditionsAreMet($model)) {
                Log::warning('Transition conditions not met', [
                    'transition_id' => $transition->id,
                    'model' => get_class($model),
                    'model_id' => $model->getKey(),
                ]);
                return false;
            }

            // Obtener estado de destino
            $toApprovalState = $transition->toState;
            $newStateClass = $this->getStateClassFromApprovalState($toApprovalState);

            if (!$newStateClass) {
                Log::error('Could not determine target state class', [
                    'transition_id' => $transition->id,
                    'to_state_name' => $toApprovalState->name,
                ]);
                return false;
            }

            // Ejecutar la transición
            $oldState = $model->state;
            $model->state = new $newStateClass($model);
            
            // También actualizar el campo status si existe para compatibilidad
            if (in_array('status', $model->getFillable())) {
                $model->status = $toApprovalState->name;
            }
            
            $model->save();

            // Registrar la transición
            $this->logTransition($model, $transition, $oldState, $model->state, $user, $data);

            // Disparar workflows si es necesario
            $this->triggerWorkflowsForTransition($model, $transition, $data);

            // Enviar notificaciones si está configurado
            $this->sendTransitionNotifications($model, $transition, $user, $data);

            Log::info('Transition executed successfully', [
                'transition_id' => $transition->id,
                'model' => get_class($model),
                'model_id' => $model->getKey(),
                'from_state' => $oldState->getStateName(),
                'to_state' => $model->state->getStateName(),
                'user_id' => $user?->id,
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error executing state transition', [
                'transition_id' => $transition->id,
                'model' => get_class($model),
                'model_id' => $model->getKey(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * Verificar si un usuario puede ejecutar una transición
     */
    public function canExecuteTransition(StateTransition $transition, Model $model, ?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Verificar si la transición puede ser ejecutada por el usuario
        if (!$transition->canBeExecutedBy($user)) {
            return false;
        }

        // Verificar si las condiciones se cumplen
        if (!$transition->conditionsAreMet($model)) {
            return false;
        }

        return true;
    }

    /**
     * Obtener clase de estado desde ApprovalState
     */
    private function getStateClassFromApprovalState(ApprovalState $approvalState): ?string
    {
        $modelType = $approvalState->model_type;
        $stateName = $approvalState->name;

        // Mapeo de nombres de estados a clases
        $stateMapping = [
            'App\\Models\\Documentation' => [
                'draft' => \App\States\DraftState::class,
                'pending_approval' => \App\States\PendingApprovalState::class,
                'approved' => \App\States\ApprovedState::class,
                'rejected' => \App\States\RejectedState::class,
                'published' => \App\States\PublishedState::class,
                'archived' => \App\States\ArchivedState::class,
            ],
        ];

        return $stateMapping[$modelType][$stateName] ?? null;
    }

    /**
     * Verificar si el modelo tiene estados
     */
    private function modelHasStates(Model $model): bool
    {
        return in_array(HasStates::class, class_uses_recursive($model));
    }

    /**
     * Registrar la transición ejecutada
     */
    private function logTransition(
        Model $model,
        StateTransition $transition,
        $oldState,
        $newState,
        ?User $user,
        array $data
    ): void {
        // Aquí puedes implementar logging de transiciones
        // Por ejemplo, usando Spatie Activity Log
        if (method_exists($model, 'activity')) {
            activity()
                ->performedOn($model)
                ->causedBy($user)
                ->withProperties([
                    'transition_id' => $transition->id,
                    'transition_name' => $transition->name,
                    'from_state' => $oldState->getStateName(),
                    'to_state' => $newState->getStateName(),
                    'data' => $data,
                ])
                ->log("Estado cambiado de '{$oldState->getStateName()}' a '{$newState->getStateName()}'");
        }
    }

    /**
     * Disparar workflows para la transición
     */
    private function triggerWorkflowsForTransition(
        Model $model,
        StateTransition $transition,
        array $data
    ): void {
        // Obtener estados para contexto
        $fromState = $transition->fromState;
        $toState = $transition->toState;
        
        // Agregar contexto unificado
        $context = array_merge($data, [
            'transition_id' => $transition->id,
            'transition_name' => $transition->name,
            'transition_label' => $transition->label,
            'from_state_id' => $transition->from_state_id,
            'to_state_id' => $transition->to_state_id,
            'from_state_name' => $fromState->name,
            'to_state_name' => $toState->name,
            'from_state_label' => $fromState->label,
            'to_state_label' => $toState->label,
        ]);

        // Disparar eventos unificados (reemplaza eventos redundantes)
        $this->workflowEngine->trigger($model, 'state_changed', $context);
        
        // Disparar evento específico de transición si es necesario
        $this->workflowEngine->trigger($model, "state_transition_{$transition->name}", $context);
        
        // Disparar eventos de estado específicos para compatibilidad
        $this->workflowEngine->trigger($model, "changed_to_state_{$toState->name}", $context);
    }

    /**
     * Enviar notificaciones para la transición
     */
    private function sendTransitionNotifications(
        Model $model,
        StateTransition $transition,
        ?User $user,
        array $data
    ): void {
        if (!$transition->notification_template) {
            return;
        }

        // Aquí podrías integrar con el sistema de email templates
        // para enviar notificaciones basadas en la transición
        Log::info('Transition notification would be sent', [
            'template' => $transition->notification_template,
            'model' => get_class($model),
            'model_id' => $model->getKey(),
            'transition' => $transition->name,
        ]);
    }

    /**
     * Obtener estado inicial para un modelo
     */
    public function getInitialState(string $modelType): ?ApprovalState
    {
        return ApprovalState::where('model_type', $modelType)
            ->where('is_initial', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Inicializar estado de un modelo nuevo
     */
    public function initializeModelState(Model $model): bool
    {
        if (!$this->modelHasStates($model)) {
            return false;
        }

        $initialState = $this->getInitialState(get_class($model));
        if (!$initialState) {
            return false;
        }

        $stateClass = $this->getStateClassFromApprovalState($initialState);
        if (!$stateClass) {
            return false;
        }

        $model->state = new $stateClass($model);
        
        // También actualizar el campo status si existe
        if (in_array('status', $model->getFillable()) || array_key_exists('status', $model->getAttributes())) {
            $model->status = $initialState->name;
        }

        return true;
    }
}