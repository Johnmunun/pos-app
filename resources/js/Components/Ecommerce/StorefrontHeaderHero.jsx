import { Link, router } from '@inertiajs/react';
import {
    ArrowRight,
    Clock,
    Headphones,
    ShieldCheck,
    Sparkles,
    Truck,
} from 'lucide-react';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import StorefrontCurrencySelect from '@/Components/Ecommerce/StorefrontCurrencySelect';
import { formatShopName } from '@/lib/shopName';

/** Contenu marque (sans lien) — le parent enveloppe avec Link si besoin. */
function BrandBlock({ logoUrl, shopName, variant = 'default', compact = false }) {
    const isSpotlight = variant === 'spotlight';
    const isEditorial = variant === 'editorial';
    const titleClass = isSpotlight
        ? 'font-bold text-sm sm:text-base text-white block truncate'
        : 'font-bold text-sm sm:text-base text-slate-900 dark:text-white block truncate';
    const subClass = isSpotlight
        ? 'text-[11px] text-white/60'
        : 'text-[11px] text-slate-500 dark:text-slate-400';

    const logoSize = isEditorial ? (compact ? 'h-12 w-12' : 'h-14 w-14') : compact ? 'h-9 w-9' : 'h-10 w-10';
    const initialSize = isEditorial ? 'text-lg' : 'text-sm';

    const inner = (
        <>
            {logoUrl ? (
                <span
                    className={`inline-flex items-center justify-center ${logoSize} rounded-2xl bg-white shadow-lg shadow-slate-900/10 ring-1 ring-slate-200 overflow-hidden`}
                >
                    <img src={logoUrl} alt={shopName || 'Logo'} className="w-full h-full object-contain" />
                </span>
            ) : (
                <span
                    className={`inline-flex items-center justify-center ${logoSize} rounded-2xl bg-gradient-to-br from-[var(--sf-primary)] to-[var(--sf-secondary)] text-white font-bold ${initialSize} shadow-lg shadow-[var(--sf-primary)]/25 ring-1 ring-white/30`}
                >
                    {shopName?.charAt(0) || 'S'}
                </span>
            )}
            <div className={isEditorial ? 'text-center' : ''}>
                <span className={titleClass}>{formatShopName(shopName, 'Ma Boutique')}</span>
                <span className={`${subClass} block`}>Boutique en ligne</span>
            </div>
        </>
    );

    if (isEditorial) {
        return <div className="flex flex-col items-center gap-2">{inner}</div>;
    }

    return <div className="flex items-center gap-3">{inner}</div>;
}

function NavLinks({ links, navPages, layoutPreset }) {
    const linkClass =
        layoutPreset === 'spotlight'
            ? 'px-3 py-2 rounded-xl text-sm font-medium text-white/75 hover:text-white hover:bg-white/10 transition-colors'
            : layoutPreset === 'minimal'
              ? 'text-sm font-medium text-slate-600 dark:text-slate-300 hover:text-[var(--sf-primary)] transition-colors'
              : 'px-3 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-[var(--sf-primary)] hover:bg-[var(--sf-primary)]/10 transition-colors';

    const wrapClass =
        layoutPreset === 'minimal'
            ? 'hidden lg:flex items-center gap-8'
            : 'hidden lg:flex items-center gap-1 rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/60 dark:bg-slate-950/30 p-1';

    return (
        <nav className={wrapClass}>
            {navPages.map((p) => (
                <Link key={p.id} href={links.page(p.slug)} className={linkClass}>
                    {p.title}
                </Link>
            ))}
            <Link href={links.blog()} className={linkClass}>
                Blog
            </Link>
        </nav>
    );
}

