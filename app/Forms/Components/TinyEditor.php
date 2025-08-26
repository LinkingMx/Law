<?php

namespace App\Forms\Components;

use Filament\Forms\Components\Field;

class TinyEditor extends Field
{
    protected string $view = 'forms.components.tiny-editor';
    
    protected array|null $modelVariables = null;
    
    protected string|null $modelType = null;
    
    protected int $height = 500;
    
    protected array $plugins = [
        'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
        'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
        'insertdatetime', 'media', 'table', 'help', 'wordcount', 'template',
        'codesample', 'emoticons', 'hr', 'pagebreak', 'nonbreaking',
        'visualchars', 'directionality', 'paste', 'textcolor', 'colorpicker',
        'textpattern', 'imagetools', 'toc', 'noneditable', 'quickbars'
    ];
    
    protected array $toolbar = [
        'undo redo | blocks | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
        'forecolor backcolor | fontfamily fontsize | link image media | template code | fullscreen preview'
    ];
    
    protected array $templates = [];
    
    public function modelVariables(array $variables): static
    {
        $this->modelVariables = $variables;
        return $this;
    }
    
    public function modelType(string $modelType): static
    {
        $this->modelType = $modelType;
        return $this;
    }
    
    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }
    
    public function plugins(array $plugins): static
    {
        $this->plugins = $plugins;
        return $this;
    }
    
    public function toolbar(array $toolbar): static
    {
        $this->toolbar = $toolbar;
        return $this;
    }
    
    public function templates(array $templates): static
    {
        $this->templates = $templates;
        return $this;
    }
    
    public function getModelVariables(): ?array
    {
        return $this->modelVariables;
    }
    
    public function getModelType(): ?string
    {
        return $this->modelType;
    }
    
    public function getHeight(): int
    {
        return $this->height;
    }
    
    public function getPlugins(): array
    {
        return $this->plugins;
    }
    
    public function getToolbar(): array
    {
        return $this->toolbar;
    }
    
    public function getTemplates(): array
    {
        return $this->templates;
    }
}