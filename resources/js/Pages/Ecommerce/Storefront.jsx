import { useState, useEffect } from 'react';
import { Link, usePage } from '@inertiajs/react';
import StorefrontSeoHead from '@/Components/Ecommerce/StorefrontSeoHead';
import { ShieldCheck, Truck, Clock, Headphones, ArrowRight, Sparkles, Facebook, Instagram, Youtube, Banknote, CreditCard, Star } from 'lucide-react';
import { formatCurrency } from '@/lib/currency';
import { convertAmountToCurrency } from '@/lib/exchangeConvert';
import { CartProvider } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import AISupportFloatingWidget from '@/Components/Ecommerce/AISupportFloatingWidget';
import { StorefrontFooterReportBar } from '@/Components/Ecommerce/StorefrontReportShop';
import StorefrontClientBootstrap from '@/Components/Ecommerce/StorefrontClientBootstrap';
import StorefrontHeaderHero from '@/Components/Ecommerce/StorefrontHeaderHero';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';
import { formatShopName } from '@/lib/shopName';

function normalizeRichTextToLine(value) {
    if (!value) return '';
    return String(value)
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/gi, ' ')
        .replace(/\u00A0/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();
}

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

function getProductPricing(product, currency, exchangeRates = {}) {
    const amount =
        exchangeRates && Object.keys(exchangeRates).length > 0
            ? convertAmountToCurrency(
                  product.price_amount ?? 0,
                  product.price_currency || currency,
                  currency,
                  exchangeRates
              )
            : product.price_amount ?? 0;
    const price = formatCurrency(amount, currency);
    const promotionPercent = Number(product.promotion_percent ?? product.discount_percent ?? 0);
    const hasPromotion = !!product.has_promotion || promotionPercent > 0;
    const isDigital = !!product.is_digital;
    const payAtDelivery = !isDigital && product.mode_paiement === 'paiement_livraison';
    const PaymentIcon = isDigital ? null : payAtDelivery ? Banknote : CreditCard;
    const paymentCaption = isDigital
        ? null
        : payAtDelivery
          ? 'Vendeur : à la livraison'
          : 'Vendeur : paiement immédiat en ligne';

    return { price, promotionPercent, hasPromotion, PaymentIcon, paymentCaption };
}

function ProductCardFeaturedHero({ product, currency, productUrl, exchangeRates = {} }) {
    const { price, promotionPercent, hasPromotion, PaymentIcon, paymentCaption } = getProductPricing(
        product,
        currency,
        exchangeRates
    );

    return (
        <Link href={productUrl(product.id)} className="group block h-full min-h-[320px] lg:min-h-[420px]">
            <div className="relative h-full rounded-3xl overflow-hidden border border-slate-200/80 dark:border-slate-700/80 shadow-xl shadow-slate-900/10 dark:shadow-black/30 hover:shadow-2xl hover:shadow-[var(--sf-primary)]/15 transition-all duration-500">
                {product.image_url ? (
                    <img
                        src={product.image_url}
                        alt={product.name}
                        className="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-700"
                    />
                ) : (
                    <div className="absolute inset-0 bg-gradient-to-br from-slate-200 to-slate-100 dark:from-slate-800 dark:to-slate-900 flex items-center justify-center text-slate-400 dark:text-slate-500">
                        Aucune image
                    </div>
                )}
                <div className="absolute inset-0 bg-gradient-to-t from-slate-950/95 via-slate-950/50 to-slate-950/10" />
                <div className="absolute inset-0 bg-gradient-to-r from-[var(--sf-primary)]/20 to-transparent opacity-60" />

                <div className="relative z-10 flex h-full flex-col justify-end p-5 sm:p-7 lg:p-8">
                    <div className="flex flex-wrap items-center gap-2 mb-3">
                        <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-400 text-amber-950 text-[11px] font-bold uppercase tracking-wide shadow-lg">
                            <Star className="h-3.5 w-3.5 fill-current" />
                            En vedette
                        </span>
                        {product.is_new && (
                            <span className="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-500 text-white">
                                <Sparkles className="h-3 w-3" /> Nouveau
                            </span>
                        )}
                        {hasPromotion && (
                            <span className="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-white/20 text-white backdrop-blur-sm">
                                Promo{promotionPercent > 0 ? ` -${promotionPercent}%` : ''}
                            </span>
                        )}
                    </div>

                    <h3 className="text-xl sm:text-2xl lg:text-3xl font-bold text-white mb-2 line-clamp-2 max-w-lg group-hover:text-[var(--sf-primary)] transition-colors">
                        {product.name}
                    </h3>

                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-xs text-white/70 mb-1">Prix</p>
                            <p className="text-2xl sm:text-3xl font-bold text-white">{price}</p>
                            {PaymentIcon && paymentCaption && (
                                <p className="mt-2 text-[11px] sm:text-xs text-white/80 flex items-center gap-1.5">
                                    <PaymentIcon className="h-3.5 w-3.5 shrink-0" />
                                    {paymentCaption}
                                </p>
                            )}
                        </div>
                        <span className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-white text-slate-900 text-sm font-semibold group-hover:bg-[var(--sf-primary)] group-hover:text-white transition-colors">
                            Découvrir
                            <ArrowRight className="h-4 w-4 group-hover:translate-x-0.5 transition-transform" />
                        </span>
                    </div>
                </div>
            </div>
        </Link>
    );
}

