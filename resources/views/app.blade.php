<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'OmniPOS') }}</title>

        {{-- Thème storefront : variables CSS inline pour éviter le flash couleur au refresh --}}
        @php
            $sfTheme = $page['props']['storefrontTheme'] ?? null;
            $themeColor = (is_array($sfTheme) && !empty($sfTheme['primary'])) ? $sfTheme['primary'] : '#f59e0b';
        @endphp
        @if(!empty($sfTheme) && is_array($sfTheme))
        <style id="storefront-theme">
:root {
    --sf-primary: {{ $sfTheme['primary'] ?? '#f59e0b' }};
    --sf-secondary: {{ $sfTheme['secondary'] ?? '#d97706' }};
    --sf-primary-hover: {{ $sfTheme['secondary'] ?? '#d97706' }};
}
        </style>
        @endif

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="{{ $themeColor }}">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="default">
        <meta name="apple-mobile-web-app-title" content="OmniPOS">
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
        <link rel="icon" href="/favicon.ico" type="image/x-icon">
        <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @routes
        @viteReactRefresh
        @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia

        <!-- Service Worker Registration -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js', { updateViaCache: 'none' })
                        .then((registration) => {
                            console.log('SW registered: ', registration);
                            
                            // Vérifier les mises à jour
                            registration.addEventListener('updatefound', () => {
                                const newWorker = registration.installing;
                                newWorker.addEventListener('statechange', () => {
                                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                        // Nouveau service worker disponible, recharger la page
                                        window.location.reload();
                                    }
                                });
                            });
                            
                            // Forcer la mise à jour au chargement
                            registration.update();
                        })
                        .catch((registrationError) => {
                            console.log('SW registration failed: ', registrationError);
                        });
                    
                    // Désactiver le service worker en développement si nécessaire
                    // Décommentez la ligne suivante pour désactiver complètement le SW
                    // navigator.serviceWorker.getRegistrations().then(registrations => {
                    //     registrations.forEach(reg => reg.unregister());
                    // });
                });
            }
        </script>
    </body>
</html>
