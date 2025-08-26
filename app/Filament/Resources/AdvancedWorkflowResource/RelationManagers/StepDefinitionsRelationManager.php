<?php

namespace App\Filament\Resources\AdvancedWorkflowResource\RelationManagers;

use App\Models\WorkflowStepDefinition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class StepDefinitionsRelationManager extends RelationManager
{
    protected static string $relationship = 'stepDefinitions';
    
    protected static ?string $title = 'Pasos del Workflow';
    
    protected static ?string $modelLabel = 'Paso';
    
    protected static ?string $pluralModelLabel = 'Pasos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Paso')
                    ->schema([
                        Forms\Components\TextInput::make('step_name')
                            ->label('Nombre del Paso')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(2),
                            
                        Forms\Components\Select::make('step_type')
                            ->label('Tipo de Paso')
                            ->required()
                            ->options([
                                WorkflowStepDefinition::TYPE_NOTIFICATION => 'Notificación',
                                WorkflowStepDefinition::TYPE_APPROVAL => 'Aprobación',
                                WorkflowStepDefinition::TYPE_ACTION => 'Acción',
                                WorkflowStepDefinition::TYPE_CONDITION => 'Condición',
                                WorkflowStepDefinition::TYPE_WAIT => 'Espera',
                            ])
                            ->reactive(),
                            
                        Forms\Components\TextInput::make('step_order')
                            ->label('Orden')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(function () {
                                $maxOrder = $this->getOwnerRecord()
                                    ->stepDefinitions()
                                    ->max('step_order');
                                return ($maxOrder ?? 0) + 1;
                            }),
                            
                        Forms\Components\Toggle::make('is_required')
                            ->label('Requerido')
                            ->default(true),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),
                    
                // Configuración específica según el tipo de paso
                Forms\Components\Section::make('Configuración del Paso')
                    ->schema([
                        // Configuración para pasos de notificación
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('step_config.email_template_key')
                                ->label('Template de Email')
                                ->options(function (callable $get) {
                                    $workflow = $this->getOwnerRecord();
                                    $targetModel = $workflow->target_model ?? null;
                                    
                                    $query = \App\Models\EmailTemplate::where('is_active', true);
                                    
                                    // Si hay un modelo objetivo, filtrar por ese modelo o templates generales
                                    if ($targetModel) {
                                        $query->where(function ($q) use ($targetModel) {
                                            $q->whereNull('model_type')
                                              ->orWhere('model_type', $targetModel);
                                        });
                                    }
                                    
                                    return $query->pluck('name', 'key')->toArray();
                                })
                                ->searchable()
                                ->placeholder('Seleccionar template de email')
                                ->helperText('Template que se usará para el email de notificación')
                                ->required(),
                                
                            Forms\Components\Select::make('step_config.recipient_config.type')
                                ->label('Tipo de Destinatario')
                                ->options([
                                    'users' => 'Usuarios específicos',
                                    'roles' => 'Por roles',
                                    'dynamic' => 'Dinámico (basado en modelo)',
                                ])
                                ->reactive()
                                ->default('users')
                                ->required(),
                                
                            Forms\Components\Select::make('step_config.recipient_config.users')
                                ->label('Usuarios Destinatarios')
                                ->multiple()
                                ->options(\App\Models\User::pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn (callable $get) => $get('step_config.recipient_config.type') === 'users'),
                                
                            Forms\Components\Select::make('step_config.recipient_config.roles')
                                ->label('Roles Destinatarios')
                                ->multiple()
                                ->options(function () {
                                    if (class_exists('\Spatie\Permission\Models\Role')) {
                                        return \Spatie\Permission\Models\Role::pluck('name', 'name');
                                    }
                                    return [];
                                })
                                ->searchable()
                                ->visible(fn (callable $get) => $get('step_config.recipient_config.type') === 'roles'),
                                
                            Forms\Components\Select::make('step_config.recipient_config.dynamic_type')
                                ->label('Tipo Dinámico')
                                ->options([
                                    'creator' => 'Creador del registro',
                                    'last_editor' => 'Último editor',
                                    'manager' => 'Manager del creador',
                                    'department_head' => 'Jefe de departamento',
                                ])
                                ->visible(fn (callable $get) => $get('step_config.recipient_config.type') === 'dynamic'),
                                
                            Forms\Components\TextInput::make('step_config.notifications.priority')
                                ->label('Prioridad')
                                ->placeholder('normal')
                                ->helperText('Alta, normal o baja'),
                                
                            Forms\Components\KeyValue::make('step_config.template_variables')
                                ->label('Variables Adicionales del Template')
                                ->keyLabel('Variable')
                                ->valueLabel('Valor')
                                ->helperText('Variables personalizadas para el template (ejemplo: approval_url, deadline_date)'),
                        ])
                        ->visible(fn (callable $get) => $get('step_type') === WorkflowStepDefinition::TYPE_NOTIFICATION)
                        ->columns(2),
                        
                        // Configuración para pasos de aprobación
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('step_config.approvers.type')
                                ->label('Tipo de Aprobación')
                                ->options([
                                    'users' => 'Usuarios específicos',
                                    'roles' => 'Por roles',
                                    'dynamic' => 'Dinámico basado en modelo',
                                ])
                                ->reactive()
                                ->default('users'),
                                
                            Forms\Components\Select::make('step_config.approvers.users')
                                ->label('Usuarios Aprobadores')
                                ->multiple()
                                ->options(\App\Models\User::pluck('name', 'id'))
                                ->searchable()
                                ->visible(fn (callable $get) => $get('step_config.approvers.type') === 'users'),
                                
                            Forms\Components\Select::make('step_config.approvers.roles')
                                ->label('Roles Aprobadores')
                                ->multiple()
                                ->options(function () {
                                    if (class_exists('\Spatie\Permission\Models\Role')) {
                                        return \Spatie\Permission\Models\Role::pluck('name', 'name');
                                    }
                                    return [];
                                })
                                ->searchable()
                                ->visible(fn (callable $get) => $get('step_config.approvers.type') === 'roles'),
                                
                            Forms\Components\Select::make('step_config.approvers.dynamic.type')
                                ->label('Tipo Dinámico')
                                ->options([
                                    'creator_manager' => 'Manager del creador',
                                    'department_head' => 'Jefe de departamento',
                                    'role_in_department' => 'Rol en departamento',
                                ])
                                ->visible(fn (callable $get) => $get('step_config.approvers.type') === 'dynamic'),
                                
                            Forms\Components\TextInput::make('step_config.timeout_hours')
                                ->label('Timeout (horas)')
                                ->numeric()
                                ->placeholder('24')
                                ->helperText('Tiempo límite para aprobar'),
                                
                            Forms\Components\Section::make('Configuración de Notificación de Aprobación')
                                ->schema([
                                    Forms\Components\Select::make('step_config.approval_email_template_key')
                                        ->label('Template de Email de Solicitud de Aprobación')
                                        ->options(function (callable $get) {
                                            $workflow = $this->getOwnerRecord();
                                            $targetModel = $workflow->target_model ?? null;
                                            
                                            $query = \App\Models\EmailTemplate::where('is_active', true);
                                            
                                            if ($targetModel) {
                                                $query->where(function ($q) use ($targetModel) {
                                                    $q->whereNull('model_type')
                                                      ->orWhere('model_type', $targetModel);
                                                });
                                            }
                                            
                                            return $query->pluck('name', 'key')->toArray();
                                        })
                                        ->searchable()
                                        ->placeholder('Seleccionar template para solicitud de aprobación')
                                        ->helperText('Template enviado a los aprobadores'),
                                        
                                    Forms\Components\Select::make('step_config.approval_response_email_template_key')
                                        ->label('Template de Email de Respuesta')
                                        ->options(function (callable $get) {
                                            $workflow = $this->getOwnerRecord();
                                            $targetModel = $workflow->target_model ?? null;
                                            
                                            $query = \App\Models\EmailTemplate::where('is_active', true);
                                            
                                            if ($targetModel) {
                                                $query->where(function ($q) use ($targetModel) {
                                                    $q->whereNull('model_type')
                                                      ->orWhere('model_type', $targetModel);
                                                });
                                            }
                                            
                                            return $query->pluck('name', 'key')->toArray();
                                        })
                                        ->searchable()
                                        ->placeholder('Seleccionar template para respuesta de aprobación')
                                        ->helperText('Template enviado cuando se aprueba/rechaza'),
                                        
                                    Forms\Components\KeyValue::make('step_config.approval_template_variables')
                                        ->label('Variables Adicionales del Template')
                                        ->keyLabel('Variable')
                                        ->valueLabel('Valor')
                                        ->helperText('Variables personalizadas para los templates de aprobación'),
                                ])
                                ->collapsed()
                                ->collapsible(),
                        ])
                        ->visible(fn (callable $get) => $get('step_type') === WorkflowStepDefinition::TYPE_APPROVAL)
                        ->columns(2),
                        
                        // Configuración para pasos de acción
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('step_config.action.type')
                                ->label('Tipo de Acción')
                                ->options([
                                    'update_model' => 'Actualizar modelo',
                                    'send_email' => 'Enviar email',
                                    'create_record' => 'Crear registro',
                                    'call_method' => 'Llamar método',
                                ])
                                ->reactive(),
                                
                            Forms\Components\KeyValue::make('step_config.action.updates')
                                ->label('Campos a Actualizar')
                                ->keyLabel('Campo')
                                ->valueLabel('Valor')
                                ->visible(fn (callable $get) => $get('step_config.action.type') === 'update_model'),
                        ])
                        ->visible(fn (callable $get) => $get('step_type') === WorkflowStepDefinition::TYPE_ACTION),
                        
                        // Configuración para pasos de espera
                        Forms\Components\Group::make([
                            Forms\Components\Select::make('step_config.wait.type')
                                ->label('Tipo de Espera')
                                ->options([
                                    'time' => 'Tiempo fijo',
                                    'condition' => 'Condición',
                                    'manual' => 'Manual',
                                ])
                                ->reactive(),
                                
                            Forms\Components\TextInput::make('step_config.wait.minutes')
                                ->label('Minutos de Espera')
                                ->numeric()
                                ->visible(fn (callable $get) => $get('step_config.wait.type') === 'time'),
                        ])
                        ->visible(fn (callable $get) => $get('step_type') === WorkflowStepDefinition::TYPE_WAIT),
                    ]),
                    
                Forms\Components\Section::make('Condiciones del Paso')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\CheckboxList::make('conditions.trigger_events')
                                ->label('Solo ejecutar en estos eventos')
                                ->options([
                                    'created' => 'Creación de registro',
                                    'updated' => 'Actualización de registro',
                                    'deleted' => 'Eliminación de registro',
                                    'state_changed' => 'Cambio de Estado (Unificado)',
                                    'state_transition_submit_for_approval' => 'Enviar para Aprobación',
                                    'state_transition_approve' => 'Aprobar',
                                    'state_transition_reject' => 'Rechazar',
                                    'state_transition_publish' => 'Publicar',
                                    'state_transition_archive' => 'Archivar',
                                    'changed_to_state_draft' => 'Cambio a: Borrador',
                                    'changed_to_state_pending_approval' => 'Cambio a: Pendiente de Aprobación',
                                    'changed_to_state_approved' => 'Cambio a: Aprobado',
                                    'changed_to_state_rejected' => 'Cambio a: Rechazado',
                                    'changed_to_state_published' => 'Cambio a: Publicado',
                                    'changed_to_state_archived' => 'Cambio a: Archivado',
                                ])
                                ->columns(2)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    // Si se selecciona solo un evento, también guardarlo como 'event' para compatibilidad
                                    if (is_array($state) && count($state) === 1) {
                                        $conditions = $get('conditions') ?? [];
                                        $conditions['event'] = $state[0];
                                        $set('conditions', $conditions);
                                    }
                                })
                                ->helperText('Si no seleccionas ninguno, se ejecutará en todos los eventos del workflow'),
                        ]),
                        
                        Forms\Components\Section::make('Condiciones de Estado')
                            ->schema([
                                Forms\Components\Select::make('conditions.state_conditions.from_state')
                                    ->label('Estado Origen')
                                    ->options(function (callable $get) {
                                        $workflow = $this->getOwnerRecord();
                                        $targetModel = $workflow->target_model;
                                        
                                        if (!$targetModel) {
                                            return [];
                                        }
                                        
                                        try {
                                            $states = \App\Models\ApprovalState::where('model_type', $targetModel)
                                                ->where('is_active', true)
                                                ->orderBy('sort_order')
                                                ->get();
                                                
                                            return $states->pluck('label', 'name')->toArray();
                                        } catch (\Exception $e) {
                                            return [];
                                        }
                                    })
                                    ->placeholder('Cualquier estado')
                                    ->helperText('Solo ejecutar si viene de este estado específico'),
                                    
                                Forms\Components\Select::make('conditions.state_conditions.to_state')
                                    ->label('Estado Destino')
                                    ->options(function (callable $get) {
                                        $workflow = $this->getOwnerRecord();
                                        $targetModel = $workflow->target_model;
                                        
                                        if (!$targetModel) {
                                            return [];
                                        }
                                        
                                        try {
                                            $states = \App\Models\ApprovalState::where('model_type', $targetModel)
                                                ->where('is_active', true)
                                                ->orderBy('sort_order')
                                                ->get();
                                                
                                            return $states->pluck('label', 'name')->toArray();
                                        } catch (\Exception $e) {
                                            return [];
                                        }
                                    })
                                    ->placeholder('Cualquier estado')
                                    ->helperText('Solo ejecutar si va hacia este estado específico'),
                                    
                                Forms\Components\Select::make('conditions.state_conditions.transition_name')
                                    ->label('Transición Específica')
                                    ->options(function (callable $get) {
                                        $workflow = $this->getOwnerRecord();
                                        $targetModel = $workflow->target_model;
                                        
                                        if (!$targetModel) {
                                            return [];
                                        }
                                        
                                        try {
                                            $transitions = \App\Models\StateTransition::whereHas('fromState', function($query) use ($targetModel) {
                                                $query->where('model_type', $targetModel);
                                            })->where('is_active', true)->get();
                                            
                                            return $transitions->pluck('label', 'name')->toArray();
                                        } catch (\Exception $e) {
                                            return [];
                                        }
                                    })
                                    ->placeholder('Cualquier transición')
                                    ->helperText('Solo ejecutar en esta transición específica'),
                            ])
                            ->columns(3),
                            
                        Forms\Components\Repeater::make('conditions.field_conditions')
                            ->label('Condiciones de Campo (Avanzado)')
                            ->schema([
                                Forms\Components\Select::make('field')
                                    ->label('Campo')
                                    ->options(function (callable $get) {
                                        $workflow = $this->getOwnerRecord();
                                        $targetModel = $workflow->target_model;
                                        
                                        if (!$targetModel) {
                                            return [];
                                        }
                                        
                                        try {
                                            $introspectionService = app(\App\Services\ModelIntrospectionService::class);
                                            $modelInfo = $introspectionService->getModelInfo($targetModel);
                                            
                                            $options = [];
                                            // Agregar campos de estado específicos
                                            $options['state'] = 'Estado Actual (Spatie)';
                                            $options['status'] = 'Status (Legacy)';
                                            
                                            foreach ($modelInfo['fields'] as $field => $info) {
                                                $options[$field] = ucfirst(str_replace('_', ' ', $field));
                                            }
                                            
                                            return $options;
                                        } catch (\Exception $e) {
                                            return [];
                                        }
                                    })
                                    ->required()
                                    ->searchable(),
                                    
                                Forms\Components\Select::make('operator')
                                    ->label('Operador')
                                    ->options([
                                        '=' => 'Igual a',
                                        '!=' => 'Diferente de',
                                        '>' => 'Mayor que',
                                        '<' => 'Menor que',
                                        '>=' => 'Mayor o igual',
                                        '<=' => 'Menor o igual',
                                        'in' => 'Está en lista',
                                        'not_in' => 'No está en lista',
                                        'contains' => 'Contiene texto',
                                        'starts_with' => 'Comienza con',
                                        'ends_with' => 'Termina con',
                                        'changed' => 'El campo cambió',
                                        'changed_to' => 'Cambió al valor',
                                        'changed_from' => 'Cambió desde el valor',
                                        'exists' => 'Campo tiene valor',
                                        'not_exists' => 'Campo está vacío',
                                    ])
                                    ->required()
                                    ->reactive(),
                                    
                                Forms\Components\TextInput::make('value')
                                    ->label('Valor')
                                    ->helperText(function (callable $get) {
                                        $operator = $get('operator');
                                        return match($operator) {
                                            'in', 'not_in' => 'Separar valores con comas',
                                            'changed', 'exists', 'not_exists' => 'No se requiere valor',
                                            default => 'Valor a comparar'
                                        };
                                    })
                                    ->visible(fn (callable $get) => !in_array($get('operator'), ['changed', 'exists', 'not_exists'])),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->addActionLabel('Agregar Condición de Campo'),
                            
                        Forms\Components\Repeater::make('conditions.context_conditions')
                            ->label('Condiciones de Contexto')
                            ->schema([
                                Forms\Components\Select::make('context_key')
                                    ->label('Variable de Contexto')
                                    ->options([
                                        'trigger_event' => 'Evento que disparó el workflow',
                                        'trigger_user_id' => 'ID del usuario que disparó',
                                        'trigger_user_name' => 'Nombre del usuario que disparó',
                                        'model_changes' => 'Cambios en el modelo',
                                        'previous_step_result' => 'Resultado del paso anterior',
                                        'execution_count' => 'Número de ejecuciones',
                                    ])
                                    ->required(),
                                    
                                Forms\Components\Select::make('operator')
                                    ->label('Operador')
                                    ->options([
                                        '=' => 'Igual a',
                                        '!=' => 'Diferente de',
                                        'contains' => 'Contiene',
                                        'in' => 'Está en lista',
                                    ])
                                    ->required(),
                                    
                                Forms\Components\TextInput::make('value')
                                    ->label('Valor')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Agregar Condición de Contexto'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('step_name')
            ->columns([
                Tables\Columns\TextColumn::make('step_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),
                    
                Tables\Columns\TextColumn::make('step_name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('step_type')
                    ->label('Tipo')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        WorkflowStepDefinition::TYPE_NOTIFICATION => 'Notificación',
                        WorkflowStepDefinition::TYPE_APPROVAL => 'Aprobación',
                        WorkflowStepDefinition::TYPE_ACTION => 'Acción',
                        WorkflowStepDefinition::TYPE_CONDITION => 'Condición',
                        WorkflowStepDefinition::TYPE_WAIT => 'Espera',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        WorkflowStepDefinition::TYPE_NOTIFICATION => 'info',
                        WorkflowStepDefinition::TYPE_APPROVAL => 'warning',
                        WorkflowStepDefinition::TYPE_ACTION => 'success',
                        WorkflowStepDefinition::TYPE_CONDITION => 'gray',
                        WorkflowStepDefinition::TYPE_WAIT => 'secondary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('email_template')
                    ->label('Email Template')
                    ->formatStateUsing(function ($record) {
                        $config = $record->step_config ?? [];
                        
                        $templates = [];
                        
                        // Para pasos de notificación
                        if ($record->step_type === WorkflowStepDefinition::TYPE_NOTIFICATION) {
                            if (!empty($config['email_template_key'])) {
                                $templates[] = $config['email_template_key'];
                            }
                        }
                        
                        // Para pasos de aprobación
                        if ($record->step_type === WorkflowStepDefinition::TYPE_APPROVAL) {
                            if (!empty($config['approval_email_template_key'])) {
                                $templates[] = $config['approval_email_template_key'];
                            }
                            if (!empty($config['approval_response_email_template_key'])) {
                                $templates[] = $config['approval_response_email_template_key'];
                            }
                        }
                        
                        if (empty($templates)) {
                            return '-';
                        }
                        
                        return implode(', ', $templates);
                    })
                    ->tooltip(function ($record) {
                        $config = $record->step_config ?? [];
                        $info = [];
                        
                        if ($record->step_type === WorkflowStepDefinition::TYPE_NOTIFICATION) {
                            if (!empty($config['email_template_key'])) {
                                $info[] = "Notificación: {$config['email_template_key']}";
                            }
                        }
                        
                        if ($record->step_type === WorkflowStepDefinition::TYPE_APPROVAL) {
                            if (!empty($config['approval_email_template_key'])) {
                                $info[] = "Solicitud: {$config['approval_email_template_key']}";
                            }
                            if (!empty($config['approval_response_email_template_key'])) {
                                $info[] = "Respuesta: {$config['approval_response_email_template_key']}";
                            }
                        }
                        
                        return implode("\n", $info);
                    })
                    ->limit(30)
                    ->wrap(),
                    
                Tables\Columns\TextColumn::make('conditions_summary')
                    ->label('Condiciones')
                    ->formatStateUsing(function ($record) {
                        $conditions = $record->conditions ?? [];
                        $summary = [];
                        
                        // Mostrar eventos configurados
                        if (!empty($conditions['trigger_events'])) {
                            $eventLabels = [
                                'created' => 'Creación',
                                'updated' => 'Actualización',
                                'deleted' => 'Eliminación',
                                'state_changed' => 'Cambio Estado',
                            ];
                            
                            $events = array_map(function($event) use ($eventLabels) {
                                return $eventLabels[$event] ?? $event;
                            }, $conditions['trigger_events']);
                            
                            if (count($events) <= 2) {
                                $summary[] = implode(', ', $events);
                            } else {
                                $summary[] = count($events) . ' eventos';
                            }
                        }
                        
                        // Mostrar condiciones de estado si existen
                        if (!empty($conditions['state_conditions'])) {
                            $stateConditions = $conditions['state_conditions'];
                            if (!empty($stateConditions['from_state']) || !empty($stateConditions['to_state'])) {
                                $summary[] = 'Condiciones Estado';
                            }
                        }
                        
                        // Mostrar si hay condiciones de campo
                        if (!empty($conditions['field_conditions'])) {
                            $summary[] = count($conditions['field_conditions']) . ' campos';
                        }
                        
                        return empty($summary) ? 'Sin condiciones' : implode(' • ', $summary);
                    })
                    ->tooltip(function ($record) {
                        $conditions = $record->conditions ?? [];
                        $details = [];
                        
                        if (!empty($conditions['trigger_events'])) {
                            $details[] = 'Eventos: ' . implode(', ', $conditions['trigger_events']);
                        }
                        
                        if (!empty($conditions['state_conditions'])) {
                            $sc = $conditions['state_conditions'];
                            if (!empty($sc['from_state'])) $details[] = "Desde: {$sc['from_state']}";
                            if (!empty($sc['to_state'])) $details[] = "Hacia: {$sc['to_state']}";
                        }
                        
                        if (!empty($conditions['field_conditions'])) {
                            foreach ($conditions['field_conditions'] as $fc) {
                                $details[] = "{$fc['field']} {$fc['operator']} {$fc['value']}";
                            }
                        }
                        
                        return empty($details) ? 'No hay condiciones configuradas' : implode("\n", $details);
                    })
                    ->color(function ($record) {
                        $conditions = $record->conditions ?? [];
                        return empty($conditions) ? 'gray' : 'success';
                    })
                    ->badge()
                    ->wrap(),
                    
                Tables\Columns\IconColumn::make('is_required')
                    ->label('Requerido')
                    ->boolean(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('step_type')
                    ->label('Tipo')
                    ->options([
                        WorkflowStepDefinition::TYPE_NOTIFICATION => 'Notificación',
                        WorkflowStepDefinition::TYPE_APPROVAL => 'Aprobación',
                        WorkflowStepDefinition::TYPE_ACTION => 'Acción',
                        WorkflowStepDefinition::TYPE_CONDITION => 'Condición',
                        WorkflowStepDefinition::TYPE_WAIT => 'Espera',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Paso'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('move_up')
                    ->label('Subir')
                    ->icon('heroicon-o-arrow-up')
                    ->color('gray')
                    ->action(function (WorkflowStepDefinition $record) {
                        $previousStep = $record->getPreviousStep();
                        if ($previousStep) {
                            $tempOrder = $record->step_order;
                            $record->update(['step_order' => $previousStep->step_order]);
                            $previousStep->update(['step_order' => $tempOrder]);
                        }
                    })
                    ->visible(fn (WorkflowStepDefinition $record) => !$record->isFirstStep()),
                    
                Tables\Actions\Action::make('move_down')
                    ->label('Bajar')
                    ->icon('heroicon-o-arrow-down')
                    ->color('gray')
                    ->action(function (WorkflowStepDefinition $record) {
                        $nextStep = $record->getNextStep();
                        if ($nextStep) {
                            $tempOrder = $record->step_order;
                            $record->update(['step_order' => $nextStep->step_order]);
                            $nextStep->update(['step_order' => $tempOrder]);
                        }
                    })
                    ->visible(fn (WorkflowStepDefinition $record) => !$record->isLastStep()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('step_order')
            ->reorderable('step_order');
    }
}