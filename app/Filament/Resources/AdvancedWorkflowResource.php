<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdvancedWorkflowResource\Pages;
use App\Filament\Resources\AdvancedWorkflowResource\RelationManagers;
use App\Models\AdvancedWorkflow;
use App\Services\ModelIntrospectionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AdvancedWorkflowResource extends Resource
{
    protected static ?string $model = AdvancedWorkflow::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    
    protected static ?string $navigationGroup = 'Workflows Avanzados';
    
    protected static ?string $navigationLabel = 'Workflows Avanzados';
    
    protected static ?string $modelLabel = 'Workflow Avanzado';
    
    protected static ?string $pluralModelLabel = 'Workflows Avanzados';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información General')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre del Workflow')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                    
                                Forms\Components\Select::make('target_model')
                                    ->label('Modelo Objetivo')
                                    ->required()
                                    ->options(function () {
                                        $introspectionService = app(ModelIntrospectionService::class);
                                        $models = $introspectionService->getAvailableModels();
                                        
                                        $options = [];
                                        foreach ($models as $model) {
                                            $options[$model['class']] = $model['display_name'];
                                        }
                                        
                                        return $options;
                                    })
                                    ->searchable()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        // Limpiar condiciones cuando cambia el modelo
                                        $set('trigger_conditions', null);
                                    })
                                    ->columnSpan(1),
                                    
                                Forms\Components\TextInput::make('version')
                                    ->label('Versión')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->columnSpan(1),
                            ]),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->helperText('Activar/desactivar la ejecución del workflow')
                            ->default(true)
                            ->inline(false),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->placeholder('Describe el propósito y funcionamiento de este workflow...'),
                    ]),
                    
                Forms\Components\Section::make('Información del Workflow')
                    ->schema([
                        Forms\Components\Placeholder::make('workflow_info')
                            ->label('')
                            ->content('**Workflow Master:** Este workflow se evaluará en todos los eventos del modelo. Las condiciones específicas se definen a nivel de cada paso.')
                            ->columnSpanFull(),
                            
                        Forms\Components\Placeholder::make('available_events')
                            ->label('Eventos Disponibles')
                            ->content(function (callable $get) {
                                $targetModel = $get('target_model');
                                if (!$targetModel) {
                                    return 'Selecciona un modelo para ver los eventos disponibles.';
                                }
                                
                                $events = static::getAvailableEventsForModel($targetModel);
                                $eventList = collect($events)->map(fn($label, $key) => "• **{$key}**: {$label}")->implode("\n");
                                
                                return "Los siguientes eventos estarán disponibles para configurar en cada paso:\n\n{$eventList}";
                            })
                            ->helperText('Cada paso del workflow puede configurarse para ejecutarse solo en eventos específicos')
                            ->columnSpanFull(),
                    ]),
                    
                Forms\Components\Section::make('Variables Disponibles')
                    ->description('Variables que puedes usar en los templates de email y pasos del workflow')
                    ->icon('heroicon-o-variable')
                    ->schema([
                        Forms\Components\Group::make([
                            Forms\Components\Placeholder::make('variables_info')
                                ->label('')
                                ->content(function (callable $get) {
                                    $targetModel = $get('target_model');
                                    if (!$targetModel) {
                                        return 'Selecciona un modelo objetivo para ver las variables disponibles.';
                                    }
                                    
                                    try {
                                        $introspectionService = app(ModelIntrospectionService::class);
                                        $modelInfo = $introspectionService->getModelInfo($targetModel);
                                        $variables = $modelInfo['available_variables'] ?? [];
                                        
                                        if (empty($variables)) {
                                            return 'No se encontraron variables para este modelo.';
                                        }
                                        
                                        $html = '<div class="space-y-4">';
                                        $html .= '<p class="text-sm opacity-70">Variables disponibles para usar en los templates:</p>';
                                        
                                        // Agrupar por categorías
                                        $grouped = [];
                                        foreach ($variables as $variable) {
                                            $category = $variable['category'] ?? 'other';
                                            $grouped[$category][] = $variable;
                                        }
                                        
                                        foreach ($grouped as $category => $categoryVariables) {
                                            $categoryTitle = match($category) {
                                                'model_field' => 'Campos del Modelo',
                                                'relation_field' => 'Campos de Relaciones',
                                                'user_field' => 'Información de Usuarios',
                                                'date_formatted' => 'Fechas Formateadas',
                                                default => ucfirst($category)
                                            };
                                            
                                            $html .= '<div class="border rounded-lg p-3">';
                                            $html .= '<h4 class="font-medium text-sm mb-2">' . $categoryTitle . '</h4>';
                                            $html .= '<div class="grid grid-cols-2 gap-2">';
                                            
                                            foreach ($categoryVariables as $variable) {
                                                $key = $variable['key'];
                                                $description = $variable['description'];
                                                
                                                $html .= '<div class="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-800 rounded text-sm">';
                                                $html .= '<div>';
                                                $html .= '<code class="font-mono text-xs bg-primary-100 dark:bg-primary-900 text-primary-800 dark:text-primary-200 px-1 py-0.5 rounded">{{' . $key . '}}</code>';
                                                $html .= '<p class="text-xs opacity-70 mt-1">' . $description . '</p>';
                                                $html .= '</div>';
                                                $html .= '</div>';
                                            }
                                            
                                            $html .= '</div>';
                                            $html .= '</div>';
                                        }
                                        
                                        $html .= '</div>';
                                        
                                        return new \Illuminate\Support\HtmlString($html);
                                        
                                    } catch (\Exception $e) {
                                        return 'Error al cargar variables: ' . $e->getMessage();
                                    }
                                })
                                ->reactive(),
                        ]),
                            
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('manage_variables')
                                ->label('Gestionar Variables Personalizadas')
                                ->icon('heroicon-o-cog-6-tooth')
                                ->color('gray')
                                ->url(fn () => \App\Filament\Resources\ModelVariableMappingResource::getUrl('index'))
                                ->openUrlInNewTab()
                                ->visible(fn (callable $get) => $get('target_model')),
                                
                            Forms\Components\Actions\Action::make('create_variable')
                                ->label('Crear Variable Nueva')
                                ->icon('heroicon-o-plus')
                                ->color('success')
                                ->url(fn () => \App\Filament\Resources\ModelVariableMappingResource::getUrl('generator'))         
                                ->openUrlInNewTab()
                                ->visible(fn (callable $get) => $get('target_model')),
                        ])
                        ->alignment('start')
                        ->visible(fn (callable $get) => $get('target_model')),
                    ])
                    ->collapsible()
                    ->collapsed(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('target_model')
                    ->label('Modelo')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('stepDefinitions_count')
                    ->label('Pasos')
                    ->counts('stepDefinitions')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('executions_count')
                    ->label('Ejecuciones')
                    ->counts('executions')
                    ->badge()
                    ->color('warning'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('version')
                    ->label('Versión')
                    ->badge()
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('target_model')
                    ->label('Modelo')
                    ->options(function () {
                        $introspectionService = app(ModelIntrospectionService::class);
                        $models = $introspectionService->getAvailableModels();
                        
                        $options = [];
                        foreach ($models as $model) {
                            $options[$model['class']] = $model['display_name'];
                        }
                        
                        return $options;
                    }),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                
                Tables\Actions\Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->action(function (AdvancedWorkflow $record) {
                        $newWorkflow = $record->replicate([
                            'name',
                        ]);
                        $newWorkflow->name = $record->name . ' (Copia)';
                        $newWorkflow->is_active = false;
                        $newWorkflow->version = 1;
                        $newWorkflow->save();
                        
                        // Duplicar pasos también
                        foreach ($record->stepDefinitions as $step) {
                            $newStep = $step->replicate();
                            $newStep->advanced_workflow_id = $newWorkflow->id;
                            $newStep->save();
                            
                            // Duplicar templates del paso
                            foreach ($step->templates as $template) {
                                $newTemplate = $template->replicate();
                                $newTemplate->workflow_step_definition_id = $newStep->id;
                                $newTemplate->save();
                            }
                        }
                        
                        return redirect()->to(static::getUrl('edit', ['record' => $newWorkflow->id]));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Duplicar Workflow')
                    ->modalDescription('Se creará una copia completa del workflow con todos sus pasos y templates.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => true]);
                        })
                        ->requiresConfirmation(),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['is_active' => false]);
                        })
                        ->requiresConfirmation(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StepDefinitionsRelationManager::class,
            RelationManagers\ExecutionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvancedWorkflows::route('/'),
            'create' => Pages\CreateAdvancedWorkflow::route('/create'),
            'edit' => Pages\EditAdvancedWorkflow::route('/{record}/edit'),
        ];
    }
    
    public static function canViewAny(): bool
    {
        return auth()->user()?->can('view_any_advanced::workflow') ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->can('create_advanced::workflow') ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->can('update_advanced::workflow') ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->can('delete_advanced::workflow') ?? false;
    }
    
    /**
     * Obtener eventos disponibles dinámicamente según el modelo
     */
    protected static function getAvailableEventsForModel(string $modelClass): array
    {
        // Eventos básicos disponibles para todos los modelos
        $events = [
            'created' => 'Creación de registro',
            'updated' => 'Actualización de registro', 
            'deleted' => 'Eliminación de registro',
        ];
        
        try {
            // Verificar si el modelo usa Spatie Model States
            $usesSpatieStates = in_array('Spatie\\ModelStates\\HasStates', class_uses_recursive($modelClass));
            
            if ($usesSpatieStates) {
                // SISTEMA UNIFICADO PARA MODELOS CON SPATIE STATES
                
                // Evento unificado de cambio de estado (reemplaza eventos específicos inconsistentes)
                $events['state_changed'] = 'Cambio de Estado (Cualquier transición)';
                
                // Cargar transiciones dinámicas disponibles
                try {
                    $transitions = \App\Models\StateTransition::whereHas('fromState', function($query) use ($modelClass) {
                        $query->where('model_type', $modelClass);
                    })->where('is_active', true)->get();
                    
                    if ($transitions->count() > 0) {
                        // Agrupar transiciones por tipo de evento
                        foreach ($transitions as $transition) {
                            $eventKey = "transition_{$transition->name}";
                            $events[$eventKey] = "Transición: {$transition->label}";
                        }
                    }
                    
                    // Estados disponibles (solo para cambios a estado específico)
                    $states = \App\Models\ApprovalState::where('model_type', $modelClass)
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();
                        
                    if ($states->count() > 0) {
                        foreach ($states as $state) {
                            $eventKey = "entered_state_{$state->name}";
                            $events[$eventKey] = "Entrada a: {$state->label}";
                        }
                    }
                    
                } catch (\Exception $e) {
                    // Si hay error con estados dinámicos, mantener el evento unificado
                    \Log::warning("Error cargando estados dinámicos para {$modelClass}: " . $e->getMessage());
                }
                
            } else {
                // Para modelos SIN Spatie States, verificar campos de estado simples
                try {
                    $introspectionService = app(\App\Services\ModelIntrospectionService::class);
                    $modelInfo = $introspectionService->getModelInfo($modelClass);
                    $fields = $modelInfo['fields'] ?? [];
                    
                    // Detectar campos de estado convencionales
                    $hasStatusFields = false;
                    foreach (['status', 'state', 'stage', 'phase'] as $statusField) {
                        if (isset($fields[$statusField])) {
                            $hasStatusFields = true;
                            break;
                        }
                    }
                    
                    if ($hasStatusFields) {
                        $events['status_changed'] = 'Campo de Estado Actualizado';
                    }
                    
                } catch (\Exception $e) {
                    // Si hay error en introspección, continuar sin eventos de estado
                    \Log::warning("Error analizando campos del modelo {$modelClass}: " . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            // Si hay error general, devolver solo eventos básicos
            \Log::warning("Error detectando eventos para modelo {$modelClass}: " . $e->getMessage());
            return [
                'created' => 'Creación de registro',
                'updated' => 'Actualización de registro', 
                'deleted' => 'Eliminación de registro',
            ];
        }
        
        return $events;
    }
}