<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;

class EmailConfiguration extends Model
{
    protected $fillable = [
        'name',
        'driver',
        'settings',
        'is_active',
        'last_tested_at',
    ];

    protected $casts = [
        'settings' => 'encrypted:array',
        'is_active' => 'boolean',
        'last_tested_at' => 'datetime',
    ];

    /**
     * Scope to get only active configurations
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the first active configuration
     */
    public static function getActive(): ?self
    {
        return static::active()->first();
    }

    /**
     * Apply this configuration to the mail settings
     */
    public function applyConfiguration(): void
    {
        $settings = $this->settings ?? [];

        switch ($this->driver) {
            case 'smtp':
                // Handle encryption setting for compatibility
                $encryption = $settings['encryption'] ?? 'tls';
                if ($encryption === 'none') {
                    $encryption = null;
                }
                
                // Actualizar configuración en memoria
                Config::set([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp' => [
                        'transport' => 'smtp',
                        'host' => $settings['host'] ?? '',
                        'port' => intval($settings['port'] ?? 587),
                        'encryption' => $encryption,
                        'username' => $settings['username'] ?? '',
                        'password' => $settings['password'] ?? '',
                        'timeout' => null,
                        'local_domain' => env('MAIL_EHLO_DOMAIN'),
                        // Add auth method if specified (for advanced SMTP configurations)
                        'auth_mode' => $settings['auth_method'] ?? null,
                    ],
                    'mail.from' => [
                        'address' => $settings['from_address'] ?? '',
                        'name' => $settings['from_name'] ?? '',
                    ],
                ]);

                // Actualizar archivo .env físicamente
                $this->updateEnvFile([
                    'MAIL_MAILER' => 'smtp',
                    'MAIL_HOST' => $settings['host'] ?? '',
                    'MAIL_PORT' => $settings['port'] ?? '587',
                    'MAIL_USERNAME' => $settings['username'] ?? '',
                    'MAIL_PASSWORD' => $settings['password'] ?? '',
                    'MAIL_ENCRYPTION' => $encryption ?: 'null',
                    'MAIL_FROM_ADDRESS' => $settings['from_address'] ?? '',
                    'MAIL_FROM_NAME' => '"' . ($settings['from_name'] ?? '') . '"',
                ]);
                break;

            case 'mailgun':
                Config::set([
                    'mail.default' => 'mailgun',
                    'mail.mailers.mailgun' => [
                        'transport' => 'mailgun',
                    ],
                    'services.mailgun' => [
                        'domain' => $settings['domain'] ?? '',
                        'secret' => $settings['secret'] ?? '',
                        'endpoint' => $settings['endpoint'] ?? 'api.mailgun.net',
                        'scheme' => 'https',
                    ],
                    'mail.from' => [
                        'address' => $settings['from_address'] ?? '',
                        'name' => $settings['from_name'] ?? '',
                    ],
                ]);
                break;

            case 'postmark':
                Config::set([
                    'mail.default' => 'postmark',
                    'mail.mailers.postmark' => [
                        'transport' => 'postmark',
                    ],
                    'services.postmark' => [
                        'token' => $settings['token'] ?? '',
                    ],
                    'mail.from' => [
                        'address' => $settings['from_address'] ?? '',
                        'name' => $settings['from_name'] ?? '',
                    ],
                ]);
                break;

            case 'ses':
                Config::set([
                    'mail.default' => 'ses',
                    'mail.mailers.ses' => [
                        'transport' => 'ses',
                    ],
                    'services.ses' => [
                        'key' => $settings['key'] ?? '',
                        'secret' => $settings['secret'] ?? '',
                        'region' => $settings['region'] ?? 'us-east-1',
                    ],
                    'mail.from' => [
                        'address' => $settings['from_address'] ?? '',
                        'name' => $settings['from_name'] ?? '',
                    ],
                ]);
                break;

            case 'sendmail':
                Config::set([
                    'mail.default' => 'sendmail',
                    'mail.mailers.sendmail' => [
                        'transport' => 'sendmail',
                        'path' => $settings['path'] ?? '/usr/sbin/sendmail -bs',
                    ],
                    'mail.from' => [
                        'address' => $settings['from_address'] ?? '',
                        'name' => $settings['from_name'] ?? '',
                    ],
                ]);
                break;
        }
    }

    /**
     * Mark configuration as tested
     */
    public function markAsTested(): void
    {
        $this->update(['last_tested_at' => now()]);
    }

    /**
     * Activate this configuration and deactivate others
     */
    public function activate(): void
    {
        static::query()->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }

    /**
     * Actualizar variables en el archivo .env
     */
    protected function updateEnvFile(array $variables): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);
        
        foreach ($variables as $key => $value) {
            // Escapar caracteres especiales en el valor
            $escapedValue = $this->escapeEnvValue($value);
            
            // Patrón para encontrar la variable existente
            $pattern = "/^{$key}=.*$/m";
            $replacement = "{$key}={$escapedValue}";
            
            if (preg_match($pattern, $envContent)) {
                // Reemplazar variable existente
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                // Agregar nueva variable al final
                $envContent .= "\n{$replacement}";
            }
        }
        
        file_put_contents($envPath, $envContent);
        
        // Limpiar cache de configuración para que Laravel use los nuevos valores
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        \Artisan::call('config:clear');
    }

    /**
     * Escapar valor para archivo .env
     */
    protected function escapeEnvValue($value): string
    {
        // Si el valor es null, devolver "null"
        if ($value === null || $value === 'null') {
            return 'null';
        }
        
        // Si el valor contiene espacios, comillas o caracteres especiales, envolverlo en comillas
        if (preg_match('/[\s"\'#\\\\]/', $value)) {
            return '"' . str_replace('"', '\\"', $value) . '"';
        }
        
        return $value;
    }
}
