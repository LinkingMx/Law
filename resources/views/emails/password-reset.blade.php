<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña - {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #2d3748;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .email-wrapper {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .security-header {
            background: linear-gradient(135deg, #ff6b6b, #ffa726);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
        }
        .security-header::before {
            content: '🔐';
            font-size: 48px;
            display: block;
            margin-bottom: 15px;
        }
        .email-content {
            padding: 40px 30px;
        }
        .security-notice {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            border-left: 4px solid #f39c12;
        }
        .security-notice h4 {
            margin: 0 0 10px 0;
            color: #856404;
            font-size: 16px;
        }
        .reset-button {
            display: block;
            width: fit-content;
            margin: 30px auto;
            padding: 16px 32px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            transition: all 0.3s ease;
        }
        .reset-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
        }
        .expiry-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
            border-left: 4px solid #2196f3;
        }
        .security-tips {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
        }
        .security-tips h4 {
            color: #495057;
            margin: 0 0 15px 0;
        }
        .security-tips ul {
            margin: 0;
            padding-left: 20px;
        }
        .security-tips li {
            margin-bottom: 8px;
            color: #6c757d;
        }
        .footer {
            background: #2d3748;
            color: #cbd5e0;
            padding: 30px;
            text-align: center;
            font-size: 14px;
        }
        .footer a {
            color: #81e6d9;
        }
        .manual-link {
            background: #f8f9fa;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            font-family: monospace;
            font-size: 12px;
            word-break: break-all;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="security-header">
            <h1 style="margin: 0; font-size: 28px;">Restablecer Contraseña</h1>
            <p style="margin: 10px 0 0 0; opacity: 0.9; font-size: 16px;">
                Solicitud de restablecimiento de contraseña
            </p>
        </div>
        
        <div class="email-content">
            <p>Hola <strong>{{ $user->name ?? 'Usuario' }}</strong>,</p>
            
            <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>{{ config('app.name') }}</strong>.</p>
            
            <div class="security-notice">
                <h4>⚠️ Importante - Seguridad</h4>
                <p style="margin: 0;">Si no has solicitado este restablecimiento, ignora este email. Tu cuenta permanece segura.</p>
            </div>
            
            <p>Para restablecer tu contraseña, haz clic en el siguiente botón:</p>
            
            <a href="{{ $resetUrl ?? '#' }}" class="reset-button">
                🔑 Restablecer Contraseña
            </a>
            
            <div class="expiry-info">
                <p style="margin: 0;">
                    ⏰ <strong>Este enlace expira en {{ $expireTime ?? '60 minutos' }}</strong>
                </p>
            </div>
            
            <p>Si el botón no funciona, copia y pega el siguiente enlace en tu navegador:</p>
            <div class="manual-link">
                {{ $resetUrl ?? 'https://example.com/reset-password' }}
            </div>
            
            <div class="security-tips">
                <h4>💡 Consejos de Seguridad</h4>
                <ul>
                    <li>Usa una contraseña única y segura</li>
                    <li>Combina letras mayúsculas, minúsculas, números y símbolos</li>
                    <li>No compartas tu contraseña con nadie</li>
                    <li>Considera usar un gestor de contraseñas</li>
                    <li>Activa la autenticación de dos factores si está disponible</li>
                </ul>
            </div>
            
            <hr style="border: none; border-top: 1px solid #dee2e6; margin: 30px 0;">
            
            <p style="font-size: 14px; color: #6c757d;">
                <strong>Información de la solicitud:</strong><br>
                📅 Fecha: {{ now()->format('d/m/Y H:i:s') }}<br>
                🌐 Dirección IP: {{ request()->ip() }}<br>
                💻 Dispositivo: {{ request()->userAgent() }}
            </p>
        </div>
        
        <div class="footer">
            <p>
                <strong>{{ config('app.name') }}</strong> - Equipo de Seguridad<br>
                Este es un email automático, por favor no respondas a este mensaje.
            </p>
            <p style="margin-top: 15px;">
                ¿Necesitas ayuda? Contacta con <a href="mailto:support@example.com">nuestro soporte</a><br>
                © {{ date('Y') }} {{ config('app.name') }}. Todos los derechos reservados.
            </p>
        </div>
    </div>
</body>
</html>