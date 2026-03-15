import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Configuration Laravel Echo + Reverb (temps réel)
// En prod sans Reverb ou sans proxy WebSocket : VITE_REVERB_ENABLED=false pour éviter l'erreur "WebSocket connection failed"
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const reverbEnabled = import.meta.env.VITE_REVERB_ENABLED !== 'false';

if (reverbEnabled) {
    window.Pusher = Pusher;
    const forceTLS = (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https';
    const defaultPort = forceTLS ? 443 : 8080;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY ?? 'local',
        wsHost: import.meta.env.VITE_REVERB_HOST ?? window.location.hostname,
        wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
        wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? defaultPort),
        forceTLS,
        enabledTransports: ['ws', 'wss'],
        authEndpoint: '/broadcasting/auth',
        withCredentials: true,
        auth: {
            headers: {
                'X-CSRF-TOKEN': document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content'),
            },
        },
    });
} else {
    const noop = () => {};
    const stubChannel = { listen: noop, stopListening: noop };
    window.Echo = {
        private: () => stubChannel,
        channel: () => stubChannel,
        leave: noop,
        disconnect: noop,
    };
}