function ProductCardSimple({ product, currency, productUrl, exchangeRates = {}, featured = false }) {
    const { price, promotionPercent, hasPromotion, PaymentIcon, paymentCaption } = getProductPricing(
        product,
        currency,
        exchangeRates
    );

    return (
        <Link href={productUrl(product.id)} className="group block h-full">
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
                    {featured && (
                        <span className="absolute left-3 top-3 inline-flex items-center gap-1 px-2 py-1 text-[10px] font-bold rounded-full bg-amber-400 text-amber-950 shadow-md">
                            <Star className="h-3 w-3 fill-current" />
                            Vedette
                        </span>
                    )}
                    {!featured && product.is_new && (
                        <span className="absolute left-3 top-3 inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-emerald-500 text-white shadow-lg shadow-emerald-500/30">
                            <Sparkles className="h-3 w-3" /> Nouveau
                        </span>
                    )}
                    {hasPromotion && (
                        <span className={`absolute ${featured ? 'right-3 top-3' : 'right-3 top-3'} inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold rounded-full bg-amber-500 text-white shadow-lg shadow-amber-500/30`}>
                            Promo{promotionPercent > 0 ? ` -${promotionPercent}%` : ''}
                        </span>
                    )}
                </div>
                <div className="flex flex-col px-4 py-4 sm:px-5 sm:py-5">
                    <h3 className="text-[13px] sm:text-sm font-semibold text-slate-900 dark:text-white mb-1 line-clamp-2 group-hover:text-[var(--sf-primary)] transition-colors">
                        {product.name}
                    </h3>
                    <div className="mt-auto pt-3 sm:pt-4 space-y-1">
                        <div className="flex items-center justify-between gap-2">
                            <span className="text-sm sm:text-base font-bold text-[var(--sf-primary)]">{price}</span>
                            <span className="inline-flex items-center text-xs font-medium text-slate-700 dark:text-slate-300 group-hover:text-[var(--sf-primary)] transition-colors">
                                Voir
                                <ArrowRight className="h-3.5 w-3 ml-1 group-hover:translate-x-0.5 transition-transform" />
                            </span>
                        </div>
                        {PaymentIcon && paymentCaption && (
                            <p className="text-[10px] sm:text-[11px] text-slate-700 dark:text-slate-300 flex items-center gap-1">
                                <PaymentIcon className="h-3 w-3 shrink-0 text-[var(--sf-primary)]" />
                                {paymentCaption}
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </Link>
    );
}

export default function EcommerceStorefront({
    shop,
    config,
    featuredProducts = [],
    newArrivals = [],
    banners = [],
    cmsPages = [],
    storefrontShops = [],
    currentStorefrontShopId,
    exchange_rates = {},
    available_currencies = [],
    pageSeo = null,
}) {
    const links = useStorefrontLinks();
    const { shop: sharedShop } = usePage().props;
    const currency = shop?.currency || sharedShop?.currency || 'CDF';
    const logoUrl = shop?.logo_url || sharedShop?.logo_url || null;

    const heroBadge = config?.hero_badge || 'Season Sale';
    const heroTitle = config?.hero_title || "MEN'S FASHION";
    const heroSubtitle = config?.hero_subtitle || 'Min. 35–70% Off';
    const heroDescription = normalizeRichTextToLine(
        config?.hero_description ||
            'Découvrez une sélection de produits modernes pour votre clientèle, avec une expérience d’achat fluide.'
    );
    const primaryLabel = config?.hero_primary_label || 'Voir la boutique';
    const secondaryLabel = config?.hero_secondary_label || 'Découvrir les nouveautés';
    const layoutPreset = config?.storefront_layout_preset || 'classic';

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
        <div className="min-h-screen bg-white dark:bg-gradient-to-b dark:from-slate-950 dark:via-slate-900 dark:to-slate-950 text-slate-950 dark:text-slate-50">
            <StorefrontSeoHead pageSeo={pageSeo} />
            <StorefrontClientBootstrap />

            <StorefrontHeaderHero
                layoutPreset={layoutPreset}
                shop={shop}
                logoUrl={logoUrl}
                links={links}
                navPages={navPages}
                storefrontShops={storefrontShops}
                currentStorefrontShopId={currentStorefrontShopId}
                available_currencies={available_currencies}
                currency={currency}
                heroBadge={heroBadge}
                heroTitle={heroTitle}
                heroSubtitle={heroSubtitle}
                heroDescription={heroDescription}
                primaryLabel={primaryLabel}
                secondaryLabel={secondaryLabel}
                banners={banners}
            />

            {/* Contenu principal */}
            <main>
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
                                <p className="text-sm text-slate-800 dark:text-slate-200 max-w-xl">
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
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 shadow-sm shadow-slate-900/5 hover:border-[var(--sf-primary)]/50 transition-colors">
                            <div className="flex-shrink-0 h-11 w-11 rounded-xl bg-[var(--sf-primary)]/20 flex items-center justify-center">
                                <Truck className="h-5 w-5 text-[var(--sf-primary)]" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">Livraison rapide</p>
                                <p className="text-xs text-slate-700 dark:text-slate-300 mt-0.5">Dans la journée sur votre ville</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 shadow-sm shadow-slate-900/5 hover:border-emerald-200 dark:hover:border-emerald-800 transition-colors">
                            <div className="flex-shrink-0 h-11 w-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center">
                                <ShieldCheck className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">Paiement sécurisé</p>
                                <p className="text-xs text-slate-700 dark:text-slate-300 mt-0.5">
                                    Chaque vendeur définit sur la fiche produit le paiement immédiat ou à la livraison
                                </p>
                            </div>
                        </div>
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 shadow-sm shadow-slate-900/5 hover:border-sky-200 dark:hover:border-sky-800 transition-colors">
                            <div className="flex-shrink-0 h-11 w-11 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center">
                                <Clock className="h-5 w-5 text-sky-600 dark:text-sky-400" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">Ouvert 24/7</p>
                                <p className="text-xs text-slate-700 dark:text-slate-300 mt-0.5">Commandez à tout moment</p>
                            </div>
                        </div>
                        <div className="flex items-start gap-4 p-4 rounded-2xl bg-white dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 shadow-sm shadow-slate-900/5 hover:border-violet-200 dark:hover:border-violet-800 transition-colors">
                            <div className="flex-shrink-0 h-11 w-11 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                                <Headphones className="h-5 w-5 text-violet-600 dark:text-violet-400" />
                            </div>
                            <div>
                                <p className="text-sm font-semibold text-slate-900 dark:text-white">Support client</p>
                                <p className="text-xs text-slate-700 dark:text-slate-300 mt-0.5">À votre écoute</p>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Produits en vedette */}
                <section className="relative py-14 lg:py-20 overflow-hidden">
                    <div className="absolute inset-0 bg-gradient-to-br from-[var(--sf-primary)]/[0.06] via-white to-amber-50/60 dark:from-[var(--sf-primary)]/10 dark:via-slate-950 dark:to-slate-900 pointer-events-none" />
                    <div className="absolute -top-32 -right-32 h-72 w-72 rounded-full bg-[var(--sf-primary)]/10 blur-3xl pointer-events-none" />
                    <div className="absolute -bottom-24 -left-24 h-56 w-56 rounded-full bg-amber-400/10 blur-3xl pointer-events-none" />

                    <div className="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8 lg:mb-10">
                            <div>
                                <p className="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-200 text-xs font-semibold mb-3">
                                    <Star className="h-3.5 w-3.5 fill-current" />
                                    Sélection du moment
                                </p>
                                <h2 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white tracking-tight">
                                    Produits en vedette
                                </h2>
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-300 max-w-xl">
                                    Les articles choisis par votre boutique pour accueillir vos visiteurs.
                                </p>
                            </div>
                            <Link
                                href={links.catalog()}
                                className="inline-flex items-center self-start sm:self-auto px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-white/80 dark:bg-slate-900/80 text-sm font-medium text-[var(--sf-primary)] hover:border-[var(--sf-primary)] hover:bg-[var(--sf-primary)]/5 transition-colors backdrop-blur-sm"
                            >
                                Voir tout le catalogue
                                <ArrowRight className="h-4 w-4 ml-2" />
                            </Link>
                        </div>

                        {featuredProducts.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 bg-white/60 dark:bg-slate-900/40 px-6 py-10 text-center">
                                <Star className="h-8 w-8 mx-auto text-slate-300 dark:text-slate-600 mb-3" />
                                <p className="text-sm text-slate-700 dark:text-slate-300">
                                    Aucun produit en vedette pour le moment.
                                </p>
                                <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                    Publiez des produits sur e-commerce, puis choisissez-les dans{' '}
                                    <span className="font-medium">E-commerce → Paramètres → Produits en vedette</span>.
                                </p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                                {featuredProducts.map((p, index) =>
                                    index === 0 ? (
                                        <div key={p.id} className="col-span-2 row-span-2">
                                            <ProductCardFeaturedHero
                                                product={p}
                                                currency={currency}
                                                productUrl={links.product}
                                                exchangeRates={exchange_rates}
                                            />
                                        </div>
                                    ) : (
                                        <ProductCardSimple
                                            key={p.id}
                                            product={p}
                                            currency={currency}
                                            productUrl={links.product}
                                            exchangeRates={exchange_rates}
                                            featured
                                        />
                                    )
                                )}
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
                                <p className="text-xs sm:text-sm text-slate-700 dark:text-slate-300">
                                    Derniers articles ajoutés à votre catalogue.
                                </p>
                            </div>
                        </div>

                        {newArrivals.length === 0 ? (
                            <div className="text-sm text-slate-800 dark:text-slate-200">
                                Ajoutez de nouveaux produits dans le module Commerce / GlobalCommerce pour voir cette section se
                                remplir automatiquement.
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-5">
                                {newArrivals.map((p) => (
                                    <ProductCardSimple
                                        key={p.id}
                                        product={p}
                                        currency={currency}
                                        productUrl={links.product}
                                        exchangeRates={exchange_rates}
                                    />
                                ))}
                            </div>
                        )}
                    </div>
                </section>

                {/* Bannières slider (si plusieurs) */}
                {sliderBanners.length > 0 && (
                    <section className="py-10 border-t border-slate-200 dark:border-slate-800 bg-slate-100 dark:bg-slate-950/40">
                        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                            <div className="flex items-center justify-between mb-4">
                                <h2 className="text-sm font-semibold tracking-wide text-slate-800 dark:text-slate-200 uppercase">
                                    Offres du moment
                                </h2>
                                {sliderBanners.length > 1 && (
                                    <div className="hidden sm:flex items-center gap-2 text-xs font-medium text-slate-800 dark:text-slate-200">
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
                <footer className="border-t border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-950">
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
                                            {formatShopName(shop?.name, 'Ma Boutique')}
                                        </div>
                                        <div className="text-xs font-medium text-slate-700 dark:text-slate-300">Boutique en ligne</div>
                                    </div>
                                </Link>
                                <p className="mt-4 text-sm text-slate-800 dark:text-slate-200 max-w-sm">
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
                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-800 dark:text-slate-200">
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
                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-800 dark:text-slate-200">
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
                                            <p className="mt-4 text-sm text-slate-800 dark:text-slate-200">
                                                Retrouvez bientôt ici nos pages d’informations (livraison, retours, contact).
                                            </p>
                                        )}
                                    </div>

                                    <div className="hidden lg:block">
                                        <p className="text-xs font-semibold uppercase tracking-wider text-slate-800 dark:text-slate-200">
                                            Confiance
                                        </p>
                                        <ul className="mt-4 space-y-2 text-sm text-slate-800 dark:text-slate-200">
                                            <li className="flex items-center gap-2">
                                                <span className="h-1.5 w-1.5 rounded-full bg-emerald-500/90" />
                                                Paiement sécurisé
                                            </li>
                                            <li className="flex items-center gap-2">
                                                <span className="h-1.5 w-1.5 rounded-full bg-sky-500/90" />
                                                Suivi par e-mail (n° de commande)
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
                            <p className="text-xs font-medium text-slate-700 dark:text-slate-300">
                                © {new Date().getFullYear()} {formatShopName(shop?.name, 'Ma Boutique')}. Tous droits réservés.
                            </p>
                            <div className="flex items-center gap-4">
                                {(config?.social_facebook_url ||
                                    config?.social_instagram_url ||
                                    config?.social_tiktok_url ||
                                    config?.social_youtube_url) && (
                                    <div className="flex items-center gap-2">
                                        <span className="text-[11px] font-medium text-slate-700 dark:text-slate-300">Suivez-nous</span>
                                        <div className="flex items-center gap-2">
                                            {config?.social_facebook_url && (
                                                <a
                                                    href={config.social_facebook_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-100 hover:bg-[var(--sf-primary)]/20 hover:text-[var(--sf-primary)] transition-colors"
                                                >
                                                    <Facebook className="h-3.5 w-3.5" />
                                                </a>
                                            )}
                                            {config?.social_instagram_url && (
                                                <a
                                                    href={config.social_instagram_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-100 hover:bg-[var(--sf-primary)]/20 hover:text-[var(--sf-primary)] transition-colors"
                                                >
                                                    <Instagram className="h-3.5 w-3.5" />
                                                </a>
                                            )}
                                            {config?.social_youtube_url && (
                                                <a
                                                    href={config.social_youtube_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex h-7 w-7 items-center justify-center rounded-full bg-slate-200 dark:bg-slate-800 text-slate-800 dark:text-slate-100 hover:bg-[var(--sf-primary)]/20 hover:text-[var(--sf-primary)] transition-colors"
                                                >
                                                    <Youtube className="h-3.5 w-3.5" />
                                                </a>
                                            )}
                                        </div>
                                    </div>
                                )}
                                <p className="text-[11px] font-medium text-slate-700 dark:text-slate-300">
                                    Propulsé par une vitrine moderne
                                </p>
                            </div>
                        </div>
                    </div>
                    <StorefrontFooterReportBar shopName={shop?.name} />
                </footer>
            </main>

            <AISupportFloatingWidget />
            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} iconOnly />
        </div>
    );

    return (
        <CartProvider
            currency={currency}
            exchangeRates={exchange_rates || {}}
            storageKey={`ecommerce_cart_${shop?.id ?? 'default'}`}
        >
            {content}
        </CartProvider>
    );
}

