<?php

namespace App\Filament\Pages;

use App\Settings\LocalizationSettings as Settings;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\SettingsPage;
use Illuminate\Support\Facades\File;

class LocalizationSettings extends SettingsPage
{
    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    protected static ?string $navigationGroup = 'Configuración';
    protected static ?string $title = 'Configuración de Localización';
    protected static ?string $navigationLabel = 'Localización';
    protected static ?int $navigationSort = 3;

    protected static string $settings = Settings::class;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Idioma y Región')
                    ->description('Configura el idioma y configuraciones regionales de la aplicación')
                    ->schema([
                        Select::make('default_language')
                            ->label('Idioma Predeterminado')
                            ->options([
                                'es' => 'Español',
                                'en' => 'English',
                                'fr' => 'Français',
                                'de' => 'Deutsch',
                                'it' => 'Italiano',
                                'pt' => 'Português',
                            ])
                            ->default('es')
                            ->required()
                            ->helperText('Idioma para toda la aplicación. Se aplica a Laravel app()->locale(). Idioma actual: ' . app()->getLocale()),

                        Select::make('timezone')
                            ->label('Zona Horaria')
                            ->options([
                                'America/Mexico_City' => 'Ciudad de México (GMT-6)',
                                'America/Cancun' => 'Cancún (GMT-5)',
                                'America/Chihuahua' => 'Chihuahua (GMT-7)',
                                'America/Hermosillo' => 'Hermosillo (GMT-7)',
                                'America/Mazatlan' => 'Mazatlán (GMT-7)',
                                'America/Merida' => 'Mérida (GMT-6)',
                                'America/Monterrey' => 'Monterrey (GMT-6)',
                                'America/Tijuana' => 'Tijuana (GMT-8)',
                                'America/New_York' => 'Nueva York (GMT-5)',
                                'America/Los_Angeles' => 'Los Ángeles (GMT-8)',
                                'America/Chicago' => 'Chicago (GMT-6)',
                                'America/Denver' => 'Denver (GMT-7)',
                                'Europe/Madrid' => 'Madrid (GMT+1)',
                                'Europe/London' => 'Londres (GMT+0)',
                                'Europe/Paris' => 'París (GMT+1)',
                                'Asia/Tokyo' => 'Tokio (GMT+9)',
                                'Australia/Sydney' => 'Sídney (GMT+10)',
                            ])
                            ->default('America/Mexico_City')
                            ->required()
                            ->searchable()
                            ->helperText('Se aplica a todo el sistema incluyendo el panel de administración. Ejemplo actual: ' . now()->format('Y-m-d H:i T')),
                    ])->columns(2),

                Section::make('Formatos de Fecha y Hora')
                    ->description('Define cómo se mostrarán las fechas y horas en la aplicación')
                    ->schema([
                        Select::make('date_format')
                            ->label('Formato de Fecha')
                            ->options([
                                'd/m/Y' => '31/12/2024 (DD/MM/YYYY)',
                                'm/d/Y' => '12/31/2024 (MM/DD/YYYY)',
                                'Y-m-d' => '2024-12-31 (YYYY-MM-DD)',
                                'd-m-Y' => '31-12-2024 (DD-MM-YYYY)',
                                'M d, Y' => 'Dec 31, 2024',
                                'd M Y' => '31 Dec 2024',
                                'F j, Y' => 'December 31, 2024',
                                'j F Y' => '31 December 2024',
                            ])
                            ->default('d/m/Y')
                            ->required()
                            ->helperText('Se usa en SettingsHelper para formatear fechas en toda la aplicación. Ejemplo: ' . now()->format('d/m/Y')),

                        Select::make('time_format')
                            ->label('Formato de Hora')
                            ->options([
                                'H:i' => '23:59 (24 horas)',
                                'g:i A' => '11:59 PM (12 horas)',
                                'g:i a' => '11:59 pm (12 horas)',
                                'H:i:s' => '23:59:59 (24 horas con segundos)',
                                'g:i:s A' => '11:59:59 PM (12 horas con segundos)',
                            ])
                            ->default('H:i')
                            ->required()
                            ->helperText('Se usa en SettingsHelper para formatear horas en toda la aplicación. Ejemplo: ' . now()->format('H:i')),
                    ])->columns(2),

