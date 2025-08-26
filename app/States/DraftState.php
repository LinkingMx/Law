<?php

namespace App\States;

class DraftState extends DocumentationState
{
    public function getStateName(): string
    {
        return 'draft';
    }

    public function getColor(): string
    {
        return 'warning';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-document-text';
    }

    public function getDescription(): string
    {
        return 'Documento en borrador, puede ser editado libremente';
    }
}