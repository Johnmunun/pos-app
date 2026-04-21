import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import Sidebar from '@/Components/Layout/Sidebar';
import Navbar from '@/Components/Layout/Navbar';
import DepotAlert from '@/Components/DepotAlert';
import FlashMessages from '@/Components/FlashMessages';
import BillingPaymentSuccessModal from '@/Components/Billing/BillingPaymentSuccessModal';
import TrialUpgradePromptModal from '@/Components/Billing/TrialUpgradePromptModal';
import PharmacyAssistant from '@/Components/Pharmacy/PharmacyAssistant';
import HardwareAssistant from '@/Components/Hardware/HardwareAssistant';
import CommerceAssistant from '@/Components/Commerce/CommerceAssistant';
import SupportFloatingButton from '@/Components/Support/SupportFloatingButton';
import SupportChatWidget from '@/Components/Support/SupportChatWidget';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import { ensureFcmToken, wireForegroundMessages } from '@/lib/firebaseMessaging';
import MobileBottomNav from '@/Components/Layout/MobileBottomNav';
import { useIsMobile } from '@/hooks/useIsMobile';

/**
 * Conteneur pour aligner verticalement les boutons des assistants
 */
function AssistantsContainer({ permissions, extraMobileBottomPx = 0 }) {
    const hasPharmacy = permissions.includes('*') || permissions.includes('module.pharmacy')
        || permissions.some((p) => typeof p === 'string' && p.startsWith('pharmacy.'));
    const hasHardware = permissions.includes('*') || permissions.includes('module.hardware')
        || permissions.some((p) => typeof p === 'string' && p.startsWith('hardware.'));
    const hasCommerce = permissions.includes('*') || permissions.includes('module.commerce')
        || permissions.includes('commerce.assistant.use')
        || permissions.some((p) => typeof p === 'string' && p.startsWith('commerce.'));

    const hasSupport = permissions.includes('*')
        || permissions.includes('support.tickets.create')
        || permissions.includes('support.tickets.view')
        || permissions.includes('support.admin');

    // Compter les assistants disponibles
    const assistants = [];
    if (hasPharmacy) assistants.push('pharmacy');
    if (hasHardware) assistants.push('hardware');
    if (hasCommerce) assistants.push('commerce');

    // Baseline: reserve bottom for support button (if enabled)
    const supportEnabled = hasSupport;
    const supportBaseIndexOffset = supportEnabled ? 1 : 0;

    // Calculer les positions : le premier est en bas, les autres au-dessus
    // Espacement : 64px entre chaque bouton sur mobile, 88px sur desktop
    // Le premier assistant (index 0) est le plus bas
    const getBottomOffset = (index) => {
        // index 0 = le plus bas (premier), index 1 = au-dessus, index 2 = encore au-dessus
        // Mobile: bottom-24 (96px), bottom-40 (160px), bottom-56 (224px)
        // Desktop: bottom-6 (24px), bottom-28 (112px), bottom-50 (200px)
        // Espacement: 64px mobile, 88px desktop entre chaque bouton
        const mobileBottom = 96 + (index * 64) + extraMobileBottomPx; // réserve bottom nav <768px
        const desktopBottom = 24 + (index * 88); // 24, 112, 200...
        
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
            {supportEnabled && (
                <SupportFloatingButton enabled bottomOffset={getBottomOffset(0)} />
            )}
            {hasPharmacy && pharmacyIndex >= 0 && (
                <PharmacyAssistant bottomOffset={getBottomOffset(pharmacyIndex + supportBaseIndexOffset)} />
            )}
            {hasHardware && hardwareIndex >= 0 && (
                <HardwareAssistant bottomOffset={getBottomOffset(hardwareIndex + supportBaseIndexOffset)} />
            )}
            {hasCommerce && commerceIndex >= 0 && (
                <CommerceAssistant bottomOffset={getBottomOffset(commerceIndex + supportBaseIndexOffset)} />
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
    const whatsappNumber = usePage().props?.shop?.whatsapp?.number || null;
    const whatsappSupportEnabled = Boolean(usePage().props?.shop?.whatsapp?.enabled);
    const tenantSector = auth?.tenantSector ?? null;
    const isRoot = user?.type === 'ROOT';
    const isImpersonating = auth?.isImpersonating ?? false;
    const isMobile = useIsMobile();

    // État pour le drawer mobile
    const [sidebarOpen, setSidebarOpen] = useState(false);

    // Décale les FAB au-dessus de la bottom nav (<768px)
    const mobileBottomNavReservePx = user && isMobile ? 72 : 0;

    // FCM Web - abonnement (token) pour notifications
    useEffect(() => {
        if (!auth?.user) return;

        (async () => {
            try {
                const token = await ensureFcmToken();
                if (!token) return;

                await window.axios.post(route('api.notifications.tokens.store'), {
                    token,
                    platform: 'web',
                });

                localStorage.setItem('fcmSubscribed', '1');
            } catch (e) {
                // eslint-disable-next-line no-console
                console.warn('Erreur abonnement FCM', e);
            }
        })();
    }, [auth?.user]);

    useEffect(() => {
        if (!auth?.user) return;
        wireForegroundMessages({
            onNotification: (payload) => {
                try {
                    window.dispatchEvent(new CustomEvent('fcm-notification', { detail: payload }));
                } catch {
                    // ignore
                }
            },
        });
    }, [auth?.user]);

    // Fermer le sidebar sur mobile quand on change de page
    useEffect(() => {
        setSidebarOpen(false);
    }, [url]);

    return (
        <div className="min-h-[100dvh] min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200 flex flex-col">
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
                    className="fixed inset-0 bg-gray-900/50 z-40 lg:hidden transition-opacity max-md:backdrop-blur-[2px]"
                    onClick={() => setSidebarOpen(false)}
                    aria-hidden
                />
            )}

            {/* Contenu principal */}
            <div className="lg:pl-64 flex flex-col min-h-[100dvh] min-h-screen flex-1 w-full min-w-0 max-md:max-w-[100vw]">
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
                    <header className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 text-gray-900 dark:text-gray-100 max-md:pt-[env(safe-area-inset-top,0px)]">
                        <div className="mx-auto max-w-7xl px-3 sm:px-6 lg:px-8 py-3 sm:py-4 max-md:px-4 max-md:py-4">
                            {header}
                        </div>
                    </header>
                )}

                {/* Contenu principal : padding bas pour bottom nav sur petit écran */}
                <main className="flex-1 min-w-0 max-md:pb-[calc(4.75rem+env(safe-area-inset-bottom,0px))] max-md:overscroll-y-contain">
                    {fullWidth ? children : (
                        <div className="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 max-md:px-4 max-md:py-1">
                            {children}
                        </div>
                    )}
                </main>
            </div>

            {/* Flash Messages */}
            <FlashMessages />

            {/* Modal succès paiement abonnement */}
            <BillingPaymentSuccessModal />

            {/* Modal marketing plan Trial (affiché à la connexion) */}
            <TrialUpgradePromptModal />

            {/* Bottom nav tactile (md:hidden dans le composant) */}
            {user ? <MobileBottomNav /> : null}

            {/* Assistants intelligents (Pharmacie + Quincaillerie + Commerce) */}
            <AssistantsContainer permissions={permissions} extraMobileBottomPx={mobileBottomNavReservePx} />

            {/* WhatsApp support (flottant) — au-dessus de la bottom nav sur mobile */}
            <WhatsAppFloatingButton
                phone={whatsappNumber}
                enabled={whatsappSupportEnabled}
                liftForMobileBottomNav={Boolean(user && isMobile)}
            />

            {/* Support chat (flottant) */}
            <SupportChatWidget liftForMobileBottomNav={Boolean(user && isMobile)} />
        </div>
    );
}