function ToolbarRight({
    links,
    layoutPreset,
    storefrontShops,
    currentStorefrontShopId,
    shop,
    available_currencies,
    currency,
    currencySelectVariant = 'default',
}) {
    const catClass =
        layoutPreset === 'spotlight'
            ? 'hidden sm:inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-white/90 bg-white/10 border border-white/15 hover:bg-white/15 transition-colors'
            : 'hidden sm:inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-[var(--sf-primary)] bg-white/60 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 hover:border-[var(--sf-primary)] transition-colors';

    const cartClass =
        layoutPreset === 'spotlight'
            ? 'relative inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-white text-slate-900 hover:bg-[var(--sf-primary)] hover:text-white transition-colors shadow-sm ring-1 ring-white/20'
            : 'relative inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-[var(--sf-primary-hover)] transition-colors shadow-sm shadow-slate-900/10 dark:shadow-none ring-1 ring-slate-900/5 dark:ring-white/10';

    return (
        <div className="flex items-center gap-2 sm:gap-3">
            <Link href={links.catalog()} className={catClass}>
                Catalogue
            </Link>
            {storefrontShops?.length > 1 && (
                <select
                    value={currentStorefrontShopId ?? shop?.id}
                    onChange={(e) => {
                        const v = e.target.value;
                        if (v) router.post(route('ecommerce.storefront.switch-shop'), { shop_id: v });
                    }}
                    className={
                        layoutPreset === 'spotlight'
                            ? 'text-xs font-medium rounded-xl border border-white/20 bg-white/10 text-white py-1.5 pl-2 pr-8 focus:ring-[var(--sf-primary)]'
                            : 'text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 py-1.5 pl-2 pr-8 focus:ring-[var(--sf-primary)]'
                    }
                >
                    {storefrontShops.map((s) => (
                        <option key={s.id} value={s.id}>
                            {s.name}
                        </option>
                    ))}
                </select>
            )}
            <StorefrontCurrencySelect
                availableCurrencies={available_currencies}
                value={currency}
                variant={currencySelectVariant}
            />
            <ShoppingCart buttonClassName={cartClass} storefrontLinks />
        </div>
    );
}

function TrustGrid() {
    return (
        <div className="pt-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div className="rounded-2xl bg-white/80 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 p-3 backdrop-blur">
                <div className="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                    <Truck className="h-4 w-4 text-[var(--sf-primary)]" />
                    Livraison
                </div>
                <p className="mt-1 text-xs text-slate-600 dark:text-slate-300">Rapide & fiable</p>
            </div>
            <div className="rounded-2xl bg-white/80 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 p-3 backdrop-blur">
                <div className="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                    <ShieldCheck className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                    Paiement
                </div>
                <p className="mt-1 text-xs text-slate-600 dark:text-slate-300">Sécurisé</p>
            </div>
            <div className="rounded-2xl bg-white/80 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 p-3 backdrop-blur">
                <div className="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                    <Clock className="h-4 w-4 text-sky-600 dark:text-sky-400" />
                    24/7
                </div>
                <p className="mt-1 text-xs text-slate-600 dark:text-slate-300">Commande facile</p>
            </div>
            <div className="rounded-2xl bg-white/80 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 p-3 backdrop-blur">
                <div className="flex items-center gap-2 text-sm font-semibold text-slate-900 dark:text-white">
                    <Headphones className="h-4 w-4 text-violet-600 dark:text-violet-400" />
                    Support
                </div>
                <p className="mt-1 text-xs text-slate-600 dark:text-slate-300">Réactif</p>
            </div>
        </div>
    );
}

/**
 * En-tête + section héro pour la page d'accueil vitrine uniquement.
 * Préréglages : classic (défaut), minimal, editorial, spotlight (plans Pro).
 */
