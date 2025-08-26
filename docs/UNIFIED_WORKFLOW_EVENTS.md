# Sistema Unificado de Eventos - Workflows + Estados

## 🎯 **Eventos Principales (Simplificados)**

### ✅ **Eventos del Modelo**
- `model_created` - Creación de registro
- `model_updated` - Actualización de registro  
- `model_deleted` - Eliminación de registro

### ✅ **Eventos de Estados (Unificados)**
- `state_changed` - **EVENTO PRINCIPAL** - Cualquier cambio de estado
- `state_transition_{name}` - Transición específica (ej: `state_transition_approve`)
- `changed_to_state_{state}` - Cambio a estado específico (ej: `changed_to_state_published`)

## ❌ **Eventos Eliminados (Redundantes)**

### Eventos Duplicados Removidos:
- ~~`Cambio de estado`~~ → `state_changed`
- ~~`Publicación`~~ → `state_transition_publish` 
- ~~`Archivado`~~ → `state_transition_archive`
- ~~`Rechazo`~~ → `state_transition_reject`
- ~~`Aprobación`~~ → `state_transition_approve`
- ~~`Cambio a estado: Draft`~~ → `changed_to_state_draft`
- ~~`Cambio a estado: Pending_approval`~~ → `changed_to_state_pending_approval`
- ~~`Cambio a estado: Rejected`~~ → `changed_to_state_rejected`
- ~~`Cambio a estado: Published`~~ → `changed_to_state_published`
- ~~`Cambio a estado: Archived`~~ → `changed_to_state_archived`

## 🔧 **Contexto Enriquecido**

Cada evento de transición incluye:

```php
$context = [
    // Información de la transición
    'transition_id' => $transition->id,
    'transition_name' => $transition->name,
    'transition_label' => $transition->label,
    
    // Estados origen y destino
    'from_state_id' => $transition->from_state_id,
    'to_state_id' => $transition->to_state_id,
    'from_state_name' => $fromState->name,
    'to_state_name' => $toState->name,
    'from_state_label' => $fromState->label, 
    'to_state_label' => $toState->label,
    
    // Datos adicionales del usuario
    'user_data' => [...],
    'timestamp' => now(),
];
```

## 📊 **Configuración de Workflows**

### Ejemplo de Workflow Unificado:

```php
AdvancedWorkflow::create([
    'name' => 'Proceso de Aprobación Documentación',
    'model_class' => 'App\Models\Documentation',
    'trigger_event' => 'state_changed', // Evento unificado
    'trigger_conditions' => [
        'to_state_name' => 'pending_approval' // Solo cuando va a pending
    ],
    'steps' => [
        // Notificar a aprobadores
        [
            'type' => 'notification',
            'recipients' => ['approvers'],
            'template' => 'documentation_needs_approval'
        ],
        // Esperar aprobación
        [
            'type' => 'approval',
            'approvers' => ['role:manager'],
            'timeout' => '72_hours'
        ]
    ]
]);
```

## ✅ **Beneficios del Sistema Unificado**

1. **Menor Redundancia**: Un solo evento `state_changed` para todos los cambios
2. **Flexibilidad**: Workflows pueden reaccionar a transiciones específicas o generales
3. **Consistencia**: Todos los cambios pasan por `StateTransitionService`
4. **Auditabilidad**: Spatie Activity Log registra automáticamente todos los cambios
5. **Mantenibilidad**: Un solo lugar para manejar lógica de estados

## 🔄 **Migración de Workflows Existentes**

Los workflows existentes seguirán funcionando, pero se recomienda:

1. Cambiar eventos específicos por `state_changed`
2. Usar `trigger_conditions` para filtrar estados específicos
3. Aprovechar el contexto enriquecido para lógica condicional

## 📝 **Ejemplo de Uso**

```php
// Antes (múltiples eventos)
$this->triggerWorkflow('approval_rejected');
$this->triggerWorkflow('documentation_rejected');
$this->triggerWorkflow('changed_to_rejected');

// Después (evento unificado)
$stateService->executeTransition($model, $rejectTransition, $user, $data);
// Automáticamente dispara:
// - state_changed
// - state_transition_reject
// - changed_to_state_rejected
```