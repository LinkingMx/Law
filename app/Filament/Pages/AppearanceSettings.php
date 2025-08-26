<?php

namespace App\Filament\Pages;

use App\Settings\AppearanceSettings as Settings;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;

class AppearanceSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $title = 'Configuración de Apariencia';
    protected static ?string $navigationLabel = 'Apariencia';
    protected static ?int $navigationSort = 2;

    protected static string $settings = Settings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Tema y Colores')
                    ->description('Personaliza la apariencia visual del panel de administración')
                    ->schema([
                        Select::make('theme')
                            ->label('Tema Principal')
                            ->options([
                                'light' => 'Claro',
                                'dark' => 'Oscuro',
                                'auto' => 'Automático (según preferencia del usuario)',
                            ])
                            ->default('light')
                            ->required()
                            ->helperText('Define el tema para el sitio web público. El panel de administración mantiene su propio sistema de temas.')
                            ->columnSpanFull(),

                        ColorPicker::make('primary_color')
                            ->label('Color Primario')
                            ->helperText('Color principal para botones, enlaces y elementos destacados.')
                            ->default('#f59e0b')
                            ->hex()
                            ->required(),

                        ColorPicker::make('danger_color')
                            ->label('Color de Peligro')
                            ->helperText('Color para acciones destructivas, errores y alertas críticas.')
                            ->default('#ef4444')
                            ->hex()
                            ->required(),

                        ColorPicker::make('gray_color')
                            ->label('Color Gris')
                            ->helperText('Color base para elementos neutros, bordes y texto secundario.')
                            ->default('#71717a')
                            ->hex()
                            ->required(),

                        ColorPicker::make('info_color')
                            ->label('Color de Información')
                            ->helperText('Color para mensajes informativos y elementos auxiliares.')
                            ->default('#3b82f6')
                            ->hex()
                            ->required(),

                        ColorPicker::make('success_color')
                            ->label('Color de Éxito')
                            ->helperText('Color para indicar operaciones exitosas y estados positivos.')
                            ->default('#10b981')
                            ->hex()
                            ->required(),

                        ColorPicker::make('warning_color')
                            ->label('Color de Advertencia')
                            ->helperText('Color para alertas, advertencias y acciones que requieren atención.')
                            ->default('#f59e0b')
                            ->hex()
                            ->required(),
                    ])->columns(3),

                Section::make('Tipografía')
                    ->description('Configura las fuentes utilizadas en la aplicación')
                    ->schema([
                        Select::make('font_family')
                            ->label('Familia de Fuente')
                            ->options([
                                'Inter' => 'Inter (Recomendada)',
                                'Roboto' => 'Roboto',
                                'Open Sans' => 'Open Sans',
                                'Nunito' => 'Nunito',
                                'Poppins' => 'Poppins',
                                'Lato' => 'Lato',
                                'Montserrat' => 'Montserrat',
                                'Source Sans Pro' => 'Source Sans Pro',
                            ])
                            ->default('Inter')
                            ->required()
                            ->helperText('Se aplica al panel de administración de Filament. Para el sitio público, configura las fuentes en el sistema de temas.'),
                    ])->columns(1),

                Section::make('Logos Adicionales')
                    ->description('Configura logos específicos para diferentes modos')
                    ->schema([
                        FileUpload::make('dark_mode_logo')
                            ->label('Logo para Modo Oscuro')
                            ->image()
                            ->disk('public')
                            ->directory('logos')
                            ->imageResizeMode('contain')
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('400')
                            ->imageResizeTargetHeight('225')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/jpg', 'image/png', 'image/webp'])
                            ->imageEditor()
                            ->imageEditorAspectRatios([
                                null,
                                '16:9',
                                '4:3',
                                '1:1',
                            ])
                            ->helperText('Se mostrará en el panel de administración de Filament cuando esté en modo oscuro. Si no se especifica, se usará el logo principal.')
                            ->downloadable(),
                    ])->columns(1),
            ]);
    }

    protected function afterSave(): void
    {
        Notification::make()
            ->title('Apariencia actualizada')
            ->body('La configuración de apariencia se ha guardado correctamente.')
            ->success()
            ->send();
    }
}