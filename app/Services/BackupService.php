<?php

namespace App\Services;

use App\Settings\BackupSettings;
use App\Services\BackupNotificationService;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class BackupService
{
    protected BackupSettings $settings;
    protected BackupNotificationService $notificationService;

    public function __construct(BackupSettings $settings, BackupNotificationService $notificationService)
    {
        $this->settings = $settings;
        $this->notificationService = $notificationService;
    }

    /**
     * Test Google Drive connection
     */
    public function testGoogleDriveConnection(): array
    {
        try {
            if (!$this->settings->google_drive_enabled) {
                return [
                    'success' => false,
                    'message' => 'Google Drive está deshabilitado.',
                ];
            }

            $credentials = $this->settings->getGoogleDriveCredentials();
            if (!$credentials) {
                return [
                    'success' => false,
                    'message' => 'No se pudieron cargar las credenciales de Google Drive.',
                ];
            }

            $client = new Client();
            $client->setAuthConfig($credentials);
            $client->addScope(Drive::DRIVE);

            $service = new Drive($client);
            
            // Try to get folder information
            if ($this->settings->google_drive_folder_id) {
                $folder = $service->files->get($this->settings->google_drive_folder_id);
                
                return [
                    'success' => true,
                    'message' => "Conexión exitosa. Carpeta: {$folder->getName()}",
                    'folder_name' => $folder->getName(),
                ];
            } else {
                // Test basic connection
                $service->files->listFiles(['pageSize' => 1]);
                
                return [
                    'success' => true,
                    'message' => 'Conexión exitosa. No se ha configurado una carpeta específica.',
                ];
            }
        } catch (\Exception $e) {
            Log::error('Google Drive connection test failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Create or get Google Drive folder
     */
    public function createGoogleDriveFolder(string $folderName): array
    {
        try {
            $credentials = $this->settings->getGoogleDriveCredentials();
            if (!$credentials) {
                return [
                    'success' => false,
                    'message' => 'No se pudieron cargar las credenciales de Google Drive.',
                ];
            }

            $client = new Client();
            $client->setAuthConfig($credentials);
            $client->addScope(Drive::DRIVE);

            $service = new Drive($client);

            // Check if folder already exists
            $query = "name='{$folderName}' and mimeType='application/vnd.google-apps.folder' and trashed=false";
            $response = $service->files->listFiles(['q' => $query]);

            if (count($response->getFiles()) > 0) {
                $folder = $response->getFiles()[0];
                return [
                    'success' => true,
                    'message' => 'Carpeta encontrada.',
                    'folder_id' => $folder->getId(),
                    'folder_name' => $folder->getName(),
                ];
            }

            // Create new folder
            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $folderName,
                'mimeType' => 'application/vnd.google-apps.folder'
            ]);

            $folder = $service->files->create($fileMetadata, ['fields' => 'id,name']);

            return [
                'success' => true,
                'message' => 'Carpeta creada exitosamente.',
                'folder_id' => $folder->getId(),
                'folder_name' => $folder->getName(),
            ];
        } catch (\Exception $e) {
            Log::error('Google Drive folder creation failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al crear carpeta: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Execute backup
     */
    public function executeBackup(): array
    {
        $startTime = microtime(true);
        
        try {
            // Clear config cache to ensure latest settings are used
            Artisan::call('config:clear');
            
            Log::info('Starting backup execution', [
                'backup_name' => $this->settings->backup_name,
                'include_files' => $this->settings->include_files,
                'include_databases' => $this->settings->include_databases,
            ]);
            
            // Prepare backup command arguments
            $arguments = [];
            if (!$this->settings->include_files) {
                $arguments['--only-db'] = true;
            }
            if (!$this->settings->include_databases) {
                $arguments['--only-files'] = true;
            }
            
            // Run backup command
            $exitCode = Artisan::call('backup:run', $arguments);
            $output = Artisan::output();
            $duration = round(microtime(true) - $startTime, 2);

            if ($exitCode === 0) {
                Log::info('Backup completed successfully', [
                    'duration' => $duration,
                    'exit_code' => $exitCode,
                ]);
                
                // Get backup statistics for notification
                $stats = $this->getLatestBackupInfo();
                $backupInfo = [
                    'duration' => $duration,
                    'size' => $stats['size'] ?? null,
                    'files_count' => $stats['files_count'] ?? null,
                    'databases_count' => $stats['databases_count'] ?? null,
                ];
                
                // Send success notification
                $this->notificationService->sendBackupSuccessful($backupInfo);
                
                return [
                    'success' => true,
                    'message' => 'Backup ejecutado exitosamente.',
                    'output' => $output,
                    'duration' => $duration,
                ];
            } else {
                Log::error('Backup failed with exit code: ' . $exitCode, [
                    'output' => $output,
                    'duration' => $duration,
                ]);
                
                // Send failure notification
                $this->notificationService->sendBackupFailed(
                    'El comando de backup falló con código de salida: ' . $exitCode,
                    [
                        'command' => 'backup:run',
                        'exit_code' => $exitCode,
                        'output' => $output,
                        'duration' => $duration,
                    ]
                );
                
                return [
                    'success' => false,
                    'message' => 'Error al ejecutar backup.',
                    'output' => $output,
                    'exit_code' => $exitCode,
                ];
            }
        } catch (\Exception $e) {
            $duration = round(microtime(true) - $startTime, 2);
            
            Log::error('Backup execution failed: ' . $e->getMessage(), [
                'exception' => $e,
                'duration' => $duration,
            ]);
            
            // Send failure notification
            $this->notificationService->sendBackupFailed(
                $e->getMessage(),
                [
                    'exception_class' => get_class($e),
                    'duration' => $duration,
                ]
            );
            
            return [
                'success' => false,
                'message' => 'Error al ejecutar backup: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get backup list from all configured disks
     */
    public function getBackupList(): array
    {
        $backups = [];
        
        // Get local backups
        try {
            $currentBackupName = $this->settings->backup_name ?? config('backup.backup.name', 'laravel-backup');
            
            // Search in multiple possible directories:
            // 1. Current configured backup name
            // 2. Laravel (default Spatie backup name)
            // 3. Any other directories that might contain backups
            $searchDirectories = [
                $currentBackupName,
                'Laravel',
                'laravel-backup',
            ];
            
            // Remove duplicates and empty values
            $searchDirectories = array_unique(array_filter($searchDirectories));
            
            Log::debug('Looking for backups in local disk', [
                'current_backup_name' => $currentBackupName,
                'search_directories' => $searchDirectories,
                'disk_path' => Storage::disk('local')->path(''),
            ]);
            
            foreach ($searchDirectories as $searchDir) {
                if (Storage::disk('local')->directoryExists($searchDir)) {
                    $localFiles = Storage::disk('local')->files($searchDir);
                    Log::debug('Found backup directory', [
                        'directory' => $searchDir,
                        'file_count' => count($localFiles),
                    ]);
                    
                    foreach ($localFiles as $file) {
                        if (str_ends_with($file, '.zip')) {
                            $backups[] = [
                                'name' => basename($file),
                                'path' => $file,
                                'disk' => 'local',
                                'size' => Storage::disk('local')->size($file),
                                'date' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file)),
                            ];
                        }
                    }
                } else {
                    Log::debug('Backup directory does not exist', [
                        'directory' => $searchDir,
                    ]);
                }
            }
            
            // If no backups found in expected directories, scan all directories for zip files
            if (empty($backups)) {
                Log::debug('No backups found in expected directories, scanning all directories');
                $allDirectories = Storage::disk('local')->directories();
                
                foreach ($allDirectories as $dir) {
                    try {
                        $files = Storage::disk('local')->files($dir);
                        $zipFiles = array_filter($files, fn($file) => str_ends_with($file, '.zip'));
                        
                        if (!empty($zipFiles)) {
                            Log::debug('Found backup files in unexpected directory', [
                                'directory' => $dir,
                                'zip_file_count' => count($zipFiles),
                            ]);
                            
                            foreach ($zipFiles as $file) {
                                $backups[] = [
                                    'name' => basename($file),
                                    'path' => $file,
                                    'disk' => 'local',
                                    'size' => Storage::disk('local')->size($file),
                                    'date' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file)),
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('Failed to scan directory for backups', [
                            'directory' => $dir,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to get local backups: ' . $e->getMessage(), [
                'exception' => $e,
                'backup_name' => $this->settings->backup_name ?? 'unknown',
            ]);
        }

        // Get Google Drive backups if configured
        if ($this->settings->google_drive_enabled && $this->settings->isGoogleDriveConfigured()) {
            try {
                $googleBackups = $this->getGoogleDriveBackups();
                $backups = array_merge($backups, $googleBackups);
            } catch (\Exception $e) {
                Log::error('Failed to get Google Drive backups: ' . $e->getMessage());
            }
        }

        // Sort by date (newest first)
        usort($backups, function ($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return $backups;
    }

    /**
     * Get backups from Google Drive
     */
    protected function getGoogleDriveBackups(): array
    {
        $credentials = $this->settings->getGoogleDriveCredentials();
        if (!$credentials) {
            return [];
        }

        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->addScope(Drive::DRIVE);

        $service = new Drive($client);
        $backups = [];

        $query = "mimeType='application/zip' and trashed=false";
        if ($this->settings->google_drive_folder_id) {
            $query .= " and parents in '{$this->settings->google_drive_folder_id}'";
        }

        $response = $service->files->listFiles([
            'q' => $query,
            'orderBy' => 'modifiedTime desc',
            'fields' => 'files(id,name,size,modifiedTime)',
        ]);

        foreach ($response->getFiles() as $file) {
            $backups[] = [
                'name' => $file->getName(),
                'path' => $file->getId(),
                'disk' => 'google',
                'size' => (int) $file->getSize(),
                'date' => Carbon::parse($file->getModifiedTime()),
            ];
        }

        return $backups;
    }

    /**
     * Delete backup
     */
    public function deleteBackup(string $disk, string $path): array
    {
        try {
            if ($disk === 'google') {
                return $this->deleteGoogleDriveBackup($path);
            } else {
                Storage::disk($disk)->delete($path);
                return [
                    'success' => true,
                    'message' => 'Backup eliminado exitosamente.',
                ];
            }
        } catch (\Exception $e) {
            Log::error("Failed to delete backup {$path} from {$disk}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al eliminar backup: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Delete backup from Google Drive
     */
    protected function deleteGoogleDriveBackup(string $fileId): array
    {
        $credentials = $this->settings->getGoogleDriveCredentials();
        if (!$credentials) {
            return [
                'success' => false,
                'message' => 'No se pudieron cargar las credenciales de Google Drive.',
            ];
        }

        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->addScope(Drive::DRIVE);

        $service = new Drive($client);
        $service->files->delete($fileId);

        return [
            'success' => true,
            'message' => 'Backup de Google Drive eliminado exitosamente.',
        ];
    }

    /**
     * Download backup file
     */
    public function downloadBackup(string $disk, string $path): array
    {
        try {
            if ($disk === 'google') {
                return $this->downloadGoogleDriveBackup($path);
            } else {
                $content = Storage::disk($disk)->get($path);
                return [
                    'success' => true,
                    'content' => $content,
                    'filename' => basename($path),
                ];
            }
        } catch (\Exception $e) {
            Log::error("Failed to download backup {$path} from {$disk}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al descargar backup: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Download backup from Google Drive
     */
    protected function downloadGoogleDriveBackup(string $fileId): array
    {
        $credentials = $this->settings->getGoogleDriveCredentials();
        if (!$credentials) {
            return [
                'success' => false,
                'message' => 'No se pudieron cargar las credenciales de Google Drive.',
            ];
        }

        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->addScope(Drive::DRIVE);

        $service = new Drive($client);
        
        // Get file metadata
        $file = $service->files->get($fileId, ['fields' => 'name']);
        
        // Download file content
        $content = $service->files->get($fileId, ['alt' => 'media']);

        return [
            'success' => true,
            'content' => $content->getBody()->getContents(),
            'filename' => $file->getName(),
        ];
    }

    /**
     * Clean old backups
     */
    public function cleanOldBackups(): array
    {
        try {
            $exitCode = Artisan::call('backup:clean');

            if ($exitCode === 0) {
                return [
                    'success' => true,
                    'message' => 'Limpieza de backups ejecutada exitosamente.',
                    'output' => Artisan::output(),
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Error al limpiar backups.',
                    'output' => Artisan::output(),
                ];
            }
        } catch (\Exception $e) {
            Log::error('Backup cleanup failed: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al limpiar backups: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get backup statistics
     */
    public function getBackupStatistics(): array
    {
        $backups = $this->getBackupList();
        
        $stats = [
            'total_backups' => count($backups),
            'total_size' => 0,
            'latest_backup' => null,
            'oldest_backup' => null,
            'by_disk' => [
                'local' => ['count' => 0, 'size' => 0],
                'google' => ['count' => 0, 'size' => 0],
            ],
        ];

        foreach ($backups as $backup) {
            $stats['total_size'] += $backup['size'];
            $stats['by_disk'][$backup['disk']]['count']++;
            $stats['by_disk'][$backup['disk']]['size'] += $backup['size'];
        }

        if (!empty($backups)) {
            $stats['latest_backup'] = $backups[0]['date'];
            $stats['oldest_backup'] = end($backups)['date'];
        }

        return $stats;
    }

    /**
     * Get information about the latest backup
     */
    protected function getLatestBackupInfo(): array
    {
        try {
            $backups = $this->getBackupList();
            
            if (empty($backups)) {
                return [];
            }
            
            $latest = $backups[0]; // Already sorted by date (newest first)
            
            return [
                'size' => $latest['size'],
                'files_count' => null, // Would need to extract from backup content
                'databases_count' => 1, // Based on configuration
            ];
        } catch (\Exception $e) {
            Log::warning('Failed to get latest backup info: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Test backup notification system
     */
    public function testNotifications(): array
    {
        return $this->notificationService->sendTestNotification();
    }

    /**
     * Validate backup integrity
     */
    public function validateBackupIntegrity(string $disk, string $path): array
    {
        try {
            if ($disk === 'local') {
                // For local files, check if file exists and is readable
                if (!Storage::disk($disk)->exists($path)) {
                    return [
                        'success' => false,
                        'message' => 'El archivo de backup no existe.',
                    ];
                }

                $size = Storage::disk($disk)->size($path);
                if ($size === 0) {
                    return [
                        'success' => false,
                        'message' => 'El archivo de backup está vacío.',
                    ];
                }

                // Basic ZIP validation (check for ZIP signature)
                $content = Storage::disk($disk)->get($path);
                if (substr($content, 0, 4) !== "PK\x03\x04") {
                    return [
                        'success' => false,
                        'message' => 'El archivo no parece ser un ZIP válido.',
                    ];
                }

                return [
                    'success' => true,
                    'message' => 'El archivo de backup parece válido.',
                    'size' => $size,
                ];
            } elseif ($disk === 'google') {
                // For Google Drive, verify file exists and get metadata
                $credentials = $this->settings->getGoogleDriveCredentials();
                if (!$credentials) {
                    return [
                        'success' => false,
                        'message' => 'No se pudieron cargar las credenciales de Google Drive.',
                    ];
                }

                $client = new Client();
                $client->setAuthConfig($credentials);
                $client->addScope(Drive::DRIVE);

                $service = new Drive($client);
                
                try {
                    $file = $service->files->get($path, ['fields' => 'id,name,size,mimeType']);
                    
                    if ($file->getSize() == 0) {
                        return [
                            'success' => false,
                            'message' => 'El archivo de backup en Google Drive está vacío.',
                        ];
                    }

                    return [
                        'success' => true,
                        'message' => 'El archivo de backup en Google Drive parece válido.',
                        'size' => (int) $file->getSize(),
                        'name' => $file->getName(),
                    ];
                } catch (\Exception $e) {
                    return [
                        'success' => false,
                        'message' => 'Error al verificar el archivo en Google Drive: ' . $e->getMessage(),
                    ];
                }
            }

            return [
                'success' => false,
                'message' => 'Tipo de almacenamiento no soportado.',
            ];
        } catch (\Exception $e) {
            Log::error("Failed to validate backup integrity for {$disk}:{$path}: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Error al validar la integridad del backup: ' . $e->getMessage(),
            ];
        }
    }
}