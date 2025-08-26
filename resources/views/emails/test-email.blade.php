<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Prueba de Email - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
            margin: 0;
            padding: 20px;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div style="text-align: center; padding: 40px 20px;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 30px;">
                <h1 style="margin: 0; font-size: 28px;">🧪 Email de Prueba</h1>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">Configuración de Email</p>
            </div>
            
            <div style="background: #f8fafc; padding: 30px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #10b981;">
                <h2 style="color: #1f2937; margin-top: 0;">✅ ¡Configuración Exitosa!</h2>
                <p style="color: #4b5563; font-size: 16px; line-height: 1.6;">
                    {{ $testMessage }}
                </p>
                
                <div style="background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <p style="color: #065f46; margin: 0; font-size: 14px;">
                        <strong>📧 Detalles del envío:</strong><br>
                        • Fecha: {{ now()->format('d/m/Y H:i:s') }}<br>
                        • Aplicación: {{ config('app.name') }}<br>
                        • Configuración: Activa y funcionando
                    </p>
                </div>
            </div>
            
            <div style="margin-top: 30px; padding: 20px; background: #f9fafb; border-radius: 8px;">
                <p style="color: #6b7280; font-size: 14px; margin: 0;">
                    Este es un mensaje de prueba automático del sistema.<br>
                    <strong>{{ config('app.name') }}</strong> - Sistema de Gestión
                </p>
            </div>
        </div>
    </div>
</body>
</html>