<?php

namespace App\States;

class RejectedState extends DocumentationState
{
    public function getStateName(): string
    {
        return 'rejected';
    }

    public function getColor(): string
    {
        return 'danger';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-x-circle';
    }

    public function getDescription(): string
    {
        return 'Documento rechazado, requiere modificaciones';
    }
}