<?php

namespace App\States;

class PublishedState extends DocumentationState
{
    public function getStateName(): string
    {
        return 'published';
    }

    public function getColor(): string
    {
        return 'success';
    }

    public function getIcon(): string
    {
        return 'heroicon-o-eye';
    }

    public function getDescription(): string
    {
        return 'Documento publicado y visible para todos';
    }
}