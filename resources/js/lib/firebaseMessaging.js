import { initializeApp } from 'firebase/app';
import { getMessaging, getToken, isSupported, onMessage } from 'firebase/messaging';

function getFirebaseConfigFromEnv() {
    return {
        apiKey: import.meta.env.VITE_FCM_WEB_API_KEY,
        authDomain: import.meta.env.VITE_FCM_WEB_AUTH_DOMAIN,
        projectId: import.meta.env.VITE_FCM_WEB_PROJECT_ID,
        storageBucket: import.meta.env.VITE_FCM_WEB_STORAGE_BUCKET,
        messagingSenderId: import.meta.env.VITE_FCM_WEB_MESSAGING_SENDER_ID,
        appId: import.meta.env.VITE_FCM_WEB_APP_ID,
    };
}

export async function ensureFcmTokenDetailed() {
    const enabled = import.meta.env.VITE_FCM_ENABLED === 'true';
    if (!enabled) {
        return { token: null, reason: 'FCM désactivé (VITE_FCM_ENABLED=false).' };
    }

    if (typeof window === 'undefined') {
        return { token: null, reason: 'Contexte non-navigateur.' };
    }

    if (!('Notification' in window)) {
        return { token: null, reason: 'API Notification indisponible sur ce navigateur.' };
    }

    if (!('serviceWorker' in navigator)) {
        return { token: null, reason: 'Service Worker indisponible sur ce navigateur.' };
    }

    if (!(await isSupported())) {
        return { token: null, reason: 'Firebase Messaging non supporté (isSupported=false).' };
    }

    const vapidKey = import.meta.env.VITE_FCM_VAPID_PUBLIC_KEY;
    if (!vapidKey) {
        return { token: null, reason: 'VAPID manquante (VITE_FCM_VAPID_PUBLIC_KEY).' };
    }
    // Very common misconfig: not a real Base64URL VAPID public key
    // (Firebase Web Push certificates public key is typically ~87 chars and often starts with "B")
    const looksBase64Url = /^[A-Za-z0-9_-]+$/.test(String(vapidKey));
    if (!looksBase64Url || String(vapidKey).length < 60) {
        return {
            token: null,
            reason:
                "VAPID invalide. Va sur Firebase Console → Project settings → Cloud Messaging → Web Push certificates, puis copie la 'Public key' (longue clé Base64URL).",
        };
    }

    const cfg = getFirebaseConfigFromEnv();
    if (!cfg.apiKey || !cfg.projectId || !cfg.messagingSenderId || !cfg.appId) {
        return { token: null, reason: 'Config web Firebase incomplète (VITE_FCM_WEB_*).' };
    }

    let permission = Notification.permission;
    if (permission === 'default') {
        permission = await Notification.requestPermission();
    }
    if (permission !== 'granted') {
        return { token: null, reason: `Permission notifications refusée (${permission}).` };
    }

    try {
        const withTimeout = async (promise, ms, label) => {
            const timeout = new Promise((_, reject) => {
                const id = setTimeout(() => {
                    clearTimeout(id);
                    reject(new Error(`Timeout (${label || 'operation'} > ${ms}ms)`));
                }, ms);
            });
            return Promise.race([promise, timeout]);
        };

        // Quick check: sw.js must be reachable
        try {
            const swRes = await withTimeout(fetch('/sw.js', { cache: 'no-store' }), 8000, 'fetch /sw.js');
            if (!swRes.ok) {
                return { token: null, reason: `sw.js inaccessible (HTTP ${swRes.status}).` };
            }
        } catch (e) {
            return { token: null, reason: e?.message || 'sw.js inaccessible.' };
        }

        // Ensure SW (always register our SW to avoid wrong registration)
        const registration = await withTimeout(navigator.serviceWorker.register('/sw.js'), 10000, 'serviceWorker.register');
        // Wait until SW is controlling/ready (getToken can hang otherwise)
        await withTimeout(navigator.serviceWorker.ready, 10000, 'serviceWorker.ready');

        const app = initializeApp(cfg);
        const messaging = getMessaging(app);

        const token = await withTimeout(
            getToken(messaging, { vapidKey, serviceWorkerRegistration: registration }),
            12000,
            'firebase getToken'
        );
        if (!token) {
            return { token: null, reason: "Token FCM vide (getToken n'a rien renvoyé)." };
        }
        return { token, reason: null };
    } catch (e) {
        return { token: null, reason: e?.message || "Erreur inconnue pendant l'initialisation FCM." };
    }
}

export async function ensureFcmToken() {
    const enabled = import.meta.env.VITE_FCM_ENABLED === 'true';
    if (!enabled) return null;

    if (typeof window === 'undefined' || !('Notification' in window) || !('serviceWorker' in navigator)) {
        return null;
    }

    if (!(await isSupported())) {
        return null;
    }

    const vapidKey = import.meta.env.VITE_FCM_VAPID_PUBLIC_KEY;
    if (!vapidKey) {
        // eslint-disable-next-line no-console
        console.warn('FCM VAPID key manquante (VITE_FCM_VAPID_PUBLIC_KEY)');
        return null;
    }

    const cfg = getFirebaseConfigFromEnv();
    if (!cfg.apiKey || !cfg.projectId || !cfg.messagingSenderId || !cfg.appId) {
        // eslint-disable-next-line no-console
        console.warn('FCM web config incomplète (VITE_FCM_WEB_*)');
        return null;
    }

    let permission = Notification.permission;
    if (permission === 'default') {
        permission = await Notification.requestPermission();
    }
    if (permission !== 'granted') {
        return null;
    }

    // Ensure SW
    let registration = await navigator.serviceWorker.getRegistration();
    if (!registration) {
        registration = await navigator.serviceWorker.register('/sw.js');
    }

    const app = initializeApp(cfg);
    const messaging = getMessaging(app);

    const token = await getToken(messaging, { vapidKey, serviceWorkerRegistration: registration });
    return token || null;
}

export async function wireForegroundMessages({ onNotification } = {}) {
    const enabled = import.meta.env.VITE_FCM_ENABLED === 'true';
    if (!enabled) return;
    if (typeof window === 'undefined') return;
    if (!(await isSupported())) return;

    const cfg = getFirebaseConfigFromEnv();
    if (!cfg.apiKey || !cfg.projectId || !cfg.messagingSenderId || !cfg.appId) return;

    const app = initializeApp(cfg);
    const messaging = getMessaging(app);

    onMessage(messaging, (payload) => {
        if (typeof onNotification === 'function') {
            onNotification(payload);
        }
    });
}

