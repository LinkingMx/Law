<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModelVariableMapping extends Model
{
    // Tipos de datos soportados
    const DATA_TYPE_STRING = 'string';
    const DATA_TYPE_INTEGER = 'integer';
    const DATA_TYPE_BOOLEAN = 'boolean';
    const DATA_TYPE_DATE = 'date';
    const DATA_TYPE_DATETIME = 'datetime';
    const DATA_TYPE_ARRAY = 'array';
    const DATA_TYPE_OBJECT = 'object';

    // Categorías de variables
    const CATEGORY_CUSTOM = 'custom';
    const CATEGORY_COMPUTED = 'computed';
    const CATEGORY_RELATION = 'relation';
    const CATEGORY_AGGREGATED = 'aggregated';
    const CATEGORY_CONDITIONAL = 'conditional';

    // Tipos de mapeo
    const MAPPING_TYPE_FIELD = 'field';
    const MAPPING_TYPE_RELATION_FIELD = 'relation_field';
    const MAPPING_TYPE_METHOD = 'method';
    const MAPPING_TYPE_COMPUTED = 'computed';
    const MAPPING_TYPE_CONDITION = 'condition';

    protected $fillable = [
        'model_class',
        'variable_key',
        'variable_name',
        'description',
        'data_type',
        'category',
        'mapping_config',
        'is_active',
        'sort_order',
        'example_value',
    ];

    protected $casts = [
        'mapping_config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Resolver el valor de esta variable para un modelo específico
     */
    public function resolveValue(Model $model)
    {
        if (!$this->is_active) {
            return null;
        }

        $config = $this->mapping_config;
        $mappingType = $config['type'] ?? self::MAPPING_TYPE_FIELD;

        try {
            return match ($mappingType) {
                self::MAPPING_TYPE_FIELD => $this->resolveFieldValue($model, $config),
                self::MAPPING_TYPE_RELATION_FIELD => $this->resolveRelationFieldValue($model, $config),
                self::MAPPING_TYPE_METHOD => $this->resolveMethodValue($model, $config),
                self::MAPPING_TYPE_COMPUTED => $this->resolveComputedValue($model, $config),
                self::MAPPING_TYPE_CONDITION => $this->resolveConditionalValue($model, $config),
                default => null,
            };
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Error resolving variable {$this->variable_key}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Resolver valor de campo directo
     */
    protected function resolveFieldValue(Model $model, array $config)
    {
        $field = $config['field'] ?? null;
        if (!$field) {
            return null;
        }

        $value = $model->getAttribute($field);
        return $this->formatValue($value);
    }

    /**
     * Resolver valor de campo de relación
     */
    protected function resolveRelationFieldValue(Model $model, array $config)
    {
        $relation = $config['relation'] ?? null;
        $field = $config['field'] ?? null;

        if (!$relation || !$field) {
            return null;
        }

        // Soporte para relaciones anidadas (ej: creator.department.name)
        $relationParts = explode('.', $relation);
        $current = $model;

        foreach ($relationParts as $relationPart) {
            if (!method_exists($current, $relationPart)) {
                return null;
            }

            $current = $current->$relationPart;
            if (!$current) {
                return null;
            }
        }

        $value = $current->getAttribute($field);
        return $this->formatValue($value);
    }

    /**
     * Resolver valor de método
     */
    protected function resolveMethodValue(Model $model, array $config)
    {
        $method = $config['method'] ?? null;
        $parameters = $config['parameters'] ?? [];

        if (!$method || !method_exists($model, $method)) {
            return null;
        }

        $value = $model->$method(...$parameters);
        return $this->formatValue($value);
    }

    /**
     * Resolver valor computado
     */
    protected function resolveComputedValue(Model $model, array $config)
    {
        $computation = $config['computation'] ?? null;

        return match ($computation) {
            'count_relation' => $this->computeRelationCount($model, $config),
            'concat_fields' => $this->computeFieldConcatenation($model, $config),
            'conditional_value' => $this->computeConditionalValue($model, $config),
            'format_date' => $this->computeFormattedDate($model, $config),
            'calculate_age' => $this->computeAge($model, $config),
            default => null,
        };
    }

    /**
     * Resolver valor condicional
     */
    protected function resolveConditionalValue(Model $model, array $config)
    {
        $conditions = $config['conditions'] ?? [];
        $defaultValue = $config['default'] ?? null;

        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? null;
            $operator = $condition['operator'] ?? '=';
            $conditionValue = $condition['value'] ?? null;
            $returnValue = $condition['return'] ?? null;

            if (!$field) {
                continue;
            }

            $modelValue = $model->getAttribute($field);
            $conditionMet = match ($operator) {
                '=' => $modelValue == $conditionValue,
                '!=' => $modelValue != $conditionValue,
                '>' => $modelValue > $conditionValue,
                '<' => $modelValue < $conditionValue,
                'in' => in_array($modelValue, (array) $conditionValue),
                'not_null' => !is_null($modelValue),
                'is_null' => is_null($modelValue),
                default => false,
            };

            if ($conditionMet) {
                return $this->formatValue($returnValue);
            }
        }

        return $this->formatValue($defaultValue);
    }

    /**
     * Computar conteo de relación
     */
    protected function computeRelationCount(Model $model, array $config): int
    {
        $relation = $config['relation'] ?? null;
        if (!$relation || !method_exists($model, $relation)) {
            return 0;
        }

        return $model->$relation()->count();
    }

    /**
     * Computar concatenación de campos
     */
    protected function computeFieldConcatenation(Model $model, array $config): string
    {
        $fields = $config['fields'] ?? [];
        $separator = $config['separator'] ?? ' ';
        $values = [];

        foreach ($fields as $field) {
            $value = $model->getAttribute($field);
            if (!is_null($value) && $value !== '') {
                $values[] = $value;
            }
        }

        return implode($separator, $values);
    }

    /**
     * Computar valor condicional
     */
    protected function computeConditionalValue(Model $model, array $config)
    {
        // Similar a resolveConditionalValue pero para computaciones específicas
        return $this->resolveConditionalValue($model, $config);
    }

    /**
     * Computar fecha formateada
     */
    protected function computeFormattedDate(Model $model, array $config): ?string
    {
        $field = $config['field'] ?? null;
        $format = $config['format'] ?? 'd/m/Y H:i';

        if (!$field) {
            return null;
        }

        $date = $model->getAttribute($field);
        if (!$date) {
            return null;
        }

        try {
            if (is_string($date)) {
                $date = \Carbon\Carbon::parse($date);
            }

            return $date->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Computar edad
     */
    protected function computeAge(Model $model, array $config): ?int
    {
        $field = $config['field'] ?? null;
        if (!$field) {
            return null;
        }

        $date = $model->getAttribute($field);
        if (!$date) {
            return null;
        }

        try {
            if (is_string($date)) {
                $date = \Carbon\Carbon::parse($date);
            }

            return $date->diffInYears(now());
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Formatear valor según el tipo de dato
     */
    protected function formatValue($value)
    {
        if (is_null($value)) {
            return null;
        }

        return match ($this->data_type) {
            self::DATA_TYPE_STRING => (string) $value,
            self::DATA_TYPE_INTEGER => (int) $value,
            self::DATA_TYPE_BOOLEAN => (bool) $value,
            self::DATA_TYPE_DATE => $this->formatDateValue($value, 'Y-m-d'),
            self::DATA_TYPE_DATETIME => $this->formatDateValue($value, 'Y-m-d H:i:s'),
            self::DATA_TYPE_ARRAY => is_array($value) ? $value : [$value],
            self::DATA_TYPE_OBJECT => is_object($value) ? $value : (object) $value,
            default => $value,
        };
    }

    /**
     * Formatear valor de fecha
     */
    protected function formatDateValue($value, string $format): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            if (is_string($value)) {
                $value = \Carbon\Carbon::parse($value);
            }

            return $value->format($format);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Scope para variables activas
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
        return $query->where('model_class', $modelClass);
    }

    /**
     * Scope ordenado
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('variable_name');
    }

    /**
     * Obtener todas las variables activas para un modelo
     */
    public static function getVariablesForModel(string $modelClass): array
    {
        return static::forModel($modelClass)
            ->active()
            ->ordered()
            ->get()
            ->mapWithKeys(function ($mapping) {
                return [
                    $mapping->variable_key => [
                        'key' => $mapping->variable_key,
                        'name' => $mapping->variable_name,
                        'description' => $mapping->description,
                        'type' => $mapping->data_type,
                        'category' => $mapping->category,
                        'example' => $mapping->example_value,
                    ]
                ];
            })
            ->toArray();
    }

    /**
     * Resolver todas las variables para un modelo específico
     */
    public static function resolveAllForModel(Model $model): array
    {
        $mappings = static::forModel(get_class($model))
            ->active()
            ->get();

        $resolved = [];
        foreach ($mappings as $mapping) {
            $resolved[$mapping->variable_key] = $mapping->resolveValue($model);
        }

        return $resolved;
    }
}