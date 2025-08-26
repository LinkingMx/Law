<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentationResource\Pages;
use App\Filament\Resources\DocumentationResource\RelationManagers;
use App\Models\Documentation;
use App\States\DocumentationState;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;

class DocumentationResource extends Resource
{
    protected static ?string $model = Documentation::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Gestión de Contenido';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $modelLabel = 'Documento';
    
    protected static ?string $pluralModelLabel = 'Documentación';
    
    protected static ?string $navigationLabel = 'Documentación';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Documento')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                            
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->required()
                            ->rows(5)
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('branches')
                            ->label('Sucursales')
                            ->relationship('branches', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Seleccione las sucursales a las que aplica este documento')
                            ->columnSpanFull(),
                            
                        Forms\Components\Select::make('state')
                            ->label('Estado')
                            ->options(function ($record) {
                                if (!$record) {
                                    return ['draft' => 'Borrador'];
                                }
                                
                                $availableTransitions = $record->getAvailableStateTransitions();
                                $currentState = $record->state;
                                
                                $options = [];
                                if ($currentState) {
                                    $options[$currentState->getStateName()] = $currentState->getDescription();
                                }
                                
                                foreach ($availableTransitions as $transition) {
                                    $toState = $transition['to_state'];
                                    $options[$toState->name] = $toState->label;
                                }
                                
                                return $options;
                            })
                            ->disabled(fn ($context) => $context === 'create')
                            ->helperText('Los documentos nuevos se crean en estado Borrador'),
                    ]),
                    
                Forms\Components\Section::make('Información de Aprobación')
                    ->schema([
                        Forms\Components\Select::make('approved_by')
                            ->label('Aprobado por')
                            ->relationship('approver', 'name')
                            ->disabled(),
                            
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Fecha de Aprobación')
                            ->disabled(),
                    ])
                    ->columns(2)
                    ->visible(fn ($record) => $record && $record->approved_at),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(100)
                    ->tooltip(fn (Documentation $record) => $record->description),
                    
                Tables\Columns\TextColumn::make('branches.name')
                    ->label('Sucursales')
                    ->badge()
                    ->color('info')
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('state')
                    ->label('Estado')
                    ->formatStateUsing(function ($record) {
                        if (!$record->state) {
                            return 'Sin estado';
                        }
                        return $record->state->getDescription();
                    })
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->state) {
                            return 'gray';
                        }
                        return $record->state->getColor();
                    }),
                    
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->default('Sistema'),
                    
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Aprobado por')
                    ->default('Pendiente')
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Aprobado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->label('Estado')
                    ->options(function () {
                        $states = \App\Models\ApprovalState::where('model_type', 'App\\Models\\Documentation')
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->get();
                        
                        return $states->pluck('label', 'name')->toArray();
                    }),
                    
                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Creado por')
                    ->relationship('creator', 'name'),
                    
                Tables\Filters\SelectFilter::make('branches')
                    ->label('Sucursal')
                    ->relationship('branches', 'name')
                    ->multiple()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                
                // Acciones de transición dinámica
                Tables\Actions\ActionGroup::make([
                    // Enviar para aprobación
                    Tables\Actions\Action::make('submit_for_approval')
                        ->label('Enviar para Aprobación')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('info')
                        ->action(function (Documentation $record) {
                            if ($record->submitForApprovalViaStates()) {
                                Notification::make()
                                    ->title('Documento enviado para aprobación')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No se pudo enviar para aprobación')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Documentation $record) => 
                            !empty(array_filter($record->getAvailableStateTransitions(), 
                                fn($t) => $t['transition']->name === 'submit_for_approval'
                            ))
                        ),
                    
                    // Aprobar
                    Tables\Actions\Action::make('approve')
                        ->label('Aprobar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (Documentation $record) {
                            if ($record->approveViaStates()) {
                                Notification::make()
                                    ->title('Documento aprobado')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No se pudo aprobar el documento')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Documentation $record) => 
                            !empty(array_filter($record->getAvailableStateTransitions(), 
                                fn($t) => $t['transition']->name === 'approve'
                            ))
                        )
                        ->requiresConfirmation(),
                    
                    // Rechazar
                    Tables\Actions\Action::make('reject')
                        ->label('Rechazar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function (Documentation $record) {
                            if ($record->rejectViaStates()) {
                                Notification::make()
                                    ->title('Documento rechazado')
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No se pudo rechazar el documento')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Documentation $record) => 
                            !empty(array_filter($record->getAvailableStateTransitions(), 
                                fn($t) => $t['transition']->name === 'reject'
                            ))
                        )
                        ->requiresConfirmation(),
                    
                    // Publicar
                    Tables\Actions\Action::make('publish')
                        ->label('Publicar')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->action(function (Documentation $record) {
                            if ($record->publishDocument()) {
                                Notification::make()
                                    ->title('Documento publicado')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No se pudo publicar el documento')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Documentation $record) => 
                            !empty(array_filter($record->getAvailableStateTransitions(), 
                                fn($t) => $t['transition']->name === 'publish'
                            ))
                        ),
                    
                    // Archivar
                    Tables\Actions\Action::make('archive')
                        ->label('Archivar')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->action(function (Documentation $record) {
                            if ($record->archiveDocument()) {
                                Notification::make()
                                    ->title('Documento archivado')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('No se pudo archivar el documento')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Documentation $record) => 
                            !empty(array_filter($record->getAvailableStateTransitions(), 
                                fn($t) => $t['transition']->name === 'archive'
                            ))
                        ),
                ])
                ->label('Transiciones')
                ->icon('heroicon-o-arrows-right-left')
                ->button()
                ->dropdownWidth('max-w-xs')
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListDocumentations::route('/'),
            'create' => Pages\CreateDocumentation::route('/create'),
            // 'view' => Pages\ViewDocumentation::route('/{record}'),
            'edit' => Pages\EditDocumentation::route('/{record}/edit'),
        ];
    }
    
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['creator', 'approver', 'branches'])
            ->latest('created_at');
    }
}
