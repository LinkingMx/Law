<?php

namespace App\Filament\Resources\IncidentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\IncidentComment;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';
    
    protected static ?string $title = 'Conversación Histórica';
    
    protected static ?string $modelLabel = 'Comentario';
    
    protected static ?string $pluralModelLabel = 'Comentarios';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Textarea::make('comment')
                    ->label('Comentario')
                    ->required()
                    ->rows(4)
                    ->placeholder('Escribe tu comentario sobre esta incidencia...')
                    ->columnSpanFull()
                    ->autosize(),
                
                Forms\Components\Toggle::make('is_internal')
                    ->label('Comentario interno')
                    ->helperText('Los comentarios internos solo son visibles para el equipo legal')
                    ->default(false),
                
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
                    ->dehydrated(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('comment')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('comment')
                    ->label('Comentario')
                    ->searchable()
                    ->limit(100)
                    ->wrap()
                    ->tooltip(function ($record): string {
                        return $record->comment;
                    }),
                
                Tables\Columns\IconColumn::make('is_internal')
                    ->label('Interno')
                    ->boolean()
                    ->icon(fn ($state): string => $state ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color(fn ($state): string => $state ? 'warning' : 'success')
                    ->tooltip(fn ($state): string => $state ? 'Comentario interno (solo equipo legal)' : 'Comentario público'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->since()
                    ->tooltip(fn ($record): string => $record->created_at->format('d/m/Y H:i:s')),
            ])
            ->defaultSort('created_at', 'asc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_internal')
                    ->label('Tipo de comentario')
                    ->placeholder('Todos')
                    ->trueLabel('Internos')
                    ->falseLabel('Públicos'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar Comentario')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->successNotificationTitle('Comentario agregado exitosamente'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn ($record) => $record->user_id === auth()->id()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn ($record) => $record->user_id === auth()->id()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->can('delete_any_incident_comment')),
                ]),
            ])
            ->emptyStateHeading('Sin comentarios')
            ->emptyStateDescription('Inicia la conversación agregando el primer comentario.')
            ->emptyStateIcon('heroicon-o-chat-bubble-left-ellipsis')
            ->poll('30s'); // Auto-refresh every 30 seconds for real-time conversation
    }
}
