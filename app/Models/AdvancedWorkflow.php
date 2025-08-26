<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvancedWorkflow extends Model
{
    protected $fillable = [
        'name',
        'description', 
        'target_model',
        'trigger_conditions',
        'is_active',
        'is_master_workflow',
        'version',
        'global_variables',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'global_variables' => 'array',
        'is_active' => 'boolean',
        'is_master_workflow' => 'boolean',
    ];

    /**
     * Boot method - Asegurar que todos los workflows sean master
     */
    protected static function boot()
    {
        parent::boot();

        // Al crear, siempre establecer como master workflow
        static::creating(function ($model) {
            $model->is_master_workflow = true;
        });

        // Al actualizar, siempre mantener como master workflow
        static::updating(function ($model) {
            $model->is_master_workflow = true;
        });
    }

    /**
     * Relación con las definiciones de pasos
     */
    public function stepDefinitions(): HasMany
    {
        return $this->hasMany(WorkflowStepDefinition::class)
                    ->orderBy('step_order');
    }

    /**
     * Relación con las ejecuciones
     */
    public function executions(): HasMany
    {
        return $this->hasMany(AdvancedWorkflowExecution::class);
    }

    /**
     * Obtener pasos activos ordenados
     */
    public function getActiveSteps()
    {
        return $this->stepDefinitions()
                    ->where('is_active', true)
                    ->orderBy('step_order')
                    ->get();
    }

    /**
     * Verificar si el workflow debe ejecutarse para un modelo
     */
    public function shouldTrigger(Model $model, string $event, array $context = []): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->target_model !== get_class($model)) {
            return false;
        }

        return $this->evaluateTriggerConditions($model, $event, $context);
    }

    /**
     * Evaluar condiciones de disparo
     */
    protected function evaluateTriggerConditions(Model $model, string $event, array $context = []): bool
    {
        $conditions = $this->trigger_conditions;

        // Si no hay condiciones, siempre se dispara
        if (empty($conditions)) {
            return true;
        }

        // Verificar evento
        if (isset($conditions['events']) && !in_array($event, $conditions['events'])) {
            return false;
        }

        // Verificar condiciones de campo
        if (isset($conditions['field_conditions'])) {
            foreach ($conditions['field_conditions'] as $condition) {
                if (!$this->evaluateFieldCondition($model, $condition)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Evaluar condición de campo
     */
    protected function evaluateFieldCondition(Model $model, array $condition): bool
    {
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        $modelValue = $model->getAttribute($field);

        return match ($operator) {
            '=' => $modelValue == $value,
            '!=' => $modelValue != $value,
            '>' => $modelValue > $value,
            '<' => $modelValue < $value,
            'in' => in_array($modelValue, (array) $value),
            'not_in' => !in_array($modelValue, (array) $value),
            'changed' => $model->wasChanged($field),
            'changed_to' => $model->wasChanged($field) && $modelValue == $value,
            'changed_from' => $model->wasChanged($field) && $model->getOriginal($field) == $value,
            default => false,
        };
    }

    /**
     * Scope para workflows activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para un modelo específico
     */
    public function scopeForModel($query, string $modelClass)
    {
        return $query->where('target_model', $modelClass);
    }

    /**
     * Obtener workflows que deben ejecutarse para el evento (método legacy)
     */
    public static function getTriggeredWorkflows(Model $model, string $event, array $context = []): array
    {
        return static::active()
                    ->forModel(get_class($model))
                    ->get()
                    ->filter(function ($workflow) use ($model, $event, $context) {
                        return $workflow->shouldTrigger($model, $event, $context);
                    })
                    ->values()
                    ->all();
    }
    
    /**
     * Obtener el Master Workflow para un modelo específico
     */
    public static function getMasterWorkflowForModel(string $modelClass): ?AdvancedWorkflow
    {
        return static::active()
                    ->forModel($modelClass)
                    ->where('is_master_workflow', true)
                    ->first();
    }
    
    /**
     * Verificar si este es un Master Workflow
     */
    public function isMasterWorkflow(): bool
    {
        return $this->is_master_workflow ?? false;
    }

    /**
     * Obtener el total de pasos activos en este workflow
     */
    public function getTotalSteps(): int
    {
        return $this->stepDefinitions()->active()->count();
    }
    
    /**
     * Scope para workflows maestros
     */
    public function scopeMaster($query)
    {
        return $query->where('is_master_workflow', true);
    }
    
    /**
     * Scope para workflows no maestros (legacy)
     */
    public function scopeLegacy($query)
    {
        return $query->where('is_master_workflow', false)->orWhereNull('is_master_workflow');
    }
}