import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/** Cookie non-httpOnly que Laravel met à jour à chaque réponse (y compris après session()->regenerate() au login). */
function readCookie(name) {
    const parts = `; ${document.cookie}`.split(`; ${name}=`);
    if (parts.length < 2) return null;
    return parts.pop().split(';').shift() || null;
}

/**
 * CSRF pour les requêtes axios (hors Inertia).
 * Après login Inertia, le <meta name="csrf-token"> reste celui de la page initiale alors que la session a un nouveau jeton :
 * les POST axios (FCM, onboarding, etc.) renvoyaient 419 puis rechargement complet via l'intercepteur ci-dessous.
 * Le cookie XSRF-TOKEN est toujours aligné sur la session — on l'envoie en X-XSRF-TOKEN à chaque requête.
 */
window.axios.interceptors.request.use((config) => {
    delete config.headers['X-CSRF-TOKEN'];
    delete config.headers['X-XSRF-TOKEN'];

    const xsrfRaw = readCookie('XSRF-TOKEN');
    if (xsrfRaw) {
        try {
            config.headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfRaw);
        } catch {
            config.headers['X-XSRF-TOKEN'] = xsrfRaw;
        }
        return config;
    }

    const meta = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (meta) {
        config.headers['X-CSRF-TOKEN'] = meta;
    }
    return config;
});

window.axios.interceptors.response.use(
    (response) => response,
    (error) => {
        // 419 = jeton CSRF / session expirée (pas Inertia). Rechargement pour resynchroniser le meta CSRF.
        if (error.response?.status === 419 && typeof window !== 'undefined') {
            window.location.reload();
            return new Promise(() => {});
        }
        return Promise.reject(error);
    },
);

// Configuration Laravel Echo + Reverb (temps réel)
// En prod sans Reverb ou sans proxy WebSocket : VITE_REVERB_ENABLED=false pour éviter l'erreur "WebSocket connection failed"
// Reverb removed: keep window.Echo stub for code paths that still call it.
const noop = () => {};
const stubChannel = { listen: noop, stopListening: noop };
window.Echo = {
    private: () => stubChannel,
    channel: () => stubChannel,
    leave: noop,
    disconnect: noop,
};
