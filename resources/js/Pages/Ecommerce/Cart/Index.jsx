import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
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
} from 'lucide-react';
import { toast } from 'react-hot-toast';

function CartContent() {
    const { cart, updateQuantity, removeFromCart, clearCart, getCartTotal, currency } = useCart();
    const [orderDrawerOpen, setOrderDrawerOpen] = useState(false);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const subtotal = getCartTotal();
    const shipping = 0; // TODO: Calculate shipping
    const tax = 0; // TODO: Calculate tax
    const total = subtotal + shipping + tax;

    const handleCheckout = () => {
        if (cart.length === 0) {
            toast.error('Votre panier est vide');
            return;
        }
        setOrderDrawerOpen(true);
    };

    return (
        <>
            <Head title="Panier" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Header */}
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
                            {/* Cart Items */}
                            <div className="lg:col-span-2 space-y-4">
                                {cart.map((item) => (
                                    <div
                                        key={item.product_id}
                                        className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6"
                                    >
                                        <div className="flex gap-4">
                                            {/* Image */}
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

                                            {/* Details */}
                                            <div className="flex-1">
                                                <h3 className="font-semibold text-gray-900 dark:text-white mb-1">
                                                    {item.name}
                                                </h3>
                                                {item.sku && (
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                        SKU: {item.sku}
                                                    </p>
                                                )}
                                                <p className="text-lg font-bold text-gray-900 dark:text-white mb-4">
                                                    {formatCurrency(item.price)}
                                                </p>

                                                {/* Quantity Controls */}
                                                <div className="flex items-center gap-4">
                                                    <div className="flex items-center gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            onClick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                                        >
                                                            <Minus className="h-4 w-4" />
                                                        </Button>
                                                        <span className="w-12 text-center font-medium">
                                                            {item.quantity}
                                                        </span>
                                                        <Button
                                                            variant="outline"
                                                            size="icon"
                                                            onClick={() => updateQuantity(item.product_id, item.quantity + 1)}
                                                            disabled={item.quantity >= item.stock}
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
                                                        <Trash2 className="h-4 w-4 mr-2" />
                                                        Supprimer
                                                    </Button>
                                                </div>
                                            </div>

                                            {/* Subtotal */}
                                            <div className="text-right">
                                                <p className="text-lg font-bold text-gray-900 dark:text-white">
                                                    {formatCurrency(item.price * item.quantity)}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {/* Order Summary */}
                            <div className="lg:col-span-1">
                                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 sticky top-4">
                                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-4">
                                        Résumé de la commande
                                    </h2>

                                    <div className="space-y-3 mb-6">
                                        <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                            <span>Sous-total</span>
                                            <span>{formatCurrency(subtotal)}</span>
                                        </div>
                                        <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                            <span>Livraison</span>
                                            <span>{formatCurrency(shipping)}</span>
                                        </div>
                                        <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                            <span>Taxes</span>
                                            <span>{formatCurrency(tax)}</span>
                                        </div>
                                        <div className="border-t border-gray-200 dark:border-gray-700 pt-3 flex justify-between">
                                            <span className="text-lg font-semibold text-gray-900 dark:text-white">
                                                Total
                                            </span>
                                            <span className="text-lg font-bold text-gray-900 dark:text-white">
                                                {formatCurrency(total)}
                                            </span>
                                        </div>
                                    </div>

                                    <Button
                                        onClick={handleCheckout}
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

            {/* Order Drawer */}
            {orderDrawerOpen && (
                <OrderDrawer
                    isOpen={orderDrawerOpen}
                    onClose={() => setOrderDrawerOpen(false)}
                    products={[]}
                    initialCart={cart}
                />
            )}
        </>
    );
}

export default function CartIndex() {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'USD';

    return (
        <CartProvider currency={currency}>
            <AppLayout
                header={
                    <div className="flex items-center justify-between w-full">
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-2">
                                <ShoppingCartIcon className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                    Panier
                                </h2>
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <ShoppingCart />
                        </div>
                    </div>
                }
            >
                <CartContent />
            </AppLayout>
        </CartProvider>
    );
}
