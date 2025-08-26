<?php

namespace App\Services;

use App\Services\AdvancedWorkflowEngine;

/**
 * Alias para mantener compatibilidad
 * Ahora WorkflowEngine usa directamente el sistema avanzado
 */
class WorkflowEngine extends AdvancedWorkflowEngine
{
    // Esta clase es ahora solo un alias para AdvancedWorkflowEngine
    // Mantiene la compatibilidad con código existente que use WorkflowEngine
}