                Section::make('Configuración Monetaria')
                    ->description('Configura la moneda y formatos monetarios')
                    ->schema([
                        Select::make('currency')
                            ->label('Moneda Predeterminada')
                            ->options([
                                'MXN' => 'Peso Mexicano (MXN)',
                                'USD' => 'Dólar Estadounidense (USD)',
                                'EUR' => 'Euro (EUR)',
                                'GBP' => 'Libra Esterlina (GBP)',
                                'CAD' => 'Dólar Canadiense (CAD)',
                                'AUD' => 'Dólar Australiano (AUD)',
                                'JPY' => 'Yen Japonés (JPY)',
                                'CHF' => 'Franco Suizo (CHF)',
                                'COP' => 'Peso Colombiano (COP)',
                                'ARS' => 'Peso Argentino (ARS)',
                                'BRL' => 'Real Brasileño (BRL)',
                                'CLP' => 'Peso Chileno (CLP)',
                                'PEN' => 'Sol Peruano (PEN)',
                            ])
                            ->default('MXN')
                            ->required()
                            ->searchable()
                            ->helperText('Moneda para facturación y reportes. Usa SettingsHelper::formatMoney() para formatear valores monetarios.'),
                    ])->columns(1),
            ]);
    }

    protected function afterSave(): void
    {
        // Update .env file with new settings
        $this->updateEnvFile();

        // Apply settings immediately to the current application instance
        $this->applySettingsImmediately();

        // Clear relevant caches
        $this->clearCaches();

        Notification::make()
            ->title('Localización actualizada')
            ->body('La configuración de localización se ha guardado y aplicado correctamente.')
            ->success()
            ->send();
    }

    /**
     * Apply localization settings immediately to the current application instance
     */
    protected function applySettingsImmediately(): void
    {
        try {
            $localizationSettings = app(Settings::class);

            // Apply timezone immediately
            if ($localizationSettings->timezone) {
                config(['app.timezone' => $localizationSettings->timezone]);
                date_default_timezone_set($localizationSettings->timezone);
            }

            // Apply locale immediately
            if ($localizationSettings->default_language) {
                config(['app.locale' => $localizationSettings->default_language]);
                app()->setLocale($localizationSettings->default_language);
            }
        } catch (\Exception $e) {
            // Log error but don't break the save process
            logger()->error('Failed to apply localization settings immediately: ' . $e->getMessage());
        }
    }

    /**
     * Update .env file with new localization settings
     */
    protected function updateEnvFile(): void
    {
        try {
            $localizationSettings = app(Settings::class);

            // Update APP_LOCALE in .env
            if ($localizationSettings->default_language) {
                $this->updateEnvValue('APP_LOCALE', $localizationSettings->default_language);
            }

            // Update APP_TIMEZONE in .env (add if doesn't exist)
            if ($localizationSettings->timezone) {
                $this->updateEnvValue('APP_TIMEZONE', $localizationSettings->timezone);
            }
        } catch (\Exception $e) {
            // Log error but don't break the save process
            logger()->error('Failed to update .env file with localization settings: ' . $e->getMessage());
        }
    }

    /**
     * Safely update a value in the .env file
     */
    protected function updateEnvValue(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!File::exists($envPath)) {
            logger()->warning('.env file not found, skipping environment variable update');
            return;
        }

        // Read the current .env file
        $envContent = File::get($envPath);
        
        // Escape the value if it contains special characters
        $escapedValue = $this->escapeEnvValue($value);
        
        // Pattern to match the key (with or without quotes)
        $pattern = "/^{$key}=.*$/m";
        $replacement = "{$key}={$escapedValue}";

        if (preg_match($pattern, $envContent)) {
            // Key exists, replace it
            $envContent = preg_replace($pattern, $replacement, $envContent);
        } else {
            // Key doesn't exist, add it at the end
            $envContent = rtrim($envContent, "\n") . "\n{$replacement}\n";
        }

        // Write the updated content back to the .env file
        File::put($envPath, $envContent);
    }

    /**
     * Escape environment variable value if needed
     */
    protected function escapeEnvValue(string $value): string
    {
        // If value contains spaces, special characters, or is empty, wrap in quotes
        if (empty($value) || preg_match('/[\s#"\'\\\\]/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }

        return $value;
    }

    /**
     * Clear relevant caches after settings change
     */
    protected function clearCaches(): void
    {
        try {
            // Clear application cache
            \Illuminate\Support\Facades\Artisan::call('cache:clear');
            
            // Clear config cache
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            
            // Clear view cache
            \Illuminate\Support\Facades\Artisan::call('view:clear');
        } catch (\Exception $e) {
            // Log error but don't break the save process
            logger()->error('Failed to clear caches after localization settings change: ' . $e->getMessage());
        }
    }
}