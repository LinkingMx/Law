PRUEBA DE EMAIL - {{ config('app.name') }}
{{ str_repeat('=', 50) }}

✅ ¡Configuración de Email Exitosa!

{{ $testMessage }}

📧 Detalles del envío:
• Fecha: {{ now()->format('d/m/Y H:i:s') }}
• Aplicación: {{ config('app.name') }}
• Configuración: Activa y funcionando

{{ str_repeat('-', 50) }}
Este es un mensaje de prueba automático del sistema.
{{ config('app.name') }} - Sistema de Gestión