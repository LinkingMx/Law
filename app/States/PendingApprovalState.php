<?php

namespace App\States;

class PendingApprovalState extends DocumentationState
{
    public function getStateName(): string
    {
        return 'pending_approval';
    }

    public function getColor(): string
    {
        return 'info';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-clock';
    }

    public function getDescription(): string
    {
        return 'Documento enviado para aprobación, esperando revisión';
    }
}