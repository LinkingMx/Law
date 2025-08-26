<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailConfigurationResource\Pages;
use App\Models\EmailConfiguration;
use App\Mail\TestEmail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\Eloquent\Model;

class EmailConfigurationResource extends Resource
{
    protected static ?string $model = EmailConfiguration::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    
    protected static ?string $navigationLabel = 'Configuración de Email';
    
    protected static ?string $modelLabel = 'Configuración de Email';
    
    protected static ?string $pluralModelLabel = 'Configuraciones de Email';
    
    protected static ?string $navigationGroup = 'Correo';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuración General')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nombre de la configuración')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Mi configuración SMTP'),
                                
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Activar esta configuración')
                                    ->helperText('Solo una configuración puede estar activa a la vez')
                                    ->reactive()
                                    ->afterStateUpdated(function (Set $set, ?bool $state, ?Model $record) {
                                        if ($state && $record && $record->exists) {
                                            // Deactivate other configurations
                                            EmailConfiguration::where('id', '!=', $record->id)
                                                ->update(['is_active' => false]);
                                        }
                                    }),
                            ]),
                        
                        Forms\Components\Select::make('driver')
                            ->label('Proveedor de Email')
                            ->required()
                            ->options([
                                'smtp' => 'SMTP',
                                'mailgun' => 'Mailgun',
                                'postmark' => 'Postmark',
                                'ses' => 'Amazon SES',
                                'sendmail' => 'Sendmail',
                            ])
                            ->live(),
                    ]),

                Forms\Components\Section::make('Configuración SMTP')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Actions::make([
                                    Forms\Components\Actions\Action::make('load_mailtrap')
                                        ->label('Cargar configuración Mailtrap')
                                        ->icon('heroicon-o-envelope')
                                        ->color('info')
                                        ->action(function (Set $set) {
                                            $set('settings.host', 'sandbox.smtp.mailtrap.io');
                                            $set('settings.port', '587');
                                            $set('settings.encryption', 'tls');
                                            $set('settings.auth_method', 'LOGIN');
                                            $set('settings.username', '912b5d949c4fb4');
                                            $set('settings.password', '1573941d01c7fe');
                                            $set('settings.from_address', 'test@example.com');
                                            $set('settings.from_name', 'Test Mailtrap');
                                        })
                                        ->tooltip('Carga automáticamente la configuración de Mailtrap para pruebas'),
                                ])
                                    ->alignStart(),
                            ]),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('settings.host')
                                    ->label('Servidor SMTP')
                                    ->required()
                                    ->placeholder('sandbox.smtp.mailtrap.io')
                                    ->helperText('Para Mailtrap usar: sandbox.smtp.mailtrap.io')
                                    ->rules(['required_if:driver,smtp']),
                                
                                Forms\Components\Select::make('settings.port')
                                    ->label('Puerto')
                                    ->required()
                                    ->options(function (Get $get) {
                                        $encryption = $get('settings.encryption');
                                        return [
                                            '25' => '25 (Sin encriptación)' . ($encryption === 'none' ? ' - Recomendado' : ''),
                                            '465' => '465 (SSL)' . ($encryption === 'ssl' ? ' - Recomendado' : ''),
                                            '587' => '587 (TLS)' . ($encryption === 'tls' ? ' - Recomendado' : ''),
                                            '2525' => '2525 (Alternativo)',
                                        ];
                                    })
                                    ->default('587')
                                    ->helperText('Para Mailtrap: 25, 465, 587 o 2525 son válidos. El puerto se ajusta automáticamente según la encriptación.')
                                    ->live()
                                    ->rules(['required_if:driver,smtp']),
                            ]),
                        
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\Select::make('settings.encryption')
                                    ->label('Encriptación')
                                    ->options([
                                        'none' => 'Ninguna',
                                        'tls' => 'TLS (STARTTLS)',
                                        'ssl' => 'SSL',
                                    ])
                                    ->default('tls')
                                    ->helperText('TLS disponible en todos los puertos de Mailtrap')
                                    ->live()
                                    ->afterStateUpdated(function (Set $set, ?string $state) {
                                        // Auto-adjust port based on encryption
                                        match ($state) {
                                            'ssl' => $set('settings.port', '465'),
                                            'tls' => $set('settings.port', '587'),
                                            'none' => $set('settings.port', '25'),
                                            default => null,
                                        };
                                    }),
                                
                                Forms\Components\Select::make('settings.auth_method')
                                    ->label('Método de autenticación')
                                    ->options([
                                        'PLAIN' => 'PLAIN',
                                        'LOGIN' => 'LOGIN (Recomendado)',
                                        'CRAM-MD5' => 'CRAM-MD5',
                                    ])
                                    ->default('LOGIN')
                                    ->helperText('Método de autenticación SMTP'),
                                
                                Forms\Components\TextInput::make('settings.username')
                                    ->label('Usuario')
                                    ->required()
                                    ->placeholder('912b5d949c4fb4')
                                    ->helperText('Tu username de Mailtrap')
                                    ->rules(['required_if:driver,smtp']),
                            ]),
                        
                        Forms\Components\TextInput::make('settings.password')
                            ->label('Contraseña')
                            ->password()
                            ->placeholder('Tu password de Mailtrap')
                            ->helperText('Para el ejemplo usar: 1573941d01c7fe')
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                            ->required()
                            ->rules(['required_if:driver,smtp']),
                    ])
                    ->description('Configuración para servidores SMTP. Para pruebas rápidas, usa el botón "Cargar configuración Mailtrap" con las credenciales proporcionadas.')
                    ->visible(fn (Get $get): bool => $get('driver') === 'smtp'),

                Forms\Components\Section::make('Configuración Mailgun')
                    ->schema([
                        Forms\Components\TextInput::make('settings.domain')
                            ->label('Dominio')
                            ->required()
                            ->placeholder('mg.example.com')
                            ->rules(['required_if:driver,mailgun']),
                        
                        Forms\Components\TextInput::make('settings.secret')
                            ->label('API Key')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                            ->required(),
                        
                        Forms\Components\TextInput::make('settings.endpoint')
                            ->label('Endpoint')
                            ->default('api.mailgun.net')
                            ->placeholder('api.mailgun.net'),
                    ])
                    ->visible(fn (Get $get): bool => $get('driver') === 'mailgun'),

                Forms\Components\Section::make('Configuración Postmark')
                    ->schema([
                        Forms\Components\TextInput::make('settings.token')
                            ->label('Server Token')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                            ->required(),
                    ])
                    ->visible(fn (Get $get): bool => $get('driver') === 'postmark'),

                Forms\Components\Section::make('Configuración Amazon SES')
                    ->schema([
                        Forms\Components\TextInput::make('settings.key')
                            ->label('Access Key ID')
                            ->required(),
                        
                        Forms\Components\TextInput::make('settings.secret')
                            ->label('Secret Access Key')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? $state : null)
                            ->required(),
                        
                        Forms\Components\Select::make('settings.region')
                            ->label('Región')
                            ->options([
                                'us-east-1' => 'US East (N. Virginia)',
                                'us-west-2' => 'US West (Oregon)',
                                'eu-west-1' => 'Europe (Ireland)',
                                'eu-central-1' => 'Europe (Frankfurt)',
                                'ap-southeast-1' => 'Asia Pacific (Singapore)',
                                'ap-southeast-2' => 'Asia Pacific (Sydney)',
                                'ap-northeast-1' => 'Asia Pacific (Tokyo)',
                            ])
                            ->default('us-east-1')
                            ->required(),
                    ])
                    ->visible(fn (Get $get): bool => $get('driver') === 'ses'),

                Forms\Components\Section::make('Configuración Sendmail')
                    ->schema([
                        Forms\Components\TextInput::make('settings.path')
                            ->label('Ruta de Sendmail')
                            ->default('/usr/sbin/sendmail -bs')
                            ->required(),
                    ])
                    ->visible(fn (Get $get): bool => $get('driver') === 'sendmail'),

                Forms\Components\Section::make('Configuración del Remitente')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('settings.from_address')
                                    ->label('Email del remitente')
                                    ->email()
                                    ->required()
                                    ->placeholder('noreply@example.com'),
                                
                                Forms\Components\TextInput::make('settings.from_name')
                                    ->label('Nombre del remitente')
                                    ->required()
                                    ->placeholder('Mi Aplicación'),
                            ]),
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
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('driver')
                    ->label('Proveedor')
                    ->searchable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'smtp' => 'gray',
                        'mailgun' => 'warning',
                        'postmark' => 'success',
                        'ses' => 'info',
                        'sendmail' => 'danger',
                        default => 'gray',
                    }),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                    
                Tables\Columns\TextColumn::make('last_tested_at')
                    ->label('Última prueba')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('Nunca'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('driver')
                    ->label('Proveedor')
                    ->options([
                        'smtp' => 'SMTP',
                        'mailgun' => 'Mailgun',
                        'postmark' => 'Postmark',
                        'ses' => 'Amazon SES',
                        'sendmail' => 'Sendmail',
                    ]),
                    
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas')
                    ->nullable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Ver'),
                    
                Tables\Actions\Action::make('test')
                    ->label('Probar')
                    ->icon('heroicon-o-envelope')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('test_email')
                            ->label('Email de prueba')
                            ->email()
                            ->required()
                            ->placeholder('test@example.com'),
                    ])
                    ->action(function (array $data, EmailConfiguration $record): void {
                        try {
                            // Apply configuration temporarily
                            $record->applyConfiguration();
                            
                            // Send test email
                            Mail::to($data['test_email'])->send(new TestEmail(
                                'Este es un email de prueba enviado desde la configuración "' . $record->name . '". Si recibes este mensaje, la configuración está funcionando correctamente.'
                            ));
                            
                            // Mark as tested
                            $record->markAsTested();
                            
                            Notification::make()
                                ->success()
                                ->title('Email enviado correctamente')
                                ->body("El email de prueba ha sido enviado a {$data['test_email']}")
                                ->send();
                                
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error al enviar email')
                                ->body('No se pudo enviar el email de prueba: ' . $e->getMessage())
                                ->send();
                        }
                    }),
                    
                Tables\Actions\Action::make('toggle_active')
                    ->label(fn (EmailConfiguration $record): string => $record->is_active ? 'Desactivar' : 'Activar')
                    ->icon(fn (EmailConfiguration $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (EmailConfiguration $record): string => $record->is_active ? 'danger' : 'success')
                    ->action(function (EmailConfiguration $record): void {
                        if ($record->is_active) {
                            $record->update(['is_active' => false]);
                            
                            Notification::make()
                                ->success()
                                ->title('Configuración desactivada')
                                ->body('La configuración ha sido desactivada correctamente.')
                                ->send();
                        } else {
                            $record->activate();
                            
                            Notification::make()
                                ->success()
                                ->title('Configuración activada')
                                ->body('La configuración ha sido activada correctamente.')
                                ->send();
                        }
                    }),
                    
                Tables\Actions\EditAction::make()
                    ->label('Editar'),
                    
                Tables\Actions\DeleteAction::make()
                    ->label('Eliminar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Eliminar seleccionadas'),
                        
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar seleccionadas')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records): void {
                            // Deactivate all first
                            EmailConfiguration::query()->update(['is_active' => false]);
                            
                            // Activate selected (only the first one if multiple)
                            $records->first()->update(['is_active' => true]);
                            
                            Notification::make()
                                ->success()
                                ->title('Configuración activada')
                                ->body('Solo la primera configuración seleccionada ha sido activada.')
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                        
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar seleccionadas')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records): void {
                            $records->each->update(['is_active' => false]);
                            
                            Notification::make()
                                ->success()
                                ->title('Configuraciones desactivadas')
                                ->body('Las configuraciones seleccionadas han sido desactivadas.')
                                ->send();
                        })
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
            'index' => Pages\ListEmailConfigurations::route('/'),
            'create' => Pages\CreateEmailConfiguration::route('/create'),
            'view' => Pages\ViewEmailConfiguration::route('/{record}'),
            'edit' => Pages\EditEmailConfiguration::route('/{record}/edit'),
        ];
    }
}
