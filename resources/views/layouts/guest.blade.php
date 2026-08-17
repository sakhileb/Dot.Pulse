<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Syne:wght@600;700;800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- Styles -->
        @livewireStyles
        <style>
            :root {
                --ink: #09090b;
                --panel: #141416;
                --accent: #f1c62e;
                --accent-soft: #f6d670;
                --navy: #08354f;
                --line: rgba(244, 244, 245, 0.10);
            }
            .font-display { font-family: 'Syne', system-ui, sans-serif; }
            /* The logo's wordmark/chevron are navy, ~1.5:1 contrast against every dark
               surface here -- a rim + glow lifts the shape without recoloring the asset. */
            .dot-logo { filter: drop-shadow(0 0 1px rgba(255,255,255,0.7)) drop-shadow(0 0 2px rgba(255,255,255,0.4)) drop-shadow(0 0 8px rgba(241,198,46,0.35)); }
        </style>
    </head>
    <body>
        <div class="font-sans text-gray-900 dark:text-gray-100 antialiased" style="font-family:'Inter',system-ui,sans-serif;">
            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
