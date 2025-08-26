<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStepDefinition extends Model
{
    // Tipos de pasos disponibles
    const TYPE_NOTIFICATION = 'notification';
    const TYPE_APPROVAL = 'approval';
    const TYPE_ACTION = 'action';
    const TYPE_CONDITION = 'condition';
    const TYPE_WAIT = 'wait';

    protected $fillable = [
        'advanced_workflow_id',
        'step_name',
        'description',
        'step_type',
        'step_order',
        'step_config',
        'conditions',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'step_config' => 'array',
        'conditions' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relación con el workflow principal
     */
    public function advancedWorkflow(): BelongsTo
    {
        return $this->belongsTo(AdvancedWorkflow::class);
    }

    /**
     * Relación con los templates de este paso
     */
    public function templates(): HasMany
    {
        return $this->hasMany(WorkflowStepTemplate::class);
    }

    /**
     * Relación con las ejecuciones de este paso
     */
    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowStepExecutionAdvanced::class, 'step_definition_id');
    }

    /**
     * Verificar si este paso debe ejecutarse según sus condiciones
     */
    public function shouldExecute(Model $model, array $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        // Si no hay condiciones específicas, siempre se ejecuta
        if (empty($this->conditions)) {
            return true;
        }

        return $this->evaluateConditions($model, $context);
    }

    /**
     * Evaluar las condiciones del paso
     */
    protected function evaluateConditions(Model $model, array $context = []): bool
    {
        $conditions = $this->conditions;

        // Evaluar condiciones de evento
        if (isset($conditions['trigger_events']) && !empty($conditions['trigger_events'])) {
            $currentEvent = $context['trigger_event'] ?? '';
            if (!in_array($currentEvent, $conditions['trigger_events'])) {
                return false;
            }
        }
        
        // Soporte para condición simple de evento (usado en master workflows)
        if (isset($conditions['event'])) {
            $currentEvent = $context['trigger_event'] ?? '';
            \Log::info('🔍 Evaluating event condition', [
                'step_name' => $this->step_name,
                'expected_event' => $conditions['event'],
                'current_event' => $currentEvent,
                'matches' => $currentEvent === $conditions['event']
            ]);
            if ($currentEvent !== $conditions['event']) {
                return false;
            }
        }

        // Evaluar condiciones de estado (nuevo sistema Spatie)
        if (isset($conditions['state_conditions'])) {
            if (!$this->evaluateStateConditions($model, $conditions['state_conditions'], $context)) {
                return false;
            }
        }

        // Evaluar condiciones de campo
        if (isset($conditions['field_conditions'])) {
            foreach ($conditions['field_conditions'] as $condition) {
                if (!$this->evaluateFieldCondition($model, $condition)) {
                    return false;
                }
            }
        }

        // Evaluar condiciones de contexto
        if (isset($conditions['context_conditions'])) {
            foreach ($conditions['context_conditions'] as $condition) {
                if (!$this->evaluateContextCondition($context, $condition)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Evaluar una condición de campo
     */
    protected function evaluateFieldCondition(Model $model, array $condition): bool
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'] ?? null;
        
        // Manejo especial para el campo de estado Spatie
        if ($field === 'state') {
            $modelValue = $model->state ? $model->state->getStateName() : null;
        } else {
            $modelValue = $model->getAttribute($field);
        }

        return match ($operator) {
            '=' => $modelValue == $value,
            '!=' => $modelValue != $value,
            '>' => $modelValue > $value,
            '<' => $modelValue < $value,
            '>=' => $modelValue >= $value,
            '<=' => $modelValue <= $value,
            'in' => in_array($modelValue, $this->parseListValue($value)),
            'not_in' => !in_array($modelValue, $this->parseListValue($value)),
            'contains' => str_contains((string) $modelValue, (string) $value),
            'starts_with' => str_starts_with((string) $modelValue, (string) $value),
            'ends_with' => str_ends_with((string) $modelValue, (string) $value),
            'changed' => $model->wasChanged($field),
            'changed_to' => $model->wasChanged($field) && $modelValue == $value,
            'changed_from' => $model->wasChanged($field) && $model->getOriginal($field) == $value,
            'exists' => !is_null($modelValue) && $modelValue !== '',
            'not_exists' => is_null($modelValue) || $modelValue === '',
            default => false,
        };
    }

    /**
     * Evaluar condiciones de estado (Spatie Model States)
     */
    protected function evaluateStateConditions(Model $model, array $stateConditions, array $context = []): bool
    {
        // Verificar estado origen (from_state)
        if (!empty($stateConditions['from_state'])) {
            $fromStateName = $context['from_state_name'] ?? null;
            if ($fromStateName !== $stateConditions['from_state']) {
                return false;
            }
        }
        
        // Verificar estado destino (to_state)
        if (!empty($stateConditions['to_state'])) {
            $toStateName = $context['to_state_name'] ?? null;
            if ($toStateName !== $stateConditions['to_state']) {
                return false;
            }
        }
        
        // Verificar transición específica
        if (!empty($stateConditions['transition_name'])) {
            $transitionName = $context['transition_name'] ?? null;
            if ($transitionName !== $stateConditions['transition_name']) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Evaluar una condición de contexto
     */
    protected function evaluateContextCondition(array $context, array $condition): bool
    {
        $contextKey = $condition['context_key'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        $contextValue = data_get($context, $contextKey);

        return match ($operator) {
            '=' => $contextValue == $value,
            '!=' => $contextValue != $value,
            'contains' => str_contains((string) $contextValue, (string) $value),
            'in' => in_array($contextValue, $this->parseListValue($value)),
            default => false,
        };
    }

    /**
     * Parsear valor de lista (separado por comas)
     */
    protected function parseListValue(?string $value): array
    {
        if (empty($value)) {
            return [];
        }

        return array_map('trim', explode(',', $value));
    }

    /**
     * Evaluar una condición específica (método legacy para compatibilidad)
     */
    protected function evaluateCondition(Model $model, array $condition, array $context = []): bool
    {
        return $this->evaluateFieldCondition($model, $condition);
    }

    /**
     * Obtener la descripción del tipo de paso
     */
    public function getTypeDescription(): string
    {
        return match ($this->step_type) {
            self::TYPE_NOTIFICATION => 'Notificación',
            self::TYPE_APPROVAL => 'Aprobación',
            self::TYPE_ACTION => 'Acción',
            self::TYPE_CONDITION => 'Condición',
            self::TYPE_WAIT => 'Espera',
            default => 'Desconocido',
        };
    }

    /**
     * Verificar si este paso requiere intervención manual (aprobación)
     */
    public function requiresManualIntervention(): bool
    {
        return $this->step_type === self::TYPE_APPROVAL;
    }

    /**
     * Obtener configuración de aprobadores para este paso
     */
    public function getApproverConfig(): array
    {
        if ($this->step_type !== self::TYPE_APPROVAL) {
            return [];
        }

        return $this->step_config['approvers'] ?? [];
    }

    /**
     * Obtener aprobadores disponibles
     */
    public function getApprovers(): array
    {
        $config = $this->getApproverConfig();
        $approvers = [];

        // Aprobadores por rol
        if (isset($config['roles'])) {
            $users = User::whereHas('roles', function ($query) use ($config) {
                $query->whereIn('id', $config['roles']);
            })->get();
            foreach ($users as $user) {
                $approvers[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => 'role'
                ];
            }
        }

        // Aprobadores específicos
        if (isset($config['users'])) {
            $users = User::whereIn('id', $config['users'])->get();
            foreach ($users as $user) {
                $approvers[] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'type' => 'user'
                ];
            }
        }

        // Aprobadores dinámicos basados en el modelo
        if (isset($config['dynamic'])) {
            // Esto se resuelve en tiempo de ejecución
            $approvers[] = [
                'type' => 'dynamic',
                'config' => $config['dynamic']
            ];
        }

        return $approvers;
    }

    /**
     * Obtener timeout para este paso si está configurado
     */
    public function getTimeout(): ?int
    {
        return $this->step_config['timeout_hours'] ?? null;
    }

    /**
     * Obtener configuración de notificaciones
     */
    public function getNotificationConfig(): array
    {
        return $this->step_config['notifications'] ?? [];
    }

    /**
     * Obtener configuración de acciones
     */
    public function getActionConfig(): array
    {
        if ($this->step_type !== self::TYPE_ACTION) {
            return [];
        }

        return $this->step_config['action'] ?? [];
    }

    /**
     * Scope para pasos activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar por orden de paso
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('step_order');
    }

    /**
     * Scope para un tipo específico de paso
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('step_type', $type);
    }

    /**
     * Obtener el siguiente paso en el workflow
     */
    public function getNextStep(): ?WorkflowStepDefinition
    {
        return $this->advancedWorkflow
            ->stepDefinitions()
            ->where('step_order', '>', $this->step_order)
            ->active()
            ->ordered()
            ->first();
    }

    /**
     * Obtener el paso anterior en el workflow
     */
    public function getPreviousStep(): ?WorkflowStepDefinition
    {
        return $this->advancedWorkflow
            ->stepDefinitions()
            ->where('step_order', '<', $this->step_order)
            ->active()
            ->ordered()
            ->orderByDesc('step_order')
            ->first();
    }

    /**
     * Verificar si es el primer paso del workflow
     */
    public function isFirstStep(): bool
    {
        return $this->advancedWorkflow
            ->stepDefinitions()
            ->active()
            ->where('step_order', '<', $this->step_order)
            ->doesntExist();
    }

    /**
     * Verificar si es el último paso del workflow
     */
    public function isLastStep(): bool
    {
        return $this->advancedWorkflow
            ->stepDefinitions()
            ->active()
            ->where('step_order', '>', $this->step_order)
            ->doesntExist();
    }

    /**
     * Obtener variables disponibles para este paso
     */
    public function getAvailableVariables(): array
    {
        $baseVariables = [
            'workflow_name' => 'Nombre del workflow',
            'step_name' => 'Nombre del paso',
            'step_order' => 'Número del paso',
            'step_type' => 'Tipo del paso',
            'execution_id' => 'ID de la ejecución',
            'target_model' => 'Tipo de modelo',
            'target_id' => 'ID del registro',
        ];

        // Variables específicas según el tipo de paso
        $typeVariables = match ($this->step_type) {
            self::TYPE_APPROVAL => [
                'approver_name' => 'Nombre del aprobador',
                'approval_deadline' => 'Fecha límite de aprobación',
                'approval_url' => 'URL para aprobar',
            ],
            self::TYPE_NOTIFICATION => [
                'notification_reason' => 'Motivo de la notificación',
                'notification_priority' => 'Prioridad de la notificación',
            ],
            self::TYPE_ACTION => [
                'action_type' => 'Tipo de acción',
                'action_result' => 'Resultado de la acción',
            ],
            default => [],
        };

        return array_merge($baseVariables, $typeVariables);
    }
}