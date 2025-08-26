<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bienvenido a {{ config('app.name') }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3498db;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 28px;
        }
        .content {
            margin-bottom: 30px;
        }
        .button {
            display: inline-block;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: bold;
            text-align: center;
            margin: 20px 0;
            transition: all 0.3s ease;
        }
        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 14px;
        }
        .highlight {
            background: linear-gradient(120deg, #a8edea 0%, #fed6e3 100%);
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Bienvenido a {{ config('app.name') }}!</h1>
            <p>Estamos emocionados de tenerte con nosotros</p>
        </div>
        
        <div class="content">
            <p>Hola <strong>{{ $user->name ?? 'Usuario' }}</strong>,</p>
            
            <p>¡Gracias por unirte a nuestra plataforma! Estamos aquí para ayudarte a conseguir el máximo provecho de nuestros servicios.</p>
            
            <div class="highlight">
                <h3>🎉 ¿Qué puedes hacer ahora?</h3>
                <ul>
                    <li>✅ Explorar todas las funcionalidades</li>
                    <li>✅ Configurar tu perfil personalizado</li>
                    <li>✅ Contactar con nuestro equipo de soporte</li>
                    <li>✅ Acceder a recursos y documentación</li>
                </ul>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/dashboard" class="button">
                    🚀 Comenzar Ahora
                </a>
            </div>
            
            <p>Si tienes cualquier pregunta, no dudes en contactarnos. Nuestro equipo estará encantado de ayudarte.</p>
            
            <p>¡Que tengas un excelente día!</p>
        </div>
        
        <div class="footer">
            <p>
                <strong>{{ config('app.name') }}</strong><br>
                © {{ date('Y') }} Todos los derechos reservados<br>
                <a href="{{ config('app.url') }}" style="color: #3498db;">{{ config('app.url') }}</a>
            </p>
        </div>
    </div>
</body>
</html>