import { MessageCircle } from 'lucide-react';

export default function WhatsAppFloatingButton({ phone, enabled, iconOnly = false, liftForMobileBottomNav = false }) {
    if (!enabled || !phone) {
        return null;
    }

    const normalized = String(phone).replace(/\D/g, '');
    if (!normalized) {
        return null;
    }

    const text = encodeURIComponent("Bonjour, je souhaite avoir des informations sur vos produits.");
    const href = `https://wa.me/${normalized}?text=${text}`;

    /** Au-dessus de la bottom nav app (même réserve que AppLayout sur petit écran). */
    const liftClasses = liftForMobileBottomNav
        ? 'max-md:!bottom-[calc(4.75rem+env(safe-area-inset-bottom,0px))]'
        : '';

    const baseClasses =
        `fixed bottom-4 right-4 sm:bottom-6 sm:right-6 z-50 inline-flex items-center gap-2 rounded-full bg-emerald-500 hover:bg-emerald-600 text-white shadow-xl shadow-emerald-500/40 font-semibold transition-transform hover:translate-y-[-2px] ${liftClasses}`;

    const iconWrapperClasses =
        'inline-flex items-center justify-center h-10 w-10 sm:h-11 sm:w-11 rounded-full bg-emerald-400/90';

    if (iconOnly) {
        return (
            <a
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                className={`${baseClasses} px-0 py-0`}
            >
                <span className={iconWrapperClasses}>
                    <MessageCircle className="h-5 w-5 sm:h-6 sm:w-6" />
                </span>
            </a>
        );
    }

    return (
        <a
            href={href}
            target="_blank"
            rel="noopener noreferrer"
            className={`${baseClasses} px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm`}
        >
            <span className="inline-flex items-center justify-center h-7 w-7 sm:h-8 sm:w-8 rounded-full bg-emerald-400/90">
                <MessageCircle className="h-4 w-4 sm:h-5 sm:w-5" />
            </span>
            <span className="hidden sm:inline">
                Support WhatsApp
            </span>
        </a>
    );
}