export default function StorefrontHeaderHero({
    layoutPreset = 'classic',
    shop,
    logoUrl,
    links,
    navPages,
    storefrontShops,
    currentStorefrontShopId,
    available_currencies = [],
    currency,
    heroBadge,
    heroTitle,
    heroSubtitle,
    heroDescription,
    primaryLabel,
    secondaryLabel,
    banners,
}) {
    const firstBanner = banners?.length > 0 ? banners[0] : null;
    const heroBg = firstBanner?.image_url;

    if (layoutPreset === 'minimal') {
        return (
            <>
                <header className="sticky top-0 z-50 border-b border-slate-200/80 dark:border-slate-800 bg-white/95 dark:bg-slate-950/90 backdrop-blur-xl">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-14 flex items-center justify-between gap-4">
                        <Link
                            href={links.index()}
                            className="rounded-2xl focus:outline-none focus:ring-2 focus:ring-[var(--sf-primary)]/30 min-w-0"
                        >
                            <BrandBlock logoUrl={logoUrl} shopName={shop?.name} />
                        </Link>
                        <NavLinks links={links} navPages={navPages} layoutPreset="minimal" />
                        <ToolbarRight
                            links={links}
                            layoutPreset="classic"
                            storefrontShops={storefrontShops}
                            currentStorefrontShopId={currentStorefrontShopId}
                            shop={shop}
                            available_currencies={available_currencies}
                            currency={currency}
                        />
                    </div>
                </header>
                <section className="relative overflow-hidden bg-gradient-to-b from-slate-50 via-white to-slate-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
                    <div className="absolute inset-0 pointer-events-none opacity-[0.35] dark:opacity-20">
                        <div className="absolute top-20 left-1/2 -translate-x-1/2 h-64 w-[42rem] rounded-full bg-[var(--sf-primary)]/25 blur-3xl" />
                    </div>
                    <div className="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-16 lg:py-24 text-center">
                        <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/90 dark:bg-slate-900/60 border border-slate-200/80 dark:border-slate-700 text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm">
                            <span className="inline-flex h-1.5 w-1.5 rounded-full bg-[var(--sf-primary)]" />
                            {heroBadge}
                        </div>
                        <h1 className="mt-6 text-4xl sm:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.08]">
                            {heroTitle}
                        </h1>
                        <p className="mt-4 text-lg sm:text-xl font-semibold text-[var(--sf-primary)]">{heroSubtitle}</p>
                        <p className="mt-4 text-base text-slate-600 dark:text-slate-300 max-w-2xl mx-auto leading-relaxed">
                            {heroDescription}
                        </p>
                        <div className="mt-8 flex flex-col sm:flex-row flex-wrap gap-3 justify-center">
                            <Link
                                href={links.catalog()}
                                className="inline-flex items-center justify-center px-7 py-3.5 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-semibold shadow-lg hover:bg-[var(--sf-primary-hover)] transition-colors"
                            >
                                {primaryLabel}
                                <ArrowRight className="h-4 w-4 ml-2" />
                            </Link>
                            <Link
                                href={links.catalog()}
                                className="inline-flex items-center justify-center px-7 py-3.5 rounded-2xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/40 text-sm font-semibold text-slate-800 dark:text-slate-100 hover:border-[var(--sf-primary)] transition-colors"
                            >
                                {secondaryLabel}
                            </Link>
                        </div>
                        {heroBg ? (
                            <div className="mt-12 rounded-3xl overflow-hidden border border-slate-200/80 dark:border-slate-700 shadow-xl shadow-slate-900/10 max-w-2xl mx-auto">
                                <img src={heroBg} alt={firstBanner?.title || ''} className="w-full h-48 sm:h-64 object-cover" />
                            </div>
                        ) : null}
                        <div className="mt-12 max-w-3xl mx-auto">
                            <TrustGrid />
                        </div>
                    </div>
                </section>
            </>
        );
    }

    if (layoutPreset === 'editorial') {
        return (
            <>
                <header className="sticky top-0 z-50 bg-white dark:bg-slate-950 border-b border-slate-200/80 dark:border-slate-800 shadow-sm shadow-slate-900/5">
                    <div className="bg-gradient-to-r from-[var(--sf-primary)]/15 via-amber-50/80 to-[var(--sf-primary)]/10 dark:from-[var(--sf-primary)]/25 dark:via-slate-900 dark:to-slate-900 border-b border-slate-200/50 dark:border-slate-800/80">
                        <p className="text-center text-[11px] sm:text-xs font-semibold text-slate-800 dark:text-slate-200 py-2 px-4 tracking-wide">
                            {heroBadge}
                        </p>
                    </div>
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 pb-2 flex justify-center">
                        <Link
                            href={links.index()}
                            className="rounded-2xl focus:outline-none focus:ring-2 focus:ring-[var(--sf-primary)]/30"
                        >
                            <BrandBlock logoUrl={logoUrl} shopName={shop?.name} variant="editorial" />
                        </Link>
                    </div>
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-4 flex flex-col lg:flex-row items-center gap-4 lg:justify-between">
                        <div className="w-full lg:flex-1 flex justify-center">
                            <NavLinks links={links} navPages={navPages} layoutPreset="minimal" />
                        </div>
                        <div className="shrink-0 flex justify-center w-full lg:w-auto">
                            <ToolbarRight
                                links={links}
                                layoutPreset="classic"
                                storefrontShops={storefrontShops}
                                currentStorefrontShopId={currentStorefrontShopId}
                                shop={shop}
                                available_currencies={available_currencies}
                                currency={currency}
                            />
                        </div>
                    </div>
                </header>
                <section className="relative min-h-[min(88vh,820px)] flex flex-col justify-end overflow-hidden">
                    <div className="absolute inset-0">
                        {heroBg ? (
                            <img src={heroBg} alt={firstBanner?.title || ''} className="h-full w-full object-cover" />
                        ) : (
                            <img
                                src="/images/ecommerce/hero-placeholder.jpg"
                                alt="Boutique"
                                className="h-full w-full object-cover"
                                onError={(e) => {
                                    e.currentTarget.style.display = 'none';
                                }}
                            />
                        )}
                        <div className="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/55 to-slate-900/20 dark:from-slate-950 dark:via-slate-950/70" />
                    </div>
                    <div className="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-14 pt-32 w-full">
                        <p className="text-xs font-semibold tracking-[0.2em] uppercase text-[var(--sf-primary)] mb-3">
                            {heroSubtitle}
                        </p>
                        <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-[1.05] max-w-3xl">
                            {heroTitle}
                        </h1>
                        <p className="mt-5 text-base sm:text-lg text-white/85 max-w-xl leading-relaxed">{heroDescription}</p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <Link
                                href={links.catalog()}
                                className="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-white text-slate-900 text-sm font-semibold shadow-lg hover:bg-[var(--sf-primary)] hover:text-white transition-colors"
                            >
                                {primaryLabel}
                                <ArrowRight className="h-4 w-4 ml-2" />
                            </Link>
                            <Link
                                href={links.catalog()}
                                className="inline-flex items-center justify-center px-6 py-3 rounded-2xl border border-white/40 text-sm font-semibold text-white hover:bg-white/10 transition-colors"
                            >
                                {secondaryLabel}
                            </Link>
                        </div>
                    </div>
                </section>
            </>
        );
    }

    if (layoutPreset === 'spotlight') {
        return (
            <>
                <header className="sticky top-0 z-50 border-b border-white/10 bg-slate-950/95 backdrop-blur-xl text-white">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
                        <Link
                            href={links.index()}
                            className="rounded-2xl focus:outline-none focus:ring-2 focus:ring-[var(--sf-primary)]/40 min-w-0"
                        >
                            <BrandBlock logoUrl={logoUrl} shopName={shop?.name} variant="spotlight" />
                        </Link>
                        <NavLinks links={links} navPages={navPages} layoutPreset="spotlight" />
                        <ToolbarRight
                            links={links}
                            layoutPreset="spotlight"
                            storefrontShops={storefrontShops}
                            currentStorefrontShopId={currentStorefrontShopId}
                            shop={shop}
                            available_currencies={available_currencies}
                            currency={currency}
                            currencySelectVariant="inverse"
                        />
                    </div>
                </header>
                <section className="relative overflow-hidden bg-slate-950">
                    <div className="absolute inset-0 opacity-40">
                        <div className="absolute -left-20 top-0 h-96 w-96 rounded-full bg-[var(--sf-primary)] blur-3xl" />
                        <div className="absolute right-0 bottom-0 h-80 w-80 rounded-full bg-sky-500/40 blur-3xl" />
                    </div>
                    <div className="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-14 lg:py-20">
                        <div className="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                            <div className="space-y-6">
                                <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/10 text-xs font-semibold text-white ring-1 ring-white/15">
                                    <Sparkles className="h-3.5 w-3.5 text-[var(--sf-primary)]" />
                                    {heroBadge}
                                </div>
                                <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-white leading-[1.05]">
                                    {heroTitle}
                                </h1>
                                <p className="text-xl font-semibold text-[var(--sf-primary)]">{heroSubtitle}</p>
                                <p className="text-base sm:text-lg text-slate-300 max-w-lg leading-relaxed">{heroDescription}</p>
                                <div className="flex flex-wrap gap-3 pt-2">
                                    <Link
                                        href={links.catalog()}
                                        className="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-[var(--sf-primary)] text-slate-950 text-sm font-bold shadow-lg shadow-[var(--sf-primary)]/25 hover:opacity-95 transition-opacity"
                                    >
                                        {primaryLabel}
                                        <ArrowRight className="h-4 w-4 ml-2" />
                                    </Link>
                                    <Link
                                        href={links.catalog()}
                                        className="inline-flex items-center justify-center px-6 py-3 rounded-2xl border border-white/25 text-sm font-semibold text-white hover:bg-white/10 transition-colors"
                                    >
                                        {secondaryLabel}
                                    </Link>
                                </div>
                                <TrustGrid />
                            </div>
                            <div className="relative">
                                <div className="absolute -inset-4 bg-gradient-to-tr from-[var(--sf-primary)]/30 to-sky-400/20 rounded-[2rem] blur-2xl" />
                                <div className="relative rounded-[2rem] overflow-hidden border border-white/10 bg-slate-900 aspect-[4/3] min-h-[260px] shadow-2xl">
                                    {heroBg ? (
                                        <Link href={firstBanner?.link || links.catalog()} className="block w-full h-full">
                                            <img
                                                src={heroBg}
                                                alt={firstBanner?.title || ''}
                                                className="w-full h-full object-cover"
                                            />
                                        </Link>
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-slate-500 text-sm px-6 text-center">
                                            Ajoutez une bannière « homepage » pour un impact maximal.
                                        </div>
                                    )}
                                    <div className="absolute inset-0 bg-gradient-to-t from-slate-950/90 via-transparent to-transparent pointer-events-none" />
                                    <div className="absolute bottom-0 left-0 right-0 p-6">
                                        <p className="text-white font-bold text-lg leading-tight">
                                            {firstBanner?.title || 'Votre sélection mise en lumière'}
                                        </p>
                                        <p className="text-white/75 text-sm mt-1">Explorez le catalogue en un clic.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </>
        );
    }

    /* classic */
    return (
        <>
            <header className="sticky top-0 z-50 border-b border-slate-200/70 dark:border-slate-800 bg-white/75 dark:bg-slate-950/60 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-slate-950/50">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <Link
                        href={links.index()}
                        className="flex items-center gap-3 rounded-2xl focus:outline-none focus:ring-2 focus:ring-[var(--sf-primary)]/30"
                    >
                        {logoUrl ? (
                            <span className="inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-white shadow-lg shadow-slate-900/10 ring-1 ring-slate-200 overflow-hidden">
                                <img src={logoUrl} alt={shop?.name || 'Logo'} className="w-full h-full object-contain" />
                            </span>
                        ) : (
                            <span className="inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-gradient-to-br from-[var(--sf-primary)] to-[var(--sf-secondary)] text-white font-bold text-sm shadow-lg shadow-[var(--sf-primary)]/25 ring-1 ring-white/30">
                                {shop?.name?.charAt(0) || 'S'}
                            </span>
                        )}
                        <div>
                            <span className="font-bold text-sm sm:text-base text-slate-900 dark:text-white block truncate">
                                {formatShopName(shop?.name, 'Ma Boutique')}
                            </span>
                            <span className="text-[11px] text-slate-500 dark:text-slate-400">Boutique en ligne</span>
                        </div>
                    </Link>
                    <div className="flex items-center gap-2 sm:gap-3">
                        <NavLinks links={links} navPages={navPages} layoutPreset="classic" />
                        <ToolbarRight
                            links={links}
                            layoutPreset="classic"
                            storefrontShops={storefrontShops}
                            currentStorefrontShopId={currentStorefrontShopId}
                            shop={shop}
                            available_currencies={available_currencies}
                            currency={currency}
                        />
                    </div>
                </div>
            </header>
            <section className="relative overflow-hidden">
                <div className="absolute inset-0">
                    {heroBg ? (
                        <img src={heroBg} alt={firstBanner?.title || 'Bannière'} className="h-full w-full object-cover" />
                    ) : (
                        <img
                            src="/images/ecommerce/hero-placeholder.jpg"
                            alt="Boutique en ligne"
                            className="h-full w-full object-cover"
                            onError={(e) => {
                                e.currentTarget.style.display = 'none';
                            }}
                        />
                    )}
                    <div className="absolute inset-0 bg-gradient-to-r from-white via-white/90 to-white/20 dark:from-slate-950 dark:via-slate-950/85 dark:to-slate-950/25" />
                    <div className="absolute inset-0 bg-gradient-to-t from-slate-50 via-transparent to-transparent dark:from-slate-950" />
                </div>

                <div className="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
                    <div className="grid lg:grid-cols-12 gap-10 items-center">
                        <div className="lg:col-span-6 space-y-5">
                            <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/80 dark:bg-slate-950/40 border border-slate-200/70 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 backdrop-blur">
                                <span className="inline-flex h-1.5 w-1.5 rounded-full bg-[var(--sf-primary)]" />
                                {heroBadge}
                            </div>
                            <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.05]">
                                {heroTitle}
                            </h1>
                            <p className="text-xl font-semibold text-[var(--sf-primary)]">{heroSubtitle}</p>
                            <p className="text-base sm:text-lg text-slate-700/90 dark:text-slate-200/80 max-w-xl break-words">
                                {heroDescription}
                            </p>

                            <div className="flex flex-wrap gap-3 pt-2">
                                <Link
                                    href={links.catalog()}
                                    className="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-semibold shadow-lg shadow-slate-900/10 dark:shadow-none hover:bg-[var(--sf-primary-hover)] transition-colors"
                                >
                                    {primaryLabel}
                                    <ArrowRight className="h-4 w-4 ml-2" />
                                </Link>
                                <Link
                                    href={links.catalog()}
                                    className="inline-flex items-center justify-center px-6 py-3 rounded-2xl bg-white/80 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 text-sm font-semibold text-slate-800 dark:text-slate-100 hover:border-[var(--sf-primary)] hover:text-[var(--sf-primary)] transition-colors backdrop-blur"
                                >
                                    {secondaryLabel}
                                </Link>
                            </div>

                            <TrustGrid />
                        </div>

                        <div className="lg:col-span-6">
                            <div className="relative">
                                <div className="absolute -inset-10 bg-gradient-to-tr from-[var(--sf-primary)]/20 via-[var(--sf-primary)]/10 to-sky-500/20 blur-3xl pointer-events-none" />
                                <div className="relative rounded-[2rem] overflow-hidden border border-white/40 dark:border-slate-800 bg-slate-900 shadow-2xl shadow-slate-900/20 aspect-[4/3] min-h-[260px]">
                                    {heroBg ? (
                                        <Link href={firstBanner?.link || links.catalog()} className="block w-full h-full">
                                            <img
                                                src={heroBg}
                                                alt={firstBanner?.title || 'Bannière'}
                                                className="w-full h-full object-cover"
                                            />
                                        </Link>
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center text-slate-300 text-sm">
                                            Ajoutez une bannière “homepage” pour sublimer l’accueil.
                                        </div>
                                    )}
                                    <div className="absolute inset-0 bg-gradient-to-tr from-slate-950/80 via-slate-950/30 to-transparent" />
                                    <div className="absolute inset-0 p-6 flex flex-col justify-between">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/10 text-xs font-semibold text-white ring-1 ring-white/15">
                                                <Sparkles className="h-3.5 w-3.5 text-[var(--sf-primary)]" />
                                                Offre du moment
                                            </span>
                                            <span className="inline-flex items-center px-3 py-1.5 rounded-full bg-white/10 text-[11px] text-white/90 ring-1 ring-white/15">
                                                {shop?.currency || 'CDF'} • Boutique en direct
                                            </span>
                                        </div>
                                        <div className="space-y-2">
                                            <p className="text-white font-bold text-lg leading-tight">
                                                {firstBanner?.title || 'Découvrez nos nouveautés'}
                                            </p>
                                            <p className="text-white/80 text-sm">
                                                Cliquez pour explorer le catalogue et profiter des meilleures offres.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
