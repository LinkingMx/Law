<?php

namespace App\Providers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Masbug\Flysystem\GoogleDriveAdapter;
use Google\Client;
use Google\Service\Drive;
use League\Flysystem\Filesystem;

class GoogleDriveServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Storage::extend('google', function ($app, $config) {
            $client = new Client();
            
            // Set up the client with service account credentials
            try {
                // Try to get credentials from backup settings
                if ($app->bound(\App\Settings\BackupSettings::class)) {
                    $backupSettings = $app->make(\App\Settings\BackupSettings::class);
                    
                    if ($backupSettings->google_drive_enabled && $backupSettings->isGoogleDriveConfigured()) {
                        $credentials = $backupSettings->getGoogleDriveCredentials();
                        if ($credentials) {
                            $client->setAuthConfig($credentials);
                            $client->addScope(Drive::DRIVE);
                            
                            $service = new Drive($client);
                            
                            // Use folder ID from settings if available
                            $folderId = $backupSettings->google_drive_folder_id ?: ($config['folderId'] ?? null);
                            $adapter = new GoogleDriveAdapter($service, $folderId);
                            
                            return new Filesystem($adapter);
                        }
                    }
                }
                
                // Fallback to environment configuration if settings not available
                $clientId = $config['clientId'] ?? env('GOOGLE_DRIVE_CLIENT_ID');
                $clientSecret = $config['clientSecret'] ?? env('GOOGLE_DRIVE_CLIENT_SECRET');
                $refreshToken = $config['refreshToken'] ?? env('GOOGLE_DRIVE_REFRESH_TOKEN');
                
                if ($clientId && $clientSecret && $refreshToken) {
                    $client->setClientId($clientId);
                    $client->setClientSecret($clientSecret);
                    $client->refreshToken($refreshToken);
                    $client->addScope(Drive::DRIVE);
                    
                    $service = new Drive($client);
                    $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?? env('GOOGLE_DRIVE_FOLDER_ID'));
                    
                    return new Filesystem($adapter);
                }
                
                // If no valid configuration found, throw an exception
                throw new \Exception('No valid Google Drive configuration found. Please configure either service account credentials in backup settings or environment variables.');
                
            } catch (\Exception $e) {
                // Log the error but don't let it crash the application
                \Log::warning('Google Drive filesystem configuration failed: ' . $e->getMessage());
                
                // Return a basic filesystem that will fail gracefully when used
                throw new \Exception('Google Drive configuration failed: ' . $e->getMessage());
            }
        });
    }
}