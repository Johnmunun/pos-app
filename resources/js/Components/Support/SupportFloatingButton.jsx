import { Link } from '@inertiajs/react';
import { LifeBuoy } from 'lucide-react';

export default function SupportFloatingButton({ enabled = true, bottomOffset }) {
    if (!enabled) return null;

    const bottom = bottomOffset?.mobile || bottomOffset?.desktop || '96px';

    return (
        <div
            className="fixed left-4 sm:left-6 lg:left-[calc(16rem+1rem)] z-40"
            style={{ bottom }}
        >
            <Link
                href="/support/tickets/create"
                className="inline-flex items-center gap-2 rounded-full bg-sky-600 hover:bg-sky-700 text-white shadow-xl shadow-sky-600/30 font-semibold transition-transform hover:translate-y-[-2px] px-3.5 py-2.5 sm:px-4 sm:py-3 text-xs sm:text-sm"
            >
                <span className="inline-flex items-center justify-center h-7 w-7 sm:h-8 sm:w-8 rounded-full bg-white/15">
                    <LifeBuoy className="h-4 w-4 sm:h-5 sm:w-5" />
                </span>
                <span className="hidden sm:inline">
                    Besoin d&apos;aide ?
                </span>
            </Link>
        </div>
    );
}

