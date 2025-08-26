<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StateTransitionResource\Pages;
use App\Models\ApprovalState;
use App\Models\StateTransition;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StateTransitionResource extends Resource
{
    protected static ?string $model = StateTransition::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static ?string $navigationLabel = 'Transiciones de Estado';

    protected static ?string $modelLabel = 'Transición de Estado';

    protected static ?string $pluralModelLabel = 'Transiciones de Estado';

    protected static ?string $navigationGroup = 'Workflows Avanzados';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\Select::make('from_state_id')
                            ->label('Estado Origen')
                            ->relationship('fromState', 'label')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\Select::make('to_state_id')
                            ->label('Estado Destino')
                            ->relationship('toState', 'label')
                            ->required()
                            ->searchable()
                            ->preload(),

                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nombre único de la transición (ej: submit_for_approval, approve, reject)'),

                        Forms\Components\TextInput::make('label')
                            ->label('Etiqueta')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Nombre visible de la transición (ej: Enviar para Aprobación, Aprobar, Rechazar)'),

                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->maxLength(1000)
                            ->rows(3),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Control de Acceso')
                    ->schema([
                        Forms\Components\Toggle::make('requires_permission')
                            ->label('Requiere Permiso')
                            ->live(),

                        Forms\Components\TextInput::make('permission_name')
                            ->label('Nombre del Permiso')
                            ->maxLength(255)
                            ->visible(fn (Forms\Get $get): bool => $get('requires_permission'))
                            ->helperText('Permiso específico requerido (ej: approve_documentation)'),

                        Forms\Components\Toggle::make('requires_role')
                            ->label('Requiere Rol')
                            ->live(),

                        Forms\Components\TagsInput::make('role_names')
                            ->label('Roles Permitidos')
                            ->visible(fn (Forms\Get $get): bool => $get('requires_role'))
                            ->helperText('Roles que pueden ejecutar esta transición'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuración de Aprobación')
                    ->schema([
                        Forms\Components\Toggle::make('requires_approval')
                            ->label('Requiere Aprobación')
                            ->live(),

                        Forms\Components\TagsInput::make('approver_roles')
                            ->label('Roles de Aprobadores')
                            ->visible(fn (Forms\Get $get): bool => $get('requires_approval'))
                            ->helperText('Roles que pueden aprobar esta transición'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Condiciones y Reglas')
                    ->schema([
                        Forms\Components\Repeater::make('condition_rules')
                            ->label('Reglas de Condición')
                            ->schema([
                                Forms\Components\TextInput::make('field')
                                    ->label('Campo')
                                    ->required()
                                    ->helperText('Campo del modelo a evaluar'),

                                Forms\Components\Select::make('operator')
                                    ->label('Operador')
                                    ->options([
                                        '=' => 'Igual a',
                                        '!=' => 'Diferente de',
                                        '>' => 'Mayor que',
                                        '<' => 'Menor que',
                                        '>=' => 'Mayor o igual que',
                                        '<=' => 'Menor o igual que',
                                        'in' => 'Está en',
                                        'not_in' => 'No está en',
                                        'contains' => 'Contiene',
                                        'starts_with' => 'Empieza con',
                                        'ends_with' => 'Termina con',
                                        'is_null' => 'Es nulo',
                                        'is_not_null' => 'No es nulo',
                                    ])
                                    ->required(),

                                Forms\Components\TextInput::make('value')
                                    ->label('Valor')
                                    ->helperText('Valor a comparar'),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->collapsed(),
                    ]),

                Forms\Components\Section::make('Configuración de Notificaciones y Mensajes')
                    ->schema([
                        Forms\Components\TextInput::make('notification_template')
                            ->label('Template de Notificación')
                            ->maxLength(255)
                            ->helperText('Clave del template de email a usar'),

                        Forms\Components\TextInput::make('success_message')
                            ->label('Mensaje de Éxito')
                            ->maxLength(255)
                            ->helperText('Mensaje mostrado cuando la transición es exitosa'),

                        Forms\Components\TextInput::make('failure_message')
                            ->label('Mensaje de Error')
                            ->maxLength(255)
                            ->helperText('Mensaje mostrado cuando la transición falla'),

                        Forms\Components\TextInput::make('sort_order')
                            ->label('Orden')
                            ->numeric()
                            ->default(0)
                            ->helperText('Orden de aparición en listas'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('La transición está disponible para uso'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('fromState.label')
                    ->label('Desde')
                    ->badge()
                    ->color(fn ($record) => $record->fromState?->color ?? 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('toState.label')
                    ->label('Hacia')
                    ->badge()
                    ->color(fn ($record) => $record->toState?->color ?? 'gray')
                    ->sortable(),

                Tables\Columns\TextColumn::make('label')
                    ->label('Transición')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\IconColumn::make('requires_permission')
                    ->label('Req. Permiso')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('requires_role')
                    ->label('Req. Rol')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('requires_approval')
                    ->label('Req. Aprobación')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('from_state_id')
                    ->label('Estado Origen')
                    ->relationship('fromState', 'label'),

                Tables\Filters\SelectFilter::make('to_state_id')
                    ->label('Estado Destino')
                    ->relationship('toState', 'label'),

                Tables\Filters\TernaryFilter::make('requires_permission')
                    ->label('Requiere Permiso'),

                Tables\Filters\TernaryFilter::make('requires_role')
                    ->label('Requiere Rol'),

                Tables\Filters\TernaryFilter::make('requires_approval')
                    ->label('Requiere Aprobación'),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('from_state_id')
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStateTransitions::route('/'),
            'create' => Pages\CreateStateTransition::route('/create'),
            'edit' => Pages\EditStateTransition::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_any_state_transition') ?? false;
    }
}