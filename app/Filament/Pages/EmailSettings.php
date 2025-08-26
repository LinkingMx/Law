<?php

namespace App\Filament\Pages;

use App\Models\EmailConfiguration;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\View;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Mail\TestEmail;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;

class EmailSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';
    protected static string $view = 'filament.pages.email-settings';
    protected static ?string $title = 'Configuración de Email';
    protected static ?string $navigationLabel = 'Configuración de Email';
    protected static ?string $navigationGroup = 'Correo';
    protected static ?int $navigationSort = 3;
    
    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];
    public ?string $testEmail = '';
    public ?string $testResult = '';

    public function mount(): void
    {
        $activeConfig = EmailConfiguration::getActive();
        
        if ($activeConfig) {
            $this->form->fill([
                'name' => $activeConfig->name,
                'driver' => $activeConfig->driver,
                'is_active' => $activeConfig->is_active,
                ...$activeConfig->settings ?? [],
            ]);
        } else {
            $this->form->fill([
                'driver' => 'smtp',
                'is_active' => false,
            ]);
        }
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración General')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre de la configuración')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Mi configuración SMTP'),
                                
                                Toggle::make('is_active')
                                    ->label('Activar esta configuración')
                                    ->helperText('Solo una configuración puede estar activa a la vez'),
                            ]),
                        
                        Select::make('driver')
                            ->label('Proveedor de Email')
                            ->required()
                            ->options([
                                'smtp' => 'SMTP',
                                'mailgun' => 'Mailgun',
                                'postmark' => 'Postmark',
                                'ses' => 'Amazon SES',
                                'sendmail' => 'Sendmail',
                            ])
                            ->live()
                            ->afterStateUpdated(fn (Set $set) => $set('testResult', '')),
                    ]),

                Section::make('Configuración SMTP')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('host')
                                    ->label('Servidor SMTP')
                                    ->required()
                                    ->placeholder('smtp.gmail.com'),
                                
                                TextInput::make('port')
                                    ->label('Puerto')
                                    ->required()
                                    ->numeric()
                                    ->default(587),
                            ]),
                        
                        Grid::make(2)
                            ->schema([
                                Select::make('encryption')
                                    ->label('Encriptación')
                                    ->options([
                                        'tls' => 'TLS',
                                        'ssl' => 'SSL',
                                        '' => 'Ninguna',
                                    ])
                                    ->default('tls'),
                                
                                TextInput::make('username')
                                    ->label('Usuario')
                                    ->required(),
                            ]),
                        
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(),
                    ])
                    ->visible(fn (Get $get): bool => $get('driver') === 'smtp'),

                Section::make('Configuración Mailgun')
                    ->schema([
                        TextInput::make('domain')
                            ->label('Dominio')
                            ->required()
                            ->placeholder('mg.example.com'),
                        
                        TextInput::make('secret')
                            ->label('API Key')
                            ->password()
                            ->required(),
                        
                        TextInput::make('endpoint')
                            ->label('Endpoint')
                            ->default('api.mailgun.net')
                            ->placeholder('api.mailgun.net'),
                    ])
                    ->visible(fn (Get $get): bool => $get('driver') === 'mailgun'),

                Section::make('Configuración Postmark')
                    ->schema([
                        TextInput::make('token')
                            ->label('Server Token')
                            ->password()
                            ->required(),
                    ])
                    ->visible(fn (Get $get): bool => $get('driver') === 'postmark'),

                Section::make('Configuración Amazon SES')
                    ->schema([
                        TextInput::make('key')
                            ->label('Access Key ID')
                            ->required(),
                        
                        TextInput::make('secret')
                            ->label('Secret Access Key')
                            ->password()
                            ->required(),
                        
                        Select::make('region')
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

                Section::make('Configuración Sendmail')
                    ->schema([
                        TextInput::make('path')
                            ->label('Ruta de Sendmail')
                            ->default('/usr/sbin/sendmail -bs')
                            ->required(),
                    ])
                    ->visible(fn (Get $get): bool => $get('driver') === 'sendmail'),

                Section::make('Configuración del Remitente')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('from_address')
                                    ->label('Email del remitente')
                                    ->email()
                                    ->required()
                                    ->placeholder('noreply@example.com'),
                                
                                TextInput::make('from_name')
                                    ->label('Nombre del remitente')
                                    ->required()
                                    ->placeholder('Mi Aplicación'),
                            ]),
                    ]),

                Section::make('Prueba de Conexión')
                    ->schema([
                        TextInput::make('testEmail')
                            ->label('Email de prueba')
                            ->email()
                            ->placeholder('test@example.com')
                            ->helperText('Ingresa un email válido para enviar una prueba'),
                        
                        View::make('filament.components.test-result')
                            ->viewData([
                                'result' => $this->testResult,
                            ]),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Guardar Configuración')
                ->submit('save'),
            
            Action::make('test')
                ->label('Probar Conexión')
                ->color('warning')
                ->action('testConnection')
                ->requiresConfirmation()
                ->modalHeading('Probar Conexión de Email')
                ->modalDescription('¿Estás seguro de que deseas enviar un email de prueba?')
                ->modalSubmitActionLabel('Enviar Prueba'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        
        // Separate configuration data from form data
        $configData = [
            'name' => $data['name'],
            'driver' => $data['driver'],
            'is_active' => $data['is_active'] ?? false,
        ];

        // Prepare settings based on driver
        $settings = [];
        switch ($data['driver']) {
            case 'smtp':
                $settings = [
                    'host' => $data['host'],
                    'port' => $data['port'],
                    'encryption' => $data['encryption'],
                    'username' => $data['username'],
                    'password' => $data['password'],
                    'from_address' => $data['from_address'],
                    'from_name' => $data['from_name'],
                ];
                break;
            
            case 'mailgun':
                $settings = [
                    'domain' => $data['domain'],
                    'secret' => $data['secret'],
                    'endpoint' => $data['endpoint'],
                    'from_address' => $data['from_address'],
                    'from_name' => $data['from_name'],
                ];
                break;
            
            case 'postmark':
                $settings = [
                    'token' => $data['token'],
                    'from_address' => $data['from_address'],
                    'from_name' => $data['from_name'],
                ];
                break;
            
            case 'ses':
                $settings = [
                    'key' => $data['key'],
                    'secret' => $data['secret'],
                    'region' => $data['region'],
                    'from_address' => $data['from_address'],
                    'from_name' => $data['from_name'],
                ];
                break;
            
            case 'sendmail':
                $settings = [
                    'path' => $data['path'],
                    'from_address' => $data['from_address'],
                    'from_name' => $data['from_name'],
                ];
                break;
        }

        $configData['settings'] = $settings;

        // Find existing active configuration or create new
        $activeConfig = EmailConfiguration::getActive();
        
        if ($activeConfig) {
            $activeConfig->update($configData);
            $config = $activeConfig;
        } else {
            $config = EmailConfiguration::create($configData);
        }

        // If this configuration should be active, deactivate others
        if ($data['is_active'] ?? false) {
            $config->activate();
        }

        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->body('La configuración de email ha sido guardada correctamente.')
            ->send();
    }

    public function testConnection(): void
    {
        $data = $this->form->getState();
        
        if (empty($this->testEmail)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Por favor ingresa un email de prueba válido.')
                ->send();
            return;
        }

        try {
            // Create a temporary configuration to test
            $tempConfig = new EmailConfiguration();
            $tempConfig->driver = $data['driver'];
            
            // Prepare settings
            $settings = [];
            switch ($data['driver']) {
                case 'smtp':
                    $settings = [
                        'host' => $data['host'],
                        'port' => $data['port'],
                        'encryption' => $data['encryption'],
                        'username' => $data['username'],
                        'password' => $data['password'],
                        'from_address' => $data['from_address'],
                        'from_name' => $data['from_name'],
                    ];
                    break;
                
                case 'mailgun':
                    $settings = [
                        'domain' => $data['domain'],
                        'secret' => $data['secret'],
                        'endpoint' => $data['endpoint'],
                        'from_address' => $data['from_address'],
                        'from_name' => $data['from_name'],
                    ];
                    break;
                
                case 'postmark':
                    $settings = [
                        'token' => $data['token'],
                        'from_address' => $data['from_address'],
                        'from_name' => $data['from_name'],
                    ];
                    break;
                
                case 'ses':
                    $settings = [
                        'key' => $data['key'],
                        'secret' => $data['secret'],
                        'region' => $data['region'],
                        'from_address' => $data['from_address'],
                        'from_name' => $data['from_name'],
                    ];
                    break;
                
                case 'sendmail':
                    $settings = [
                        'path' => $data['path'],
                        'from_address' => $data['from_address'],
                        'from_name' => $data['from_name'],
                    ];
                    break;
            }

            $tempConfig->settings = $settings;
            
            // Apply configuration temporarily
            $tempConfig->applyConfiguration();
            
            // Send test email using the TestEmail mailable
            Mail::to($this->testEmail)->send(new TestEmail(
                'Este es un email de prueba enviado desde la configuración de email del sistema. Si recibes este mensaje, la configuración está funcionando correctamente.'
            ));

            $this->testResult = 'success';
            
            // If we have an existing configuration, mark it as tested
            $activeConfig = EmailConfiguration::getActive();
            if ($activeConfig) {
                $activeConfig->markAsTested();
            }

            Notification::make()
                ->success()
                ->title('Email enviado correctamente')
                ->body("El email de prueba ha sido enviado a {$this->testEmail}")
                ->send();

        } catch (\Exception $e) {
            $this->testResult = 'error';
            
            Notification::make()
                ->danger()
                ->title('Error al enviar email')
                ->body('No se pudo enviar el email de prueba: ' . $e->getMessage())
                ->send();
        }
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }
}