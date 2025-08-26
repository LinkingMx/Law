# Sistema de Condiciones de Workflow con Spatie Model States

## 🎯 **Nuevas Condiciones de Estado**

### **1. Condiciones de Estado (Recomendadas)**

#### **Estado Origen**
- Ejecutar solo si el modelo viene de un estado específico
- Ejemplo: Solo ejecutar si viene de "Borrador"
```php
'state_conditions' => [
    'from_state' => 'draft'
]
```

#### **Estado Destino**  
- Ejecutar solo si el modelo va hacia un estado específico
- Ejemplo: Solo ejecutar si va hacia "Pendiente de Aprobación"
```php
'state_conditions' => [
    'to_state' => 'pending_approval'
]
```

#### **Transición Específica**
- Ejecutar solo en una transición específica
- Ejemplo: Solo ejecutar en la transición "Aprobar"
```php
'state_conditions' => [
    'transition_name' => 'approve'
]
```

#### **Combinaciones**
```php
'state_conditions' => [
    'from_state' => 'pending_approval',
    'to_state' => 'approved',
    'transition_name' => 'approve'
]
```

### **2. Condiciones de Campo Mejoradas**

#### **Campo de Estado Spatie**
```php
'field_conditions' => [
    [
        'field' => 'state',
        'operator' => '=',
        'value' => 'draft'
    ]
]
```

#### **Campo Status Legacy (Compatibilidad)**
```php
'field_conditions' => [
    [
        'field' => 'status', 
        'operator' => '=',
        'value' => 'pending_approval'
    ]
]
```

## 🔧 **Contexto Disponible en Transiciones**

Cuando se dispara un evento de transición de estado, el contexto incluye:

```php
$context = [
    'trigger_event' => 'state_changed',
    'transition_id' => 1,
    'transition_name' => 'approve',
    'transition_label' => 'Aprobar',
    'from_state_id' => 1,
    'to_state_id' => 3,
    'from_state_name' => 'pending_approval',
    'to_state_name' => 'approved',
    'from_state_label' => 'Pendiente de Aprobación',
    'to_state_label' => 'Aprobado',
    // ... más contexto
];
```

## 📊 **Ejemplos de Uso**

### **Ejemplo 1: Notificación al Enviar para Aprobación**
```php
// Evento de trigger: state_changed
// Condiciones del paso:
'conditions' => [
    'state_conditions' => [
        'to_state' => 'pending_approval'
    ]
]
```

### **Ejemplo 2: Notificación Solo al Aprobar**
```php
// Evento de trigger: state_transition_approve
// Sin condiciones adicionales necesarias
```

### **Ejemplo 3: Acción Solo de Borrador a Publicado**
```php
// Evento de trigger: state_changed
// Condiciones del paso:
'conditions' => [
    'state_conditions' => [
        'from_state' => 'draft',
        'to_state' => 'published'
    ]
]
```

### **Ejemplo 4: Workflow Complejo con Múltiples Condiciones**
```php
// Evento de trigger: state_changed
// Condiciones del paso:
'conditions' => [
    'trigger_events' => ['state_changed'],
    'state_conditions' => [
        'from_state' => 'pending_approval',
        'to_state' => 'rejected'
    ],
    'field_conditions' => [
        [
            'field' => 'created_by',
            'operator' => '!=',
            'value' => '1' // No ejecutar si lo creó el admin
        ]
    ]
]
```

## ✅ **Beneficios del Sistema Actualizado**

1. **🎯 Condiciones Específicas de Estado**: Control granular sobre cuándo ejecutar pasos
2. **🔄 Compatibilidad**: Funciona tanto con Spatie States como con status legacy
3. **📊 Contexto Rico**: Acceso completo a información de transición
4. **⚡ Flexibilidad**: Combinación de múltiples tipos de condiciones
5. **🛠️ Mantenibilidad**: Condiciones claras y fáciles de entender

## 🎮 **Configuración en la Interfaz**

En `/admin/advanced-workflows/{workflow}/steps`, ahora verás:

### **Sección "Condiciones de Estado"**
- **Estado Origen**: Dropdown con estados disponibles
- **Estado Destino**: Dropdown con estados disponibles  
- **Transición Específica**: Dropdown con transiciones disponibles

### **Sección "Condiciones de Campo (Avanzado)"**
- **Campo**: Incluye opciones especiales como "Estado Actual (Spatie)"
- **Operador**: Todos los operadores tradicionales más operadores de cambio
- **Valor**: Valor a comparar

¡El sistema ahora está completamente alineado con Spatie Model States! 🎉