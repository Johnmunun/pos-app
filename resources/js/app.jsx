import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from 'react-hot-toast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// Dark mode support basé sur la préférence du navigateur
function initDarkMode() {
    // Vérifier la préférence système
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Vérifier si l'utilisateur a déjà une préférence stockée
    const stored = localStorage.getItem('darkMode');
    
    if (stored === null) {
        // Première visite : utiliser la préférence système
        if (prefersDark) {
            document.documentElement.classList.add('dark');
        }
    } else {
        // Utiliser la préférence stockée
        if (stored === 'true') {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    }
    
    // Écouter les changements de préférence système
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (localStorage.getItem('darkMode') === null) {
            if (e.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    });
}

// Initialiser le dark mode avant le rendu
initDarkMode();

// PWA: Gérer l'installation
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then((registration) => {
                console.log('Service Worker registered:', registration);
            })
            .catch((error) => {
                console.log('Service Worker registration failed:', error);
            });
    });
}

// Initialiser les services offline
if (typeof window !== 'undefined') {
    import('./lib/syncService').then(() => {
        console.log('Offline services initialized');
    });
}

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) =>
        resolvePageComponent(
            `./Pages/${name}.jsx`,
            import.meta.glob('./Pages/**/*.jsx'),
        ),
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <>
                <App {...props} />
                <Toaster
                    position="top-right"
                    toastOptions={{
                        duration: 4000,
                        className: 'dark:bg-gray-800 dark:text-gray-100',
                        style: {
                            background: 'var(--toast-bg, #fff)',
                            color: 'var(--toast-color, #1f2937)',
                            borderRadius: '0.75rem',
                            boxShadow: '0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)',
                            padding: '16px',
                            fontSize: '14px',
                            fontWeight: '500',
                            border: '1px solid var(--toast-border, #e5e7eb)',
                        },
                        success: {
                            iconTheme: {
                                primary: '#10b981',
                                secondary: '#fff',
                            },
                            style: {
                                background: 'var(--toast-success-bg, #f0fdf4)',
                                color: 'var(--toast-success-color, #065f46)',
                                border: '1px solid #10b981',
                            },
                            className: 'dark:bg-emerald-900/20 dark:text-emerald-400 dark:border-emerald-500',
                        },
                        error: {
                            iconTheme: {
                                primary: '#ef4444',
                                secondary: '#fff',
                            },
                            style: {
                                background: 'var(--toast-error-bg, #fef2f2)',
                                color: 'var(--toast-error-color, #991b1b)',
                                border: '1px solid #ef4444',
                            },
                            className: 'dark:bg-red-900/20 dark:text-red-400 dark:border-red-500',
                        },
                    }}
                />
            </>
        );
    },
    progress: {
        color: '#f59e0b',
    },
});
