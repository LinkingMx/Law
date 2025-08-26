<?php

namespace App\Console\Commands;

use App\Settings\BackupSettings;
use Illuminate\Console\Command;

class FixBackupSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:fix-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix BackupSettings by converting empty strings to null for nullable fields';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing BackupSettings nullable fields...');

        try {
            $settings = app(BackupSettings::class);
            
            $changed = false;

            // Fix nullable string fields
            $nullableFields = [
                'google_drive_service_account_path',
                'google_drive_service_account_original_name', 
                'google_drive_folder_id',
                'notification_email',
                'slack_webhook_url'
            ];

            foreach ($nullableFields as $field) {
                if (isset($settings->$field) && $settings->$field === '') {
                    $settings->$field = null;
                    $changed = true;
                    $this->line("Fixed field: {$field}");
                }
            }

            if ($changed) {
                $settings->save();
                $this->info('BackupSettings have been fixed and saved.');
            } else {
                $this->info('No changes needed. BackupSettings are already correct.');
            }

        } catch (\Exception $e) {
            $this->error('Error fixing BackupSettings: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}