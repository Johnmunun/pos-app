import { useState, useEffect, useMemo } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { CartProvider, useCart } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import OrderDrawer from '@/Components/Ecommerce/OrderDrawer';
import StorefrontClientBootstrap from '@/Components/Ecommerce/StorefrontClientBootstrap';
import StorefrontCurrencySelect from '@/Components/Ecommerce/StorefrontCurrencySelect';
import {
    ShoppingBag,
    ArrowLeft,
    Plus,
    Minus,
    Trash2,
    ShoppingCart as ShoppingCartIcon,
    ArrowRight,
    Package,
    Tag,
    Truck,
    ShieldCheck,
    CreditCard,
    Info,
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import { formatCurrency, normalizeCurrencyCode } from '@/lib/currency';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import AISupportFloatingWidget from '@/Components/Ecommerce/AISupportFloatingWidget';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';
import { cartRequiresFusionPay, paymentMethodsForCart } from '@/lib/ecommerceCartPayment';

function storefrontPaymentExplainer(pm) {
    if (!pm) return null;
    const t = String(pm.type || '').toLowerCase();
    if (t === 'cash_on_delivery') {
        return {
            title: 'Paiement à la livraison',
            text: 'Le vendeur a configuré au moins un article de votre panier pour un règlement à la réception. Aucun prélèvement en ligne pour ce moyen : vous payez au colis. Confirmation par e-mail avec numéro de commande.',
        };
    }
    if (t === 'fusionpay') {
        return {
            title: 'Paiement en ligne immédiat',
            text: 'Le vendeur a défini au moins un article avec règlement en ligne tout de suite après validation. Vous serez redirigé vers la page de paiement sécurisée (mobile money ou carte selon les options).',
        };
    }
    if (t === 'card' || t === 'wallet') {
        return {
            title: 'Paiement en ligne',
            text: 'Règlement lors de la validation, selon les options activées par la boutique.',
        };
    }
    if (t === 'bank_transfer') {
        return {
            title: 'Virement bancaire',
            text: 'Instructions possibles dans l’e-mail de confirmation selon la configuration de la boutique.',
        };
    }
    return {
        title: 'Moyen de paiement',
        text: 'Le détail figure dans l’e-mail de confirmation de commande.',
    };
}

function StorefrontCartHeader({ shop, cmsPages = [], currency, availableCurrencies = [], selectedCurrencyCode }) {
    const links = useStorefrontLinks();
    const { shop: sharedShop } = usePage().props;
    const logoUrl = shop?.logo_url || sharedShop?.logo_url || null;

    return (
        <header className="sticky top-0 z-40 border-b border-slate-200/70 dark:border-slate-800 bg-white/95 dark:bg-slate-950/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.index()}
                        className="p-2 -ml-2 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 dark:hover:text-amber-400 transition-colors"
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Link>
                    <div className="flex items-center gap-3">
                        {logoUrl ? (
                            <span className="inline-flex justify-center h-10 w-10 rounded-xl bg-white shadow-lg shadow-slate-900/10 ring-1 ring-slate-200 overflow-hidden">
                                <img src={logoUrl} alt={shop?.name || 'Logo'} className="w-full h-full object-contain" />
                            </span>
                        ) : (
                            <span className="inline-flex justify-center h-10 w-10 rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-white font-bold text-sm shadow-lg shadow-amber-500/25">
                                {shop?.name?.charAt(0) || 'S'}
                            </span>
                        )}
                        <div>
                            <span className="font-semibold text-slate-900 dark:text-white block truncate">{shop?.name || 'Boutique'}</span>
                            <span className="text-xs text-slate-500 dark:text-slate-400">{currency || 'CDF'} • Panier</span>
                        </div>
                    </div>
                </div>
                <div className="flex items-center gap-3 sm:gap-4">
                    {cmsPages.length > 0 && (
                        <nav className="hidden md:flex items-center gap-1">
                            {cmsPages.slice(0, 5).map((p) => (
                                <Link
                                    key={p.id}
                                    href={links.page(p.slug)}
                                    className="px-2.5 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                                >
                                    {p.title}
                                </Link>
                            ))}
                        </nav>
                    )}
                    <Link
                        href={links.catalog()}
                        className="hidden sm:inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300 hover:text-amber-600 dark:hover:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                    >
                        Catalogue
                    </Link>
                    <StorefrontCurrencySelect
                        availableCurrencies={availableCurrencies}
                        value={selectedCurrencyCode || shop?.currency}
                        variant="compact"
                    />
                    <ShoppingCart
                        buttonClassName="relative inline-flex items-center justify-center h-10 w-10 rounded-xl bg-[var(--sf-primary,#f59e0b)] text-white hover:bg-[var(--sf-primary-hover,#d97706)] shadow-lg transition-all"
                        storefrontLinks
                    />
                </div>
            </div>
        </header>
    );
}

function CartContent({
    shippingMethods,
    paymentMethods,
    taxRate,
    products,
    shop,
    cmsPages,
    whatsapp = {},
    config = {},
    available_currencies = [],
}) {
    const links = useStorefrontLinks();
    const { cart, updateQuantity, removeFromCart, getCartTotal, getPriceInDisplayCurrency, currency, clearCart, exchangeRates } = useCart();
    const [orderDrawerOpen, setOrderDrawerOpen] = useState(false);
    const [selectedShippingId, setSelectedShippingId] = useState(shippingMethods?.[0]?.id ?? '');
    const [selectedPaymentId, setSelectedPaymentId] = useState(paymentMethods?.[0]?.id ?? '');
    const [shippingAmount, setShippingAmount] = useState(0);
    const [couponCode, setCouponCode] = useState('');
    const [couponDiscount, setCouponDiscount] = useState(0);
    const [couponApplied, setCouponApplied] = useState(false);
    const [couponLoading, setCouponLoading] = useState(false);

    const displayCurrency = currency || shop?.currency || 'XAF';
    const format = (amount) => formatCurrency(amount, displayCurrency);
    const normalizedDisplayCurrency = normalizeCurrencyCode(displayCurrency);
    const displayCurrencyLabel = normalizedDisplayCurrency === 'XAF' || normalizedDisplayCurrency === 'XOF'
        ? 'FCFA'
        : normalizedDisplayCurrency;
    const hasRateForCurrency = (value) => {
        const code = normalizeCurrencyCode(value || normalizedDisplayCurrency);
        return code === normalizedDisplayCurrency || Object.prototype.hasOwnProperty.call(exchangeRates || {}, code);
    };
    const canConvertAllItems = cart.every((item) => hasRateForCurrency(item.price_currency));
    const cartCurrencies = [...new Set(cart.map((item) => normalizeCurrencyCode(item.price_currency || normalizedDisplayCurrency)))];
    const rawSubtotal = cart.reduce((sum, item) => sum + (Number(item.price) || 0) * (Number(item.quantity) || 0), 0);

    const formatItemAmount = (amount, itemCurrency) => {
        if (!canConvertAllItems) {
            return formatCurrency(amount, itemCurrency || displayCurrency);
        }
        return format(getPriceInDisplayCurrency(amount, itemCurrency));
    };

    const useFlatShipping = !!config?.storefront_use_flat_shipping;
    const flatShippingAmount = Math.max(0, Number(config?.storefront_flat_shipping_amount ?? 0));
    const hasPhysicalItems = cart.some((i) => !i.is_digital);

    const visiblePaymentMethods = useMemo(
        () => paymentMethodsForCart(paymentMethods, cart),
        [paymentMethods, cart]
    );
    const fusionPayRequired = cartRequiresFusionPay(cart);
    const fusionPayMissing =
        fusionPayRequired && visiblePaymentMethods.length === 0 && (paymentMethods?.length ?? 0) > 0;

    useEffect(() => {
        if (visiblePaymentMethods.length === 0) {
            return;
        }
        const stillValid = visiblePaymentMethods.some((m) => m.id === selectedPaymentId);
        if (!stillValid) {
            setSelectedPaymentId(visiblePaymentMethods[0].id);
        }
    }, [visiblePaymentMethods, selectedPaymentId]);

    const subtotal = !canConvertAllItems && cartCurrencies.length === 1 ? rawSubtotal : getCartTotal();
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + shippingAmount + taxAmount - couponDiscount;

    useEffect(() => {
        if (cart.length === 0) {
            setShippingAmount(0);
            return;
        }
        if (useFlatShipping) {
            setShippingAmount(hasPhysicalItems ? flatShippingAmount : 0);
            return;
        }
        if (!selectedShippingId) {
            setShippingAmount(0);
            return;
        }
        axios
            .post(route('ecommerce.checkout.calculate-shipping'), {
                shipping_method_id: selectedShippingId,
                cart_subtotal: subtotal,
                cart_weight: 0,
            })
            .then((res) => {
                if (res.data?.success) {
                    setShippingAmount(res.data.shipping_amount ?? 0);
                }
            })
            .catch(() => setShippingAmount(0));
    }, [selectedShippingId, subtotal, cart.length, useFlatShipping, flatShippingAmount, hasPhysicalItems]);

    const handleApplyCoupon = () => {
        if (!couponCode.trim()) return;
        setCouponLoading(true);
        axios
            .post(route('ecommerce.checkout.validate-coupon'), {
                code: couponCode.trim(),
                cart_subtotal: subtotal,
                cart_items: cart.map((item) => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    unit_price: item.price,
                })),
            })
            .then((res) => {
                if (res.data?.success) {
                    setCouponDiscount(res.data.discount_amount ?? 0);
                    setCouponApplied(true);
                    toast.success('Code promo appliqué');
                } else {
                    setCouponDiscount(0);
                    setCouponApplied(false);
                    toast.error(res.data?.message ?? 'Code invalide');
                }
            })
            .catch((err) => {
                setCouponDiscount(0);
                setCouponApplied(false);
                toast.error(err.response?.data?.message ?? 'Erreur');
            })
            .finally(() => setCouponLoading(false));
    };

    const handleRemoveCoupon = () => {
        setCouponCode('');
        setCouponDiscount(0);
        setCouponApplied(false);
    };

    const handleCheckout = () => {
        if (cart.length === 0) {
            toast.error('Votre panier est vide');
            return;
        }
        if (fusionPayMissing) {
            toast.error(
                'Le paiement en ligne sécurisé requis pour ce panier n’est pas disponible pour le moment. Contactez la boutique.'
            );
            return;
        }
        setOrderDrawerOpen(true);
    };

    const selectedPm = visiblePaymentMethods?.find((m) => m.id === selectedPaymentId);
    const selectedPaymentCode = selectedPm?.code ?? '';
    const selectedPaymentType = selectedPm?.type ?? '';
    const paymentExplainer = storefrontPaymentExplainer(selectedPm);
    const physicalPaymentModes = cart.filter((i) => !i.is_digital).map((i) => i.mode_paiement || 'paiement_immediat');
    const hasMixedProductPaymentModes = physicalPaymentModes.length > 1 && new Set(physicalPaymentModes).size > 1;
    const hasDeliveryItems = cart.some((i) => !i.is_digital && (i.mode_paiement || 'paiement_immediat') === 'paiement_livraison');
    const willSplitOrder = fusionPayRequired && hasDeliveryItems;

    // Ne jamais marquer la commande « payée » côté panier sans passage par le flux de paiement en ligne.
    // Les produits numériques se valident après paiement en ligne ; sinon la commande reste en attente (ex. paiement à la livraison).
    const shouldAutoMarkPaid = false;

    const whatsappNumber = whatsapp.number || null;
    const whatsappSupportEnabled = !!whatsapp.enabled;

    const primaryColor = config?.theme_primary_color || '#f59e0b';
    const secondaryColor = config?.theme_secondary_color || '#d97706';

    return (
        <>
            <Head title="Panier - Boutique" />
            <StorefrontClientBootstrap />
            <StorefrontCartHeader
                shop={shop}
                cmsPages={cmsPages}
                currency={displayCurrencyLabel}
                availableCurrencies={available_currencies}
                selectedCurrencyCode={normalizedDisplayCurrency}
            />

            <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-slate-950 dark:to-slate-900">
                {/* Hero section */}
                <div
                    className="relative overflow-hidden bg-gradient-to-br from-[var(--sf-primary)] via-[var(--sf-primary-soft)] to-[var(--sf-primary-soft)]"
                    style={{
                        '--sf-primary': primaryColor,
                        '--sf-primary-soft': secondaryColor,
                    }}
                >
                    <div className="absolute inset-0 opacity-10 bg-[length:24px_24px] [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.4)_1px,transparent_0)]" />
                    <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                            <div>
                                <h1 className="text-2xl sm:text-3xl font-bold text-white tracking-tight">
                                    Votre panier
                                </h1>
                                <p className="mt-1 text-amber-100 text-sm sm:text-base">
                                    {cart.length > 0
                                        ? `${cart.length} article${cart.length > 1 ? 's' : ''} • Finalisez votre commande`
                                        : 'Ajoutez des produits pour commencer'}
                                </p>
                            </div>
                            <Link
                                href={links.catalog()}
                                className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-white/20 hover:bg-white/30 text-white text-sm font-medium backdrop-blur transition-colors"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Continuer les achats
                            </Link>
                        </div>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 -mt-2 sm:-mt-4 relative z-10">
                    {cart.length === 0 ? (
                        <div className="bg-white dark:bg-slate-800/50 rounded-3xl shadow-xl shadow-slate-200/50 dark:shadow-slate-900/50 border border-slate-200/80 dark:border-slate-700/80 p-12 sm:p-16 text-center">
                            <div className="w-24 h-24 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-6">
                                <ShoppingBag className="h-12 w-12 text-slate-400" />
                            </div>
                            <h2 className="text-xl font-semibold text-slate-900 dark:text-white mb-2">Votre panier est vide</h2>
                            <p className="text-slate-500 dark:text-slate-400 mb-8 max-w-sm mx-auto">
                                Découvrez nos produits et ajoutez-les à votre panier pour passer commande
                            </p>
                            <Link href={links.catalog()}>
                                <Button size="lg" className="gap-2 rounded-xl h-12 px-8 shadow-lg shadow-[var(--sf-primary,#f59e0b)]/25">
                                    <Package className="h-5 w-5" />
                                    Parcourir le catalogue
                                </Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10">
                            {/* Cart items */}
                            <div className="lg:col-span-2 space-y-4">
                                {cart.map((item) => (
                                    <div
                                        key={item.product_id}
                                        className="group bg-white dark:bg-slate-800/80 rounded-2xl shadow-md shadow-slate-200/50 dark:shadow-slate-900/50 border border-slate-200/80 dark:border-slate-700/80 p-5 sm:p-6 hover:shadow-lg hover:border-amber-200/60 dark:hover:border-amber-800/40 transition-all duration-300"
                                    >
                                        <div className="flex gap-5">
                                            <div className="flex-shrink-0">
                                                {item.image_url ? (
                                                    <img
                                                        src={item.image_url}
                                                        alt={item.name}
                                                        className="w-24 h-24 sm:w-28 sm:h-28 object-cover rounded-xl ring-1 ring-slate-200/50 dark:ring-slate-600/50"
                                                    />
                                                ) : (
                                                    <div className="w-24 h-24 sm:w-28 sm:h-28 bg-slate-100 dark:bg-slate-700 rounded-xl flex items-center justify-center">
                                                        <Package className="h-10 w-10 text-slate-400" />
                                                    </div>
                                                )}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <h3 className="font-semibold text-slate-900 dark:text-white mb-0.5 truncate">{item.name}</h3>
                                                {item.is_digital ? (
                                                    <p className="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
                                                        Indication vendeur : produit numérique — lien après paiement en ligne confirmé
                                                    </p>
                                                ) : (
                                                    <p className="text-[11px] font-medium text-slate-600 dark:text-slate-300 mb-2">
                                                        {item.mode_paiement === 'paiement_livraison'
                                                            ? 'Indication vendeur (fiche produit) : paiement à la livraison'
                                                            : 'Indication vendeur (fiche produit) : paiement en ligne immédiat'}
                                                    </p>
                                                )}
                                                {item.sku && (
                                                    <p className="text-xs text-slate-500 dark:text-slate-400 mb-3">Réf. {item.sku}</p>
                                                )}
                                                <p className="text-lg font-bold text-amber-600 dark:text-amber-400 mb-4">
                                                    {formatItemAmount(item.price, item.price_currency)}
                                                    <span className="text-sm font-normal text-slate-500 dark:text-slate-400"> / unité</span>
                                                </p>
                                                <div className="flex flex-wrap items-center gap-3">
                                                    <div className="inline-flex items-center gap-1 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-700/50 p-1">
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                                            className="h-8 w-8 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600"
                                                        >
                                                            <Minus className="h-3.5 w-3.5" />
                                                        </Button>
                                                        <span className="w-10 text-center text-sm font-semibold">{item.quantity}</span>
                                                        <Button
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => updateQuantity(item.product_id, item.quantity + 1)}
                                                            disabled={item.quantity >= (item.stock ?? 999)}
                                                            className="h-8 w-8 rounded-lg hover:bg-slate-200 dark:hover:bg-slate-600 disabled:opacity-50"
                                                        >
                                                            <Plus className="h-3.5 w-3.5" />
                                                        </Button>
                                                    </div>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => removeFromCart(item.product_id)}
                                                        className="h-9 w-9 p-0 text-red-600 dark:text-red-400 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                                                        title="Supprimer"
                                                        aria-label="Supprimer"
                                                    >
                                                        <Trash2 className="h-4 w-4 shrink-0" />
                                                    </Button>
                                                </div>
                                            </div>
                                            <div className="flex-shrink-0 text-right">
                                                <p className="text-xl font-bold text-slate-900 dark:text-white">
                                                    {formatItemAmount((Number(item.price) || 0) * item.quantity, item.price_currency)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Summary sidebar */}
                            <div className="lg:col-span-1">
                                <div className="lg:sticky lg:top-24 bg-white dark:bg-slate-800/80 rounded-2xl shadow-xl shadow-slate-200/50 dark:shadow-slate-900/50 border border-slate-200/80 dark:border-slate-700/80 p-6 space-y-5">
                                    <div className="flex items-center justify-between">
                                        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">Résumé</h2>
                                        <span className="text-xs font-medium px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300">
                                            {displayCurrencyLabel}
                                        </span>
                                    </div>
                                    {!canConvertAllItems && (
                                        <div className="rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/80 dark:bg-amber-950/30 px-3 py-2.5 text-xs text-amber-800 dark:text-amber-200">
                                            Conversion indisponible pour une ou plusieurs devises: affichage des montants dans la devise d'origine des articles.
                                        </div>
                                    )}

                                    {useFlatShipping ? (
                                        <div className="rounded-xl border border-slate-200/80 dark:border-slate-600 bg-slate-50/80 dark:bg-slate-900/40 px-3 py-2.5">
                                            <p className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                                <Truck className="h-4 w-4 text-amber-500" />
                                                Frais de livraison
                                            </p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                                                Fixés par la boutique ({displayCurrencyLabel}) — non modifiables.
                                            </p>
                                            <p className="text-base font-semibold text-slate-900 dark:text-white">
                                                {hasPhysicalItems ? format(flatShippingAmount) : format(0)}
                                                {!hasPhysicalItems && (
                                                    <span className="block text-xs font-normal text-slate-500 mt-1">
                                                        (commande 100 % numérique : pas de livraison)
                                                    </span>
                                                )}
                                            </p>
                                        </div>
                                    ) : (
                                        shippingMethods?.length > 0 && (
                                            <div className="rounded-xl border border-slate-200/80 dark:border-slate-600 bg-slate-50/80 dark:bg-slate-900/40 px-3 py-2.5">
                                                <p className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                                                    <Truck className="h-4 w-4 text-amber-500" />
                                                    Livraison
                                                </p>
                                                <p className="text-xs text-slate-500 dark:text-slate-400 mb-1">
                                                    Définie par la boutique — non modifiable.
                                                </p>
                                                <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                                    {shippingMethods[0]?.name ?? 'Standard'}
                                                    {' — '}
                                                    {shippingAmount > 0 ? format(shippingAmount) : 'Gratuit'}
                                                </p>
                                            </div>
                                        )
                                    )}

                                    {fusionPayMissing && (
                                        <div className="rounded-xl border border-red-200 dark:border-red-900/50 bg-red-50/90 dark:bg-red-950/30 px-3 py-2.5 text-xs text-red-800 dark:text-red-200">
                                            <p className="font-semibold">Paiement en ligne indisponible</p>
                                            <p className="mt-1">
                                                Ce panier nécessite un règlement en ligne immédiat, mais la boutique n’a pas encore activé
                                                cette option. Contactez le vendeur ou réessayez plus tard.
                                            </p>
                                        </div>
                                    )}
                                    {fusionPayRequired && !fusionPayMissing && (
                                        <div className="rounded-xl border border-sky-200 dark:border-sky-800/60 bg-sky-50/80 dark:bg-sky-950/25 px-3 py-2.5 text-xs text-sky-900 dark:text-sky-100">
                                            <p className="font-medium">Règle définie par le vendeur sur la fiche produit</p>
                                            <p className="mt-1 text-sky-800/95 dark:text-sky-200/90">
                                                Au moins un article est réglé en ligne, tout de suite après validation de la commande — sur
                                                la page de paiement sécurisée de la boutique (comme pour un produit numérique).
                                            </p>
                                        </div>
                                    )}
                                    {visiblePaymentMethods?.length > 0 && (
                                        <div>
                                            <label className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                <CreditCard className="h-4 w-4 text-amber-500" />
                                                Moyen de paiement
                                            </label>
                                            <p className="text-[11px] text-slate-500 dark:text-slate-400 mb-2">
                                                Livraison vs immédiat : défini par le vendeur sur chaque produit (e-commerce). Ici vous
                                                choisissez seulement parmi les moyens compatibles avec votre panier.
                                            </p>
                                            <select
                                                value={selectedPaymentId}
                                                onChange={(e) => setSelectedPaymentId(e.target.value)}
                                                className="w-full rounded-xl border border-slate-200 dark:border-slate-600 dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-colors"
                                            >
                                                {visiblePaymentMethods.map((m) => (
                                                    <option key={m.id} value={m.id}>
                                                        {m.name}
                                                    </option>
                                                ))}
                                            </select>
                                            {hasMixedProductPaymentModes && hasPhysicalItems && (
                                                <div className="mt-3 rounded-xl border border-amber-200 dark:border-amber-800/60 bg-amber-50/80 dark:bg-amber-950/30 px-3 py-2.5 text-xs text-amber-900 dark:text-amber-100">
                                                    <p className="font-medium flex items-center gap-1.5">
                                                        <Info className="h-3.5 w-3.5 shrink-0" />
                                                        Règles vendeur différentes sur le panier
                                                    </p>
                                                    <p className="mt-1 text-amber-800/95 dark:text-amber-200/90">
                                                        Certains articles sont configurés « paiement à la livraison », d’autres « paiement
                                                        immédiat » (fiches produit). Au moment de valider, la boutique créera
                                                        automatiquement deux commandes: une commande payée en ligne et une commande à la
                                                        livraison.
                                                    </p>
                                                </div>
                                            )}
                                            {paymentExplainer && (
                                                <div className="mt-3 rounded-xl border border-slate-200 dark:border-slate-600 bg-slate-50/80 dark:bg-slate-900/40 px-3 py-2.5 text-xs text-slate-700 dark:text-slate-200">
                                                    <p className="font-semibold text-slate-800 dark:text-slate-100">{paymentExplainer.title}</p>
                                                    <p className="mt-1 text-slate-600 dark:text-slate-300">{paymentExplainer.text}</p>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">Code promo</label>
                                        <div className="flex gap-2">
                                            <Input
                                                value={couponCode}
                                                onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                                                placeholder="Ex: PROMO20"
                                                disabled={couponApplied}
                                                className="flex-1 rounded-xl border-slate-200 dark:border-slate-600"
                                            />
                                            {couponApplied ? (
                                                <Button type="button" variant="outline" size="sm" onClick={handleRemoveCoupon} className="rounded-xl shrink-0">
                                                    Retirer
                                                </Button>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={handleApplyCoupon}
                                                    disabled={couponLoading || !couponCode.trim()}
                                                    className="rounded-xl shrink-0"
                                                >
                                                    {couponLoading ? '...' : 'OK'}
                                                </Button>
                                            )}
                                        </div>
                                        {couponApplied && couponDiscount > 0 && (
                                            <p className="flex items-center gap-1.5 text-sm text-emerald-600 dark:text-emerald-400 mt-2">
                                                <Tag className="h-4 w-4" />
                                                -{format(couponDiscount)} appliqué
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                                        <div className="flex justify-between text-sm">
                                            <span className="text-slate-600 dark:text-slate-400">Sous-total</span>
                                            <span className="font-medium text-slate-900 dark:text-white">{format(subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-slate-600 dark:text-slate-400">
                                                Livraison{useFlatShipping ? ' (fixe)' : ''}
                                            </span>
                                            <span className="font-medium text-slate-900 dark:text-white">{format(shippingAmount)}</span>
                                        </div>
                                        <div className="flex justify-between text-sm">
                                            <span className="text-slate-600 dark:text-slate-400">Taxes ({taxRate}%)</span>
                                            <span className="font-medium text-slate-900 dark:text-white">{format(taxAmount)}</span>
                                        </div>
                                        {couponDiscount > 0 && (
                                            <div className="flex justify-between text-sm text-emerald-600 dark:text-emerald-400">
                                                <span>Réduction promo</span>
                                                <span>-{format(couponDiscount)}</span>
                                            </div>
                                        )}
                                        <div className="flex justify-between items-center pt-4 border-t-2 border-slate-200 dark:border-slate-700">
                                            <span className="text-lg font-semibold text-slate-900 dark:text-white">Total</span>
                                            <span className="text-2xl font-bold text-amber-600 dark:text-amber-400">{format(total)}</span>
                                        </div>
                                    </div>

                                    <Button
                                        onClick={handleCheckout}
                                        disabled={fusionPayMissing}
                                        className="w-full gap-2 rounded-xl h-12 text-base font-semibold shadow-lg shadow-[var(--sf-primary,#f59e0b)]/25 transition-all"
                                        size="lg"
                                    >
                                        Passer la commande
                                        <ArrowRight className="h-4 w-4" />
                                    </Button>

                                    {willSplitOrder && (
                                        <div className="rounded-xl border border-indigo-200 dark:border-indigo-800/60 bg-indigo-50/80 dark:bg-indigo-950/25 px-3 py-2.5 text-xs text-indigo-900 dark:text-indigo-100">
                                            <p className="font-medium">Validation intelligente du panier</p>
                                            <p className="mt-1 text-indigo-800/95 dark:text-indigo-200/90">
                                                Votre panier contient des articles à paiement immédiat et des articles à la livraison.
                                                La validation va créer automatiquement deux commandes, puis ouvrir le paiement en ligne
                                                uniquement pour les articles concernés.
                                            </p>
                                        </div>
                                    )}

                                    <div className="flex flex-col gap-2 pt-2 text-xs text-slate-500 dark:text-slate-400">
                                        <span className="inline-flex items-center gap-1">
                                            <ShieldCheck className="h-4 w-4 text-emerald-500 shrink-0" />
                                            {fusionPayRequired
                                                ? 'Paiement en ligne immédiat — puis confirmation par e-mail'
                                                : selectedPaymentType === 'cash_on_delivery'
                                                  ? 'Au colis (règle vendeur : à la livraison) — confirmation par e-mail avec n° de commande'
                                                  : 'Selon le moyen proposé ci-dessus — confirmation par e-mail'}
                                        </span>
                                        <span className="inline-flex items-center gap-1">
                                            <Truck className="h-4 w-4 text-amber-500 shrink-0" />
                                            Suivi : conservez l’e-mail de commande (n° indiqué)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {orderDrawerOpen && (
                <OrderDrawer
                    isOpen={orderDrawerOpen}
                    onClose={() => setOrderDrawerOpen(false)}
                    products={products ?? []}
                    initialCart={cart}
                    shippingMethods={shippingMethods ?? []}
                    paymentMethods={visiblePaymentMethods ?? []}
                    shippingAmount={shippingAmount}
                    taxRate={taxRate}
                    taxAmount={taxAmount}
                    couponDiscount={couponDiscount}
                    selectedShippingId={selectedShippingId}
                    selectedPaymentCode={selectedPaymentCode}
                    selectedPaymentType={selectedPaymentType}
                    paymentStatusOnSubmit={shouldAutoMarkPaid ? 'paid' : 'pending'}
                    readonlyShipping={useFlatShipping}
                    orderCurrency={normalizedDisplayCurrency}
                    onSuccess={(data) => {
                        clearCart();
                        setOrderDrawerOpen(false);
                        const num = data?.order?.order_number;
                        const secondaryNum = data?.secondary_order?.order_number;
                        if (data?.online_payment_unavailable && num) {
                            toast.error(
                                secondaryNum
                                    ? `Commande ${num} enregistrée en attente de paiement en ligne. Une autre commande ${secondaryNum} a été créée pour la livraison. Vérifiez vos e-mails.`
                                    : `Commande ${num} enregistrée en attente de paiement en ligne. La boutique vous contactera pour finaliser.`,
                                { duration: 11000 }
                            );
                        } else if (num) {
                            toast.success(
                                secondaryNum
                                    ? `Commande ${num} créée pour le paiement en ligne, et commande ${secondaryNum} créée pour les articles à la livraison. Vérifiez vos e-mails de confirmation.`
                                    : `Commande ${num} enregistrée. Un e-mail de confirmation vous a été envoyé : utilisez ce numéro pour le suivi ou toute question auprès de la boutique.`,
                                { duration: 9500 }
                            );
                        } else {
                            toast.success('Commande enregistrée. Vérifiez votre boîte e-mail pour la confirmation.');
                        }
                    }}
                />
            )}

            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} />
            <AISupportFloatingWidget />
        </>
    );
}

export default function StorefrontCart({
    shop,
    cmsPages = [],
    shipping_methods = [],
    payment_methods = [],
    tax_rate = 0,
    currency = 'CDF',
    exchange_rates = {},
    available_currencies = [],
    products = [],
    whatsapp = {},
    config = {},
}) {
    return (
        <CartProvider
            currency={currency}
            exchangeRates={exchange_rates}
            storageKey={`ecommerce_cart_${shop?.id ?? 'default'}`}
        >
            <CartContent
                shippingMethods={shipping_methods}
                paymentMethods={payment_methods}
                taxRate={tax_rate ?? 0}
                products={products}
                shop={shop}
                cmsPages={cmsPages}
                whatsapp={whatsapp}
                config={config}
                available_currencies={available_currencies}
            />
        </CartProvider>
    );
}
