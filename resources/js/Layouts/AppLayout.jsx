import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import Sidebar from '@/Components/Layout/Sidebar';
import Navbar from '@/Components/Layout/Navbar';
import DepotAlert from '@/Components/DepotAlert';
import FlashMessages from '@/Components/FlashMessages';
import PharmacyAssistant from '@/Components/Pharmacy/PharmacyAssistant';

/**
 * Layout: AppLayout
 * 
 * Layout principal de l'application avec sidebar et navbar
 * Mobile-first avec support drawer/off-canvas
 * Support dark mode
 */
export default function AppLayout({ children, header, fullWidth = false }) {
    const { auth, url } = usePage().props;
    const user = auth?.user;
    const permissions = Array.isArray(auth?.permissions) ? auth.permissions : [];
    const tenantSector = auth?.tenantSector ?? null;
    const isRoot = user?.type === 'ROOT';
    const isImpersonating = auth?.isImpersonating ?? false;

    // État pour le drawer mobile
    const [sidebarOpen, setSidebarOpen] = useState(false);

    // Fermer le sidebar sur mobile quand on change de page
    useEffect(() => {
        setSidebarOpen(false);
    }, [url]);

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
            {/* Sidebar - Desktop (fixe) / Mobile (drawer) */}
            <Sidebar 
                permissions={permissions}
                tenantSector={tenantSector}
                isRoot={isRoot}
                isOpen={sidebarOpen}
                onClose={() => setSidebarOpen(false)}
                currentUrl={url}
            />

            {/* Overlay pour mobile quand sidebar est ouvert */}
            {sidebarOpen && (
                <div 
                    className="fixed inset-0 bg-gray-900/50 z-40 lg:hidden transition-opacity"
                    onClick={() => setSidebarOpen(false)}
                />
            )}

            {/* Contenu principal */}
            <div className="lg:pl-64 flex flex-col min-h-screen">
                {/* Navbar */}
                <Navbar 
                    user={user}
                    permissions={permissions}
                    onMenuClick={() => setSidebarOpen(!sidebarOpen)}
                    isImpersonating={isImpersonating}
                />

                {/* Alerte dépôt non sélectionné (multi-dépôts) */}
                <DepotAlert
                    depots={auth?.depots ?? []}
                    currentDepot={auth?.currentDepot ?? null}
                />

                {/* Header optionnel */}
                {header && (
                    <header className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                        <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-4">
                            {header}
                        </div>
                    </header>
                )}

                {/* Contenu principal : container cohérent mobile-first (sauf fullWidth) */}
                <main className="flex-1 min-w-0">
                    {fullWidth ? children : (
                        <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                            {children}
                        </div>
                    )}
                </main>
            </div>

            {/* Flash Messages */}
            <FlashMessages />

            {/* Assistant Intelligent Pharmacie (chatbot) */}
            <PharmacyAssistant />
        </div>
    );
}

