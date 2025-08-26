<?php

namespace App\States;

class ArchivedState extends DocumentationState
{
    public function getStateName(): string
    {
        return 'archived';
    }

    public function getColor(): string
    {
        return 'gray';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-archive-box';
    }

    public function getDescription(): string
    {
        return 'Documento archivado, no está activo';
    }
}