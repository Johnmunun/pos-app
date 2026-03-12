import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import Sidebar from '@/Components/Layout/Sidebar';
import Navbar from '@/Components/Layout/Navbar';
import DepotAlert from '@/Components/DepotAlert';
import FlashMessages from '@/Components/FlashMessages';
import PharmacyAssistant from '@/Components/Pharmacy/PharmacyAssistant';
import HardwareAssistant from '@/Components/Hardware/HardwareAssistant';
import CommerceAssistant from '@/Components/Commerce/CommerceAssistant';

/**
 * Conteneur pour aligner verticalement les boutons des assistants
 */
function AssistantsContainer({ permissions }) {
    const hasPharmacy = permissions.includes('*') || permissions.includes('module.pharmacy')
        || permissions.some((p) => typeof p === 'string' && p.startsWith('pharmacy.'));
    const hasHardware = permissions.includes('*') || permissions.includes('module.hardware')
        || permissions.some((p) => typeof p === 'string' && p.startsWith('hardware.'));
    const hasCommerce = permissions.includes('*') || permissions.includes('module.commerce')
        || permissions.includes('commerce.assistant.use')
        || permissions.some((p) => typeof p === 'string' && p.startsWith('commerce.'));

    // Compter les assistants disponibles
    const assistants = [];
    if (hasPharmacy) assistants.push('pharmacy');
    if (hasHardware) assistants.push('hardware');
    if (hasCommerce) assistants.push('commerce');

    // Calculer les positions : le premier est en bas, les autres au-dessus
    // Espacement : 64px entre chaque bouton sur mobile, 88px sur desktop
    // Le premier assistant (index 0) est le plus bas
    const getBottomOffset = (index) => {
        // index 0 = le plus bas (premier), index 1 = au-dessus, index 2 = encore au-dessus
        // Mobile: bottom-24 (96px), bottom-40 (160px), bottom-56 (224px)
        // Desktop: bottom-6 (24px), bottom-28 (112px), bottom-50 (200px)
        // Espacement: 64px mobile, 88px desktop entre chaque bouton
        const mobileBottom = 96 + (index * 64); // 96, 160, 224
        const desktopBottom = 24 + (index * 88); // 24, 112, 200
        
        return {
            mobile: `${mobileBottom}px`,
            desktop: `${desktopBottom}px`,
        };
    };

    let pharmacyIndex = -1;
    let hardwareIndex = -1;
    let commerceIndex = -1;
    
    assistants.forEach((name, index) => {
        if (name === 'pharmacy') pharmacyIndex = index;
        if (name === 'hardware') hardwareIndex = index;
        if (name === 'commerce') commerceIndex = index;
    });

    return (
        <>
            {hasPharmacy && pharmacyIndex >= 0 && (
                <PharmacyAssistant bottomOffset={getBottomOffset(pharmacyIndex)} />
            )}
            {hasHardware && hardwareIndex >= 0 && (
                <HardwareAssistant bottomOffset={getBottomOffset(hardwareIndex)} />
            )}
            {hasCommerce && commerceIndex >= 0 && (
                <CommerceAssistant bottomOffset={getBottomOffset(commerceIndex)} />
            )}
        </>
    );
}

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

    // Notifications ROOT temps réel (inscriptions, etc.)
    useEffect(() => {
        const user = auth?.user;
        const permissions = Array.isArray(auth?.permissions) ? auth.permissions : [];
        const isRoot = user?.type === 'ROOT' || permissions.includes('admin.dashboard.view');

        if (!isRoot || typeof window === 'undefined' || !window.Echo) {
            return;
        }

        const channel = window.Echo.private('root.notifications')
            .listen('.user.registered', (event) => {
                // Pour l'instant, simple log console. On branchera plus tard la cloche UI.
                // event.notification contient la ligne AppNotification créée côté backend.
                // eslint-disable-next-line no-console
                console.log('Nouvelle inscription (realtime)', event.notification);
            });

        return () => {
            channel.stopListening('.user.registered');
        };
    }, [auth?.user, auth?.permissions]);

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
                        <div className="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 py-3 sm:py-4">
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

            {/* Assistants intelligents (Pharmacie + Quincaillerie + Commerce) */}
            <AssistantsContainer permissions={permissions} />
        </div>
    );
}

