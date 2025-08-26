<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepTemplate extends Model
{
    // Tipos de destinatarios
    const RECIPIENT_TYPE_CREATOR = 'creator';
    const RECIPIENT_TYPE_APPROVER = 'approver';
    const RECIPIENT_TYPE_ROLE = 'role';
    const RECIPIENT_TYPE_USER = 'user';
    const RECIPIENT_TYPE_CONDITIONAL = 'conditional';
    const RECIPIENT_TYPE_DYNAMIC = 'dynamic';
    const RECIPIENT_TYPE_EMAIL = 'email';

    protected $fillable = [
        'workflow_step_definition_id',
        'recipient_type',
        'recipient_config',
        'email_template_key',
        'template_variables',
    ];

    protected $casts = [
        'recipient_config' => 'array',
        'template_variables' => 'array',
    ];

    /**
     * Relación con la definición del paso
     */
    public function stepDefinition(): BelongsTo
    {
        return $this->belongsTo(WorkflowStepDefinition::class, 'workflow_step_definition_id');
    }

    /**
     * Obtener destinatarios para este template
     */
    public function getRecipients(Model $model, array $context = []): array
    {
        return match ($this->recipient_type) {
            self::RECIPIENT_TYPE_CREATOR => $this->getCreatorRecipients($model),
            self::RECIPIENT_TYPE_APPROVER => $this->getApproverRecipients($context),
            self::RECIPIENT_TYPE_ROLE => $this->getRoleRecipients(),
            self::RECIPIENT_TYPE_USER => $this->getUserRecipients(),
            self::RECIPIENT_TYPE_CONDITIONAL => $this->getConditionalRecipients($model, $context),
            self::RECIPIENT_TYPE_DYNAMIC => $this->getDynamicRecipients($model),
            self::RECIPIENT_TYPE_EMAIL => $this->getDirectEmailRecipients(),
            default => [],
        };
    }

    /**
     * Obtener email del creador del modelo
     */
    protected function getCreatorRecipients(Model $model): array
    {
        $recipients = [];

        // Intentar obtener creador por relación
        if (method_exists($model, 'creator') && $model->creator) {
            $recipients[] = $model->creator->email;
        }
        // Intentar obtener por campo created_by
        elseif (isset($model->created_by)) {
            $creator = User::find($model->created_by);
            if ($creator) {
                $recipients[] = $creator->email;
            }
        }

        return array_filter($recipients);
    }

    /**
     * Obtener emails de aprobadores desde el contexto
     */
    protected function getApproverRecipients(array $context): array
    {
        $recipients = [];
        $config = $this->recipient_config;

        // Si hay aprobador actual en el contexto
        if (isset($context['current_approver_id'])) {
            $approver = User::find($context['current_approver_id']);
            if ($approver) {
                $recipients[] = $approver->email;
            }
        }

        // Si se especifican aprobadores en la configuración
        if (isset($config['approver_ids'])) {
            $approvers = User::whereIn('id', $config['approver_ids'])->get();
            foreach ($approvers as $approver) {
                $recipients[] = $approver->email;
            }
        }

        return array_filter($recipients);
    }

    /**
     * Obtener emails de usuarios con roles específicos
     */
    protected function getRoleRecipients(): array
    {
        $config = $this->recipient_config;
        $recipients = [];

        // Soporte para role_ids (legacy)
        if (isset($config['role_ids'])) {
            $users = User::whereHas('roles', function ($query) use ($config) {
                $query->whereIn('id', $config['role_ids']);
            })->get();

            foreach ($users as $user) {
                $recipients[] = $user->email;
            }
        }

        // Soporte para role_names (nuevo)
        if (isset($config['role_names'])) {
            $users = User::whereHas('roles', function ($query) use ($config) {
                $query->whereIn('name', $config['role_names']);
            })->get();

            foreach ($users as $user) {
                $recipients[] = $user->email;
            }
        }

        return array_filter($recipients);
    }

    /**
     * Obtener emails de usuarios específicos
     */
    protected function getUserRecipients(): array
    {
        $config = $this->recipient_config;
        $recipients = [];

        if (isset($config['user_ids'])) {
            $users = User::whereIn('id', $config['user_ids'])->get();
            foreach ($users as $user) {
                $recipients[] = $user->email;
            }
        }

        return array_filter($recipients);
    }

    /**
     * Obtener emails según condiciones
     */
    protected function getConditionalRecipients(Model $model, array $context): array
    {
        $config = $this->recipient_config;
        $recipients = [];

        if (!isset($config['condition']) || !isset($config['then_recipients'])) {
            return $recipients;
        }

        // Evaluar condición
        $condition = $config['condition'];
        $field = $condition['field'];
        $operator = $condition['operator'];
        $value = $condition['value'];
        $modelValue = $model->getAttribute($field);

        $conditionMet = match ($operator) {
            '=' => $modelValue == $value,
            '!=' => $modelValue != $value,
            '>' => $modelValue > $value,
            '<' => $modelValue < $value,
            'in' => in_array($modelValue, (array) $value),
            'not_in' => !in_array($modelValue, (array) $value),
            default => false,
        };

        if ($conditionMet) {
            // Obtener destinatarios si la condición se cumple
            $thenRecipients = $config['then_recipients'];
            
            if ($thenRecipients['type'] === 'users') {
                $users = User::whereIn('id', $thenRecipients['user_ids'])->get();
                foreach ($users as $user) {
                    $recipients[] = $user->email;
                }
            } elseif ($thenRecipients['type'] === 'roles') {
                $users = User::whereHas('roles', function ($query) use ($thenRecipients) {
                    $query->whereIn('id', $thenRecipients['role_ids']);
                })->get();
                foreach ($users as $user) {
                    $recipients[] = $user->email;
                }
            }
        }

        return array_filter($recipients);
    }

    /**
     * Obtener emails de forma dinámica según el modelo
     */
    protected function getDynamicRecipients(Model $model): array
    {
        $config = $this->recipient_config;
        $recipients = [];

        if (!isset($config['dynamic_type'])) {
            return $recipients;
        }

        switch ($config['dynamic_type']) {
            case 'last_editor':
                if (method_exists($model, 'lastEditor') && $model->lastEditor) {
                    $recipients[] = $model->lastEditor->email;
                } elseif (isset($model->last_edited_by)) {
                    $editor = User::find($model->last_edited_by);
                    if ($editor) {
                        $recipients[] = $editor->email;
                    }
                }
                break;

            case 'assigned_user':
                if (isset($model->assigned_to)) {
                    $assignedUser = User::find($model->assigned_to);
                    if ($assignedUser) {
                        $recipients[] = $assignedUser->email;
                    }
                }
                break;

            case 'manager':
                // Manager del creador
                if (method_exists($model, 'creator') && $model->creator) {
                    $creator = $model->creator;
                    if (method_exists($creator, 'manager') && $creator->manager) {
                        $recipients[] = $creator->manager->email;
                    }
                }
                break;

            case 'department_head':
                // Jefe del departamento del creador
                if (method_exists($model, 'creator') && $model->creator) {
                    $creator = $model->creator;
                    if (method_exists($creator, 'department') && $creator->department) {
                        $department = $creator->department;
                        if (method_exists($department, 'head') && $department->head) {
                            $recipients[] = $department->head->email;
                        }
                    }
                }
                break;

            case 'field_value':
                // Email contenido en un campo del modelo
                if (isset($config['field_name'])) {
                    $fieldValue = $model->getAttribute($config['field_name']);
                    if ($fieldValue && filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                        $recipients[] = $fieldValue;
                    }
                }
                break;
        }

        return array_filter($recipients);
    }

    /**
     * Obtener emails directos de la configuración
     */
    protected function getDirectEmailRecipients(): array
    {
        $config = $this->recipient_config;
        
        if (isset($config['emails']) && is_array($config['emails'])) {
            return array_filter($config['emails'], function ($email) {
                return filter_var($email, FILTER_VALIDATE_EMAIL);
            });
        }

        return [];
    }

    /**
     * Procesar variables específicas para este template
     */
    public function processTemplateVariables(array $baseVariables = []): array
    {
        $templateVars = $this->template_variables ?? [];
        
        // Combinar variables base con variables específicas del template
        return array_merge($baseVariables, $templateVars);
    }

    /**
     * Obtener descripción del tipo de destinatario
     */
    public function getRecipientTypeDescription(): string
    {
        return match ($this->recipient_type) {
            self::RECIPIENT_TYPE_CREATOR => 'Creador del registro',
            self::RECIPIENT_TYPE_APPROVER => 'Aprobador asignado',
            self::RECIPIENT_TYPE_ROLE => 'Usuarios con rol específico',
            self::RECIPIENT_TYPE_USER => 'Usuarios específicos',
            self::RECIPIENT_TYPE_CONDITIONAL => 'Destinatarios condicionales',
            self::RECIPIENT_TYPE_DYNAMIC => 'Destinatarios dinámicos',
            self::RECIPIENT_TYPE_EMAIL => 'Emails directos',
            default => 'Tipo desconocido',
        };
    }

    /**
     * Verificar si este template debe enviarse según el contexto
     */
    public function shouldSend(Model $model, array $context = []): bool
    {
        // Si hay condiciones específicas del template
        if (isset($this->template_variables['send_conditions'])) {
            $sendConditions = $this->template_variables['send_conditions'];
            
            // Verificar eventos de disparo
            if (isset($sendConditions['trigger_events'])) {
                $currentEvent = $context['trigger_event'] ?? '';
                if (!in_array($currentEvent, $sendConditions['trigger_events'])) {
                    return false;
                }
            }
            
            // Verificar condiciones de campo si existen
            if (isset($sendConditions['field_conditions'])) {
                foreach ($sendConditions['field_conditions'] as $condition) {
                    if (!isset($condition['field']) || !isset($condition['operator'])) {
                        continue;
                    }
                    
                    $field = $condition['field'];
                    $operator = $condition['operator'];
                    $value = $condition['value'] ?? null;
                    $modelValue = $model->getAttribute($field);

                    $conditionMet = match ($operator) {
                        '=' => $modelValue == $value,
                        '!=' => $modelValue != $value,
                        '>' => $modelValue > $value,
                        '<' => $modelValue < $value,
                        'changed' => $model->wasChanged($field),
                        'exists' => !is_null($modelValue) && $modelValue !== '',
                        'not_exists' => is_null($modelValue) || $modelValue === '',
                        default => false,
                    };

                    if (!$conditionMet) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
}