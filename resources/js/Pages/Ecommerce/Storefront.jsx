import { Head, Link, usePage } from '@inertiajs/react';
import { ShieldCheck, Truck, Headphones, ArrowRight } from 'lucide-react';
import { formatCurrency } from '@/lib/currency';
import { CartProvider } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';

function ProductCardSimple({ product, currency }) {
    const price = formatCurrency(product.price_amount ?? 0, product.price_currency || currency);

    return (
        <div className="group bg-white dark:bg-slate-900 rounded-xl shadow-sm hover:shadow-lg border border-slate-100 dark:border-slate-800 overflow-hidden transition-all duration-200">
            <Link href={route('ecommerce.catalog.show', product.id)} className="flex flex-col h-full">
                <div className="relative aspect-[4/3] bg-slate-100 dark:bg-slate-800 overflow-hidden">
                    {product.image_url ? (
                        <img
                            src={product.image_url}
                            alt={product.name}
                            className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                        />
                    ) : (
                        <div className="w-full h-full flex items-center justify-center text-slate-400 text-xs">
                            Aucune image
                        </div>
                    )}
                    {product.is_new && (
                        <span className="absolute left-3 top-3 inline-flex items-center px-2 py-1 text-[11px] font-semibold rounded-full bg-emerald-500 text-white shadow-sm">
                            Nouveau
                        </span>
                    )}
                </div>
                <div className="flex-1 flex flex-col p-4">
                    <h3 className="text-sm font-semibold text-slate-900 dark:text-white mb-1 line-clamp-2 group-hover:text-amber-600 dark:group-hover:text-amber-400">
                        {product.name}
                    </h3>
                    <div className="mt-auto flex items-center justify-between pt-3">
                        <span className="text-base font-bold text-amber-600 dark:text-amber-400">
                            {price}
                        </span>
                        <span className="inline-flex items-center text-[12px] font-medium text-slate-500 dark:text-slate-400 group-hover:text-slate-900 dark:group-hover:text-slate-100">
                            Voir le détail
                            <ArrowRight className="h-3 w-3 ml-1" />
                        </span>
                    </div>
                </div>
            </Link>
        </div>
    );
}

