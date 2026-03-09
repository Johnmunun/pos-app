import { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { useCart } from '@/Contexts/CartContext';
import {
    ShoppingCart as ShoppingCartIcon,
    X,
    Plus,
    Minus,
    Trash2,
    ShoppingBag,
    ArrowRight,
} from 'lucide-react';

export default function ShoppingCart({ buttonClassName }) {
    const { cart, updateQuantity, removeFromCart, getCartTotal, currency } = useCart();
    const [isOpen, setIsOpen] = useState(false);
    const [isMiniCartOpen, setIsMiniCartOpen] = useState(false);

    const itemCount = cart.reduce((sum, item) => sum + item.quantity, 0);
    const subtotal = getCartTotal();

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const handleQuantityChange = (productId, newQuantity) => {
        if (newQuantity <= 0) {
            removeFromCart(productId);
        } else {
            updateQuantity(productId, newQuantity);
        }
    };

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

            {/* Mini Cart Sidebar */}
            <div
                className={`fixed top-0 right-0 h-full w-full sm:w-96 bg-white dark:bg-gray-800 shadow-2xl z-50 transform transition-transform duration-300 ease-in-out ${
                    isMiniCartOpen ? 'translate-x-0' : 'translate-x-full'
                }`}
            >
                <div className="flex flex-col h-full">
                    {/* Header */}
                    <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <ShoppingBag className="h-5 w-5" />
                            Panier ({itemCount})
                        </h2>
                        <button
                            onClick={() => setIsMiniCartOpen(false)}
                            className="p-1 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-full transition-colors"
                        >
                            <X className="h-5 w-5 text-gray-500" />
                        </button>
                    </div>

                    {/* Cart Items */}
                    <div className="flex-1 overflow-y-auto p-4">
                        {cart.length === 0 ? (
                            <div className="flex flex-col items-center justify-center h-full text-center">
                                <ShoppingBag className="h-16 w-16 text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-gray-600 dark:text-gray-400 mb-2">Votre panier est vide</p>
                                <Link href={route('ecommerce.catalog.index')}>
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
                                        className="flex gap-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg"
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
                                            <h3 className="font-medium text-sm text-gray-900 dark:text-white truncate">
                                                {item.name}
                                            </h3>
                                            <p className="text-sm font-semibold text-gray-900 dark:text-white mt-1">
                                                {formatCurrency(item.price)}
                                            </p>
                                            <div className="flex items-center gap-2 mt-2">
                                                <button
                                                    onClick={() => handleQuantityChange(item.product_id, item.quantity - 1)}
                                                    className="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                                                >
                                                    <Minus className="h-3 w-3" />
                                                </button>
                                                <span className="text-sm font-medium w-8 text-center">
                                                    {item.quantity}
                                                </span>
                                                <button
                                                    onClick={() => handleQuantityChange(item.product_id, item.quantity + 1)}
                                                    className="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded"
                                                >
                                                    <Plus className="h-3 w-3" />
                                                </button>
                                                <button
                                                    onClick={() => removeFromCart(item.product_id)}
                                                    className="ml-auto p-1 hover:bg-red-100 dark:hover:bg-red-900/30 rounded text-red-600 dark:text-red-400"
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
                        <div className="border-t border-gray-200 dark:border-gray-700 p-4 space-y-3">
                            <div className="flex justify-between items-center">
                                <span className="text-gray-600 dark:text-gray-400">Sous-total</span>
                                <span className="text-lg font-bold text-gray-900 dark:text-white">
                                    {formatCurrency(subtotal)}
                                </span>
                            </div>
                            <Link href={route('ecommerce.cart.index')} className="block">
                                <Button
                                    className="w-full gap-2"
                                    onClick={() => setIsMiniCartOpen(false)}
                                >
                                    Voir le panier
                                    <ArrowRight className="h-4 w-4" />
                                </Button>
                            </Link>
                        </div>
                    )}
                </div>

                {/* Overlay - clic pour fermer */}
                {isMiniCartOpen && (
                    <div
                        className="fixed inset-0 bg-black/50 z-40"
                        onClick={() => setIsMiniCartOpen(false)}
                        aria-hidden="true"
                    />
                )}
            </div>
        </>
    );
}
