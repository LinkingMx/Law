<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Filament\Resources\BranchResource\RelationManagers;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    
    protected static ?string $modelLabel = 'Sucursal';
    
    protected static ?string $pluralModelLabel = 'Sucursales';
    
    protected static ?string $navigationGroup = 'Documentación Legal';
    
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de la Sucursal')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Nombre de la sucursal'),
                        
                        Forms\Components\Textarea::make('address')
                            ->label('Dirección')
                            ->rows(3)
                            ->maxLength(65535)
                            ->placeholder('Dirección completa de la sucursal'),
                    ])
                    ->columns(1),
                
                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('contact_name')
                            ->label('Nombre del Contacto')
                            ->maxLength(255)
                            ->placeholder('Nombre completo del contacto'),
                        
                        Forms\Components\TextInput::make('contact_email')
                            ->label('Email del Contacto')
                            ->email()
                            ->maxLength(255)
                            ->placeholder('email@ejemplo.com'),
                        
                        Forms\Components\TextInput::make('contact_phone')
                            ->label('Teléfono del Contacto')
                            ->tel()
                            ->maxLength(255)
                            ->placeholder('+1 (555) 000-0000'),
                    ])
                    ->columns(1),
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
                
                Tables\Columns\TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),
                
                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contacto')
                    ->searchable()
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('contact_email')
                    ->label('Email')
                    ->searchable()
                    ->toggleable()
                    ->copyable()
                    ->copyMessage('Email copiado')
                    ->copyMessageDuration(1500),
                
                Tables\Columns\TextColumn::make('contact_phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(),
                
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
            ->defaultSort('name')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->icon('heroicon-o-trash')
                            ->title('Sucursal eliminada')
                            ->body('La sucursal ha sido eliminada exitosamente.')
                            ->duration(5000)
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->successNotification(
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->icon('heroicon-o-trash')
                                ->title('Sucursales eliminadas')
                                ->body('Las sucursales seleccionadas han sido eliminadas.')
                                ->duration(5000)
                        ),
                ]),
            ])
            ->emptyStateHeading('No hay sucursales registradas')
            ->emptyStateDescription('Comienza creando tu primera sucursal.')
            ->emptyStateIcon('heroicon-o-building-office');
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
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'view' => Pages\ViewBranch::route('/{record}'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
