<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\Branch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use STS\FilamentImpersonate\Tables\Actions\Impersonate;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?string $pluralModelLabel = 'Usuarios';

    protected static ?string $navigationGroup = 'Gestión de Usuarios';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información Personal')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Avatar')
                            ->avatar()
                            ->disk('public')
                            ->directory('avatars')
                            ->visibility('public')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('150')
                            ->imageResizeTargetHeight('150')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
                            ->helperText('Imagen de perfil del usuario (150x150px máximo)')
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Correo Electrónico')
                            ->email()
                            ->required()
                            ->unique(User::class, 'email', ignoreRecord: true)
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Seguridad')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->same('passwordConfirmation')
                            ->dehydrated(fn ($state) => filled($state))
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state)),
                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->label('Confirmar Contraseña')
                            ->password()
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->dehydrated(false),
                    ])->columns(2),

                Forms\Components\Section::make('Asignación de Sucursales')
                    ->schema([
                        Forms\Components\Select::make('branches')
                            ->label('Sucursales')
                            ->relationship('branches', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->optionsLimit(50)
                            ->getSearchResultsUsing(function (string $search): array {
                                return Branch::where('name', 'like', "%{$search}%")
                                    ->orWhere('address', 'like', "%{$search}%")
                                    ->limit(50)
                                    ->get()
                                    ->mapWithKeys(function ($branch) {
                                        return [$branch->id => $branch->name . ' - ' . $branch->address];
                                    })
                                    ->toArray();
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                return Branch::whereIn('id', $values)
                                    ->get()
                                    ->mapWithKeys(function ($branch) {
                                        return [$branch->id => $branch->name . ' - ' . $branch->address];
                                    })
                                    ->toArray();
                            })
                            ->helperText('Selecciona las sucursales a las que el usuario tendrá acceso')
                            ->columnSpanFull(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=' . urlencode('Usuario') . '&color=7F9CF5&background=EBF4FF'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('branches.name')
                    ->label('Sucursales')
                    ->badge()
                    ->separator(',')
                    ->color('primary')
                    ->limit(50)
                    ->tooltip(function ($record): string {
                        return $record->branches->pluck('name')->join(', ');
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/M/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/M/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                    Impersonate::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información Personal')
                    ->schema([
                        Infolists\Components\ImageEntry::make('avatar_url')
                            ->label('Avatar')
                            ->circular()
                            ->size(80)
                            ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name ?? 'Usuario') . '&color=7F9CF5&background=EBF4FF')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('name')
                            ->label('Nombre')
                            ->icon('heroicon-o-user')
                            ->copyable(),
                        Infolists\Components\TextEntry::make('email')
                            ->label('Correo Electrónico')
                            ->icon('heroicon-o-envelope')
                            ->copyable()
                            ->badge()
                            ->color('primary'),
                    ])->columns(2),

                Infolists\Components\Section::make('Sucursales Asignadas')
                    ->schema([
                        Infolists\Components\TextEntry::make('branches.name')
                            ->label('Sucursales')
                            ->badge()
                            ->separator(', ')
                            ->color('primary')
                            ->icon('heroicon-o-building-office')
                            ->listWithLineBreaks()
                            ->placeholder('Sin sucursales asignadas'),
                    ])->columns(1)
                    ->collapsible(),

                Infolists\Components\Section::make('Información del Sistema')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Fecha de Registro')
                            ->dateTime('d/M/Y H:i')
                            ->icon('heroicon-o-calendar-days')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Última Actualización')
                            ->dateTime('d/M/Y H:i')
                            ->icon('heroicon-o-clock')
                            ->badge()
                            ->color('warning'),
                    ])->columns(2)
                    ->collapsible(),

                Infolists\Components\Section::make('Estadísticas')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID de Usuario')
                            ->badge()
                            ->color('gray'),
                        Infolists\Components\TextEntry::make('days_since_registration')
                            ->label('Días desde el Registro')
                            ->state(function (User $record): string {
                                return $record->created_at->diffInDays(now()) . ' días';
                            })
                            ->badge()
                            ->color('info'),
                    ])->columns(2)
                    ->collapsible(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
