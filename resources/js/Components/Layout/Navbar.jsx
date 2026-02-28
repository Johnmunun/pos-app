import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
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
    const { auth } = usePage().props;
    const depots = auth?.depots ?? [];
    const currentDepot = auth?.currentDepot ?? null;

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

    // Nombre de notifications (placeholder)
    const notificationCount = 3;

    return (
        <nav className="sticky top-0 z-40 flex flex-wrap items-center gap-2 sm:gap-x-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 px-3 sm:px-4 lg:px-8 shadow-sm min-w-0">
            {/* Bouton menu mobile */}
            <button
                type="button"
                onClick={onMenuClick}
                className="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden"
            >
                <span className="sr-only">Ouvrir le menu</span>
                <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>

            {/* Séparateur */}
            <div className="h-6 w-px bg-gray-200 dark:bg-gray-700 lg:hidden" />

            {/* Sélecteur de dépôt (affiché dès qu'il y a des dépôts) */}
            {depots && depots.length > 0 && (
                <DepotSelector depots={depots} currentDepot={currentDepot} />
            )}

            {/* Barre de recherche globale */}
            <div className="flex flex-1 min-w-0 gap-x-4 self-stretch lg:gap-x-6 mt-2 sm:mt-0">
                <GlobalSearch isRoot={user?.type === 'ROOT'} />
            </div>

            {/* Indicateur d'impersonation */}
            {isImpersonating && (
                <div className="hidden md:flex items-center gap-2 px-3 py-1 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-200 rounded-lg text-sm font-medium">
                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Mode impersonation
                </div>
            )}

            {/* Actions droite */}
            <div className="flex flex-wrap items-center gap-2 sm:gap-x-4 lg:gap-x-6 justify-end min-w-0 ml-auto mt-2 sm:mt-0">
                {/* Toggle Dark Mode */}
                <button
                    type="button"
                    onClick={toggleDarkMode}
                    className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
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
                {permissions.includes('notifications.view') || permissions.length === 0 ? (
                    <button
                        type="button"
                        className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors relative"
                        title="Notifications"
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
                ) : null}

                {/* Aide / Support */}
                <button
                    type="button"
                    className="hidden sm:block rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                    title="Aide"
                >
                    <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </button>

                {/* Séparateur */}
                <div className="hidden lg:block lg:h-6 lg:w-px lg:bg-gray-200 dark:lg:bg-gray-700" />

                {/* Menu utilisateur */}
                <Dropdown>
                    <Dropdown.Trigger>
                        <span className="flex items-center">
                            <button
                                type="button"
                                className="flex items-center gap-x-3 rounded-lg p-2 text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                            >
                                <div className="h-8 w-8 rounded-full bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center text-white font-bold text-sm">
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
                                <svg className="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <Dropdown.Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="flex items-center gap-2 text-red-600 dark:text-red-400"
                        >
                            <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Déconnexion
                        </Dropdown.Link>
                    </Dropdown.Content>
                </Dropdown>
            </div>
        </nav>
    );
}

