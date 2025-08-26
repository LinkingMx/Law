<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings as Settings;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;

class GeneralSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $title = 'Configuración General';
    protected static ?string $navigationLabel = 'General';
    protected static ?int $navigationSort = 1;

    protected static string $settings = Settings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Aplicación')
                    ->description('Configura la información básica de tu aplicación')
                    ->schema([
                        TextInput::make('app_name')
                            ->label('Nombre de la Aplicación')
                            ->required()
                            ->maxLength(255)
                            ->minLength(2)
                            ->placeholder('Ej: SaaS Helpdesk')
                            ->rule('string')
                            ->helperText('Este nombre se mostrará en el panel de administración de Filament y como marca del sistema.'),

                        Textarea::make('app_description')
                            ->label('Descripción de la Aplicación')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Breve descripción de tu aplicación...')
                            ->helperText('Esta descripción se utiliza para SEO, documentación y presentación pública. No afecta el panel de administración.'),

                        FileUpload::make('app_logo')
                            ->label('Logo de la Aplicación')
                            ->image()
                            ->disk('public')
                            ->directory('logos')
                            ->imageResizeMode('contain')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('800')
                            ->maxSize(5120)
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/svg+xml'])
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '1:1',
                                '4:3',
                                '3:2',
                                '16:9',
                                '2:1',
                            ])
                            ->helperText('Logo de la aplicación con aspecto libre. Formatos soportados: JPG, PNG, WebP, SVG. Tamaño máximo: 5MB. Se redimensionará manteniendo proporciones.')
                            ->downloadable(),
                    ])->columns(1),

                Section::make('Información de Contacto')
                    ->description('Configura la información de contacto que se mostrará en la aplicación')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Email de Contacto')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('soporte@tudominio.com')
                            ->suffixIcon('heroicon-m-envelope')
                            ->helperText('Email utilizado para contacto general. Se puede usar en notificaciones y formularios públicos. No afecta el panel de administración.'),

                        TextInput::make('site_url')
                            ->label('URL del Sitio Web')
                            ->url()
                            ->required()
                            ->maxLength(255)
                            ->placeholder('https://tudominio.com')
                            ->suffixIcon('heroicon-m-globe-alt')
                            ->helperText('URL principal de tu sitio web público. Se utiliza para enlaces externos y referencias. No afecta el panel de administración.'),
                    ])->columns(2),
            ]);
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Configuración actualizada')
            ->body('La configuración general se ha guardado correctamente.')
            ->success()
            ->send();
    }
}