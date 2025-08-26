<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key',
        'name',
        'subject',
        'content',
        'variables',
        'model_type',
        'model_variables',
        'language',
        'is_active',
        'description',
    ];

    protected $casts = [
        'variables' => 'array',
        'model_variables' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Obtener template por clave y idioma
     */
    public static function getByKey(string $key, string $language = 'es'): ?self
    {
        return static::where('key', $key)
            ->where('language', $language)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Obtener templates por modelo
     */
    public static function getByModel(string $modelType, string $language = 'es'): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('model_type', $modelType)
            ->where('language', $language)
            ->where('is_active', true)
            ->get();
    }

    /**
     * Procesar contenido con variables
     */
    public function processContent(array $variables = []): string
    {
        $content = $this->content ?? '';
        
        // Procesar variables con soporte para anidadas y formateo
        $content = $this->processVariables($content, $variables);
        
        return $content;
    }
    
    /**
     * Procesar variables con soporte para anidadas y formateo
     */
    protected function processVariables(string $content, array $variables): string
    {
        // Primero procesar variables con formateo {{variable|formato}}
        $content = preg_replace_callback('/\{\{([^}|]+)\|([^}]+)\}\}/', function ($matches) use ($variables) {
            $varPath = trim($matches[1]);
            $format = trim($matches[2]);
            $value = $this->getNestedValue($variables, $varPath);
            
            // Si el valor está vacío, usar valor por defecto antes del formateo
            if ($value === null || $value === '') {
                $defaultValue = $this->getDefaultValueForVariable($varPath);
                if ($defaultValue !== null) {
                    $value = $defaultValue;
                }
            }
            
            // Aplicar formateo
            if ($value !== null && $value !== '') {
                return $this->formatValue($value, $format);
            }
            
            return $matches[0];
        }, $content);
        
        // Luego procesar variables normales y anidadas {{variable}} o {{variable.nested}}
        $content = preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($variables) {
            $varPath = trim($matches[1]);
            $value = $this->getNestedValue($variables, $varPath);
            
            if ($value !== null && $value !== '') {
                if (is_array($value)) {
                    return json_encode($value);
                }
                return (string) $value;
            }
            
            // Si la variable está vacía o es nula, usar valores por defecto
            $defaultValue = $this->getDefaultValueForVariable($varPath);
            if ($defaultValue !== null) {
                return $defaultValue;
            }
            
            // Si no hay valor por defecto, devolver la variable original sin procesar
            return $matches[0];
        }, $content);
        
        return $content;
    }
    
    /**
     * Obtener valor anidado de un array usando notación de puntos
     */
    protected function getNestedValue(array $array, string $path)
    {
        $keys = explode('.', $path);
        $value = $array;
        
        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } elseif (is_object($value) && property_exists($value, $key)) {
                $value = $value->{$key};
            } else {
                return null;
            }
        }
        
        return $value;
    }
    
    /**
     * Formatear valor según el formato especificado
     */
    protected function formatValue($value, string $format): string
    {
        // Formateo de fechas
        if (strpos($format, 'date:') === 0) {
            $dateFormat = substr($format, 5);
            if ($value instanceof \DateTime) {
                return $value->format($dateFormat);
            }
            try {
                return \Carbon\Carbon::parse($value)->format($dateFormat);
            } catch (\Exception $e) {
                return (string) $value;
            }
        }
        
        // Formateo de números
        if (strpos($format, 'number:') === 0) {
            $decimals = (int) substr($format, 7) ?: 0;
            return number_format((float) $value, $decimals, '.', ',');
        }
        
        // Formateo de moneda
        if (strpos($format, 'currency:') === 0) {
            $currency = substr($format, 9) ?: 'USD';
            return $currency . ' ' . number_format((float) $value, 2, '.', ',');
        }
        
        // Formateo uppercase/lowercase
        if ($format === 'upper') {
            return strtoupper((string) $value);
        }
        if ($format === 'lower') {
            return strtolower((string) $value);
        }
        if ($format === 'capitalize') {
            return ucfirst(strtolower((string) $value));
        }
        
        return (string) $value;
    }

    /**
     * Obtener valor por defecto para una variable específica
     */
    protected function getDefaultValueForVariable(string $varPath): ?string
    {
        // Mapeo de variables a valores por defecto
        $defaults = [
            'document.state' => 'En borrador',
            'document.status' => 'Activo',
            'document.approval_level' => 'Sin nivel',
            'document.approved_by' => 'Sin aprobar',
            'document.rejected_by' => 'No aplica',
            'document.rejection_reason' => 'No aplica',
            'documentation.state' => 'En borrador',
            'documentation.status' => 'Activo',
            'documentation.approval_level' => 'Sin nivel',
            'documentation.approved_by' => 'Sin aprobar',
            'documentation.rejected_by' => 'No aplica',
            'documentation.rejection_reason' => 'No aplica',
            'user.role' => 'Usuario',
            'user.department' => 'No especificado',
            'ticket.assigned_user' => 'Sin asignar',
            'ticket.priority' => 'Media',
            'ticket.due_date' => 'Sin fecha límite',
            'order.delivery_date' => 'Por definir',
            'order.payment_method' => 'No especificado',
            'invoice.due_date' => 'Sin fecha límite',
            'invoice.payment_terms' => '30 días',
        ];
        
        // Verificar si hay un valor por defecto específico
        if (isset($defaults[$varPath])) {
            return $defaults[$varPath];
        }
        
        // Valores por defecto basados en el tipo de campo
        $fieldName = substr(strrchr($varPath, '.'), 1) ?: $varPath;
        
        switch ($fieldName) {
            case 'state':
            case 'status':
                return 'No especificado';
            case 'level':
            case 'approval_level':
                return 'Sin nivel';
            case 'approved_by':
            case 'assigned_user':
            case 'assigned_to':
                return 'Sin asignar';
            case 'rejected_by':
                return 'No aplica';
            case 'rejection_reason':
            case 'reason':
                return 'No aplica';
            case 'due_date':
            case 'delivery_date':
                return 'Por definir';
            case 'priority':
                return 'Media';
            case 'role':
                return 'Usuario';
            case 'department':
            case 'category':
                return 'General';
            case 'payment_method':
                return 'No especificado';
            case 'payment_terms':
                return '30 días';
            default:
                return null;
        }
    }

    /**
     * Procesar asunto con variables
     */
    public function processSubject(array $variables = []): string
    {
        return $this->processVariables($this->subject, $variables);
    }
    
    /**
     * Obtener variables disponibles del modelo
     */
    public function getModelVariables(): array
    {
        if (!$this->model_type) {
            return [];
        }
        
        try {
            $introspectionService = app(\App\Services\ModelIntrospectionService::class);
            $modelInfo = $introspectionService->getModelInfo($this->model_type);
            return $modelInfo['available_variables'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Scope para templates activos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para idioma específico
     */
    public function scopeLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    /**
     * Scope para modelo específico
     */
    public function scopeModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }
}
