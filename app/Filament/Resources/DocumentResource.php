<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $modelLabel = 'Documento';
    
    protected static ?string $pluralModelLabel = 'Documentos';
    
    protected static ?string $navigationGroup = 'Documentación Legal';
    
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Básica')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('document_category_id')
                                    ->label('Categoría del Documento')
                                    ->relationship('documentCategory', 'name')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->suffixIcon('heroicon-o-folder'),
                                
                                Forms\Components\Select::make('branches')
                                    ->label('Sucursales')
                                    ->relationship('branches', 'name')
                                    ->required()
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->suffixIcon('heroicon-o-building-office')
                                    ->helperText('Seleccione una o más sucursales donde aplica este documento'),
                            ]),
                        
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre del Documento')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-document')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ]),
                
                Forms\Components\Section::make('Fechas y Configuración')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('expire_date')
                                    ->label('Fecha de Vencimiento')
                                    ->displayFormat('d/m/Y')
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar-days'),
                                    
                                Forms\Components\TextInput::make('notification_days')
                                    ->label('Días de Notificación')
                                    ->numeric()
                                    ->default(30)
                                    ->minValue(1)
                                    ->maxValue(365)
                                    ->suffixIcon('heroicon-o-bell')
                                    ->helperText('Días antes del vencimiento para notificar'),
                            ]),
                    ]),

                Forms\Components\Section::make('Archivo del Documento')
                    ->schema([
                        Forms\Components\FileUpload::make('file_path')
                            ->label('Archivo')
                            ->disk('public')
                            ->directory('documents')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->maxSize(50 * 1024)
                            ->downloadable()
                            ->previewable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('documentCategory.name')
                    ->label('Categoría')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('branches.name')
                    ->label('Sucursales')
                    ->badge()
                    ->searchable()
                    ->limitList(3)
                    ->listWithLineBreaks()
                    ->expandableLimitedList(),
                
                Tables\Columns\IconColumn::make('has_file')
                    ->label('Archivo')
                    ->getStateUsing(fn (Document $record) => $record->hasFile())
                    ->boolean()
                    ->icon(fn ($state): string => $state ? 'heroicon-o-document-check' : 'heroicon-o-document-minus')
                    ->color(fn ($state): string => $state ? 'success' : 'gray'),
                
                Tables\Columns\TextColumn::make('expire_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->badge()
                    ->color(fn (Document $record): string => 
                        !$record->expire_date ? 'gray' : 
                        ($record->expire_date->isPast() ? 'danger' : 
                        ($record->isExpiringSoon() ? 'warning' : 'success'))
                    )
                    ->icon(fn (Document $record): string => 
                        !$record->expire_date ? '' : 
                        ($record->expire_date->isPast() ? 'heroicon-o-x-circle' : 
                        ($record->isExpiringSoon() ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-circle'))
                    ),
                    
                Tables\Columns\TextColumn::make('notification_days')
                    ->label('Días Notif.')
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-bell')
                    ->formatStateUsing(fn ($state): string => $state . ' días')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expire_date', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('document_category_id')
                    ->label('Categoría')
                    ->relationship('documentCategory', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('branches')
                    ->label('Sucursales')
                    ->relationship('branches', 'name')
                    ->multiple()
                    ->preload(),
                
                Tables\Filters\TernaryFilter::make('expired')
                    ->label('Estado de Vencimiento')
                    ->placeholder('Todos')
                    ->trueLabel('Vencidos')
                    ->falseLabel('Vigentes')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('expire_date')->where('expire_date', '<', now()),
                        false: fn (Builder $query) => $query->where(function ($query) {
                            $query->whereNull('expire_date')->orWhere('expire_date', '>=', now());
                        }),
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->action(function (Document $record) {
                        if (!$record->file_path || !Storage::disk('public')->exists($record->file_path)) {
                            Notification::make()
                                ->danger()
                                ->title('Archivo no encontrado')
                                ->body('El archivo del documento no está disponible.')
                                ->send();
                            return;
                        }

                        return response()->download(
                            Storage::disk('public')->path($record->file_path),
                            $record->file_name ?? 'documento.' . $record->file_extension
                        );
                    })
                    ->visible(fn (Document $record): bool => $record->hasFile()),
                    
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hay documentos registrados')
            ->emptyStateDescription('Comienza registrando el primer documento legal.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $now = now();
        $expiringSoon = static::getModel()::query()
            ->whereNotNull('expire_date')
            ->where(function ($query) use ($now) {
                $query->whereBetween('expire_date', [
                    $now,
                    $now->copy()->addDays(30)
                ])
                ->orWhere(function ($subQuery) use ($now) {
                    $subQuery->whereNotNull('notification_days')
                        ->whereRaw('expire_date BETWEEN ? AND datetime(?, "+" || COALESCE(notification_days, 30) || " days")', [
                            $now->toDateTimeString(),
                            $now->toDateTimeString()
                        ]);
                });
            })
            ->count();

        return $expiringSoon > 0 ? (string) $expiringSoon : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $now = now();
        $expiringSoon = static::getModel()::query()
            ->whereNotNull('expire_date')
            ->where(function ($query) use ($now) {
                $query->whereBetween('expire_date', [
                    $now,
                    $now->copy()->addDays(30)
                ])
                ->orWhere(function ($subQuery) use ($now) {
                    $subQuery->whereNotNull('notification_days')
                        ->whereRaw('expire_date BETWEEN ? AND datetime(?, "+" || COALESCE(notification_days, 30) || " days")', [
                            $now->toDateTimeString(),
                            $now->toDateTimeString()
                        ]);
                });
            })
            ->count();

        return $expiringSoon > 0 ? 'warning' : null;
    }
}