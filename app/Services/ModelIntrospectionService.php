<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

class ModelIntrospectionService
{
    /**
     * Obtener información completa de un modelo
     */
    public function getModelInfo(string $modelClass): array
    {
        if (!class_exists($modelClass) || !is_subclass_of($modelClass, Model::class)) {
            throw new \InvalidArgumentException("La clase {$modelClass} no es un modelo Eloquent válido");
        }

        $model = new $modelClass;
        $reflection = new ReflectionClass($modelClass);
        $tableName = $model->getTable();

        return [
            'class' => $modelClass,
            'table' => $tableName,
            'name' => class_basename($modelClass),
            'fields' => $this->getModelFields($model),
            'relations' => $this->getModelRelations($reflection),
            'statuses' => $this->getModelStatuses($reflection),
            'scopes' => $this->getModelScopes($reflection),
            'casts' => $this->getModelCasts($model),
            'fillable' => $model->getFillable(),
            'dates' => $this->getDateFields($model),
            'user_fields' => $this->getUserFields($model),
            'available_variables' => $this->generateAvailableVariables($model, $reflection),
        ];
    }

    /**
     * Obtener todos los modelos disponibles en la aplicación
     */
    public function getAvailableModels(): array
    {
        $models = [];
        $modelPath = app_path('Models');
        
        if (!is_dir($modelPath)) {
            return $models;
        }

        $files = glob($modelPath . '/*.php');

        foreach ($files as $file) {
            $className = 'App\\Models\\' . pathinfo($file, PATHINFO_FILENAME);
            
            if (class_exists($className) && is_subclass_of($className, Model::class)) {
                try {
                    $model = new $className;
                    $models[] = [
                        'class' => $className,
                        'name' => class_basename($className),
                        'table' => $model->getTable(),
                        'display_name' => $this->getModelDisplayName($className),
                    ];
                } catch (\Exception $e) {
                    // Skip models that can't be instantiated
                    continue;
                }
            }
        }

        return collect($models)->sortBy('display_name')->values()->toArray();
    }

    /**
     * Obtener campos de la tabla del modelo
     */
    protected function getModelFields(Model $model): array
    {
        $tableName = $model->getTable();
        $columns = Schema::getColumns($tableName);
        
        return collect($columns)->map(function ($column) {
            return [
                'name' => $column['name'],
                'type' => $column['type_name'],
                'nullable' => $column['nullable'],
                'default' => $column['default'],
                'auto_increment' => $column['auto_increment'] ?? false,
            ];
        })->keyBy('name')->toArray();
    }

