<?php

namespace App\States;

class ApprovedState extends DocumentationState
{
    public function getStateName(): string
    {
        return 'approved';
    }

    public function getColor(): string
    {
        return 'success';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-check-circle';
    }

    public function getDescription(): string
    {
        return 'Documento aprobado, listo para publicación';
    }
}