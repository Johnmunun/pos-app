import { useState, useEffect } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { ShieldCheck, Truck, Clock, Headphones, ArrowRight, Sparkles, Facebook, Instagram, Youtube } from 'lucide-react';
import { formatCurrency } from '@/lib/currency';
import { CartProvider } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';

function shouldShowPageInNav(page) {
    if (!page) return false;
    const title = (page.title || '').toLowerCase();
    const slug = (page.slug || '').toLowerCase();

    const isCgv =
        (slug && (slug.includes('cgv') || slug.includes('conditions-generales-de-vente'))) ||
        (title && title.includes('condition') && title.includes('vente'));

    const isPrivacy =
        (slug && slug.includes('politique-de-confidentialite')) ||
        (title && title.includes('politique') && title.includes('confidentialit'));

    return !isCgv && !isPrivacy;
}

function ProductCardSimple({ product, currency, productUrl }) {
    const price = formatCurrency(product.price_amount ?? 0, product.price_currency || currency);

    return (
        <Link
            href={productUrl(product.id)}
            className="group block h-full"
        >
            <div className="h-full bg-white dark:bg-slate-900/80 rounded-2xl overflow-hidden border border-slate-200/80 dark:border-slate-700/80 hover:border-[var(--sf-primary)]/80 dark:hover:border-[var(--sf-primary)] hover:shadow-xl hover:shadow-[var(--sf-primary)]/10 transition-all duration-300">
                <div className="relative aspect-[4/5] sm:aspect-[4/3] bg-gradient-to-br from-slate-100 to-slate-50 dark:from-slate-800 dark:to-slate-900 overflow-hidden">
                    {product.image_url ? (
                        <img
                            src={product.image_url}
                            alt={product.name}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center text-slate-400 dark:text-slate-500 text-xs sm:text-sm">
                            Aucune image
                        </div>
                    )}
                    <div className="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity" />
                    {product.is_new && (
                        <span className="absolute left-3 top-3 inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            <Sparkles className="h-3 w-3" /> Nouveau
                        </span>
                    )}
                </div>
                <div className="flex flex-col px-4 py-4 sm:px-5 sm:py-5">
                    <h3 className="text-[13px] sm:text-sm font-semibold text-slate-900 dark:text-white mb-1 line-clamp-2 group-hover:text-[var(--sf-primary)] transition-colors">
                        {product.name}
                    </h3>
                    <div className="mt-auto flex items-center justify-between pt-3 sm:pt-4">
                        <span className="text-sm sm:text-base font-bold text-[var(--sf-primary)]">
                            {price}
                        </span>
                        <span className="inline-flex items-center text-xs font-medium text-slate-500 dark:text-slate-400 group-hover:text-[var(--sf-primary)] transition-colors">
                            Voir
                            <ArrowRight className="h-3.5 w-3 ml-1 group-hover:translate-x-0.5 transition-transform" />
                        </span>
                    </div>
                </div>
            </div>
        </Link>
    );
}

