<?php

namespace App\Filament\Resources\AdvancedWorkflowResource\RelationManagers;

use App\Models\AdvancedWorkflowExecution;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExecutionsRelationManager extends RelationManager
{
    protected static string $relationship = 'executions';
    
    protected static ?string $title = 'Ejecuciones del Workflow';
    
    protected static ?string $modelLabel = 'Ejecución';
    
    protected static ?string $pluralModelLabel = 'Ejecuciones';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('target_model')
                    ->label('Modelo Objetivo')
                    ->disabled(),
                    
                Forms\Components\TextInput::make('target_id')
                    ->label('ID del Registro')
                    ->disabled(),
                    
                Forms\Components\Select::make('status')
                    ->label('Estado')
                    ->options([
                        AdvancedWorkflowExecution::STATUS_PENDING => 'Pendiente',
                        AdvancedWorkflowExecution::STATUS_IN_PROGRESS => 'En progreso',
                        AdvancedWorkflowExecution::STATUS_COMPLETED => 'Completado',
                        AdvancedWorkflowExecution::STATUS_FAILED => 'Fallado',
                        AdvancedWorkflowExecution::STATUS_CANCELLED => 'Cancelado',
                    ])
                    ->disabled(),
                    
                Forms\Components\Textarea::make('context_data')
                    ->label('Datos de Contexto')
                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT))
                    ->disabled()
                    ->rows(10),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('target_model')
                    ->label('Modelo')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->badge()
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('target_id')
                    ->label('Registro')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        AdvancedWorkflowExecution::STATUS_PENDING => 'Pendiente',
                        AdvancedWorkflowExecution::STATUS_IN_PROGRESS => 'En progreso',
                        AdvancedWorkflowExecution::STATUS_COMPLETED => 'Completado',
                        AdvancedWorkflowExecution::STATUS_FAILED => 'Fallado',
                        AdvancedWorkflowExecution::STATUS_CANCELLED => 'Cancelado',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        AdvancedWorkflowExecution::STATUS_PENDING => 'warning',
                        AdvancedWorkflowExecution::STATUS_IN_PROGRESS => 'info',
                        AdvancedWorkflowExecution::STATUS_COMPLETED => 'success',
                        AdvancedWorkflowExecution::STATUS_FAILED => 'danger',
                        AdvancedWorkflowExecution::STATUS_CANCELLED => 'gray',
                        default => 'secondary',
                    }),
                    
                Tables\Columns\TextColumn::make('current_step_order')
                    ->label('Paso Actual')
                    ->sortable()
                    ->formatStateUsing(function ($state, $record) {
                        if (!$state) return '-';
                        $totalSteps = $record->workflow->stepDefinitions()->active()->count();
                        return "{$state} / {$totalSteps}";
                    }),
                    
                Tables\Columns\TextColumn::make('progress')
                    ->label('Progreso')
                    ->formatStateUsing(fn ($state, $record) => $record->getProgress() . '%')
                    ->badge()
                    ->color('success'),
                    
                Tables\Columns\TextColumn::make('initiator.name')
                    ->label('Iniciado por')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('completed_at')
                    ->label('Completado')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('En progreso'),
                    
                Tables\Columns\TextColumn::make('elapsed_time')
                    ->label('Tiempo')
                    ->formatStateUsing(function ($state, $record) {
                        $elapsed = $record->getElapsedTime();
                        if (!$elapsed) return '-';
                        
                        $hours = intval($elapsed / 60);
                        $minutes = $elapsed % 60;
                        
                        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        AdvancedWorkflowExecution::STATUS_PENDING => 'Pendiente',
                        AdvancedWorkflowExecution::STATUS_IN_PROGRESS => 'En progreso',
                        AdvancedWorkflowExecution::STATUS_COMPLETED => 'Completado',
                        AdvancedWorkflowExecution::STATUS_FAILED => 'Fallado',
                        AdvancedWorkflowExecution::STATUS_CANCELLED => 'Cancelado',
                    ]),
                    
                Tables\Filters\Filter::make('created_today')
                    ->label('Hoy')
                    ->query(fn (Builder $query): Builder => $query->whereDate('created_at', today())),
                    
                Tables\Filters\Filter::make('in_progress')
                    ->label('En progreso')
                    ->query(fn (Builder $query): Builder => $query->where('status', AdvancedWorkflowExecution::STATUS_IN_PROGRESS)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Detalles de la Ejecución')
                    ->modalContent(function ($record) {
                        $stepExecutions = $record->stepExecutions()
                            ->with(['stepDefinition', 'assignedUser', 'completedByUser'])
                            ->orderBy('id')
                            ->get();
                            
                        return view('filament.modals.workflow-execution-details', [
                            'execution' => $record,
                            'stepExecutions' => $stepExecutions,
                        ]);
                    }),
                    
                Tables\Actions\Action::make('cancel')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(function (AdvancedWorkflowExecution $record) {
                        $record->markAsCancelled('Cancelado manualmente', auth()->id());
                    })
                    ->requiresConfirmation()
                    ->visible(fn (AdvancedWorkflowExecution $record) => $record->canBeCancelled()),
                    
                Tables\Actions\Action::make('restart')
                    ->label('Reiniciar')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->action(function (AdvancedWorkflowExecution $record) {
                        // Crear nueva ejecución basada en la anterior
                        $newExecution = $record->replicate([
                            'started_at',
                            'completed_at',
                            'current_step_id',
                            'current_step_order',
                            'step_results',
                        ]);
                        $newExecution->status = AdvancedWorkflowExecution::STATUS_PENDING;
                        $newExecution->save();
                        
                        // Inicializar nueva ejecución
                        if ($newExecution->initialize()) {
                            $workflowEngine = app(\App\Services\AdvancedWorkflowEngine::class);
                            $workflowEngine->processExecution($newExecution);
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (AdvancedWorkflowExecution $record) => $record->canBeRestarted()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('cancel_selected')
                        ->label('Cancelar seleccionados')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->canBeCancelled()) {
                                    $record->markAsCancelled('Cancelado en lote', auth()->id());
                                }
                            }
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
    
    public function isReadOnly(): bool
    {
        return false;
    }
}