import { createContext, useContext, useState, useEffect } from 'react';
import { toast } from 'react-hot-toast';
import { normalizeCurrencyCode } from '@/lib/currency';

const CartContext = createContext();

export const useCart = () => {
    const context = useContext(CartContext);
    if (!context) {
        throw new Error('useCart must be used within CartProvider');
    }
    return context;
};

const CART_STORAGE_KEY = 'ecommerce_cart';

/**
 * Converts amount from one currency to the display currency.
 * exchangeRates: { [code]: rate } where rate = units of that currency per 1 unit of default.
 * Default currency has rate 1.
 * Formula: amountInTarget = amount * targetRate / sourceRate
 */
function convertToCurrency(amount, fromCurrency, toCurrency, exchangeRates = {}) {
    if (!fromCurrency || !toCurrency) return Number(amount);
    const fromNorm = normalizeCurrencyCode(fromCurrency);
    const toNorm = normalizeCurrencyCode(toCurrency);
    if (fromNorm === toNorm) return Number(amount);
    const fromRate = exchangeRates[fromNorm] ?? exchangeRates[fromCurrency] ?? 1;
    const toRate = exchangeRates[toNorm] ?? exchangeRates[toCurrency] ?? 1;
    if (toRate === 0) return 0;
    return (Number(amount) * toRate) / fromRate;
}

export const CartProvider = ({ children, initialCart = [], currency = 'USD', exchangeRates = {}, storageKey = CART_STORAGE_KEY }) => {
    const [cart, setCart] = useState(() => {
        try {
            const stored = localStorage.getItem(storageKey);
            return stored ? JSON.parse(stored) : initialCart;
        } catch {
            return initialCart;
        }
    });

    useEffect(() => {
        localStorage.setItem(storageKey, JSON.stringify(cart));
    }, [cart, storageKey]);

    const addToCart = (product, quantity = 1) => {
        if (product.stock < quantity) {
            toast.error('Stock insuffisant');
            return;
        }

        setCart((prevCart) => {
            const existing = prevCart.find((item) => item.product_id === product.id);
            if (existing) {
                const newQuantity = existing.quantity + quantity;
                if (newQuantity > product.stock) {
                    toast.error('Stock insuffisant');
                    return prevCart;
                }
                return prevCart.map((item) =>
                    item.product_id === product.id
                        ? { ...item, quantity: newQuantity }
                        : item
                );
            } else {
                return [
                    ...prevCart,
                    {
                        product_id: product.id,
                        name: product.name,
                        price: product.price_amount,
                        price_currency: product.price_currency || currency,
                        quantity,
                        image_url: product.image_url,
                        sku: product.sku,
                        stock: product.stock,
                        // Infos nécessaires au checkout (numérique / livraison / paiement)
                        is_digital: !!product.is_digital,
                        requires_shipping: product.requires_shipping ?? !product.is_digital,
                        mode_paiement: product.mode_paiement ?? (product.is_digital ? 'paiement_immediat' : 'paiement_immediat'),
                    },
                ];
            }
        });
        toast.success('Produit ajouté au panier');
    };

    const updateQuantity = (productId, quantity) => {
        if (quantity <= 0) {
            removeFromCart(productId);
            return;
        }

        setCart((prevCart) => {
            const item = prevCart.find((i) => i.product_id === productId);
            if (!item) return prevCart;
            if (quantity > item.stock) {
                toast.error('Stock insuffisant');
                return prevCart;
            }
            return prevCart.map((item) =>
                item.product_id === productId ? { ...item, quantity } : item
            );
        });
    };

    const removeFromCart = (productId) => {
        setCart((prevCart) => prevCart.filter((item) => item.product_id !== productId));
        toast.success('Produit retiré du panier');
    };

    const clearCart = () => {
        setCart([]);
    };

    const displayCurrency = normalizeCurrencyCode(currency || 'USD');

    const getCartTotal = () => {
        const rates = Object.keys(exchangeRates).length ? exchangeRates : { [displayCurrency]: 1 };
        return cart.reduce((sum, item) => {
            const itemCurrency = item.price_currency || displayCurrency;
            const convertedPrice = convertToCurrency(item.price, itemCurrency, displayCurrency, rates);
            return sum + convertedPrice * item.quantity;
        }, 0);
    };

    const getPriceInDisplayCurrency = (amount, fromCurrency) => {
        const rates = Object.keys(exchangeRates).length ? exchangeRates : { [displayCurrency]: 1 };
        return convertToCurrency(amount, fromCurrency || displayCurrency, displayCurrency, rates);
    };

    const getCartItemCount = () => {
        return cart.reduce((sum, item) => sum + item.quantity, 0);
    };

    return (
        <CartContext.Provider
            value={{
                cart,
                addToCart,
                updateQuantity,
                removeFromCart,
                clearCart,
                getCartTotal,
                getCartItemCount,
                getPriceInDisplayCurrency,
            currency: displayCurrency,
            exchangeRates,
            }}
        >
            {children}
        </CartContext.Provider>
    );
};
