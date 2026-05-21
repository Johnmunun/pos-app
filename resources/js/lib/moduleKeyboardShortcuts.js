import { router } from '@inertiajs/react';

const STORAGE_KEY = 'omnipos_active_pos_module';

/**
 * Raccourcis globaux (Ctrl+Shift + touche) pour Global Commerce, Quincaillerie et Pharmacy.
 */
export const MODULE_KEYBOARD_SHORTCUTS = [
    { key: 'v', keys: 'Ctrl+Shift+V', label: 'Nouvelle vente (caisse)' },
    { key: 'd', keys: 'Ctrl+Shift+D', label: 'Dashboard' },
    { key: 'b', keys: 'Ctrl+Shift+B', label: 'Bons de commande / Achats' },
    { key: 'c', keys: 'Ctrl+Shift+C', label: 'Clients' },
    { key: 'p', keys: 'Ctrl+Shift+P', label: 'Produits' },
    { key: 'g', keys: 'Ctrl+Shift+G', label: 'Catégories (G de catéGorie)' },
];

export const POS_MODULE_LABELS = {
    commerce: 'Global Commerce',
    hardware: 'Quincaillerie',
    pharmacy: 'Pharmacy',
};

const MODULE_ROUTE_KEYS = {
    commerce: {
        v: 'commerce.sales.create',
        d: 'commerce.dashboard',
        b: 'commerce.purchases.index',
        c: 'commerce.customers.index',
        p: 'commerce.products.index',
        g: 'commerce.categories.index',
    },
    hardware: {
        v: 'hardware.sales.create',
        d: 'hardware.dashboard',
        b: 'hardware.purchases.index',
        c: 'hardware.customers.index',
        p: 'hardware.products',
        g: 'hardware.categories.index',
    },
    pharmacy: {
        v: 'pharmacy.sales.create',
        d: 'pharmacy.dashboard',
        b: 'pharmacy.purchases.index',
        c: 'pharmacy.customers.index',
        p: 'pharmacy.products',
        g: 'pharmacy.categories.index',
    },
};

/** Priorité utilisateur : Global Commerce & Quincaillerie en premier. */
const MODULE_FALLBACK_ORDER = ['commerce', 'hardware', 'pharmacy'];

export function detectActiveModule(pathname = typeof window !== 'undefined' ? window.location.pathname : '') {
    if (pathname.startsWith('/commerce')) return 'commerce';
    if (pathname.startsWith('/hardware')) return 'hardware';
    if (pathname.startsWith('/pharmacy')) return 'pharmacy';
    return null;
}

function routeExists(routeName) {
    try {
        if (typeof route !== 'function') return false;
        route(routeName);
        return true;
    } catch {
        return false;
    }
}

export function moduleHasShortcutRoutes(moduleKey) {
    const routes = MODULE_ROUTE_KEYS[moduleKey];
    if (!routes) return false;
    return routeExists(routes.d);
}

export function getAvailablePosModules() {
    return MODULE_FALLBACK_ORDER.filter((moduleKey) => moduleHasShortcutRoutes(moduleKey));
}

function persistActiveModule(pathname) {
    const moduleKey = detectActiveModule(pathname);
    if (!moduleKey) return;
    try {
        sessionStorage.setItem(STORAGE_KEY, moduleKey);
    } catch {
        // ignore
    }
}

function readStoredModule() {
    try {
        const stored = sessionStorage.getItem(STORAGE_KEY);
        if (stored && MODULE_ROUTE_KEYS[stored] && moduleHasShortcutRoutes(stored)) {
            return stored;
        }
    } catch {
        // ignore
    }
    return null;
}

function resolveModuleForShortcut(pathname) {
    const fromPath = detectActiveModule(pathname);
    if (fromPath) return fromPath;

    const stored = readStoredModule();
    if (stored) return stored;

    for (const moduleKey of MODULE_FALLBACK_ORDER) {
        if (moduleHasShortcutRoutes(moduleKey)) {
            return moduleKey;
        }
    }

    return null;
}

function isTypingTarget(target) {
    return (
        target instanceof HTMLInputElement ||
        target instanceof HTMLTextAreaElement ||
        target instanceof HTMLSelectElement ||
        (target && target.isContentEditable)
    );
}

function visitModuleRoute(moduleKey, shortcutKey) {
    const routeName = MODULE_ROUTE_KEYS[moduleKey]?.[shortcutKey];
    if (!routeName || !routeExists(routeName)) return false;

    router.visit(route(routeName));
    return true;
}

export function initModuleKeyboardShortcuts() {
    if (typeof window === 'undefined' || window.__omniposModuleShortcutsInitialized) {
        return;
    }

    window.__omniposModuleShortcutsInitialized = true;

    persistActiveModule(window.location.pathname);

    router.on('navigate', (event) => {
        const url = event.detail?.page?.url ?? window.location.pathname;
        persistActiveModule(url);
    });

    window.addEventListener('keydown', (event) => {
        if (!event.ctrlKey || !event.shiftKey || event.altKey || event.metaKey) return;
        if (isTypingTarget(event.target)) return;

        const shortcutKey = String(event.key || '').toLowerCase();
        const supported = MODULE_KEYBOARD_SHORTCUTS.some((item) => item.key === shortcutKey);
        if (!supported) return;

        const moduleKey = resolveModuleForShortcut(window.location.pathname);
        if (!moduleKey) return;

        if (visitModuleRoute(moduleKey, shortcutKey)) {
            event.preventDefault();
        }
    });
}
