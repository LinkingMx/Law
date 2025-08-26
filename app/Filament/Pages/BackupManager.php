<?php

namespace App\Filament\Pages;

use App\Services\BackupService;
use Filament\Actions\Action;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\IconPosition;
use Illuminate\Contracts\Support\Htmlable;

class BackupManager extends Page implements HasInfolists
{
    use InteractsWithInfolists;
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';
    protected static ?string $navigationGroup = 'Respaldos';
    protected static ?string $title = 'Gestión de Backups';
    protected static ?string $navigationLabel = 'Gestión de Backups';
    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.backup-manager';

    public array $statistics = [];

    public function mount(): void
    {
        $this->loadStatistics();
    }

    protected function getBackupService(): BackupService
    {
        return app(BackupService::class);
    }

    protected function loadStatistics(): void
    {
        $this->statistics = $this->getBackupService()->getBackupStatistics();
    }

    public function getTitle(): string|Htmlable
    {
        return 'Gestión de Backups';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('executeBackup')
                ->label('Ejecutar Backup')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Ejecutar Backup')
                ->modalDescription('¿Estás seguro de que quieres ejecutar un backup ahora? Este proceso puede tomar varios minutos.')
                ->modalSubmitActionLabel('Ejecutar')
                ->action(function () {
                    $result = $this->getBackupService()->executeBackup();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Backup ejecutado')
                            ->body('El backup se ha ejecutado correctamente.')
                            ->success()
                            ->persistent()
                            ->send();
                        
                        // Refresh statistics
                        $this->loadStatistics();
                    } else {
                        Notification::make()
                            ->title('Error en backup')
                            ->body($result['message'])
                            ->danger()
                            ->persistent()
                            ->send();
                    }
                }),

