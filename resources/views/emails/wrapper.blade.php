<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ config('app.name') }}</title>
    <style>
        /* Email-safe CSS styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #374151;
            background-color: #f9fafb;
        }
        
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
        }
        
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            text-align: center;
        }
        
        .email-header img {
            max-height: 60px;
            max-width: 200px;
            margin: 20px 0;
        }
        
        .email-content {
            padding: 0;
        }
        
        .email-footer {
            background-color: #f3f4f6;
            padding: 30px 20px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        
        .email-footer p {
            margin: 5px 0;
            font-size: 12px;
            color: #6b7280;
        }
        
        .email-footer a {
            color: #3b82f6;
            text-decoration: none;
        }
        
        .footer-links {
            margin: 15px 0;
        }
        
        .footer-links a {
            color: #6b7280;
            text-decoration: none;
            margin: 0 15px;
            font-size: 12px;
        }
        
        .footer-links a:hover {
            color: #3b82f6;
        }
        
        /* Responsive design */
        @media only screen and (max-width: 600px) {
            .email-wrapper {
                width: 100% !important;
                margin: 0 !important;
            }
            
            .email-header,
            .email-content,
            .email-footer {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header with Logo -->
        <div class="email-header">
            @php
                $appearanceSettings = app(\App\Settings\AppearanceSettings::class);
                $generalSettings = app(\App\Settings\GeneralSettings::class);
                $logoUrl = $appearanceSettings->logo_url ?? null;
                $appName = $generalSettings->app_name ?? config('app.name');
            @endphp
            @if($logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $appName }}" />
            @else
                <div style="color: white; font-size: 24px; font-weight: bold; margin: 20px 0;">
                    {{ $appName }}
                </div>
            @endif
        </div>
        
        <!-- Main Content -->
        <div class="email-content">
            {!! $content !!}
        </div>
        
        <!-- Footer -->
        <div class="email-footer">
            @php
                $contactEmail = $generalSettings->contact_email ?? null;
            @endphp
            <p>
                <strong>{{ $appName }}</strong>
            </p>
            
            @if($contactEmail)
            <p>
                Email: <a href="mailto:{{ $contactEmail }}">{{ $contactEmail }}</a>
            </p>
            @endif
            
            <div class="footer-links">
                <a href="{{ config('app.url') }}">Inicio</a>
                @if($contactEmail)
                    <a href="mailto:{{ $contactEmail }}">Soporte</a>
                @endif
                <a href="{{ config('app.url') }}/privacy">Privacidad</a>
            </div>
            
            <p style="margin-top: 20px;">
                © {{ date('Y') }} {{ $appName }}. Todos los derechos reservados.
            </p>
            
            <p style="margin-top: 10px; font-size: 11px; color: #9ca3af;">
                Este email fue enviado automáticamente. Por favor no respondas a este mensaje.
            </p>
        </div>
    </div>
</body>
</html>