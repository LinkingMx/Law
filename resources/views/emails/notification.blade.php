<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notificación - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .email-container {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .email-body {
            padding: 30px;
        }
        .notification-type {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 20px;
        }
        .type-info { background: #e3f2fd; color: #1976d2; }
        .type-success { background: #e8f5e8; color: #2e7d32; }
        .type-warning { background: #fff3e0; color: #f57c00; }
        .type-error { background: #ffebee; color: #d32f2f; }
        
        .message-content {
            background: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #667eea;
            margin: 20px 0;
            border-radius: 4px;
        }
        .actions {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin: 0 10px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5a67d8;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .footer {
            padding: 20px 30px;
            background: #f8f9fa;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1 style="margin: 0; font-size: 24px;">{{ $title ?? 'Notificación' }}</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">{{ config('app.name') }}</p>
        </div>
        
        <div class="email-body">
            <div class="notification-type type-{{ $type ?? 'info' }}">
                {{ ucfirst($type ?? 'info') }}
            </div>
            
            <p>Hola <strong>{{ $user->name ?? 'Usuario' }}</strong>,</p>
            
            <div class="message-content">
                <h3 style="margin-top: 0;">{{ $subject ?? 'Nueva notificación' }}</h3>
                <p>{!! $message ?? 'Tienes una nueva notificación en tu cuenta.' !!}</p>
            </div>
            
            @if(isset($actionUrl) && isset($actionText))
            <div class="actions">
                <a href="{{ $actionUrl }}" class="btn">{{ $actionText }}</a>
                @if(isset($secondaryUrl) && isset($secondaryText))
                    <a href="{{ $secondaryUrl }}" class="btn btn-secondary">{{ $secondaryText }}</a>
                @endif
            </div>
            @endif
            
            <p>Si no has solicitado esta notificación, puedes ignorar este email de forma segura.</p>
            
            <hr style="border: none; border-top: 1px solid #dee2e6; margin: 30px 0;">
            
            <p style="font-size: 14px; color: #6c757d;">
                <strong>Detalles adicionales:</strong><br>
                📅 Fecha: {{ now()->format('d/m/Y H:i') }}<br>
                🌐 IP: {{ request()->ip() }}<br>
                💻 Navegador: {{ request()->userAgent() }}
            </p>
        </div>
        
        <div class="footer">
            <p>
                Este email fue enviado automáticamente por <strong>{{ config('app.name') }}</strong><br>
                Si necesitas ayuda, contacta con nuestro <a href="mailto:support@example.com" style="color: #667eea;">equipo de soporte</a>
            </p>
            <p style="margin-top: 15px;">
                © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>