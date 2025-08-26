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

class BackupHistory extends Page implements HasInfolists
{
    use InteractsWithInfolists;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Respaldos';
    protected static ?string $navigationLabel = 'Historial de Backups';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.backup-history';

    public array $backups = [];

    public function mount(): void
    {
        $this->loadBackups();
    }

    protected function getBackupService(): BackupService
    {
        return app(BackupService::class);
    }

    protected function loadBackups(): void
    {
        $this->backups = $this->getBackupService()->getBackupList();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Actualizar')
                ->icon('heroicon-o-arrow-path')
                ->size(ActionSize::Small)
                ->action(function () {
                    $this->loadBackups();
                    
                    Notification::make()
                        ->title('Lista actualizada')
                        ->body('La lista de backups ha sido actualizada.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function backupsInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->state([
                'backups' => $this->backups,
                'total_count' => count($this->backups),
                'total_size' => array_sum(array_column($this->backups, 'size')),
            ])
            ->schema([
                Section::make('Resumen de Backups')
                    ->description('Información general sobre los backups disponibles')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('total_count')
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
                                        return !empty($this->backups) 
                                            ? $this->backups[0]['date']->diffForHumans()
                                            : 'Sin backups';
                                    })
                                    ->badge()
                                    ->color('warning'),
                            ]),
                    ]),

                Section::make('Lista de Backups')
                    ->description('Todos los backups disponibles ordenados por fecha (más reciente primero)')
                    ->headerActions([
                        InfolistAction::make('refresh')
                            ->label('Actualizar')
                            ->icon('heroicon-o-arrow-path')
                            ->size(ActionSize::Small)
                            ->action(function () {
                                $this->loadBackups();
                                Notification::make()
                                    ->title('Lista actualizada')
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->schema($this->getBackupEntries()),
            ]);
    }

    protected function getBackupEntries(): array
    {
        if (empty($this->backups)) {
            return [
                TextEntry::make('empty_state')
                    ->label('')
                    ->getStateUsing(fn () => 'No hay backups disponibles')
                    ->icon('heroicon-o-circle-stack')
                    ->color('gray'),
            ];
        }

        $entries = [];
        
        foreach ($this->backups as $index => $backup) {
            $entries[] = Section::make('Backup #' . ($index + 1))
                ->description($backup['name'])
                ->icon('heroicon-o-archive-box')
                ->collapsible()
                ->persistCollapsed()
                ->headerActions([
                    InfolistAction::make("download_{$index}")
                        ->label('Descargar')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('primary')
                        ->size(ActionSize::Small)
                        ->action(function () use ($backup) {
                            return $this->downloadBackup($backup['disk'], $backup['path']);
                        }),

                    InfolistAction::make("validate_{$index}")
                        ->label('Validar')
                        ->icon('heroicon-o-shield-check')
                        ->color('success')
                        ->size(ActionSize::Small)
                        ->action(function () use ($backup) {
                            $this->validateBackup($backup['disk'], $backup['path']);
                        }),

                    InfolistAction::make("delete_{$index}")
                        ->label('Eliminar')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->size(ActionSize::Small)
                        ->requiresConfirmation()
                        ->modalHeading('Eliminar backup')
                        ->modalDescription('¿Estás seguro de que quieres eliminar este backup? Esta acción no se puede deshacer.')
                        ->modalIcon('heroicon-o-exclamation-triangle')
                        ->action(function () use ($backup) {
                            $this->deleteBackup($backup['disk'], $backup['path']);
                        }),
                ])
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make("backup_{$index}_name")
                                ->label('Archivo')
                                ->getStateUsing(fn () => $backup['name'])
                                ->icon('heroicon-o-document')
                                ->copyable(),

                            TextEntry::make("backup_{$index}_disk")
                                ->label('Ubicación')
                                ->getStateUsing(fn () => match ($backup['disk']) {
                                    'local' => 'Local',
                                    'google' => 'Google Drive',
                                    default => $backup['disk'],
                                })
                                ->badge()
                                ->color(fn () => match ($backup['disk']) {
                                    'local' => 'primary',
                                    'google' => 'success',
                                    default => 'gray',
                                })
                                ->icon(fn () => match ($backup['disk']) {
                                    'local' => 'heroicon-o-computer-desktop',
                                    'google' => 'heroicon-o-cloud',
                                    default => 'heroicon-o-question-mark-circle',
                                }),

                            TextEntry::make("backup_{$index}_size")
                                ->label('Tamaño')
                                ->getStateUsing(fn () => $this->formatBytes($backup['size']))
                                ->icon('heroicon-o-server')
                                ->badge()
                                ->color('info'),

                            TextEntry::make("backup_{$index}_date")
                                ->label('Fecha de Creación')
                                ->getStateUsing(fn () => $backup['date']->format('d/m/Y H:i'))
                                ->helperText(fn () => $backup['date']->diffForHumans())
                                ->icon('heroicon-o-calendar-days'),
                        ]),
                ]);
        }

        return $entries;
    }

    public function downloadBackup(string $disk, string $path): \Symfony\Component\HttpFoundation\StreamedResponse|\Illuminate\Http\Response
    {
        $result = $this->getBackupService()->downloadBackup($disk, $path);
        
        if ($result['success']) {
            return response()->streamDownload(function () use ($result) {
                echo $result['content'];
            }, $result['filename'], [
                'Content-Type' => 'application/zip',
            ]);
        } else {
            Notification::make()
                ->title('Error de descarga')
                ->body($result['message'])
                ->danger()
                ->send();
                
            return response()->noContent();
        }
    }

    public function deleteBackup(string $disk, string $path): void
    {
        // Show processing notification
        Notification::make()
            ->title('Eliminando backup...')
            ->body('Por favor espera mientras se elimina el archivo.')
            ->warning()
            ->send();
            
        $result = $this->getBackupService()->deleteBackup($disk, $path);
        
        if ($result['success']) {
            Notification::make()
                ->title('Backup eliminado')
                ->body('El backup ha sido eliminado correctamente.')
                ->success()
                ->send();
            
            // Refresh the backups list
            $this->loadBackups();
        } else {
            Notification::make()
                ->title('Error al eliminar')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function validateBackup(string $disk, string $path): void
    {
        // Show processing notification
        Notification::make()
            ->title('Validando backup...')
            ->body('Verificando la integridad del archivo.')
            ->warning()
            ->send();
            
        $result = $this->getBackupService()->validateBackupIntegrity($disk, $path);
        
        if ($result['success']) {
            Notification::make()
                ->title('Backup válido')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Backup inválido')
                ->body($result['message'])
                ->danger()
                ->send();
        }
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