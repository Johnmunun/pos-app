import { useState } from 'react';
import { createPortal } from 'react-dom';
import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { useCart } from '@/Contexts/CartContext';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';
import {
    ShoppingCart as ShoppingCartIcon,
    X,
    Plus,
    Minus,
    Trash2,
    ShoppingBag,
    ArrowRight,
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

export default function ShoppingCart({ buttonClassName, storefrontLinks = false }) {
    const links = useStorefrontLinks();
    const { cart, updateQuantity, removeFromCart, getCartTotal, getPriceInDisplayCurrency, currency } = useCart();
    const [isMiniCartOpen, setIsMiniCartOpen] = useState(false);
    const catalogUrl = storefrontLinks ? links.catalog() : route('ecommerce.catalog.index');
    const cartUrl = storefrontLinks ? links.cart() : route('ecommerce.cart.index');

    const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const displayCurrency = currency || 'CDF';
    const format = (amount) => formatCurrency(amount, displayCurrency);

    const handleQuantityChange = (productId, newQuantity) => {
        if (newQuantity <= 0) {
            removeFromCart(productId);
        } else {
            updateQuantity(productId, newQuantity);
        }
    };

    const drawerContent = isMiniCartOpen && (
        <>
            {/* Overlay - clic pour fermer */}
            <div
                className="fixed inset-0 bg-black/50 z-[9998]"
                onClick={() => setIsMiniCartOpen(false)}
                aria-hidden="true"
            />
            {/* Mini Cart Sidebar - au-dessus de tout */}
            <div
                className="fixed top-0 right-0 h-[100dvh] w-full max-w-[100vw] sm:w-96 bg-white dark:bg-slate-800 shadow-2xl z-[9999] flex flex-col overflow-hidden transform transition-transform duration-300 ease-in-out translate-x-0 pt-[env(safe-area-inset-top,0px)] pb-[env(safe-area-inset-bottom,0px)]"
            >
                {/* Header - style vitrine cohérent */}
                <div className="sticky top-0 z-10 flex items-center justify-between p-4 bg-[var(--sf-primary)] border-b border-[var(--sf-secondary)]/50">
                    <h2 className="text-lg font-semibold text-white flex items-center gap-2">
                        <ShoppingCartIcon className="h-5 w-5" />
                        Panier ({itemCount})
                    </h2>
                    <button
                        onClick={() => setIsMiniCartOpen(false)}
                        className="p-1.5 hover:bg-white/20 rounded-full transition-colors text-white"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Cart Items */}
                <div className="flex-1 overflow-y-auto p-4 min-h-0">
                        {cart.length === 0 ? (
                            <div className="flex flex-col items-center justify-center h-full text-center">
                                <ShoppingBag className="h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-gray-600 dark:text-gray-400 mb-2">Votre panier est vide</p>
                                <Link href={catalogUrl}>
                                    <Button variant="outline" onClick={() => setIsMiniCartOpen(false)}>
                                        Continuer les achats
                                    </Button>
                                </Link>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {cart.map((item) => (
                                    <div
                                        key={item.product_id}
                                        className="flex gap-3 p-3 bg-slate-50 dark:bg-slate-700/50 rounded-xl border border-slate-100 dark:border-slate-600/50"
                                    >
                                        {item.image_url ? (
                                            <img
                                                src={item.image_url}
                                                alt={item.name}
                                                className="w-16 h-16 object-cover rounded"
                                            />
                                        ) : (
                                            <div className="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded flex items-center justify-center">
                                                <ShoppingBag className="h-6 w-6 text-gray-400" />
                                            </div>
                                        )}
                                        <div className="flex-1 min-w-0">
                                            <h3 className="font-medium text-sm text-slate-900 dark:text-white truncate">
                                                {item.name}
                                            </h3>
                                            <p className="text-sm font-semibold text-[var(--sf-primary)] mt-1">
                                                {format(getPriceInDisplayCurrency(item.price, item.price_currency))}
                                            </p>
                                            <div className="flex items-center gap-2 mt-2">
                                                <button
                                                    onClick={() => handleQuantityChange(item.product_id, item.quantity - 1)}
                                                    className="p-1.5 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors"
                                                >
                                                    <Minus className="h-3 w-3" />
                                                </button>
                                                <span className="text-sm font-medium w-8 text-center text-slate-900 dark:text-slate-100">
                                                    {item.quantity}
                                                </span>
                                                <button
                                                    onClick={() => handleQuantityChange(item.product_id, item.quantity + 1)}
                                                    className="p-1.5 text-slate-700 dark:text-slate-200 hover:bg-slate-200 dark:hover:bg-slate-600 rounded-lg transition-colors"
                                                >
                                                    <Plus className="h-3 w-3" />
                                                </button>
                                                <button
                                                    onClick={() => removeFromCart(item.product_id)}
                                                    className="ml-auto p-1.5 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg text-red-600 dark:text-red-400 transition-colors"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                {/* Footer */}
                {cart.length > 0 && (
                    <div className="sticky bottom-0 z-10 border-t border-slate-200 dark:border-slate-700 p-4 space-y-3 bg-white dark:bg-slate-800">
                        <div className="flex justify-between items-center">
                            <span className="text-slate-600 dark:text-slate-400">Sous-total</span>
                            <span className="text-lg font-bold text-slate-900 dark:text-white">
                                {format(getCartTotal())}
                            </span>
                        </div>
                        <Link href={cartUrl} className="block">
                            <Button
                                className="w-full gap-2 bg-[var(--sf-primary)] hover:bg-[var(--sf-primary-hover)]"
                                onClick={() => setIsMiniCartOpen(false)}
                            >
                                Voir le panier
                                <ArrowRight className="h-4 w-4" />
                            </Button>
                        </Link>
                    </div>
                )}
            </div>
        </>
    );

    return (
        <>
            {/* Mini Cart Button (Header) */}
            <button
                onClick={() => setIsMiniCartOpen(true)}
                className={buttonClassName ?? 'relative p-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors'}
            >
                <ShoppingCartIcon className="h-6 w-6" />
                {itemCount > 0 && (
                    <Badge className="absolute -top-1 -right-1 h-5 w-5 flex items-center justify-center p-0 bg-red-500 text-white text-xs">
                        {itemCount}
                    </Badge>
                )}
            </button>

            {/* Drawer via Portal - rendu dans document.body pour éviter les problèmes de z-index / overflow des parents */}
            {typeof document !== 'undefined' && createPortal(drawerContent, document.body)}
        </>
    );
}
