<?php

namespace App\Filament\Resources\EmailConfigurationResource\Pages;

use App\Filament\Resources\EmailConfigurationResource;
use App\Models\EmailConfiguration;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEmailConfiguration extends CreateRecord
{
    protected static string $resource = EmailConfigurationResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // If this configuration should be active, deactivate others
        if ($data['is_active'] ?? false) {
            EmailConfiguration::query()->update(['is_active' => false]);
        }
        
        return $data;
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
