import { Link, usePage } from '@inertiajs/react';
import { buildMobileBottomNavItems } from '@/lib/mobileBottomNavItems';

/**
 * Barre de navigation inférieure — visible uniquement sous 768px (md:hidden).
 * Raccourcis vers les mêmes routes que la sidebar (items construits via les mêmes règles d'accès).
 */
export default function MobileBottomNav() {
    const { auth, url } = usePage().props;
    const user = auth?.user;
    if (!user) return null;

    const permissions = Array.isArray(auth?.permissions) ? auth.permissions : [];
    const tenantSector = auth?.tenantSector ?? null;
    const isRoot = user?.type === 'ROOT';

    const items = buildMobileBottomNavItems({ permissions, tenantSector, isRoot, url });
    if (items.length === 0) return null;

    const normalize = (u) => String(u || '').replace(/\/$/, '') || '/';
    const current = normalize(url);

    return (
        <nav
            className="md:hidden fixed bottom-0 left-0 right-0 z-30 border-t border-gray-200 dark:border-slate-600 bg-white/95 dark:bg-slate-900/95 backdrop-blur-md pb-[env(safe-area-inset-bottom,0px)]"
            aria-label="Navigation rapide"
        >
            <div className="mx-auto flex max-w-lg items-stretch justify-around gap-0 px-1 pt-1">
                {items.map(({ key, label, href, icon: Icon }) => {
                    const active = current === normalize(href) || current.startsWith(`${normalize(href)}/`);
                    return (
                        <Link
                            key={key}
                            href={href}
                            className={`flex min-h-[52px] min-w-[56px] flex-1 flex-col items-center justify-center gap-0.5 rounded-xl px-2 py-2 text-[11px] font-semibold transition-colors active:scale-[0.98] ${
                                active
                                    ? 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40'
                                    : 'text-gray-600 dark:text-gray-400 active:bg-gray-100 dark:active:bg-slate-800'
                            }`}
                        >
                            {Icon ? <Icon className={`h-6 w-6 shrink-0 ${active ? '' : 'opacity-90'}`} strokeWidth={active ? 2.25 : 2} /> : null}
                            <span className="leading-tight text-center">{label}</span>
                        </Link>
                    );
                })}
            </div>
        </nav>
    );
}
