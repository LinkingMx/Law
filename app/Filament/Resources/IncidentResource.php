<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IncidentResource\Pages;
use App\Filament\Resources\IncidentResource\RelationManagers;
use App\Models\Incident;
use App\Models\Branch;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    
    protected static ?string $modelLabel = 'Incidencia';
    
    protected static ?string $pluralModelLabel = 'Incidencias';
    
    protected static ?string $navigationGroup = 'Soporte Legal';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Incidencia')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Título de la Incidencia')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Describe brevemente el problema')
                            ->prefixIcon('heroicon-o-exclamation-triangle')
                            ->columnSpanFull(),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('branch_id')
                                    ->label('Sucursal')
                                    ->relationship('branch', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->suffixIcon('heroicon-o-building-office')
                                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name . ' - ' . $record->address),
                                
                                Forms\Components\Select::make('priority')
                                    ->label('Prioridad')
                                    ->options([
                                        'low' => 'Baja',
                                        'medium' => 'Media',
                                        'high' => 'Alta',
                                        'urgent' => 'Urgente',
                                    ])
                                    ->default('medium')
                                    ->required()
                                    ->suffixIcon('heroicon-o-flag'),
                            ]),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción Detallada')
                            ->required()
                            ->rows(4)
                            ->placeholder('Proporciona una descripción detallada del problema, incluyendo pasos para reproducirlo si aplica...')
                            ->columnSpanFull()
                            ->autosize(),
                    ])->columns(2),

                Forms\Components\Section::make('Archivo Adjunto')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Archivo Relacionado')
                            ->disk('public')
                            ->directory('incidents')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'application/vnd.ms-excel',
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'image/jpeg',
                                'image/png',
                                'image/gif',
                                'text/plain',
                                'application/zip',
                            ])
                            ->maxSize(25 * 1024) // 25MB
                            ->downloadable()
                            ->previewable(true)
                            ->openable()
                            ->helperText('Opcional: Adjunta documentos, capturas de pantalla o archivos relacionados')
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, $set, $record) {
                                if ($state) {
                                    $file = $state;
                                    if (is_string($file)) {
                                        if ($record && Storage::disk('public')->exists($file)) {
                                            $path = Storage::disk('public')->path($file);
                                            $set('file_name', basename($file));
                                            $set('file_extension', pathinfo($file, PATHINFO_EXTENSION));
                                            $set('file_size', filesize($path));
                                            $set('mime_type', Storage::disk('public')->mimeType($file));
                                        }
                                    } elseif ($file instanceof \Illuminate\Http\UploadedFile) {
                                        $set('file_name', $file->getClientOriginalName());
                                        $set('file_extension', $file->getClientOriginalExtension());
                                        $set('file_size', $file->getSize());
                                        $set('mime_type', $file->getMimeType());
                                    }
                                }
                            }),
                        
                        Forms\Components\Hidden::make('file_name'),
                        Forms\Components\Hidden::make('file_extension'),
                        Forms\Components\Hidden::make('file_size'),
                        Forms\Components\Hidden::make('mime_type'),
                    ]),

                Forms\Components\Section::make('Gestión Administrativa')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Estado')
                                    ->options([
                                        'open' => 'Abierta',
                                        'in_progress' => 'En Progreso',
                                        'resolved' => 'Resuelta',
                                        'closed' => 'Cerrada',
                                    ])
                                    ->default('open')
                                    ->required()
                                    ->suffixIcon('heroicon-o-queue-list'),
                                
                                Forms\Components\Select::make('assigned_to')
                                    ->label('Asignado a')
                                    ->relationship('assignedTo', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->suffixIcon('heroicon-o-user')
                                    ->placeholder('Sin asignar'),
                            ]),
                        
                        Forms\Components\DateTimePicker::make('resolved_at')
                            ->label('Fecha de Resolución')
                            ->displayFormat('d/m/Y H:i')
                            ->native(false)
                            ->suffixIcon('heroicon-o-calendar-days')
                            ->visible(fn ($get) => in_array($get('status'), ['resolved', 'closed']))
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible(),
                
                Forms\Components\Hidden::make('user_id')
                    ->default(auth()->id())
                    ->dehydrated(true),
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
                    ->weight('bold')
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Reportado por')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-user'),
                
                Tables\Columns\TextColumn::make('branch.name')
                    ->label('Sucursal')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'open' => 'Abierta',
                        'in_progress' => 'En Progreso',
                        'resolved' => 'Resuelta',
                        'closed' => 'Cerrada',
                        default => $state
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray'
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'open' => 'heroicon-o-exclamation-triangle',
                        'in_progress' => 'heroicon-o-arrow-path',
                        'resolved' => 'heroicon-o-check-circle',
                        'closed' => 'heroicon-o-x-circle',
                        default => 'heroicon-o-question-mark-circle'
                    }),
                
                Tables\Columns\TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                        default => $state
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'low' => 'success',
                        'medium' => 'warning',
                        'high' => 'danger',
                        'urgent' => 'danger',
                        default => 'gray'
                    }),
                
                Tables\Columns\IconColumn::make('has_file')
                    ->label('Archivo')
                    ->getStateUsing(fn (Incident $record) => $record->hasFile())
                    ->boolean()
                    ->icon(fn ($state): string => $state ? 'heroicon-o-paper-clip' : 'heroicon-o-document-minus')
                    ->color(fn ($state): string => $state ? 'success' : 'gray')
                    ->tooltip(fn (Incident $record): string => 
                        $record->hasFile() 
                            ? "Archivo: {$record->file_name} ({$record->getFormattedFileSize()})"
                            : 'Sin archivo adjunto'
                    ),
                
                Tables\Columns\TextColumn::make('assignedTo.name')
                    ->label('Asignado a')
                    ->searchable()
                    ->placeholder('Sin asignar')
                    ->badge()
                    ->color('primary'),
                
                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Comentarios')
                    ->counts('comments')
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-chat-bubble-left-right'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'open' => 'Abierta',
                        'in_progress' => 'En Progreso',
                        'resolved' => 'Resuelta',
                        'closed' => 'Cerrada',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                        'urgent' => 'Urgente',
                    ])
                    ->multiple(),
                
                Tables\Filters\SelectFilter::make('branch_id')
                    ->label('Sucursal')
                    ->relationship('branch', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->label('Asignado a')
                    ->relationship('assignedTo', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\Filter::make('unassigned')
                    ->label('Sin asignar')
                    ->query(fn (Builder $query): Builder => $query->whereNull('assigned_to')),
                
                Tables\Filters\Filter::make('has_file')
                    ->label('Con archivo adjunto')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('file_path')),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function (Incident $record) {
                        if (!$record->file_path || !Storage::disk('public')->exists($record->file_path)) {
                            Notification::make()
                                ->danger()
                                ->icon('heroicon-o-exclamation-triangle')
                                ->title('Archivo no encontrado')
                                ->body('El archivo de la incidencia no está disponible.')
                                ->send();
                            return;
                        }

                        return response()->download(
                            Storage::disk('public')->path($record->file_path),
                            $record->file_name ?? 'incidencia.' . $record->file_extension,
                            [
                                'Content-Type' => $record->mime_type ?? 'application/octet-stream',
                            ]
                        );
                    })
                    ->visible(fn (Incident $record): bool => $record->hasFile()),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->icon('heroicon-o-trash')
                            ->title('Incidencia eliminada')
                            ->body('La incidencia ha sido eliminada exitosamente.')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            Notification::make()
                                ->success()
                                ->icon('heroicon-o-trash')
                                ->title('Incidencias eliminadas')
                                ->body('Las incidencias seleccionadas han sido eliminadas.')
                        ),
                ]),
            ])
            ->emptyStateHeading('No hay incidencias registradas')
            ->emptyStateDescription('Comienza reportando la primera incidencia al equipo legal.')
            ->emptyStateIcon('heroicon-o-exclamation-triangle');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información de la Incidencia')
                    ->schema([
                        Infolists\Components\TextEntry::make('title')
                            ->label('Título')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->weight('bold')
                            ->columnSpanFull(),
                        
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.name')
                                    ->label('Reportado por')
                                    ->icon('heroicon-o-user')
                                    ->badge()
                                    ->color('info'),
                                
                                Infolists\Components\TextEntry::make('branch.name')
                                    ->label('Sucursal')
                                    ->icon('heroicon-o-building-office')
                                    ->badge()
                                    ->color('primary'),
                            ]),
                        
                        Infolists\Components\TextEntry::make('description')
                            ->label('Descripción')
                            ->prose()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Estado y Prioridad')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->label('Estado')
                                    ->formatStateUsing(fn (Incident $record): string => $record->getStatusLabel())
                                    ->badge()
                                    ->color(fn (Incident $record): string => $record->getStatusColor()),
                                
                                Infolists\Components\TextEntry::make('priority')
                                    ->label('Prioridad')
                                    ->formatStateUsing(fn (Incident $record): string => $record->getPriorityLabel())
                                    ->badge()
                                    ->color(fn (Incident $record): string => $record->getPriorityColor()),
                                
                                Infolists\Components\TextEntry::make('assignedTo.name')
                                    ->label('Asignado a')
                                    ->placeholder('Sin asignar')
                                    ->icon('heroicon-o-user')
                                    ->badge()
                                    ->color('success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Archivo Adjunto')
                    ->schema([
                        Infolists\Components\TextEntry::make('file_name')
                            ->label('Nombre del archivo')
                            ->icon('heroicon-o-paper-clip')
                            ->placeholder('Sin archivo adjunto'),
                        
                        Infolists\Components\TextEntry::make('file_size')
                            ->label('Tamaño')
                            ->formatStateUsing(fn (Incident $record): ?string => $record->getFormattedFileSize())
                            ->icon('heroicon-o-scale'),
                        
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('download')
                                ->label('Descargar archivo')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('primary')
                                ->action(function (Incident $record) {
                                    if (!$record->hasFile()) {
                                        return;
                                    }
                                    return response()->download(
                                        Storage::disk('public')->path($record->file_path),
                                        $record->file_name ?? 'incidencia.' . $record->file_extension
                                    );
                                })
                                ->visible(fn (Incident $record): bool => $record->hasFile()),
                        ]),
                    ])
                    ->visible(fn (Incident $record): bool => $record->hasFile())
                    ->collapsible(),

                Infolists\Components\Section::make('Fechas')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Creado')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-o-calendar-days')
                                    ->badge()
                                    ->color('info'),
                                
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Última actualización')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-o-clock')
                                    ->badge()
                                    ->color('warning'),
                                
                                Infolists\Components\TextEntry::make('resolved_at')
                                    ->label('Fecha de resolución')
                                    ->dateTime('d/m/Y H:i')
                                    ->icon('heroicon-o-check-circle')
                                    ->badge()
                                    ->color('success')
                                    ->placeholder('No resuelto'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CommentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListIncidents::route('/'),
            'create' => Pages\CreateIncident::route('/create'),
            'view' => Pages\ViewIncident::route('/{record}'),
            'edit' => Pages\EditIncident::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $openIncidents = static::getModel()::query()
            ->where('status', 'open')
            ->count();

        return $openIncidents > 0 ? (string) $openIncidents : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $openIncidents = static::getModel()::query()
            ->where('status', 'open')
            ->count();

        return $openIncidents > 0 ? 'warning' : null;
    }
}
