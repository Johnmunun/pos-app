import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF for Laravel session-authenticated POST/PUT/DELETE requests
function syncAxiosCsrfFromDom() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
    }
}
syncAxiosCsrfFromDom();

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
