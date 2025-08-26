<?php

namespace App\Filament\Resources\StateTransitionResource\Pages;

use App\Filament\Resources\StateTransitionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStateTransitions extends ListRecords
{
    protected static string $resource = StateTransitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