export default function EcommerceStorefront({ shop, config, featuredProducts = [], newArrivals = [], banners = [], cmsPages = [] }) {
    const { shop: sharedShop } = usePage().props;
    const currency = shop?.currency || sharedShop?.currency || 'CDF';

    const heroBadge = config?.hero_badge || 'Season Sale';
    const heroTitle = config?.hero_title || "MEN'S FASHION";
    const heroSubtitle = config?.hero_subtitle || 'Min. 35–70% Off';
    const heroDescription =
        config?.hero_description ||
        'Découvrez une sélection de produits modernes pour votre clientèle, avec une expérience d’achat fluide.';
    const primaryLabel = config?.hero_primary_label || 'Voir la boutique';
    const secondaryLabel = config?.hero_secondary_label || 'Découvrir les nouveautés';

    const content = (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-50">
            <Head title="Boutique en ligne" />

            {/* Header simple pour la vitrine */}
            <header className="border-b border-slate-200/70 dark:border-slate-800 bg-white/90 dark:bg-slate-950/90 backdrop-blur">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <span className="inline-flex items-center justify-center h-9 w-9 rounded-full bg-amber-500 text-white font-bold text-sm shadow-sm">
                            {shop?.name?.charAt(0) || 'S'}
                        </span>
                        <div className="flex flex-col">
                            <span className="font-semibold text-sm sm:text-base truncate">
                                {shop?.name || 'Ma Boutique en ligne'}
                            </span>
                            <span className="text-[11px] text-slate-500 dark:text-slate-400">
                                Powered by POS E-commerce
                            </span>
                        </div>
                    </div>
                    <div className="flex items-center gap-3 sm:gap-4">
                        {cmsPages.length > 0 && (
                            <nav className="hidden md:flex items-center gap-2">
                                {cmsPages.slice(0, 5).map((p) => (
                                    <Link
                                        key={p.id}
                                        href={route('ecommerce.storefront.page', p.slug)}
                                        className="text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400"
                                    >
                                        {p.title}
                                    </Link>
                                ))}
                            </nav>
                        )}
                        <Link
                            href={route('ecommerce.catalog.index')}
                            className="hidden sm:inline-flex items-center text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400"
                        >
                            Parcourir tout le catalogue
                        </Link>
                        <ShoppingCart buttonClassName="relative inline-flex items-center justify-center h-9 w-9 rounded-full bg-slate-900 text-white hover:bg-amber-600 transition-colors" />
                    </div>
                </div>
            </header>

            {/* Contenu principal */}
            <main>
                {/* Hero principal */}
                <section className="bg-white dark:bg-slate-950">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 lg:py-14 grid lg:grid-cols-2 gap-10 items-center">
                        <div className="space-y-4">
                            <div className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-50 dark:bg-amber-900/20 border border-amber-200/80 dark:border-amber-800/80 text-xs font-semibold text-amber-700 dark:text-amber-300">
                                {heroBadge}
                            </div>
                            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-slate-900 dark:text-white leading-tight">
                                {heroTitle}
                            </h1>
                            <p className="text-lg font-semibold text-amber-600 dark:text-amber-400">
                                {heroSubtitle}
                            </p>
                            <p className="text-sm sm:text-base text-slate-600 dark:text-slate-300 max-w-xl">
                                {heroDescription}
                            </p>
                            <div className="flex flex-wrap gap-3 pt-2">
                                <Link
                                    href={route('ecommerce.catalog.index')}
                                    className="inline-flex items-center justify-center px-5 py-3 rounded-full bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold shadow-lg shadow-amber-500/30 transition-colors"
                                >
                                    {primaryLabel}
                                    <ArrowRight className="h-4 w-4 ml-2" />
                                </Link>
                                <Link
                                    href={route('ecommerce.catalog.index')}
                                    className="inline-flex items-center justify-center px-5 py-3 rounded-full border border-slate-300 dark:border-slate-600 text-sm font-semibold text-slate-700 dark:text-slate-200 hover:border-amber-500 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                >
                                    {secondaryLabel}
                                </Link>
                            </div>
                        </div>

                        {/* Visuel hero - bannière CMS ou placeholder */}
                        <div className="relative">
                            <div className="absolute -inset-6 bg-gradient-to-tr from-amber-500/10 via-amber-400/5 to-sky-500/10 blur-3xl pointer-events-none" />
                            <div className="relative bg-slate-900 rounded-3xl overflow-hidden shadow-2xl aspect-[4/3] min-h-[240px]">
                                {banners.length > 0 && banners[0].image_url ? (
                                    <Link
                                        href={banners[0].link || route('ecommerce.catalog.index')}
                                        className="block w-full h-full"
                                    >
                                        <img
                                            src={banners[0].image_url}
                                            alt={banners[0].title || 'Bannière'}
                                            className="w-full h-full object-cover"
                                        />
                                    </Link>
                                ) : (
                                    <img
                                        src="/images/ecommerce/hero-placeholder.jpg"
                                        alt="Boutique en ligne"
                                        className="w-full h-full object-cover opacity-90"
                                        onError={(e) => {
                                            e.currentTarget.style.display = 'none';
                                        }}
                                    />
                                )}
                                <div className="absolute inset-0 bg-gradient-to-tr from-slate-900/80 via-slate-900/40 to-transparent" />
                                <div className="absolute inset-0 p-5 flex flex-col justify-between">
                                    <div className="flex items-center justify-between">
                                        <span className="text-xs font-semibold text-amber-300 uppercase tracking-[0.15em]">
                                            Nouvelle collection
                                        </span>
                                        <span className="inline-flex items-center px-2 py-1 rounded-full bg-slate-800/80 text-[11px] text-slate-100">
                                            {shop?.currency || 'CDF'} • Vitrine en temps réel
                                        </span>
                                    </div>
                                    <div className="space-y-2">
                                        <p className="text-xs text-slate-300">
                                            Interface d’aperçu identique à celle de vos clients finaux.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Avantages */}
                <section className="border-y border-slate-100 dark:border-slate-800 bg-slate-50/60 dark:bg-slate-950/60">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                <Truck className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-slate-900 dark:text-white">Livraison rapide</p>
                                <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                    Dans la journée sur votre ville
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                <ShieldCheck className="h-4 w-4 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-slate-900 dark:text-white">Paiement sécurisé</p>
                                <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                    Mobile money, carte et espèces
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
                                <span className="text-sky-600 dark:text-sky-400 text-xs font-bold">24/7</span>
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-slate-900 dark:text-white">Boutique toujours ouverte</p>
                                <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                    Vos clients commandent à tout moment
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-3">
                            <div className="h-9 w-9 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center">
                                <Headphones className="h-4 w-4 text-slate-700 dark:text-slate-200" />
                            </div>
                            <div>
                                <p className="text-xs font-semibold text-slate-900 dark:text-white">Support dédié</p>
                                <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                    Assistance pour configurer votre vitrine
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Produits en vedette */}
                <section className="py-10 lg:py-12">
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
                                href={route('ecommerce.catalog.index')}
                                className="hidden sm:inline-flex items-center text-xs font-medium text-amber-700 dark:text-amber-400 hover:text-amber-800"
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
                                    <ProductCardSimple key={p.id} product={p} currency={currency} />
                                ))}
                            </div>
                        )}
                    </div>
                </section>

                {/* Nouveautés */}
                <section className="pb-12">
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
                                    <ProductCardSimple key={p.id} product={p} currency={currency} />
                                ))}
                            </div>
                        )}
                    </div>
                </section>

                {/* Bannières slider (si plusieurs) */}
                {banners.filter((b) => b.position === 'slider').length > 0 && (
                    <section className="py-6 border-t border-slate-100 dark:border-slate-800">
                        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div className="flex gap-4 overflow-x-auto pb-2 snap-x snap-mandatory">
                                {banners
                                    .filter((b) => b.position === 'slider')
                                    .map((b) => (
                                        <Link
                                            key={b.id}
                                            href={b.link || '#'}
                                            className="flex-shrink-0 w-[280px] sm:w-[320px] snap-center rounded-xl overflow-hidden bg-slate-200 dark:bg-slate-800"
                                        >
                                            {b.image_url ? (
                                                <img src={b.image_url} alt={b.title} className="w-full h-32 object-cover" />
                                            ) : (
                                                <div className="w-full h-32 flex items-center justify-center text-slate-400 text-sm">{b.title}</div>
                                            )}
                                            <div className="p-2 text-xs font-medium truncate">{b.title}</div>
                                        </Link>
                                    ))}
                            </div>
                        </div>
                    </section>
                )}

                {/* Footer avec liens pages CMS */}
                {cmsPages.length > 0 && (
                    <footer className="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950 py-6">
                        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-wrap justify-center gap-x-6 gap-y-2">
                            {cmsPages.map((p) => (
                                <Link
                                    key={p.id}
                                    href={route('ecommerce.storefront.page', p.slug)}
                                    className="text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400"
                                >
                                    {p.title}
                                </Link>
                            ))}
                        </div>
                    </footer>
                )}
            </main>
        </div>
    );

    return <CartProvider currency={currency}>{content}</CartProvider>;
}