            Action::make('cleanOldBackups')
                ->label('Limpiar Backups')
                ->icon('heroicon-o-trash')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Limpiar Backups Antiguos')
                ->modalDescription('¿Estás seguro de que quieres eliminar los backups antiguos según la configuración de retención?')
                ->modalSubmitActionLabel('Limpiar')
                ->action(function () {
                    $result = $this->getBackupService()->cleanOldBackups();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Limpieza completada')
                            ->body('Los backups antiguos han sido eliminados según la configuración.')
                            ->success()
                            ->send();
                        
                        // Refresh statistics
                        $this->loadStatistics();
                    } else {
                        Notification::make()
                            ->title('Error en limpieza')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('refresh')
                ->label('Actualizar')
                ->icon('heroicon-o-arrow-path')
                ->size(ActionSize::Small)
                ->action(function () {
                    $this->loadStatistics();
                    
                    Notification::make()
                        ->title('Estadísticas actualizadas')
                        ->body('Las estadísticas de backup han sido actualizadas.')
                        ->success()
                        ->send();
                }),

            Action::make('testNotifications')
                ->label('Probar Notificaciones')
                ->icon('heroicon-o-bell')
                ->color('info')
                ->size(ActionSize::Small)
                ->action(function () {
                    $result = $this->getBackupService()->testNotifications();
                    
                    if ($result['success']) {
                        Notification::make()
                            ->title('Notificación de prueba enviada')
                            ->body('Las notificaciones están funcionando correctamente.')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Error en notificaciones')
                            ->body($result['message'])
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function statisticsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([
                'statistics' => $this->statistics,
                'total_backups' => $this->statistics['total_backups'] ?? 0,
                'total_size' => $this->statistics['total_size'] ?? 0,
                'latest_backup' => $this->statistics['latest_backup'] ?? null,
                'oldest_backup' => $this->statistics['oldest_backup'] ?? null,
                'local_count' => $this->statistics['by_disk']['local']['count'] ?? 0,
                'local_size' => $this->statistics['by_disk']['local']['size'] ?? 0,
                'google_count' => $this->statistics['by_disk']['google']['count'] ?? 0,
                'google_size' => $this->statistics['by_disk']['google']['size'] ?? 0,
            ])
            ->schema([
                Section::make('Resumen General')
                    ->description('Estadísticas generales de todos los backups disponibles')
                    ->headerActions([
                        InfolistAction::make('refresh')
                            ->label('Actualizar')
                            ->icon('heroicon-o-arrow-path')
                            ->size(ActionSize::Small)
                            ->action(function () {
                                $this->loadStatistics();
                                Notification::make()
                                    ->title('Estadísticas actualizadas')
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->schema([
                        Grid::make(4)
                            ->schema([
                                TextEntry::make('total_backups')
                                    ->label('Total de Backups')
                                    ->icon('heroicon-o-circle-stack')
                                    ->badge()
                                    ->color('primary'),

                                TextEntry::make('total_size')
                                    ->label('Tamaño Total')
                                    ->icon('heroicon-o-server')
                                    ->formatStateUsing(fn ($state) => $this->formatBytes($state ?? 0))
                                    ->badge()
                                    ->color('success'),

                                TextEntry::make('latest_backup')
                                    ->label('Último Backup')
                                    ->icon('heroicon-o-clock')
                                    ->getStateUsing(function () {
                                        return $this->statistics['latest_backup'] 
                                            ? $this->statistics['latest_backup']->diffForHumans()
                                            : 'Sin backups';
                                    })
                                    ->badge()
                                    ->color('warning'),

                                TextEntry::make('oldest_backup')
                                    ->label('Backup Más Antiguo')
                                    ->icon('heroicon-o-calendar-days')
                                    ->getStateUsing(function () {
                                        return $this->statistics['oldest_backup'] 
                                            ? $this->statistics['oldest_backup']->diffForHumans()
                                            : 'Sin backups';
                                    })
                                    ->badge()
                                    ->color('gray'),
                            ]),
                    ]),

                Section::make('Distribución por Almacenamiento')
                    ->description('Detalle de backups por tipo de almacenamiento')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Section::make('Almacenamiento Local')
                                    ->description('Backups almacenados localmente en el servidor')
                                    ->icon('heroicon-o-computer-desktop')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('local_count')
                                                    ->label('Cantidad')
                                                    ->icon('heroicon-o-document-duplicate')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('local_size')
                                                    ->label('Tamaño')
                                                    ->icon('heroicon-o-server')
                                                    ->formatStateUsing(fn ($state) => $this->formatBytes($state ?? 0))
                                                    ->badge()
                                                    ->color('info'),
                                            ]),
                                    ]),

                                Section::make('Google Drive')
                                    ->description('Backups almacenados en Google Drive')
                                    ->icon('heroicon-o-cloud')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('google_count')
                                                    ->label('Cantidad')
                                                    ->icon('heroicon-o-document-duplicate')
                                                    ->badge()
                                                    ->color('success'),

                                                TextEntry::make('google_size')
                                                    ->label('Tamaño')
                                                    ->icon('heroicon-o-server')
                                                    ->formatStateUsing(fn ($state) => $this->formatBytes($state ?? 0))
                                                    ->badge()
                                                    ->color('info'),
                                            ]),
                                    ]),
                            ]),
                    ]),

                Section::make('Acciones Rápidas')
                    ->description('Acciones de gestión y navegación')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('view_history')
                                    ->label('')
                                    ->getStateUsing(fn () => 'Ver Historial Completo')
                                    ->icon('heroicon-o-clock')
                                    ->color('primary')
                                    ->url(fn () => route('filament.admin.pages.backup-history'))
                                    ->openUrlInNewTab(false),

                                TextEntry::make('backup_settings')
                                    ->label('')
                                    ->getStateUsing(fn () => 'Configurar Backups')
                                    ->icon('heroicon-o-cog-6-tooth')
                                    ->color('warning')
                                    ->url(fn () => route('filament.admin.pages.backup-configuration'))
                                    ->openUrlInNewTab(false),

                                TextEntry::make('backup_notifications')
                                    ->label('')
                                    ->getStateUsing(fn () => 'Configurar Notificaciones')
                                    ->icon('heroicon-o-bell')
                                    ->color('info')
                                    ->url(fn () => route('filament.admin.pages.backup-configuration'))
                                    ->openUrlInNewTab(false),
                            ]),
                    ]),
            ]);
    }

    public function refreshStatistics(): void
    {
        $this->loadStatistics();
        
        Notification::make()
            ->title('Estadísticas actualizadas')
            ->body('Las estadísticas de backup han sido actualizadas.')
            ->success()
            ->send();
    }

    public function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}