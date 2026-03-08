import { createContext, useContext, useState, useEffect } from 'react';
import { toast } from 'react-hot-toast';

const CartContext = createContext();

export const useCart = () => {
    const context = useContext(CartContext);
    if (!context) {
        throw new Error('useCart must be used within CartProvider');
    }
    return context;
};

const CART_STORAGE_KEY = 'ecommerce_cart';

export const CartProvider = ({ children, initialCart = [], currency = 'USD' }) => {
    const [cart, setCart] = useState(() => {
        try {
            const stored = localStorage.getItem(CART_STORAGE_KEY);
            return stored ? JSON.parse(stored) : initialCart;
        } catch {
            return initialCart;
        }
    });

    useEffect(() => {
        localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
    }, [cart]);

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

    const getCartTotal = () => {
        return cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
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
                currency,
            }}
        >
            {children}
        </CartContext.Provider>
    );
};
