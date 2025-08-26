<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailTemplateResource\Pages;
use App\Filament\Resources\EmailTemplateResource\RelationManagers;
use App\Models\EmailTemplate;
use App\Settings\EmailTemplateSettings;
use App\Services\ModelIntrospectionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationGroup = 'Correo';
    
    // Templates de documentación disponibles
    protected static bool $shouldRegisterNavigation = true;
    
    protected static ?string $navigationLabel = 'Templates de Email';
    
    protected static ?string $modelLabel = 'Template de Email';
    
    protected static ?string $pluralModelLabel = 'Templates de Email';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        $settings = app(EmailTemplateSettings::class);
        
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('key')
                                    ->label('Clave')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true)
                                    ->helperText('Identificador único del template (ej: backup-success)'),
                                    
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('Nombre descriptivo del template'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('language')
                                    ->label('Idioma')
                                    ->required()
                                    ->options($settings->available_languages)
                                    ->default($settings->default_language),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),
                            ]),
                            
                        Forms\Components\Select::make('model_type')
                            ->label('Modelo Asociado')
                            ->placeholder('Selecciona un modelo (opcional)')
                            ->options(function () {
                                $introspectionService = app(ModelIntrospectionService::class);
                                $models = $introspectionService->getAvailableModels();
                                
                                $options = ['' => 'Sin modelo específico'];
                                foreach ($models as $model) {
                                    $options[$model['class']] = $model['display_name'];
                                }
                                
                                return $options;
                            })
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    try {
                                        $introspectionService = app(ModelIntrospectionService::class);
                                        $modelInfo = $introspectionService->getModelInfo($state);
                                        $variables = $modelInfo['available_variables'] ?? [];
                                        $set('model_variables', $variables);
                                    } catch (\Exception $e) {
                                        $set('model_variables', []);
                                    }
                                } else {
                                    $set('model_variables', []);
                                }
                            })
                            ->helperText('Asocia este template a un modelo para acceder a sus variables dinámicas'),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->maxLength(500)
                            ->helperText('Descripción opcional del template'),
                    ]),
                    
                Forms\Components\Section::make('Contenido del Email')
                    ->schema([
                        Forms\Components\TextInput::make('subject')
                            ->label('Asunto')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Asunto del email. Puedes usar variables como {{app_name}}')
                            ->hintActions([
                                Forms\Components\Actions\Action::make('subject_variables_help')
                                    ->label('Variables')
                                    ->icon('heroicon-o-question-mark-circle')
                                    ->color('info')
                                    ->size('sm')
                                    ->modalHeading('Variables para el Asunto')
                                    ->modalContent(function ($get) use ($settings) {
                                        $currentCategory = $get('category') ?? 'general';
                                        return view('filament.resources.email-template.variables-help', [
                                            'currentTemplate' => null,
                                            'globalVariables' => $settings->global_variables,
                                            'commonVariables' => $settings->getCommonVariables(),
                                            'categoryVariables' => [
                                                'backup' => $settings->getCategoryVariables('backup'),
                                                'user' => $settings->getCategoryVariables('user'),
                                                'system' => $settings->getCategoryVariables('system'),
                                                'documentation' => $settings->getCategoryVariables('documentation'),
                                                'notification' => $settings->getCategoryVariables('notification'),
                                                $currentCategory => $settings->getCategoryVariables($currentCategory),
                                            ]
                                        ]);
                                    })
                                    ->modalSubmitAction(false)
                                    ->modalCancelActionLabel('Cerrar')
                                    ->modalWidth('7xl'),
                            ]),
                            
                        TinyEditor::make('content')
                            ->label('Contenido del Email')
                            ->required()
                            ->showMenuBar()
                            ->minHeight(600)
                            ->columnSpanFull()
                            ->profile('default')
                            ->helperText('Usa el editor para crear emails HTML profesionales. Variables disponibles: {{user.name}}, {{app_name}}, etc.')
                            ->hintAction(
                                Forms\Components\Actions\Action::make('showVariables')
                                    ->label('Ver Variables Disponibles')
                                    ->icon('heroicon-o-information-circle')
                                    ->modalHeading('Variables Disponibles')
                                    ->modalContent(function (callable $get) {
                                        $modelType = $get('model_type');
                                        $settings = app(EmailTemplateSettings::class);
                                        
                                        $html = '<div class="space-y-4">';
                                        
                                        // Variables del modelo
                                        if ($modelType) {
                                            try {
                                                $introspectionService = app(ModelIntrospectionService::class);
                                                $modelInfo = $introspectionService->getModelInfo($modelType);
                                                $modelVars = $modelInfo['available_variables'] ?? [];
                                                
                                                if (!empty($modelVars)) {
                                                    $html .= '<div class="mb-4">';
                                                    $html .= '<h3 class="text-lg font-semibold mb-2">Variables del Modelo ' . class_basename($modelType) . ':</h3>';
                                                    $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-2">';
                                                    foreach ($modelVars as $var) {
                                                        $html .= '<div class="p-2 bg-gray-50 dark:bg-gray-800 rounded">';
                                                        $html .= '<code class="text-sm text-primary-600 dark:text-primary-400">{{' . $var['key'] . '}}</code>';
                                                        $html .= '<p class="text-xs text-gray-600 dark:text-gray-400 mt-1">' . $var['description'] . '</p>';
                                                        $html .= '</div>';
                                                    }
                                                    $html .= '</div></div>';
                                                }
                                            } catch (\Exception $e) {
                                                // Ignorar errores
                                            }
                                        }
                                        
                                        // Variables globales
                                        $commonVars = $settings->getCommonVariables();
                                        if (!empty($commonVars)) {
                                            $html .= '<div class="mb-4">';
                                            $html .= '<h3 class="text-lg font-semibold mb-2">Variables Globales:</h3>';
                                            $html .= '<div class="grid grid-cols-1 md:grid-cols-2 gap-2">';
                                            foreach ($commonVars as $key => $description) {
                                                $html .= '<div class="p-2 bg-gray-50 dark:bg-gray-800 rounded">';
                                                $html .= '<code class="text-sm text-primary-600 dark:text-primary-400">{{' . $key . '}}</code>';
                                                $html .= '<p class="text-xs text-gray-600 dark:text-gray-400 mt-1">' . $description . '</p>';
                                                $html .= '</div>';
                                            }
                                            $html .= '</div></div>';
                                        }
                                        
                                        // Ejemplos de formateo
                                        $html .= '<div class="mt-4 pt-4 border-t">';
                                        $html .= '<h3 class="text-lg font-semibold mb-2">Formateo de Variables:</h3>';
                                        $html .= '<div class="space-y-1 text-sm">';
                                        $html .= '<div><code>{{created_at|date:Y-m-d}}</code> → Fecha formateada</div>';
                                        $html .= '<div><code>{{price|currency:USD}}</code> → Formato de moneda</div>';
                                        $html .= '<div><code>{{name|upper}}</code> → Texto en mayúsculas</div>';
                                        $html .= '<div><code>{{user.profile.name}}</code> → Variables anidadas</div>';
                                        $html .= '</div></div>';
                                        
                                        $html .= '</div>';
                                        
                                        return new \Illuminate\Support\HtmlString($html);
                                    })
                                    ->modalWidth('4xl')
                            )
                    ]),
                    
                Forms\Components\Section::make('Variables y Configuración')
                    ->schema([
                        Forms\Components\KeyValue::make('variables')
                            ->label('Variables Personalizadas del Template')
                            ->keyLabel('Variable')
                            ->valueLabel('Descripción')
                            ->helperText('Define variables específicas para este template además de las del modelo'),
                            
                        Forms\Components\Hidden::make('model_variables'),
                        
                        Forms\Components\Placeholder::make('dynamic_variables_info')
                            ->label('Variables Dinámicas Disponibles')
                            ->content(function (callable $get) use ($settings) {
                                $modelType = $get('model_type');
                                
                                $html = '<div class="space-y-4">';
                                
                                // Variables del modelo
                                if ($modelType) {
                                    try {
                                        $introspectionService = app(ModelIntrospectionService::class);
                                        $modelInfo = $introspectionService->getModelInfo($modelType);
                                        $modelVars = $modelInfo['available_variables'] ?? [];
                                        
                                        if (!empty($modelVars)) {
                                            $html .= '<div>';
                                            $html .= '<h4 class="font-medium text-sm mb-2">Variables del Modelo ' . class_basename($modelType) . ':</h4>';
                                            $html .= '<div class="grid grid-cols-2 gap-2">';
                                            
                                            foreach ($modelVars as $var) {
                                                $html .= '<div class="text-xs">';
                                                $html .= '<code class="text-primary-600">{{' . $var['key'] . '}}</code>';
                                                $html .= ' - ' . $var['description'];
                                                $html .= '</div>';
                                            }
                                            
                                            $html .= '</div></div>';
                                        }
                                    } catch (\Exception $e) {
                                        // Ignorar errores
                                    }
                                }
                                
                                // Variables comunes
                                $commonVars = $settings->getCommonVariables();
                                if (!empty($commonVars)) {
                                    $html .= '<div>';
                                    $html .= '<h4 class="font-medium text-sm mb-2">Variables Globales:</h4>';
                                    $html .= '<div class="grid grid-cols-2 gap-2">';
                                    
                                    foreach ($commonVars as $key => $description) {
                                        $html .= '<div class="text-xs">';
                                        $html .= '<code class="text-primary-600">{{' . $key . '}}</code>';
                                        $html .= ' - ' . $description;
                                        $html .= '</div>';
                                    }
                                    
                                    $html .= '</div></div>';
                                }
                                
                                
                                $html .= '</div>';
                                
                                return new \Illuminate\Support\HtmlString($html);
                            }),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
                    
                Tables\Columns\TextColumn::make('key')
                    ->label('Clave')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                    
                Tables\Columns\TextColumn::make('model_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn ($state) => $state ? class_basename($state) : '-')
                    ->badge()
                    ->color('info')
                    ->searchable(),
                    
                Tables\Columns\BadgeColumn::make('language')
                    ->label('Idioma')
                    ->colors([
                        'success' => 'es',
                        'info' => 'en',
                    ]),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Modelo')
                    ->options(function () {
                        $introspectionService = app(ModelIntrospectionService::class);
                        $models = $introspectionService->getAvailableModels();
                        
                        $options = ['' => 'Sin modelo específico'];
                        foreach ($models as $model) {
                            $options[$model['class']] = $model['display_name'];
                        }
                        
                        return $options;
                    }),
                    
                Tables\Filters\SelectFilter::make('language')
                    ->label('Idioma')
                    ->options([
                        'es' => 'Español',
                        'en' => 'English',
                    ]),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                Tables\Actions\Action::make('preview')
                    ->label('Vista Previa')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Vista Previa del Template')
                    ->modalContent(fn (EmailTemplate $record) => view('filament.resources.email-template.preview', ['template' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar'),
                Tables\Actions\Action::make('variables_help')
                    ->label('Variables')
                    ->icon('heroicon-o-question-mark-circle')
                    ->color('info')
                    ->modalHeading('Guía de Variables Disponibles')
                    ->modalContent(fn (EmailTemplate $record) => view('filament.resources.email-template.variables-help', [
                        'currentTemplate' => $record,
                        'globalVariables' => app(\App\Settings\EmailTemplateSettings::class)->global_variables,
                        'commonVariables' => app(\App\Settings\EmailTemplateSettings::class)->getCommonVariables(),
                        'modelVariables' => $record->getModelVariables()
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('7xl'),
                Tables\Actions\Action::make('code_examples')
                    ->label('Código')
                    ->icon('heroicon-o-code-bracket')
                    ->color('secondary')
                    ->modalHeading('Ejemplos de Código')
                    ->modalContent(fn () => view('filament.resources.email-template.code-example'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalWidth('7xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar')
                        ->icon('heroicon-o-check')
                        ->action(fn ($records) => $records->each->update(['is_active' => true]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn ($records) => $records->each->update(['is_active' => false]))
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('duplicate')
                        ->label('Duplicar')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $newTemplate = $record->replicate();
                                $newTemplate->key = $record->key . '_copy_' . uniqid();
                                $newTemplate->name = $record->name . ' (Copia)';
                                $newTemplate->is_active = false;
                                $newTemplate->save();
                            });
                        })
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmailTemplates::route('/'),
            'create' => Pages\CreateEmailTemplate::route('/create'),
            'edit' => Pages\EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
