<?php

namespace App\Providers\Filament;

use App\Helpers\SettingsHelper;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Datlechin\FilamentMenuBuilder\FilamentMenuBuilderPlugin;
use BezhanSalleh\FilamentExceptions\FilamentExceptionsPlugin;
use Jeffgreco13\FilamentBreezy\BreezyCore;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // Evita acceso a settings si la tabla no existe o si es comando de migración
        $generalSettings = null;
        $appearanceSettings = null;
        $canLoadSettings = true;
        try {
            // No cargar settings si es comando artisan migrate, db:*, etc.
            if (app()->runningInConsole()) {
                $artisanCommands = ['migrate', 'db:', 'queue:', 'seed', 'optimize', 'cache:', 'config:', 'event:', 'schedule:', 'test'];
                $args = implode(' ', $_SERVER['argv'] ?? []);
                foreach ($artisanCommands as $cmd) {
                    if (str_contains($args, $cmd)) {
                        $canLoadSettings = false;
                        break;
                    }
                }
            }
            // Verifica si la tabla settings existe
            if ($canLoadSettings && \Schema::hasTable('settings')) {
                $generalSettings = SettingsHelper::general();
                $appearanceSettings = SettingsHelper::appearance();
            }
        } catch (\Throwable $e) {
            $canLoadSettings = false;
        }

        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->brandName($generalSettings->app_name ?? 'SaaS Helpdesk')
            ->brandLogo($generalSettings && $generalSettings->app_logo ? asset('storage/' . $generalSettings->app_logo) : null)
            ->brandLogoHeight('2rem')
            ->darkModeBrandLogo($appearanceSettings && $appearanceSettings->dark_mode_logo ? asset('storage/' . $appearanceSettings->dark_mode_logo) : null)
            ->login()
            ->registration()
            ->passwordReset()
            ->emailVerification()
            ->colors($appearanceSettings ? SettingsHelper::getFilamentColors() : [
                'primary' => '#f59e0b',
                'danger' => '#ef4444',
                'gray' => '#71717a',
                'info' => '#3b82f6',
                'success' => '#10b981',
                'warning' => '#f59e0b',
            ])
            ->font($appearanceSettings->font_family ?? 'Inter')
            ->navigationGroups([
                'Documentación Legal',
                'Soporte Legal',
                'Workflows Avanzados',
                'Gestión de Usuarios',
                'Comunicaciones',
                'Configuración',
                'Sistema & Backup',
                'Monitoreo y Logs',
                'Shield',
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->resources([
                \Z3d0X\FilamentLogger\Resources\ActivityResource::class,
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')

            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->plugins([
                \RickDBCN\FilamentEmail\FilamentEmail::make(),
                FilamentShieldPlugin::make(),
                FilamentMenuBuilderPlugin::make()
                    ->navigationGroup('Workflows Avanzados')
                    ->navigationLabel('Menús Personalizados'),
                BreezyCore::make()
                    ->myProfile(
                        shouldRegisterUserMenu: true,
                        shouldRegisterNavigation: false,
                        hasAvatars: true
                    )
                    ->enableTwoFactorAuthentication()
                    ->enableSanctumTokens(),
                FilamentExceptionsPlugin::make()
                    ->navigationGroup('Monitoreo y Logs')
                    ->navigationSort(3),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
