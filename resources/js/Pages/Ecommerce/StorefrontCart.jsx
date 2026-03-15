import { useState, useEffect } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { CartProvider, useCart } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import OrderDrawer from '@/Components/Ecommerce/OrderDrawer';
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
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import { formatCurrency } from '@/lib/currency';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';

function StorefrontCartHeader({ shop, cmsPages = [], currency }) {
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
                    <ShoppingCart
                        buttonClassName="relative inline-flex items-center justify-center h-10 w-10 rounded-xl bg-[var(--sf-primary,#f59e0b)] text-white hover:bg-[var(--sf-primary-hover,#d97706)] shadow-lg transition-all"
                        storefrontLinks
                    />
                </div>
            </div>
        </header>
    );
}

function CartContent({ shippingMethods, paymentMethods, taxRate, products, shop, cmsPages, whatsapp = {}, config = {} }) {
    const { cart, updateQuantity, removeFromCart, getCartTotal, getPriceInDisplayCurrency, currency, clearCart } = useCart();
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

    const subtotal = getCartTotal();
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + shippingAmount + taxAmount - couponDiscount;

    useEffect(() => {
        if (!selectedShippingId || cart.length === 0) {
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
    }, [selectedShippingId, subtotal, cart.length]);

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
        setOrderDrawerOpen(true);
    };

    const selectedPaymentCode = paymentMethods?.find((m) => m.id === selectedPaymentId)?.code ?? '';
    const selectedPaymentType = paymentMethods?.find((m) => m.id === selectedPaymentId)?.type ?? '';

    const hasDigitalItems = cart.some((i) => !!i.is_digital);
    const hasPhysicalItems = cart.some((i) => !i.is_digital);
    const isPayOnDelivery = selectedPaymentType === 'cash_on_delivery';
    // Paiement immédiat: on marque payé automatiquement pour délivrer les produits numériques sans attendre le vendeur.
    const shouldAutoMarkPaid = hasDigitalItems && !isPayOnDelivery;

    const whatsappNumber = whatsapp.number || null;
    const whatsappSupportEnabled = !!whatsapp.enabled;

    const primaryColor = config?.theme_primary_color || '#f59e0b';
    const secondaryColor = config?.theme_secondary_color || '#d97706';

    return (
        <>
            <Head title="Panier - Boutique" />
            <StorefrontCartHeader shop={shop} cmsPages={cmsPages} currency={displayCurrency} />

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
                                                {item.sku && (
                                                    <p className="text-xs text-slate-500 dark:text-slate-400 mb-3">Réf. {item.sku}</p>
                                                )}
                                                <p className="text-lg font-bold text-amber-600 dark:text-amber-400 mb-4">
                                                    {format(getPriceInDisplayCurrency(item.price, item.price_currency))}
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
                                                        className="text-red-600 dark:text-red-400 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg"
                                                    >
                                                        <Trash2 className="h-4 w-4 shrink-0 mr-1" />
                                                        Supprimer
                                                    </Button>
                                                </div>
                                            </div>
                                            <div className="flex-shrink-0 text-right">
                                                <p className="text-xl font-bold text-slate-900 dark:text-white">
                                                    {format(getPriceInDisplayCurrency(item.price, item.price_currency) * item.quantity)}
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
                                            {displayCurrency}
                                        </span>
                                    </div>

                                    {shippingMethods?.length > 0 && (
                                        <div>
                                            <label className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                <Truck className="h-4 w-4 text-amber-500" />
                                                Livraison
                                            </label>
                                            <select
                                                value={selectedShippingId}
                                                onChange={(e) => setSelectedShippingId(e.target.value)}
                                                className="w-full rounded-xl border border-slate-200 dark:border-slate-600 dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-colors"
                                            >
                                                {shippingMethods.map((m) => (
                                                    <option key={m.id} value={m.id}>
                                                        {m.name} – {m.base_cost > 0 ? format(m.base_cost) : 'Gratuit'}
                                                        {m.free_shipping_threshold && ` (gratuit dès ${format(m.free_shipping_threshold)})`}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}

                                    {paymentMethods?.length > 0 && (
                                        <div>
                                            <label className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                                <CreditCard className="h-4 w-4 text-amber-500" />
                                                Paiement
                                            </label>
                                            <select
                                                value={selectedPaymentId}
                                                onChange={(e) => setSelectedPaymentId(e.target.value)}
                                                className="w-full rounded-xl border border-slate-200 dark:border-slate-600 dark:bg-slate-900 px-3 py-2.5 text-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-colors"
                                            >
                                                {paymentMethods.map((m) => (
                                                    <option key={m.id} value={m.id}>
                                                        {m.name}
                                                    </option>
                                                ))}
                                            </select>
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
                                            <span className="text-slate-600 dark:text-slate-400">Livraison</span>
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
                                        className="w-full gap-2 rounded-xl h-12 text-base font-semibold shadow-lg shadow-[var(--sf-primary,#f59e0b)]/25 transition-all"
                                        size="lg"
                                    >
                                        Passer la commande
                                        <ArrowRight className="h-4 w-4" />
                                    </Button>

                                    <div className="flex items-center gap-3 pt-2 text-xs text-slate-500 dark:text-slate-400">
                                        <span className="inline-flex items-center gap-1">
                                            <ShieldCheck className="h-4 w-4 text-emerald-500" />
                                            Paiement sécurisé
                                        </span>
                                        <span className="inline-flex items-center gap-1">
                                            <Truck className="h-4 w-4 text-amber-500" />
                                            Livraison rapide
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
                    paymentMethods={paymentMethods ?? []}
                    shippingAmount={shippingAmount}
                    taxRate={taxRate}
                    taxAmount={taxAmount}
                    couponDiscount={couponDiscount}
                    selectedShippingId={selectedShippingId}
                    selectedPaymentCode={selectedPaymentCode}
                    paymentStatusOnSubmit={shouldAutoMarkPaid ? 'paid' : 'pending'}
                    onSuccess={() => {
                        clearCart();
                        setOrderDrawerOpen(false);
                    }}
                />
            )}

            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} />
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
    products = [],
    whatsapp = {},
    config = {},
}) {
    return (
        <CartProvider currency={currency} exchangeRates={exchange_rates}>
            <CartContent
                shippingMethods={shipping_methods}
                paymentMethods={payment_methods}
                taxRate={tax_rate ?? 0}
                products={products}
                shop={shop}
                cmsPages={cmsPages}
                whatsapp={whatsapp}
                config={config}
            />
        </CartProvider>
    );
}
