import { useState, useEffect, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
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
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import { cartRequiresFusionPay, paymentMethodsForCart } from '@/lib/ecommerceCartPayment';

function CartContent({
    shippingMethods,
    paymentMethods,
    taxRate,
    products,
}) {
    const { cart, updateQuantity, removeFromCart, getCartTotal, getPriceInDisplayCurrency, currency } = useCart();
    const [orderDrawerOpen, setOrderDrawerOpen] = useState(false);
    const [selectedShippingId, setSelectedShippingId] = useState(shippingMethods?.[0]?.id ?? '');
    const [selectedPaymentId, setSelectedPaymentId] = useState(paymentMethods?.[0]?.id ?? '');
    const [shippingAmount, setShippingAmount] = useState(0);
    const [couponCode, setCouponCode] = useState('');
    const [couponDiscount, setCouponDiscount] = useState(0);
    const [couponApplied, setCouponApplied] = useState(false);
    const [couponLoading, setCouponLoading] = useState(false);

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

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const subtotal = getCartTotal();
    const taxAmount = subtotal * (taxRate / 100);
    const total = subtotal + shippingAmount + taxAmount - couponDiscount;

    useEffect(() => {
        if (!selectedShippingId || cart.length === 0) {
            setShippingAmount(0);
            return;
        }
        axios.post(route('ecommerce.checkout.calculate-shipping'), {
            shipping_method_id: selectedShippingId,
            cart_subtotal: subtotal,
            cart_weight: 0,
        }).then((res) => {
            if (res.data?.success) {
                setShippingAmount(res.data.shipping_amount ?? 0);
            }
        }).catch(() => setShippingAmount(0));
    }, [selectedShippingId, subtotal, cart.length]);

    const handleApplyCoupon = () => {
        if (!couponCode.trim()) return;
        setCouponLoading(true);
        axios.post(route('ecommerce.checkout.validate-coupon'), {
            code: couponCode.trim(),
            cart_subtotal: subtotal,
            cart_items: cart.map((item) => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.price,
            })),
        }).then((res) => {
            if (res.data?.success) {
                setCouponDiscount(res.data.discount_amount ?? 0);
                setCouponApplied(true);
                toast.success('Code promo appliqué');
            } else {
                setCouponDiscount(0);
                setCouponApplied(false);
                toast.error(res.data?.message ?? 'Code invalide');
            }
        }).catch((err) => {
            setCouponDiscount(0);
            setCouponApplied(false);
            toast.error(err.response?.data?.message ?? 'Erreur');
        }).finally(() => setCouponLoading(false));
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
                'Le paiement en ligne sécurisé requis pour ce panier n’est pas disponible. Vérifiez la configuration des moyens de paiement.'
            );
            return;
        }
        setOrderDrawerOpen(true);
    };

    const selectedPaymentName = visiblePaymentMethods?.find((m) => m.id === selectedPaymentId)?.name ?? '';
    const selectedPaymentCode = visiblePaymentMethods?.find((m) => m.id === selectedPaymentId)?.code ?? '';
    const selectedPaymentType = visiblePaymentMethods?.find((m) => m.id === selectedPaymentId)?.type ?? '';

    return (
        <>
            <Head title="Panier" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="mb-6">
                        <Link
                            href={route('ecommerce.catalog.index')}
                            className="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-2 mb-4"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Continuer les achats
                        </Link>
                        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                            Panier
                        </h1>
                    </div>

                    {cart.length === 0 ? (
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                            <ShoppingBag className="h-16 w-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                            <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                Votre panier est vide
                            </p>
                            <p className="text-gray-500 dark:text-gray-400 mb-6">
                                Découvrez nos produits et ajoutez-les à votre panier
                            </p>
                            <Link href={route('ecommerce.catalog.index')}>
                                <Button className="gap-2">
                                    <Package className="h-4 w-4" />
                                    Voir le catalogue
                                </Button>
                            </Link>
                        </div>
                    ) : (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            <div className="lg:col-span-2 space-y-4">
                                {cart.map((item) => (
                                    <div
                                        key={item.product_id}
                                        className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
                                    >
                                        <div className="flex gap-4">
                                            {item.image_url ? (
                                                <img
                                                    src={item.image_url}
                                                    alt={item.name}
                                                    className="w-24 h-24 object-cover rounded-lg"
                                                />
                                            ) : (
                                                <div className="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                    <Package className="h-8 w-8 text-gray-400" />
                                                </div>
                                            )}
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-gray-900 dark:text-white mb-1">{item.name}</h3>
                                                {item.sku && (
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">SKU: {item.sku}</p>
                                                )}
                                                <p className="text-lg font-bold text-gray-900 dark:text-white mb-4">
                                                    {formatCurrency(getPriceInDisplayCurrency(item.price, item.price_currency))}
                                                </p>
                                                <div className="flex items-center gap-4">
                                                    <div className="flex items-center gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            onClick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                                        >
                                                            <Minus className="h-4 w-4" />
                                                        </Button>
                                                        <span className="w-12 text-center font-medium">{item.quantity}</span>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            onClick={() => updateQuantity(item.product_id, item.quantity + 1)}
                                                            disabled={item.quantity >= (item.stock ?? 999)}
                                                        >
                                                            <Plus className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => removeFromCart(item.product_id)}
                                                        className="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                                                    >
                                                        <Trash2 className="h-4 w-4 shrink-0" />
                                                        <span>Supprimer</span>
                                                    </Button>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <p className="text-lg font-bold text-gray-900 dark:text-white">
                                                    {formatCurrency(getPriceInDisplayCurrency(item.price, item.price_currency) * item.quantity)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            <div className="lg:col-span-1">
                                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-4 space-y-4">
                                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                                        Résumé de la commande
                                    </h2>

                                    {shippingMethods?.length > 0 && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Mode de livraison
                                            </label>
                                            <select
                                                value={selectedShippingId}
                                                onChange={(e) => setSelectedShippingId(e.target.value)}
                                                className="w-full rounded-md border border-gray-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2 text-sm"
                                            >
                                                {shippingMethods.map((m) => (
                                                    <option key={m.id} value={m.id}>
                                                        {m.name} – {m.base_cost > 0
                                                            ? formatCurrency(m.base_cost)
                                                            : 'Gratuit'}
                                                        {m.free_shipping_threshold && ` (gratuit dès ${formatCurrency(m.free_shipping_threshold)})`}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}

                                    {fusionPayMissing && (
                                        <div className="rounded-md border border-red-200 dark:border-red-900/50 bg-red-50 dark:bg-red-950/30 px-3 py-2 text-xs text-red-800 dark:text-red-200">
                                            Paiement en ligne requis pour ce panier (produit numérique ou paiement immédiat). Configurez un
                                            moyen de paiement en ligne compatible dans les paramètres.
                                        </div>
                                    )}
                                    {fusionPayRequired && !fusionPayMissing && (
                                        <p className="text-xs text-sky-700 dark:text-sky-300">
                                            Règle vendeur (fiche produit) : au moins un article exige un paiement en ligne immédiat.
                                        </p>
                                    )}
                                    {visiblePaymentMethods?.length > 0 && (
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Mode de paiement
                                            </label>
                                            <select
                                                value={selectedPaymentId}
                                                onChange={(e) => setSelectedPaymentId(e.target.value)}
                                                className="w-full rounded-md border border-gray-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2 text-sm"
                                            >
                                                {visiblePaymentMethods.map((m) => (
                                                    <option key={m.id} value={m.id}>
                                                        {m.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Code promo
                                        </label>
                                        <div className="flex gap-2">
                                            <Input
                                                value={couponCode}
                                                onChange={(e) => setCouponCode(e.target.value.toUpperCase())}
                                                placeholder="Code promo"
                                                disabled={couponApplied}
                                                className="flex-1"
                                            />
                                            {couponApplied ? (
                                                <Button type="button" variant="outline" size="sm" onClick={handleRemoveCoupon}>
                                                    Retirer
                                                </Button>
                                            ) : (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={handleApplyCoupon}
                                                    disabled={couponLoading || !couponCode.trim()}
                                                >
                                                    {couponLoading ? '...' : 'Appliquer'}
                                                </Button>
                                            )}
                                        </div>
                                        {couponApplied && couponDiscount > 0 && (
                                            <p className="text-sm text-green-600 dark:text-green-400 mt-1">
                                                <Tag className="h-4 w-4 inline mr-1" />
                                                -{formatCurrency(couponDiscount)} appliqué
                                            </p>
                                        )}
                                    </div>

                                    <div className="space-y-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                        <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                            <span>Sous-total</span>
                                            <span>{formatCurrency(subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                            <span>Livraison</span>
                                            <span>{formatCurrency(shippingAmount)}</span>
                                        </div>
                                        <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                            <span>Taxes ({taxRate}%)</span>
                                            <span>{formatCurrency(taxAmount)}</span>
                                        </div>
                                        {couponDiscount > 0 && (
                                            <div className="flex justify-between text-green-600 dark:text-green-400">
                                                <span>Réduction promo</span>
                                                <span>-{formatCurrency(couponDiscount)}</span>
                                            </div>
                                        )}
                                        <div className="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between">
                                            <span className="text-lg font-semibold text-gray-900 dark:text-white">Total</span>
                                            <span className="text-lg font-bold text-gray-900 dark:text-white">
                                                {formatCurrency(total)}
                                            </span>
                                        </div>
                                    </div>

                                    <Button
                                        onClick={handleCheckout}
                                        disabled={fusionPayMissing}
                                        className="w-full gap-2 mb-3"
                                        size="lg"
                                    >
                                        Passer la commande
                                        <ArrowRight className="h-4 w-4" />
                                    </Button>

                                    <Link href={route('ecommerce.catalog.index')}>
                                        <Button variant="outline" className="w-full">
                                            Continuer les achats
                                        </Button>
                                    </Link>
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
                />
            )}
        </>
    );
}

export default function CartIndex({
    shipping_methods = [],
    payment_methods = [],
    tax_rate = 0,
    currency = 'USD',
    exchange_rates = {},
    products = [],
}) {
    return (
        <CartProvider currency={currency} exchangeRates={exchange_rates}>
            <AppLayout
                header={
                    <div className="flex items-center justify-between w-full">
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-2">
                                <ShoppingCartIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Panier</h2>
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <ShoppingCart />
                        </div>
                    </div>
                }
            >
                <CartContent
                    shippingMethods={shipping_methods}
                    paymentMethods={payment_methods}
                    taxRate={tax_rate ?? 0}
                    products={products}
                />
            </AppLayout>
        </CartProvider>
    );
}