export default function EcommerceStorefront({ shop, config, featuredProducts = [], newArrivals = [], banners = [], cmsPages = [], storefrontShops = [], currentStorefrontShopId }) {
    const links = useStorefrontLinks();
    const { shop: sharedShop } = usePage().props;
    const currency = shop?.currency || sharedShop?.currency || 'CDF';
    const logoUrl = shop?.logo_url || sharedShop?.logo_url || null;

    const heroBadge = config?.hero_badge || 'Season Sale';
    const heroTitle = config?.hero_title || "MEN'S FASHION";
    const heroSubtitle = config?.hero_subtitle || 'Min. 35–70% Off';
    const heroDescription =
        config?.hero_description ||
        'Découvrez une sélection de produits modernes pour votre clientèle, avec une expérience d’achat fluide.';
    const primaryLabel = config?.hero_primary_label || 'Voir la boutique';
    const secondaryLabel = config?.hero_secondary_label || 'Découvrir les nouveautés';

    const sliderBanners = banners.filter((b) => b.position === 'slider' && (b.image_url || b.title));
    const promotionBanner = banners.find((b) => b.position === 'promotion' && (b.image_url || b.title));
    const [activeSlide, setActiveSlide] = useState(0);

    const whatsappNumber = config?.whatsapp_number || null;
    const whatsappSupportEnabled = !!config?.whatsapp_support_enabled;

    useEffect(() => {
        if (sliderBanners.length <= 1) return;
        const interval = setInterval(() => {
            setActiveSlide((prev) => (prev + 1) % sliderBanners.length);
        }, 7000);
        return () => clearInterval(interval);
    }, [sliderBanners.length]);

    const navPages = (cmsPages || []).filter(shouldShowPageInNav).slice(0, 4);

    const content = (
        <div className="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 text-slate-900 dark:text-slate-50">
            <Head title="Boutique en ligne" />

            {/* Header */}
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
                                {shop?.name || 'Ma Boutique'}
                            </span>
                            <span className="text-[11px] text-slate-500 dark:text-slate-400">Boutique en ligne</span>
                        </div>
                    </Link>
                    <div className="flex items-center gap-2 sm:gap-3">
                        <nav className="hidden lg:flex items-center gap-1 rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/60 dark:bg-slate-950/30 p-1">
                            {navPages.map((p) => (
                                <Link
                                    key={p.id}
                                    href={links.page(p.slug)}
                                    className="px-3 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-[var(--sf-primary)] hover:bg-[var(--sf-primary)]/10 transition-colors"
                                >
                                    {p.title}
                                </Link>
                            ))}
                            <Link
                                href={links.blog()}
                                className="px-3 py-2 rounded-xl text-sm font-semibold text-slate-600 dark:text-slate-300 hover:text-[var(--sf-primary)] hover:bg-[var(--sf-primary)]/10 transition-colors"
                            >
                                Blog
                            </Link>
                        </nav>
                        <Link
                            href={links.catalog()}
                            className="hidden sm:inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold text-slate-700 dark:text-slate-200 hover:text-[var(--sf-primary)] bg-white/60 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 hover:border-[var(--sf-primary)] transition-colors"
                        >
                            Catalogue
                        </Link>
                        {storefrontShops?.length > 1 && (
                            <select
                                value={currentStorefrontShopId ?? shop?.id}
                                onChange={(e) => { const v = e.target.value; if (v) router.post(route('ecommerce.storefront.switch-shop'), { shop_id: v }); }}
                                className="text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-200 py-1.5 pl-2 pr-8 focus:ring-[var(--sf-primary)]"
                            >
                                {storefrontShops.map((s) => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </select>
                        )}
                        <ShoppingCart
                            buttonClassName="relative inline-flex items-center justify-center h-10 w-10 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-[var(--sf-primary-hover)] transition-colors shadow-sm shadow-slate-900/10 dark:shadow-none ring-1 ring-slate-900/5 dark:ring-white/10"
                            storefrontLinks
                        />
                    </div>
                </div>
            </header>

            {/* Contenu principal */}
            <main>
                {/* Hero principal */}
                <section className="relative overflow-hidden">
                    {/* Background media */}
                    <div className="absolute inset-0">
                        {banners.length > 0 && banners[0].image_url ? (
                            <img
                                src={banners[0].image_url}
                                alt={banners[0].title || 'Bannière'}
                                className="h-full w-full object-cover"
                            />
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
                            {/* Copy */}
                            <div className="lg:col-span-6 space-y-5">
                                <div className="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full bg-white/80 dark:bg-slate-950/40 border border-slate-200/70 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-200 backdrop-blur">
                                    <span className="inline-flex h-1.5 w-1.5 rounded-full bg-[var(--sf-primary)]" />
                                    {heroBadge}
                                </div>
                                <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-[1.05]">
                                    {heroTitle}
                                </h1>
                                <p className="text-xl font-semibold text-[var(--sf-primary)]">
                                    {heroSubtitle}
                                </p>
                                <p className="text-base sm:text-lg text-slate-700/90 dark:text-slate-200/80 max-w-xl">
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
                            </div>

                            {/* Visual card */}
                            <div className="lg:col-span-6">
                                <div className="relative">
                                    <div className="absolute -inset-10 bg-gradient-to-tr from-[var(--sf-primary)]/20 via-[var(--sf-primary)]/10 to-sky-500/20 blur-3xl pointer-events-none" />
                                    <div className="relative rounded-[2rem] overflow-hidden border border-white/40 dark:border-slate-800 bg-slate-900 shadow-2xl shadow-slate-900/20 aspect-[4/3] min-h-[260px]">
                                        {banners.length > 0 && banners[0].image_url ? (
                                            <Link
                                                href={banners[0].link || links.catalog()}
                                                className="block w-full h-full"
                                            >
                                                <img
                                                    src={banners[0].image_url}
                                                    alt={banners[0].title || 'Bannière'}
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
                                                    {banners?.[0]?.title || 'Découvrez nos nouveautés'}
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

                {/* Bloc promotion (bannière dédiée) */}
                {promotionBanner && (
                    <section className="py-8 border-y border-slate-200/80 dark:border-slate-800 bg-gradient-to-r from-[var(--sf-primary)]/10 via-white to-[var(--sf-primary)]/10 dark:from-[var(--sf-primary)]/20 dark:via-slate-950 dark:to-[var(--sf-primary)]/10">
                        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center gap-6">
                            <div className="flex-1 space-y-2">
                                <p className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-[var(--sf-primary)]/20 text-[var(--sf-secondary)] text-xs font-semibold">
                                    <Sparkles className="h-3.5 w-3.5" />
                                    Promotion spéciale
                                </p>
                                <h2 className="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                                    {promotionBanner.title || 'Offre promotionnelle en cours'}
                                </h2>
                                <p className="text-sm text-slate-600 dark:text-slate-300 max-w-xl">
                                    Profitez de cette promotion pour booster vos ventes ou mettre en avant un produit phare de votre
                                    boutique.
                                </p>
                                <div className="pt-2">
                                    <Link
                                        href={promotionBanner.link || links.catalog()}
                                        className="inline-flex items-center px-5 py-2.5 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-semibold hover:bg-[var(--sf-primary-hover)] transition-colors"
                                    >
                                        Voir l&apos;offre
                                        <ArrowRight className="h-4 w-4 ml-2" />
                                    </Link>
                                </div>
                            </div>
                            {promotionBanner.image_url && (
                                <Link
                                    href={promotionBanner.link || links.catalog()}
                                    className="flex-shrink-0 w-full max-w-sm rounded-2xl overflow-hidden border border-[var(--sf-primary)]/70 shadow-md shadow-[var(--sf-primary)]/10"
                                >
                                    <img
                                        src={promotionBanner.image_url}
                                        alt={promotionBanner.title || 'Promotion'}
                                        className="w-full h-48 object-cover"
                                    />
                                </Link>
                            )}
                        </div>
                    </section>
                )}

                {/* Avantages */}
                <section className="border-y border-slate-200/80 dark:border-slate-800">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white/80 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-[var(--sf-primary)]/50 transition-colors">
                            <div className="flex-shrink-0 h-11 w-11 rounded-xl bg-[var(--sf-primary)]/20 flex items-center justify-center">
                                <Truck className="h-5 w-5 text-[var(--sf-primary)]" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">Livraison rapide</p>
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Dans la journée sur votre ville</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white/80 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-emerald-200 dark:hover:border-emerald-800 transition-colors">
                            <div className="flex-shrink-0 h-11 w-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                <ShieldCheck className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">Paiement sécurisé</p>
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Mobile money, carte et espèces</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white/80 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-sky-200 dark:hover:border-sky-800 transition-colors">
                            <div className="flex-shrink-0 h-11 w-11 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
                                <Clock className="h-5 w-5 text-sky-600 dark:text-sky-400" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">Ouvert 24/7</p>
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Commandez à tout moment</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white/80 dark:bg-slate-900/50 border border-slate-100 dark:border-slate-800 hover:border-violet-200 dark:hover:border-violet-800 transition-colors">
                            <div className="flex-shrink-0 h-11 w-11 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                                <Headphones className="h-5 w-5 text-violet-600 dark:text-violet-400" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">Support client</p>
                                <p className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">À votre écoute</p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Produits en vedette */}
                <section className="py-12 lg:py-16">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-end justify-between mb-5">
                            <div>
                                <h2 className="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                                    Produits en vedette
                                </h2>
                                <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                    Une sélection courte pour mettre en avant vos meilleurs articles.
                                </p>
                            </div>
                            <Link
                                href={links.catalog()}
                                className="hidden sm:inline-flex items-center text-xs font-medium text-[var(--sf-primary)] hover:text-[var(--sf-secondary)]"
                            >
                                Voir tout le catalogue
                                <ArrowRight className="h-3 w-3 ml-1" />
                            </Link>
                        </div>

                        {featuredProducts.length === 0 ? (
                            <div className="text-sm text-slate-500 dark:text-slate-400">
                                Aucun produit publié pour le moment. Activez l&apos;option &quot;Publier sur e-commerce&quot; dans vos
                                produits pour remplir cette section.
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                                {featuredProducts.map((p) => (
                                    <ProductCardSimple key={p.id} product={p} currency={currency} productUrl={links.product} />
                                ))}
                            </div>
                        )}
                    </div>
                </section>

                {/* Nouveautés */}
                <section className="pb-16">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex items-end justify-between mb-5">
                            <div>
                                <h2 className="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                                    Nouveautés
                                </h2>
                                <p className="text-xs sm:text-sm text-slate-500 dark:text-slate-400">
                                    Derniers articles ajoutés à votre catalogue.
                                </p>
                            </div>
                        </div>

                        {newArrivals.length === 0 ? (
                            <div className="text-sm text-slate-500 dark:text-slate-400">
                                Ajoutez de nouveaux produits dans le module Commerce / GlobalCommerce pour voir cette section se
                                remplir automatiquement.
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                                {newArrivals.map((p) => (
                                    <ProductCardSimple key={p.id} product={p} currency={currency} productUrl={links.product} />
                                ))}
                            </div>
                        )}
                    </div>
                </section>

                {/* Bannières slider (si plusieurs) */}
                {sliderBanners.length > 0 && (
                    <section className="py-10 border-t border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/40">
                        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-sm font-semibold tracking-wide text-slate-500 dark:text-slate-400 uppercase">
                                    Offres du moment
                                </h2>
                                {sliderBanners.length > 1 && (
                                    <div className="hidden sm:flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400">
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setActiveSlide((prev) =>
                                                    prev === 0 ? sliderBanners.length - 1 : prev - 1
                                                )
                                            }
                                            className="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-[var(--sf-primary)] transition-colors"
                                        >
                                            ‹
                                        </button>
                                        <button
                                            type="button"
                                            onClick={() =>
                                                setActiveSlide((prev) =>
                                                    (prev + 1) % sliderBanners.length
                                                )
                                            }
                                            className="inline-flex h-7 w-7 items-center justify-center rounded-full border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:border-[var(--sf-primary)] transition-colors"
                                        >
                                            ›
                                        </button>
                                    </div>
                                )}
                            </div>

                            <div className="relative">
                                <div className="overflow-hidden rounded-2xl bg-slate-900/90 dark:bg-slate-900">
                                    <div
                                        className="flex transition-transform duration-500 ease-out"
                                        style={{ transform: `translateX(-${activeSlide * 100}%)` }}
                                    >
                                        {sliderBanners.map((b) => (
                                            <Link
                                                key={b.id}
                                                href={b.link || '#'}
                                                className="min-w-full flex-shrink-0 h-40 sm:h-52 md:h-56 relative"
                                            >
                                                {b.image_url ? (
                                                    <img
                                                        src={b.image_url}
                                                        alt={b.title}
                                                        className="w-full h-full object-cover"
                                                    />
                                                ) : (
                                                    <div className="w-full h-full flex items-center justify-center text-slate-200 text-sm">
                                                        {b.title}
                                                    </div>
                                                )}
                                                <div className="absolute inset-0 bg-gradient-to-r from-black/70 via-black/30 to-transparent" />
                                                <div className="absolute inset-y-0 left-0 px-6 sm:px-8 flex flex-col justify-center">
                                                    <p className="text-xs font-semibold tracking-[0.18em] uppercase text-[var(--sf-primary)]">
                                                        Promotion
                                                    </p>
                                                    <p className="mt-2 text-base sm:text-lg font-bold text-white line-clamp-2 max-w-md">
                                                        {b.title}
                                                    </p>
                                                    <p className="mt-2 text-[11px] sm:text-xs text-slate-200/90">
                                                        Cliquez pour découvrir les détails de l&apos;offre.
                                                    </p>
                                                </div>
                                            </Link>
                                        ))}
                                    </div>
                                </div>

                                {sliderBanners.length > 1 && (
                                    <div className="mt-3 flex items-center justify-center gap-2">
                                        {sliderBanners.map((b, idx) => (
                                            <button
                                                key={b.id}
                                                type="button"
                                                onClick={() => setActiveSlide(idx)}
                                                className={`h-2.5 rounded-full transition-all ${
                                                    activeSlide === idx
                                                        ? 'w-6 bg-[var(--sf-primary)]'
                                                        : 'w-2 bg-slate-300 dark:bg-slate-600 hover:bg-[var(--sf-primary)]/70'
                                                }`}
                                            />
                                        ))}
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>
                )}

                {/* Footer */}
                <footer className="border-t border-slate-200/80 dark:border-slate-800 bg-white/70 dark:bg-slate-950/40 backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-slate-950/30">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                        <div className="grid gap-10 lg:grid-cols-12">
                            {/* Brand */}
                            <div className="lg:col-span-4">
                                <Link href={links.index()} className="inline-flex items-center gap-3">
                                    <span className="inline-flex items-center justify-center h-11 w-11 rounded-2xl bg-gradient-to-br from-[var(--sf-primary)] to-[var(--sf-secondary)] text-white font-bold text-sm shadow-lg shadow-[var(--sf-primary)]/20">
                                        {shop?.name?.charAt(0) || 'S'}
                                    </span>
                                    <div className="leading-tight">
                                        <div className="text-sm font-bold text-slate-900 dark:text-white">
                                            {shop?.name || 'Ma Boutique'}
                                        </div>
                                        <div className="text-xs text-slate-500 dark:text-slate-400">Boutique en ligne</div>
                                    </div>
                                </Link>
                                <p className="mt-4 text-sm text-slate-600 dark:text-slate-300 max-w-sm">
                                    Une expérience d’achat simple, rapide et sécurisée. Découvrez nos nouveautés et nos produits en vedette.
                                </p>
                                <div className="mt-5 flex flex-wrap gap-2">
                                    <Link
                                        href={links.catalog()}
                                        className="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 text-sm font-semibold hover:bg-[var(--sf-primary-hover)] transition-colors"
                                    >
                                        Voir le catalogue
                                    </Link>
                                    <Link
                                        href={links.cart()}
                                        className="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:border-[var(--sf-primary)] hover:text-[var(--sf-primary)] transition-colors"
                                    >
                                        Panier
                                    </Link>
                                </div>
                            </div>

                            {/* Links */}
                            <div className="lg:col-span-8">
                                <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                                    <div>
                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Boutique
                                        </p>
                                        <ul className="mt-4 space-y-2">
                                            <li>
                                                <Link
                                                    href={links.catalog()}
                                                    className="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-[var(--sf-primary)] transition-colors"
                                                >
                                                    Catalogue
                                                </Link>
                                            </li>
                                            <li>
                                                <Link
                                                    href={links.cart()}
                                                    className="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-[var(--sf-primary)] transition-colors"
                                                >
                                                    Panier
                                                </Link>
                                            </li>
                                        </ul>
                                    </div>

                                    <div className={cmsPages.length > 0 ? '' : 'sm:col-span-2 lg:col-span-2'}>
                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Informations
                                        </p>
                                        {cmsPages.length > 0 ? (
                                            <ul className="mt-4 space-y-2">
                                                {cmsPages.map((p) => (
                                                    <li key={p.id}>
                                                        <Link
                                                            href={links.page(p.slug)}
                                                            className="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-[var(--sf-primary)] transition-colors"
                                                        >
                                                            {p.title}
                                                        </Link>
                                                    </li>
                                                ))}
                                            </ul>
                                        ) : (
                                            <p className="mt-4 text-sm text-slate-600 dark:text-slate-300">
                                                Retrouvez bientôt ici nos pages d’informations (livraison, retours, contact).
                                            </p>
                                        )}
                                    </div>

                                    <div className="hidden lg:block">
                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                            Confiance
                                        </p>
                                        <ul className="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                            <li className="flex items-center gap-2">
                                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500/90" />
                                                Paiement sécurisé
                                            </li>
                                            <li className="flex items-center gap-2">
                                                <span className="h-1.5 w-1.5 rounded-full bg-sky-500/90" />
                                                Suivi des commandes
                                            </li>
                                            <li className="flex items-center gap-2">
                                                <span className="h-1.5 w-1.5 rounded-full bg-[var(--sf-primary)]/90" />
                                                Support réactif
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="mt-10 pt-8 border-t border-slate-200/80 dark:border-slate-800 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                © {new Date().getFullYear()} {shop?.name || 'Ma Boutique'}. Tous droits réservés.
                            </p>
                            <div className="flex items-center gap-4">
                                {(config?.social_facebook_url ||
                                    config?.social_instagram_url ||
                                    config?.social_tiktok_url ||
                                    config?.social_youtube_url) && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-[11px] text-slate-500 dark:text-slate-400">Suivez-nous</span>
                                        <div className="flex items-center gap-2">
                                            {config?.social_facebook_url && (
                                                <a
                                                    href={config.social_facebook_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-200 hover:bg-[var(--sf-primary)]/20 hover:text-[var(--sf-primary)] transition-colors"
                                                >
                                                    <Facebook className="h-3.5 w-3.5" />
                                                </a>
                                            )}
                                            {config?.social_instagram_url && (
                                                <a
                                                    href={config.social_instagram_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-200 hover:bg-[var(--sf-primary)]/20 hover:text-[var(--sf-primary)] transition-colors"
                                                >
                                                    <Instagram className="h-3.5 w-3.5" />
                                                </a>
                                            )}
                                            {config?.social_youtube_url && (
                                                <a
                                                    href={config.social_youtube_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-200 hover:bg-[var(--sf-primary)]/20 hover:text-[var(--sf-primary)] transition-colors"
                                                >
                                                    <Youtube className="h-3.5 w-3.5" />
                                                </a>
                                            )}
                                        </div>
                                    </div>
                                )}
                                <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                    Propulsé par une vitrine moderne
                                </p>
                            </div>
                        </div>
                    </div>
                </footer>
            </main>

            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} iconOnly />
        </div>
    );

    return <CartProvider currency={currency}>{content}</CartProvider>;
}

