<?php

namespace App\Filament\Resources\ApprovalStateResource\Pages;

use App\Filament\Resources\ApprovalStateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListApprovalStates extends ListRecords
{
    protected static string $resource = ApprovalStateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
