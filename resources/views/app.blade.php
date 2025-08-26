<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Dynamic theme styles from Filament appearance settings --}}
        @php
            $appearanceSettings = app(\App\Settings\AppearanceSettings::class);
        @endphp
        <style>
            :root {
                --font-family: '{{ $appearanceSettings->font_family ?? 'Nunito' }}', ui-sans-serif, system-ui;
                
                @if($appearanceSettings->primary_color)
                    --primary: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->primary_color) }};
                    --ring: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->primary_color) }};
                    --sidebar-primary: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->primary_color) }};
                    --sidebar-ring: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->primary_color) }};
                @endif
                
                @if($appearanceSettings->success_color)
                    --success: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->success_color) }};
                @endif
                
                @if($appearanceSettings->warning_color)
                    --warning: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->warning_color) }};
                @endif
                
                @if($appearanceSettings->danger_color)
                    --destructive: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->danger_color) }};
                @endif
                
                @if($appearanceSettings->info_color)
                    --info: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->info_color) }};
                @endif
            }
            
            .dark:root {
                @if($appearanceSettings->primary_color)
                    --primary: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->primary_color, true) }};
                    --ring: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->primary_color, true) }};
                    --sidebar-primary: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->primary_color, true) }};
                    --sidebar-ring: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->primary_color, true) }};
                @endif
                
                @if($appearanceSettings->success_color)
                    --success: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->success_color, true) }};
                @endif
                
                @if($appearanceSettings->warning_color)
                    --warning: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->warning_color, true) }};
                @endif
                
                @if($appearanceSettings->danger_color)
                    --destructive: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->danger_color, true) }};
                @endif
                
                @if($appearanceSettings->info_color)
                    --info: {{ \App\Helpers\ColorHelper::hexToOklch($appearanceSettings->info_color, true) }};
                @endif
            }

            /* Page backgrounds matching Filament exactly */
            html {
                background-color: oklch(98.5% 0 0); /* Light gray like Filament */
            }

            html.dark {
                background-color: oklch(7% 0 0); /* Very dark like Filament dark mode */
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        @php
            $fontUrl = match($appearanceSettings->font_family ?? 'Nunito') {
                'Roboto' => 'family=Roboto:wght@400;500;600',
                'Open Sans' => 'family=Open+Sans:wght@400;500;600',
                'Nunito' => 'family=Nunito:wght@400;500;600',
                'Poppins' => 'family=Poppins:wght@400;500;600',
                'Lato' => 'family=Lato:wght@400;500;600',
                'Montserrat' => 'family=Montserrat:wght@400;500;600',
                'Source Sans Pro' => 'family=Source+Sans+Pro:wght@400;500;600',
                'Inter' => 'family=Inter:wght@400;500;600',
                default => 'family=Nunito:wght@400;500;600',
            };
        @endphp
        <link href="https://fonts.bunny.net/css?{{ $fontUrl }}" rel="stylesheet" />

        @routes
        @viteReactRefresh
        @vite(['resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
