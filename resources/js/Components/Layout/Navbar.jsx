import { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Dropdown from '@/Components/Dropdown';
import GlobalSearch from '@/Components/GlobalSearch';
import DepotSelector from '@/Components/DepotSelector';

/**
 * Component: Navbar
 * 
 * Navbar professionnelle avec :
 * - Logo / Nom
 * - Sélecteur de dépôt (si multi-dépôts)
 * - Toggle Dark/Light mode
 * - Barre de recherche globale
 * - Notifications (badge)
 * - Menu utilisateur (dropdown)
 * - Mobile-first design
 */
export default function Navbar({ user, permissions, onMenuClick, isImpersonating = false }) {
    const props = usePage().props;
    const auth = props.auth;
    // Dépôts : auth (partagé par HandleInertiaRequests) ou fallback sur les props de la page (module Hardware)
    const depotsList = (auth?.depots?.length ? auth.depots : props.depots) ?? [];
    const depots = Array.isArray(depotsList) ? depotsList : [];
    const currentDepot = auth?.currentDepot ?? props.currentDepot ?? null;

    // Toggle dark mode
    const toggleDarkMode = () => {
        const isDark = document.documentElement.classList.contains('dark');
        if (isDark) {
            document.documentElement.classList.remove('dark');
            localStorage.setItem('darkMode', 'false');
        } else {
            document.documentElement.classList.add('dark');
            localStorage.setItem('darkMode', 'true');
        }
    };

    // Vérifier si dark mode est actif
    const isDarkMode = document.documentElement.classList.contains('dark');

    const [notificationCount, setNotificationCount] = useState(0);
    const [notifications, setNotifications] = useState([]);
    const [showNotifications, setShowNotifications] = useState(false);

    const isRoot = user?.type === 'ROOT' || permissions.includes('admin.dashboard.view');

    const fetchNotifications = async () => {
        if (!isRoot) return;
        try {
            const response = await window.axios.get(route('api.notifications.index'));
            setNotificationCount(response.data?.unread_count ?? 0);
            setNotifications(response.data?.notifications ?? []);
        } catch (e) {
            // eslint-disable-next-line no-console
            console.warn('Erreur chargement notifications', e);
        }
    };

    const markNotificationAsRead = async (n) => {
        if (n.read_at) return;
        try {
            await window.axios.patch(route('api.notifications.mark-read', { id: n.id }));
            setNotifications((prev) =>
                prev.map((item) =>
                    item.id === n.id ? { ...item, read_at: new Date().toISOString().slice(0, 19).replace('T', ' ') } : item
                )
            );
            setNotificationCount((c) => Math.max(0, c - 1));
        } catch (e) {
            // eslint-disable-next-line no-console
            console.warn('Erreur marquage notification lu', e);
        }
    };

    // Charger les notifications pour ROOT / admins au montage
    useEffect(() => {
        fetchNotifications();
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [isRoot]);

    // Temps réel : écouter les notifications (inscription, produit créé, vente) pour toast + mise à jour liste sans clic sur la cloche
    useEffect(() => {
        if (!isRoot || typeof window === 'undefined' || !window.Echo) return;

        const channel = window.Echo.private('root.notifications');

        const normalize = (payload) => {
            const n = payload?.notification ?? payload;
            if (!n?.title) return null;
            return {
                id: n.id,
                title: n.title,
                body: n.body ?? '',
                type: n.type ?? null,
                created_at: n.created_at ?? new Date().toISOString().slice(0, 19).replace('T', ' '),
                read_at: n.read_at ?? null,
            };
        };

        const onNotification = (event) => {
            const notif = normalize(event);
            if (!notif) return;
            setNotifications((prev) => [notif, ...prev]);
            setNotificationCount((c) => c + 1);
            toast.success(notif.title, { description: notif.body });
        };

        channel.listen('.user.registered', onNotification);
        channel.listen('.admin.notification', onNotification);

        return () => {
            channel.stopListening('.user.registered');
            channel.stopListening('.admin.notification');
        };
    }, [isRoot]);

    return (
        <nav className="sticky top-0 z-40 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm max-md:pt-[env(safe-area-inset-top,0px)]">
            {/* Ligne principale — zones tactiles plus grandes sur petit écran */}
            <div className="flex items-center gap-2 sm:gap-3 px-3 sm:px-4 lg:px-8 py-2.5 min-w-0 max-md:py-3.5 max-md:px-3">
                {/* Bouton menu mobile */}
                <button
                    type="button"
                    onClick={onMenuClick}
                    className="flex-shrink-0 p-2 text-gray-700 dark:text-gray-300 lg:hidden -ml-1 max-md:min-h-11 max-md:min-w-11 max-md:inline-flex max-md:items-center max-md:justify-center max-md:rounded-xl max-md:active:bg-gray-100 dark:max-md:active:bg-slate-700/80"
                >
                    <span className="sr-only">Ouvrir le menu</span>
                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>

                {/* Séparateur */}
                <div className="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden flex-shrink-0" />

                {/* Sélecteur de dépôt (affiché dès qu'il y a des dépôts) */}
                {depots && depots.length > 0 && (
                    <div className="flex-shrink-0">
                        <DepotSelector depots={depots} currentDepot={currentDepot} />
                    </div>
                )}

                {/* Barre de recherche globale */}
                <div className="flex-1 min-w-0 max-w-full">
                    <GlobalSearch isRoot={user?.type === 'ROOT'} />
                </div>

                {/* Actions droite */}
                <div className="flex items-center gap-1.5 sm:gap-2 lg:gap-3 flex-shrink-0">
                    <Link
                        href={route('billing.onboarding.payment')}
                        className="hidden md:inline-flex items-center rounded-lg bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-700"
                    >
                        Upgrade
                    </Link>
                    {/* Indicateur d'impersonation */}
                    {isImpersonating && (
                        <div className="hidden md:flex items-center gap-2 px-2 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 rounded-lg text-xs font-medium">
                            <svg className="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <span className="hidden lg:inline">Mode impersonation</span>
                        </div>
                    )}

                    {/* Prévisualisation boutique e-commerce */}
                    {(permissions.includes('module.ecommerce') || permissions.some((p) => typeof p === 'string' && p.startsWith('ecommerce.'))) && (
                        <Link
                            href={route('ecommerce.storefront.index')}
                            target="_blank"
                            className="hidden sm:inline-flex rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0 items-center justify-center"
                            title="Prévisualiser la boutique"
                        >
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 12s3-7 9-7 9 7 9 7-3 7-9 7-9-7-9-7z" />
                                <circle cx="12" cy="12" r="3" />
                            </svg>
                        </Link>
                    )}

                    {/* Toggle Dark Mode */}
                    <button
                        type="button"
                        onClick={toggleDarkMode}
                        className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0"
                        title={isDarkMode ? 'Mode clair' : 'Mode sombre'}
                    >
                        {isDarkMode ? (
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        ) : (
                            <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                        )}
                    </button>

                    {/* Notifications */}
                    {isRoot && (
                        <div className="relative">
                            <button
                                type="button"
                                className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors relative flex-shrink-0"
                                title="Notifications"
                                onClick={async () => {
                                    await fetchNotifications();
                                    setShowNotifications((prev) => !prev);
                                }}
                            >
                                <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                                </svg>
                                {notificationCount > 0 && (
                                    <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-amber-500 text-xs font-bold text-white">
                                        {notificationCount > 9 ? '9+' : notificationCount}
                                    </span>
                                )}
                            </button>
                            {showNotifications && (
                                <div className="absolute right-0 mt-2 w-80 max-h-96 overflow-y-auto rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-lg z-50">
                                    <div className="px-3 py-2 border-b border-gray-100 dark:border-gray-700 flex items-center justify-between">
                                        <span className="text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Notifications
                                        </span>
                                        <span className="text-[11px] text-gray-400">
                                            {notificationCount} non lue(s)
                                        </span>
                                    </div>
                                    <div className="divide-y divide-gray-100 dark:divide-gray-700">
                                        {notifications.length === 0 && (
                                            <div className="px-3 py-3 text-xs text-gray-500 dark:text-gray-400">
                                                Aucune notification pour le moment.
                                            </div>
                                        )}
                                        {notifications.map((n) => (
                                            <button
                                                key={n.id}
                                                type="button"
                                                onClick={() => markNotificationAsRead(n)}
                                                className={`w-full text-left px-3 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-700/60 transition-colors ${n.read_at ? 'opacity-70' : ''}`}
                                            >
                                                <div className="font-semibold text-gray-800 dark:text-gray-100">
                                                    {n.title}
                                                </div>
                                                {n.body && (
                                                    <div className="mt-0.5 text-gray-600 dark:text-gray-300">
                                                        {n.body}
                                                    </div>
                                                )}
                                                <div className="mt-0.5 text-[10px] text-gray-400 dark:text-gray-500">
                                                    {n.created_at}
                                                </div>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Aide / Support */}
                    <button
                        type="button"
                        className="hidden sm:block rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors flex-shrink-0"
                        title="Aide"
                    >
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </button>

                    {/* Séparateur */}
                    <div className="hidden lg:block h-6 w-px bg-gray-200 dark:bg-gray-700 flex-shrink-0" />

                    {/* Menu utilisateur */}
                    <Dropdown>
                        <Dropdown.Trigger>
                            <span className="flex items-center">
                                <button
                                    type="button"
                                    className="flex items-center gap-x-2 sm:gap-x-3 rounded-lg p-1.5 sm:p-2 text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex-shrink-0"
                                >
                                    <div className="h-8 w-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white font-bold text-sm flex-shrink-0">
                                        {user?.first_name?.[0] || user?.name?.[0] || 'U'}
                                    </div>
                                    <span className="hidden lg:block">
                                        <span className="block text-sm font-medium text-gray-900 dark:text-white">
                                            {user?.first_name && user?.last_name 
                                                ? `${user.first_name} ${user.last_name}`
                                                : user?.name || 'Utilisateur'
                                            }
                                        </span>
                                        <span className="block text-xs text-gray-500 dark:text-gray-400">
                                            {user?.email || ''}
                                        </span>
                                    </span>
                                    <svg className="h-4 w-4 sm:h-5 sm:w-5 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </span>
                        </Dropdown.Trigger>

                    <Dropdown.Content align="right" width="48">
                        <Dropdown.Link href="/profile" className="flex items-center gap-2">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Profil
                        </Dropdown.Link>
                        <Dropdown.Link href="#" className="flex items-center gap-2">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            Paramètres
                        </Dropdown.Link>
                        <Dropdown.Link href="#" className="flex items-center gap-2">
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Sécurité
                        </Dropdown.Link>
                        {isImpersonating && (
                            <>
                                <div className="border-t border-gray-200 dark:border-gray-700 my-1" />
                                <Dropdown.Link
                                    href={route('admin.stop-impersonation')}
                                    method="post"
                                    as="button"
                                    className="flex items-center gap-2 text-amber-600 dark:text-amber-400"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Arrêter l'impersonation
                                </Dropdown.Link>
                            </>
                        )}
                        <div className="border-t border-gray-200 dark:border-gray-700 my-1" />
                        <button
                            type="button"
                            onClick={() => router.post(route('logout'))}
                            className={
                                'block w-full px-4 py-2 text-start text-sm leading-5 text-gray-700 dark:text-gray-300 transition duration-150 ease-in-out hover:bg-gray-100 dark:hover:bg-gray-700 focus:bg-gray-100 dark:focus:bg-gray-700 focus:outline-none ' +
                                'flex items-center gap-2 text-red-600 dark:text-red-400'
                            }
                        >
                            <svg className="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Déconnexion
                        </button>
                        </Dropdown.Content>
                    </Dropdown>
                </div>
            </div>
        </nav>
    );
}

