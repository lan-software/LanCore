<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

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

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        @if(! empty($activeTheme))
            {{-- Server-render the active palette Theme's CSS variable overrides
                 so the first paint matches the theme (no FOUC, no hydration
                 mismatch). Light overrides target :root; dark overrides target
                 .dark, mirroring the cascade in resources/css/app.css. --}}
            @if(! empty($activeTheme['lightConfig']) && is_array($activeTheme['lightConfig']))
                <style id="event-theme-vars-light">
                    :root {
                        @foreach($activeTheme['lightConfig'] as $cssVar => $cssValue)
                            @if(preg_match('/^--[a-z][a-z0-9-]*$/', $cssVar) && ! preg_match('/[;}<>]/', $cssValue))
                                {{ $cssVar }}: {{ $cssValue }};
                            @endif
                        @endforeach
                    }
                </style>
            @endif
            @if(! empty($activeTheme['darkConfig']) && is_array($activeTheme['darkConfig']))
                <style id="event-theme-vars-dark">
                    .dark {
                        @foreach($activeTheme['darkConfig'] as $cssVar => $cssValue)
                            @if(preg_match('/^--[a-z][a-z0-9-]*$/', $cssVar) && ! preg_match('/[;}<>]/', $cssValue))
                                {{ $cssVar }}: {{ $cssValue }};
                            @endif
                        @endforeach
                    }
                </style>
            @endif
        @endif

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