    /**
     * Detectar relaciones del modelo
     */
    protected function getModelRelations(ReflectionClass $reflection): array
    {
        $relations = [];
        $relationTypes = [
            'hasOne', 'hasMany', 'belongsTo', 'belongsToMany', 
            'morphTo', 'morphOne', 'morphMany', 'morphToMany'
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== $reflection->getName() || 
                $method->getNumberOfParameters() > 0 ||
                Str::startsWith($method->getName(), '_')) {
                continue;
            }

            try {
                $returnType = $method->getReturnType();
                if ($returnType && $returnType->getName()) {
                    $relationClass = $returnType->getName();
                    
                    // Check if it's an Eloquent relation
                    foreach ($relationTypes as $relationType) {
                        if (Str::contains($relationClass, $relationType)) {
                            $relations[$method->getName()] = [
                                'name' => $method->getName(),
                                'type' => $relationType,
                                'class' => $relationClass,
                            ];
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Skip methods that can't be analyzed
                continue;
            }
        }

        return $relations;
    }

    /**
     * Detectar constantes de estado en el modelo
     */
    protected function getModelStatuses(ReflectionClass $reflection): array
    {
        $statuses = [];
        $constants = $reflection->getConstants();

        foreach ($constants as $name => $value) {
            if (Str::startsWith($name, 'STATUS_')) {
                $statuses[strtolower(Str::after($name, 'STATUS_'))] = $value;
            }
        }

        return $statuses;
    }

    /**
     * Detectar scopes del modelo
     */
    protected function getModelScopes(ReflectionClass $reflection): array
    {
        $scopes = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (Str::startsWith($method->getName(), 'scope')) {
                $scopeName = Str::camel(Str::after($method->getName(), 'scope'));
                $scopes[] = $scopeName;
            }
        }

        return $scopes;
    }

    /**
     * Obtener campos cast del modelo
     */
    protected function getModelCasts(Model $model): array
    {
        return $model->getCasts();
    }

    /**
     * Detectar campos de fecha
     */
    protected function getDateFields(Model $model): array
    {
        $dates = [];
        $casts = $model->getCasts();
        
        foreach ($casts as $field => $cast) {
            if (in_array($cast, ['datetime', 'date', 'timestamp'])) {
                $dates[] = $field;
            }
        }

        // Add default Laravel timestamps
        if ($model->usesTimestamps()) {
            $dates[] = $model->getCreatedAtColumn();
            $dates[] = $model->getUpdatedAtColumn();
        }

        return array_unique($dates);
    }

    /**
     * Detectar campos de usuario (relaciones con User)
     */
    protected function getUserFields(Model $model): array
    {
        $userFields = [];
        $fields = $this->getModelFields($model);

        foreach ($fields as $field) {
            if (Str::endsWith($field['name'], ['_by', '_user_id']) || 
                Str::contains($field['name'], ['user', 'creator', 'editor', 'approver'])) {
                $userFields[] = $field['name'];
            }
        }

        return $userFields;
    }

    /**
     * Generar variables disponibles para templates con análisis profundo
     */
    protected function generateAvailableVariables(Model $model, ReflectionClass $reflection): array
    {
        $variables = [];
        $modelName = strtolower(class_basename($reflection->getName()));
        
        // Variables básicas del modelo
        $fields = $this->getModelFields($model);
        foreach ($fields as $field) {
            $fieldName = $field['name'];
            $variables["{$modelName}_{$fieldName}"] = [
                'key' => "{$modelName}_{$fieldName}",
                'description' => "Campo {$fieldName} del {$modelName}",
                'type' => $field['type'],
                'category' => 'model_field',
                'nullable' => $field['nullable'],
                'example' => $this->getFieldExample($field),
            ];
        }

        // Variables de relaciones profundas
        $relations = $this->getModelRelations($reflection);
        foreach ($relations as $relation) {
            $this->addRelationVariables($variables, $relation, $model);
        }

        // Variables de estado
        $statuses = $this->getModelStatuses($reflection);
        if (!empty($statuses)) {
            $variables["{$modelName}_status_description"] = [
                'key' => "{$modelName}_status_description",
                'description' => "Descripción legible del estado",
                'type' => 'string',
                'category' => 'model_status',
                'example' => 'Publicado, Borrador, etc.',
            ];
            
            $variables["{$modelName}_status_color"] = [
                'key' => "{$modelName}_status_color",
                'description' => "Color asociado al estado",
                'type' => 'string',
                'category' => 'model_status',
                'example' => 'success, warning, danger',
            ];
        }

        // Variables de fechas formateadas
        $dateFields = $this->getDateFields($model);
        foreach ($dateFields as $dateField) {
            $variables["{$dateField}_formatted"] = [
                'key' => "{$dateField}_formatted",
                'description' => "Fecha {$dateField} en formato d/m/Y H:i",
                'type' => 'string',
                'category' => 'date_formatted',
                'example' => '04/08/2025 15:30',
            ];
            
            $variables["{$dateField}_human"] = [
                'key' => "{$dateField}_human",
                'description' => "Fecha {$dateField} en formato humano",
                'type' => 'string',
                'category' => 'date_human',
                'example' => 'hace 2 horas, ayer, etc.',
            ];
        }

        // Variables de URL y navegación
        $variables["{$modelName}_url"] = [
            'key' => "{$modelName}_url",
            'description' => "URL para ver el {$modelName}",
            'type' => 'string', 
            'category' => 'url',
            'example' => 'https://app.com/admin/documentations/123',
        ];
        
        $variables["{$modelName}_edit_url"] = [
            'key' => "{$modelName}_edit_url",
            'description' => "URL para editar el {$modelName}",
            'type' => 'string',
            'category' => 'url',
            'example' => 'https://app.com/admin/documentations/123/edit',
        ];

        // Variables de contexto del workflow
        $this->addWorkflowContextVariables($variables);

        return $variables;
    }

    /**
     * Agregar variables de relaciones con análisis profundo
     */
    protected function addRelationVariables(array &$variables, array $relation, Model $model): void
    {
        $relationName = $relation['name'];
        $relationType = $relation['type'];
        
        // Para relaciones singulares (belongsTo, hasOne)
        if (Str::contains($relationType, ['belongsTo', 'hasOne'])) {
            $variables["{$relationName}_id"] = [
                'key' => "{$relationName}_id",
                'description' => "ID del {$relationName} relacionado",
                'type' => 'integer',
                'category' => 'relation_id',
                'example' => '123',
            ];
            
            $variables["{$relationName}_name"] = [
                'key' => "{$relationName}_name",
                'description' => "Nombre del {$relationName}",
                'type' => 'string',
                'category' => 'relation_field',
                'example' => 'Juan Pérez',
            ];
            
            // Si es relación con User, agregar campos específicos
            if ($this->isUserRelation($relation, $model)) {
                $this->addUserRelationVariables($variables, $relationName);
            }
            
            // Si es relación con otros modelos conocidos, agregar campos específicos
            $this->addKnownModelRelationVariables($variables, $relation, $relationName);
        }
        
        // Para relaciones múltiples (hasMany, belongsToMany)
        elseif (Str::contains($relationType, ['hasMany', 'belongsToMany'])) {
            $variables["{$relationName}_count"] = [
                'key' => "{$relationName}_count",
                'description' => "Número de {$relationName} relacionados",
                'type' => 'integer',
                'category' => 'relation_count',
                'example' => '5',
            ];
            
            $variables["{$relationName}_list"] = [
                'key' => "{$relationName}_list",
                'description' => "Lista de nombres de {$relationName}",
                'type' => 'string',
                'category' => 'relation_list',
                'example' => 'Item 1, Item 2, Item 3',
            ];
        }
    }

    /**
     * Verificar si una relación es con el modelo User
     */
    protected function isUserRelation(array $relation, Model $model): bool
    {
        try {
            if (!method_exists($model, $relation['name'])) {
                return false;
            }
            
            $relationInstance = $model->{$relation['name']}();
            $relatedModel = $relationInstance->getRelated();
            
            return $relatedModel instanceof \App\Models\User;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Agregar variables específicas para relaciones con User
     */
    protected function addUserRelationVariables(array &$variables, string $relationName): void
    {
        $userFields = ['email', 'phone', 'department', 'position', 'avatar_url'];
        
        foreach ($userFields as $field) {
            $variables["{$relationName}_{$field}"] = [
                'key' => "{$relationName}_{$field}",
                'description' => ucfirst($field) . " del {$relationName}",
                'type' => 'string',
                'category' => 'user_field',
                'example' => $this->getUserFieldExample($field),
            ];
        }
    }

    /**
     * Agregar variables para modelos conocidos del sistema
     */
    protected function addKnownModelRelationVariables(array &$variables, array $relation, string $relationName): void
    {
        // Mapeo de modelos conocidos y sus campos importantes
        $knownModels = [
            'Documentation' => ['title', 'status', 'description'],
            'Department' => ['name', 'code', 'manager_id'],
            'Role' => ['name', 'guard_name'],
            'Permission' => ['name', 'guard_name'],
        ];

        $relationClass = $relation['class'] ?? '';
        $modelName = class_basename($relationClass);
        
        if (isset($knownModels[$modelName])) {
            foreach ($knownModels[$modelName] as $field) {
                $variables["{$relationName}_{$field}"] = [
                    'key' => "{$relationName}_{$field}",
                    'description' => ucfirst($field) . " del {$relationName}",
                    'type' => 'string',
                    'category' => 'known_model_field',
                    'example' => $this->getKnownModelFieldExample($modelName, $field),
                ];
            }
        }
    }

    /**
     * Agregar variables de contexto del workflow
     */
    protected function addWorkflowContextVariables(array &$variables): void
    {
        $contextVariables = [
            'workflow_name' => 'Nombre del workflow que se está ejecutando',
            'workflow_step' => 'Nombre del paso actual',
            'workflow_step_order' => 'Número del paso actual',
            'workflow_progress' => 'Porcentaje de progreso del workflow',
            'trigger_event' => 'Evento que disparó el workflow (created, updated, etc.)',
            'trigger_user_name' => 'Nombre del usuario que disparó el workflow',
            'trigger_user_email' => 'Email del usuario que disparó el workflow',
            'trigger_date' => 'Fecha y hora cuando se disparó el workflow',
            'model_changes' => 'Resumen de cambios realizados al modelo',
            'previous_step_result' => 'Resultado del paso anterior',
            'execution_id' => 'ID único de la ejecución del workflow',
        ];

        foreach ($contextVariables as $key => $description) {
            $variables[$key] = [
                'key' => $key,
                'description' => $description,
                'type' => 'string',
                'category' => 'workflow_context',
                'example' => $this->getContextVariableExample($key),
            ];
        }
    }

    /**
     * Obtener ejemplo para un campo
     */
    protected function getFieldExample(array $field): string
    {
        return match($field['type']) {
            'varchar', 'text' => 'Texto de ejemplo',
            'integer', 'bigint' => '123',
            'boolean' => 'true/false',
            'datetime', 'timestamp' => '2025-08-04 15:30:00',
            'date' => '2025-08-04',
            'decimal', 'float' => '123.45',
            default => 'valor'
        };
    }

    /**
     * Obtener ejemplo para campo de usuario
     */
    protected function getUserFieldExample(string $field): string
    {
        return match($field) {
            'email' => 'usuario@ejemplo.com',
            'phone' => '+34 123 456 789',
            'department' => 'Desarrollo',
            'position' => 'Desarrollador Senior',
            'avatar_url' => 'https://ejemplo.com/avatar.jpg',
            default => 'valor'
        };
    }

    /**
     * Obtener ejemplo para campo de modelo conocido
     */
    protected function getKnownModelFieldExample(string $modelName, string $field): string
    {
        return match($modelName) {
            'Documentation' => match($field) {
                'title' => 'Manual de Usuario',
                'status' => 'published',
                'description' => 'Descripción del documento',
                default => 'valor'
            },
            'Department' => match($field) {
                'name' => 'Desarrollo',
                'code' => 'DEV',
                'manager_id' => '123',
                default => 'valor'
            },
            default => 'valor'
        };
    }

    /**
     * Obtener ejemplo para variable de contexto
     */
    protected function getContextVariableExample(string $key): string
    {
        return match($key) {
            'workflow_name' => 'Gestión de Documentos',
            'workflow_step' => 'Notificar Creación',
            'workflow_step_order' => '1',
            'workflow_progress' => '25%',
            'trigger_event' => 'created',
            'trigger_user_name' => 'Juan Pérez',
            'trigger_user_email' => 'juan@ejemplo.com',
            'trigger_date' => '04/08/2025 15:30',
            'model_changes' => 'title: Nuevo → Actualizado',
            'previous_step_result' => 'completed',
            'execution_id' => 'exec_123456',
            default => 'valor de ejemplo'
        };
    }

    /**
     * Obtener nombre de display del modelo
     */
    protected function getModelDisplayName(string $modelClass): string
    {
        $basename = class_basename($modelClass);
        
        // Convertir CamelCase a palabras
        $words = preg_split('/(?=[A-Z])/', $basename, -1, PREG_SPLIT_NO_EMPTY);
        
        return implode(' ', $words);
    }

    /**
     * Detectar cambios en campos específicos
     */
    public function detectFieldChanges(Model $model, array $watchedFields = []): array
    {
        $changes = [];
        
        if (empty($watchedFields)) {
            $watchedFields = array_keys($this->getModelFields($model));
        }

        foreach ($watchedFields as $field) {
            if ($model->wasChanged($field)) {
                $changes[$field] = [
                    'old' => $model->getOriginal($field),
                    'new' => $model->getAttribute($field),
                    'changed' => true,
                ];
            }
        }

        return $changes;
    }

    /**
     * Obtener destinatarios dinámicos basados en el modelo
     */
    public function getDynamicRecipients(Model $model, array $recipientRules): array
    {
        $recipients = [];

        foreach ($recipientRules as $rule) {
            switch ($rule['type']) {
                case 'field_user':
                    // Usuario en un campo específico (created_by, assigned_to, etc.)
                    if (isset($model->{$rule['field']})) {
                        $user = \App\Models\User::find($model->{$rule['field']});
                        if ($user) {
                            $recipients[] = $user->email;
                        }
                    }
                    break;

                case 'relation_user':
                    // Usuario a través de una relación (creator, assignedUser, etc.)
                    if (method_exists($model, $rule['relation'])) {
                        $relatedUser = $model->{$rule['relation']};
                        if ($relatedUser && isset($relatedUser->email)) {
                            $recipients[] = $relatedUser->email;
                        }
                    }
                    break;

                case 'role_based':
                    // Usuarios con un rol específico
                    $users = \App\Models\User::whereHas('roles', function ($query) use ($rule) {
                        $query->where('name', $rule['role']);
                    })->get();
                    foreach ($users as $user) {
                        $recipients[] = $user->email;
                    }
                    break;

                case 'conditional':
                    // Condición basada en valores del modelo
                    if ($this->evaluateCondition($model, $rule['condition'])) {
                        $recipients = array_merge($recipients, 
                            $this->getDynamicRecipients($model, $rule['then_recipients'])
                        );
                    }
                    break;
            }
        }

        return array_unique(array_filter($recipients));
    }

    /**
     * Evaluar condición sobre el modelo
     */
    protected function evaluateCondition(Model $model, array $condition): bool
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
            '>=' => $modelValue >= $value,
            '<=' => $modelValue <= $value,
            'in' => in_array($modelValue, (array) $value),
            'not_in' => !in_array($modelValue, (array) $value),
            'contains' => Str::contains($modelValue, $value),
            'starts_with' => Str::startsWith($modelValue, $value),
            'ends_with' => Str::endsWith($modelValue, $value),
            'changed' => $model->wasChanged($field),
            'changed_to' => $model->wasChanged($field) && $modelValue == $value,
            'changed_from' => $model->wasChanged($field) && $model->getOriginal($field) == $value,
            default => false,
        };
    }
}