<?php

namespace App\Filament\Pages;

use App\Services\BackupService;
use App\Settings\BackupSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Facades\Storage;

class BackupConfiguration extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Respaldos';
    protected static ?string $title = 'Configuración de Backup';
    protected static ?string $navigationLabel = 'Backup';
    protected static ?int $navigationSort = 1;

    protected static string $settings = BackupSettings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración de Google Drive')
                    ->description('Configura la integración con Google Drive para almacenar backups en la nube')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Checkbox::make('google_drive_enabled')
                                    ->label('Habilitar Google Drive')
                                    ->helperText('Activa el almacenamiento automático de backups en Google Drive')
                                    ->live(),

                                TextInput::make('google_drive_folder_name')
                                    ->label('Nombre de Carpeta')
                                    ->placeholder('Laravel Backups')
                                    ->default('Laravel Backups')
                                    ->helperText('Nombre de la carpeta donde se almacenarán los backups'),
                            ]),

                        FileUpload::make('google_drive_service_account_path')
                            ->label('Archivo de Cuenta de Servicio')
                            ->acceptedFileTypes(['application/json'])
                            ->disk('local')
                            ->directory('private')
                            ->visibility('private')
                            ->maxSize(1024)
                            ->helperText('Sube el archivo JSON de credenciales de la cuenta de servicio de Google')
                            ->storeFileNamesIn('google_drive_service_account_original_name')
                            ->rules([
                                'file',
                                'mimes:json',
                                'max:1024',
                                function ($attribute, $value, $fail) {
                                    if ($value && $value instanceof \Illuminate\Http\UploadedFile) {
                                        // Validate JSON structure
                                        $content = file_get_contents($value->getRealPath());
                                        $json = json_decode($content, true);
                                        
                                        if (json_last_error() !== JSON_ERROR_NONE) {
                                            $fail('El archivo debe ser un JSON válido.');
                                            return;
                                        }
                                        
                                        // Validate required Google Service Account fields
                                        $requiredFields = ['type', 'project_id', 'private_key_id', 'private_key', 'client_email', 'client_id', 'auth_uri', 'token_uri'];
                                        foreach ($requiredFields as $field) {
                                            if (!isset($json[$field])) {
                                                $fail("El archivo JSON debe contener el campo requerido: {$field}");
                                                return;
                                            }
                                        }
                                        
                                        // Validate that it's a service account
                                        if (($json['type'] ?? '') !== 'service_account') {
                                            $fail('El archivo debe ser de una cuenta de servicio de Google.');
                                            return;
                                        }
                                    }
                                },
                            ])
                            ->visible(fn (callable $get) => $get('google_drive_enabled')),

                        TextInput::make('google_drive_folder_id')
                            ->label('ID de Carpeta de Google Drive')
                            ->placeholder('1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgvE2upms')
                            ->helperText('ID de la carpeta donde se almacenarán los backups (opcional)')
                            ->nullable()
                            ->dehydrateStateUsing(fn ($state) => empty($state) ? null : $state)
                            ->visible(fn (callable $get) => $get('google_drive_enabled')),
                    ])->columns(1),

                Section::make('Configuración de Backup')
                    ->description('Define qué incluir en los backups y cómo estructurarlos')
                    ->schema([
                        TextInput::make('backup_name')
                            ->label('Nombre del Backup')
                            ->required()
                            ->placeholder('SaaS Helpdesk')
                            ->helperText('Nombre identificativo para tus backups'),

                        Grid::make(2)
                            ->schema([
                                Checkbox::make('include_files')
                                    ->label('Incluir Archivos')
                                    ->helperText('Incluye archivos de la aplicación en el backup'),

                                Checkbox::make('include_databases')
                                    ->label('Incluir Base de Datos')
                                    ->helperText('Incluye la base de datos en el backup'),
                            ]),

                        Textarea::make('directories_to_backup')
                            ->label('Directorios a Incluir')
                            ->rows(4)
                            ->placeholder("app\nconfig\ndatabase\nresources\nstorage/app")
                            ->helperText('Un directorio por línea (relativo al directorio raíz)')
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state)
                            ->dehydrateStateUsing(fn ($state) => array_filter(array_map('trim', explode("\n", $state ?? '')))),

                        Textarea::make('exclude_directories')
                            ->label('Directorios a Excluir')
                            ->rows(3)
                            ->placeholder("vendor\nnode_modules\nstorage/logs")
                            ->helperText('Un directorio por línea (relativo al directorio raíz)')
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state)
                            ->dehydrateStateUsing(fn ($state) => array_filter(array_map('trim', explode("\n", $state ?? '')))),

                        Textarea::make('databases_to_backup')
                            ->label('Bases de Datos')
                            ->rows(2)
                            ->placeholder("sqlite\nmysql")
                            ->helperText('Una conexión de base de datos por línea')
                            ->formatStateUsing(fn ($state) => is_array($state) ? implode("\n", $state) : $state)
                            ->dehydrateStateUsing(fn ($state) => array_filter(array_map('trim', explode("\n", $state ?? '')))),
                    ])->columns(1),

                Section::make('Configuración de Retención')
                    ->description('Define cuánto tiempo conservar los backups antes de eliminarlos automáticamente')
                    ->schema([
                        Checkbox::make('delete_old_backups_enabled')
                            ->label('Eliminar Backups Antiguos')
                            ->helperText('Activa la eliminación automática de backups antiguos')
                            ->live(),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('keep_all_backups_for_days')
                                    ->label('Conservar Todos (días)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('7')
                                    ->helperText('Días para conservar todos los backups'),

                                TextInput::make('keep_daily_backups_for_days')
                                    ->label('Conservar Diarios (días)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('16')
                                    ->helperText('Días para conservar backups diarios'),

                                TextInput::make('keep_weekly_backups_for_weeks')
                                    ->label('Conservar Semanales (semanas)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('8')
                                    ->helperText('Semanas para conservar backups semanales'),
                            ])
                            ->visible(fn (callable $get) => $get('delete_old_backups_enabled')),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('keep_monthly_backups_for_months')
                                    ->label('Conservar Mensuales (meses)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('4')
                                    ->helperText('Meses para conservar backups mensuales'),

                                TextInput::make('keep_yearly_backups_for_years')
                                    ->label('Conservar Anuales (años)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->placeholder('2')
                                    ->helperText('Años para conservar backups anuales'),
                            ])
                            ->visible(fn (callable $get) => $get('delete_old_backups_enabled')),
                    ])->columns(1),

                Section::make('Configuración de Notificaciones')
                    ->description('Configura cómo recibir notificaciones sobre el estado de los backups')
                    ->schema([
                        Checkbox::make('notifications_enabled')
                            ->label('Habilitar Notificaciones')
                            ->helperText('Activa las notificaciones de backup')
                            ->live(),

                        Grid::make(2)
                            ->schema([
                                Checkbox::make('notify_on_success')
                                    ->label('Notificar Éxito')
                                    ->helperText('Recibe notificaciones cuando los backups sean exitosos'),

                                Checkbox::make('notify_on_failure')
                                    ->label('Notificar Fallos')
                                    ->helperText('Recibe notificaciones cuando los backups fallen'),
                            ])
                            ->visible(fn (callable $get) => $get('notifications_enabled')),

                        TextInput::make('notification_email')
                            ->label('Email de Notificaciones')
                            ->email()
                            ->placeholder('backup@tudominio.com')
                            ->helperText('Email donde recibir las notificaciones (separar múltiples con comas)')
                            ->nullable()
                            ->dehydrateStateUsing(fn ($state) => empty($state) ? null : $state)
                            ->visible(fn (callable $get) => $get('notifications_enabled')),

                        TextInput::make('slack_webhook_url')
                            ->label('Webhook de Slack')
                            ->url()
                            ->placeholder('https://hooks.slack.com/services/...')
                            ->helperText('URL del webhook de Slack para notificaciones')
                            ->nullable()
                            ->dehydrateStateUsing(fn ($state) => empty($state) ? null : $state)
                            ->visible(fn (callable $get) => $get('notifications_enabled')),
                    ])->columns(1),

                Section::make('Programación de Backups')
                    ->description('Configura la ejecución automática de backups según un horario')
                    ->schema([
                        Checkbox::make('schedule_enabled')
                            ->label('Habilitar Programación')
                            ->helperText('Activa la ejecución automática de backups')
                            ->live(),

                        Grid::make(2)
                            ->schema([
                                Select::make('schedule_frequency')
                                    ->label('Frecuencia')
                                    ->options(BackupSettings::getScheduleFrequencies())
                                    ->placeholder('Selecciona frecuencia')
                                    ->helperText('Con qué frecuencia ejecutar los backups'),

                                TimePicker::make('schedule_time')
                                    ->label('Hora de Ejecución')
                                    ->format('H:i')
                                    ->placeholder('02:00')
                                    ->helperText('Hora a la que ejecutar los backups'),
                            ])
                            ->visible(fn (callable $get) => $get('schedule_enabled')),
                    ])->columns(1),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('testGoogleDrive')
                ->label('Probar Google Drive')
                ->icon('heroicon-o-cloud')
                ->color('info')
                ->action(function (BackupService $backupService) {
                    $result = $backupService->testGoogleDriveConnection();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Conexión exitosa')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error de conexión')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->data['google_drive_enabled'] ?? false),

            Action::make('createGoogleDriveFolder')
                ->label('Crear Carpeta')
                ->icon('heroicon-o-folder-plus')
                ->color('success')
                ->action(function (BackupService $backupService) {
                    $folderName = $this->data['google_drive_folder_name'] ?? 'Laravel Backups';
                    $result = $backupService->createGoogleDriveFolder($folderName);
                    
                    if ($result['success']) {
                        // Update settings with the folder ID
                        $this->data['google_drive_folder_id'] = $result['folder_id'];
                        $this->form->fill($this->data);
                        
                        Notification::make()
                            ->title('Carpeta creada')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error al crear carpeta')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->data['google_drive_enabled'] ?? false),

            Action::make('testNotifications')
                ->label('Probar Notificaciones')
                ->icon('heroicon-o-bell')
                ->color('warning')
                ->action(function (BackupService $backupService) {
                    $result = $backupService->testNotifications();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Notificación enviada')
                            ->body($result['message'])
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error en notificación')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                })
                ->visible(fn () => $this->data['notifications_enabled'] ?? false),
        ];
    }

    protected function afterSave(): void
    {
        // Clear config cache to apply new settings
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        Notification::make()
            ->title('Configuración actualizada')
            ->body('La configuración de backup se ha guardado correctamente.')
            ->success()
            ->send();
    }
}