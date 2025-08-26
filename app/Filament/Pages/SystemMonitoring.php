<?php

namespace App\Filament\Pages;

use Dotswan\FilamentLaravelPulse\Widgets\PulseCache;
use Dotswan\FilamentLaravelPulse\Widgets\PulseExceptions;
use Dotswan\FilamentLaravelPulse\Widgets\PulseQueues;
use Dotswan\FilamentLaravelPulse\Widgets\PulseServers;
use Dotswan\FilamentLaravelPulse\Widgets\PulseSlowQueries;
use Dotswan\FilamentLaravelPulse\Widgets\PulseSlowRequests;
use Filament\Pages\Page;

class SystemMonitoring extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    
    protected static string $view = 'filament.pages.system-monitoring';
    
    protected static ?string $navigationGroup = 'Monitoreo y Logs';
    
    protected static ?string $title = 'Monitoreo del Sistema';
    
    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            PulseServers::class,
            PulseCache::class,
            PulseQueues::class,
            PulseExceptions::class,
            PulseSlowRequests::class,
            PulseSlowQueries::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 2;
    }
}