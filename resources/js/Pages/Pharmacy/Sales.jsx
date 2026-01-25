import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { useState, useMemo, useEffect } from 'react';
import { Plus, Minus, ShoppingCart, X, Search, Filter, ImageIcon, CheckCircle, AlertCircle, CreditCard, Smartphone, Wallet, Receipt, ArrowLeft } from 'lucide-react';
import toast from 'react-hot-toast';

const PAYMENT_TYPES = [
    { value: 'cash', label: 'Espèces', icon: Wallet },
    { value: 'mobile_money', label: 'Mobile Money', icon: Smartphone },
    { value: 'card', label: 'Carte', icon: CreditCard },
    { value: 'credit', label: 'Crédit', icon: Receipt },
];

export default function Sales({ products = [], categories = [] }) {
    const { flash } = usePage().props;
    const [cart, setCart] = useState([]);
    const [selectedCategory, setSelectedCategory] = useState(null);
    const [searchQuery, setSearchQuery] = useState('');
    const [showStockAlert, setShowStockAlert] = useState(true);
    const [showSuccessScreen, setShowSuccessScreen] = useState(false);
    const [saleData, setSaleData] = useState(null);

    const { data, setData, post, processing, errors, reset } = useForm({
        items: [],
        customer_id: null,
        payment_type: 'cash',
        subtotal: 0,
        tax_amount: 0,
        discount_amount: 0,
        total: 0,
        notes: '',
    });

    // Handle sale_data from backend (single toast, no flash message)
    useEffect(() => {
        if (flash?.sale_data) {
            setSaleData(flash.sale_data);
            setShowSuccessScreen(true);
            // Single toast notification
            toast.success(`Vente #${flash.sale_data.sale_number} enregistrée avec succès.`);
        }
    }, [flash]);

    // Filter products by category and search
    const filteredProducts = useMemo(() => {
        let filtered = products;

        // Filter by category
        if (selectedCategory) {
            filtered = filtered.filter(p => p.category_id === selectedCategory);
        }

        // Filter by search query
        if (searchQuery.trim()) {
            const query = searchQuery.toLowerCase();
            filtered = filtered.filter(p =>
                p.name.toLowerCase().includes(query) ||
                p.sku?.toLowerCase().includes(query) ||
                p.barcode?.toLowerCase().includes(query)
            );
        }

        return filtered;
    }, [products, selectedCategory, searchQuery]);

    // Calculate cart totals
    const cartTotals = useMemo(() => {
        let subtotal = 0;
        let totalTax = 0;

        cart.forEach(item => {
            const itemSubtotal = (item.unit_price * item.quantity) - (item.discount_amount || 0);
            const itemTax = itemSubtotal * ((item.tax_rate || 0) / 100);
            subtotal += itemSubtotal;
            totalTax += itemTax;
        });

        const discountAmount = cart.reduce((sum, item) => sum + (item.discount_amount || 0), 0);
        const total = subtotal + totalTax;

        return { subtotal, tax_amount: totalTax, discount_amount: discountAmount, total };
    }, [cart]);

    // Update form data when cart changes
    useEffect(() => {
        const items = cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            unit_price: item.unit_price,
            tax_rate: item.tax_rate || 0,
            discount_amount: item.discount_amount || 0,
        }));

        setData({
            ...data,
            items,
            subtotal: cartTotals.subtotal,
            tax_amount: cartTotals.tax_amount,
            discount_amount: cartTotals.discount_amount,
            total: cartTotals.total,
        });
    }, [cart, cartTotals]);

    const addToCart = (product) => {
        // Check stock availability
        if (product.available_stock <= 0) {
            alert(`Stock insuffisant pour ${product.name}`);
            return;
        }

        const existingItem = cart.find(item => item.id === product.id);
        if (existingItem) {
            // Check if adding more would exceed stock
            if (existingItem.quantity + 1 > product.available_stock) {
                alert(`Stock insuffisant. Stock disponible: ${product.available_stock}`);
                return;
            }
            setCart(cart.map(item =>
                item.id === product.id
                    ? { ...item, quantity: item.quantity + 1 }
                    : item
            ));
        } else {
            setCart([...cart, {
                ...product,
                quantity: 1,
                unit_price: product.selling_price,
                tax_rate: product.tax_rate || 0,
                discount_amount: 0,
            }]);
        }
    };

    const removeFromCart = (productId) => {
        setCart(cart.filter(item => item.id !== productId));
    };

    const updateQuantity = (productId, quantity) => {
        if (quantity <= 0) {
            removeFromCart(productId);
            return;
        }

        const cartItem = cart.find(item => item.id === productId);
        const product = products.find(p => p.id === productId);

        if (product && quantity > product.available_stock) {
            alert(`Stock insuffisant. Stock disponible: ${product.available_stock}`);
            return;
        }

        setCart(cart.map(item =>
            item.id === productId
                ? { ...item, quantity }
                : item
        ));
    };

    const updateDiscount = (productId, discountAmount) => {
        setCart(cart.map(item =>
            item.id === productId
                ? { ...item, discount_amount: Math.max(0, discountAmount) }
                : item
        ));
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (cart.length === 0) {
            toast.error('Le panier est vide');
            return;
        }

        if (!data.payment_type) {
            toast.error('Veuillez sélectionner un type de paiement');
            return;
        }

        // Validate stock for all items
        for (const item of cart) {
            const product = products.find(p => p.id === item.id);
            if (!product || item.quantity > product.available_stock) {
                toast.error(`Stock insuffisant pour ${item.name}`);
                return;
            }
        }

        post(route('pharmacy.sales.store'), {
            onSuccess: () => {
                // Cart will be cleared after success screen
            },
            onError: (errors) => {
                if (errors.payment_type) {
                    toast.error(errors.payment_type);
                } else {
                    toast.error('Erreur lors de l\'enregistrement de la vente');
                }
            }
        });
    };

    const handleNewSale = () => {
        setCart([]);
        reset();
        setSearchQuery('');
        setSelectedCategory(null);
        setShowSuccessScreen(false);
        setSaleData(null);
        setData('payment_type', 'cash');
    };

    const currencySymbol = cart[0]?.currency_symbol || products[0]?.currency_symbol || 'FC';

    return (
        <AppLayout
            header={
                <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                    Point de Vente (POS)
                </h2>
            }
        >
            <Head title="Ventes - Pharmacy" />
            <FlashMessages />

            <div className="flex flex-col lg:flex-row h-[calc(100vh-180px)] gap-4 p-4">
                {/* Products Section */}
                <div className="flex-1 flex flex-col bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    {/* Search and Filters */}
                    <div className="p-4 border-b border-gray-200 dark:border-gray-700 space-y-3">
                        {/* Search */}
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                            <input
                                type="text"
                                placeholder="Rechercher un produit (nom, SKU, code-barres)..."
                                value={searchQuery}
                                onChange={(e) => setSearchQuery(e.target.value)}
                                className="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                            />
                        </div>

                        {/* Category Filter */}
                        <div className="flex items-center gap-2 flex-wrap">
                            <Filter className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                            <button
                                onClick={() => setSelectedCategory(null)}
                                className={`px-3 py-1 rounded-lg text-sm font-medium transition-colors ${
                                    selectedCategory === null
                                        ? 'bg-amber-600 text-white'
                                        : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                }`}
                            >
                                Tous
                            </button>
                            {categories.map((category) => (
                                <button
                                    key={category.id}
                                    onClick={() => setSelectedCategory(category.id)}
                                    className={`px-3 py-1 rounded-lg text-sm font-medium transition-colors ${
                                        selectedCategory === category.id
                                            ? 'bg-amber-600 text-white'
                                            : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'
                                    }`}
                                >
                                    {category.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Products Grid */}
                    <div className="flex-1 overflow-y-auto p-4">
                        {filteredProducts.length === 0 ? (
                            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                <p>Aucun produit trouvé</p>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                                {filteredProducts.map((product) => {
                                    const inCart = cart.find(item => item.id === product.id);
                                    const isOutOfStock = product.available_stock <= 0;
                                    const isLowStock = product.available_stock > 0 && product.available_stock <= 5;

                                    return (
                                        <div
                                            key={product.id}
                                            className={`bg-white dark:bg-gray-700 rounded-lg shadow p-3 cursor-pointer transition-all ${
                                                isOutOfStock
                                                    ? 'opacity-50 cursor-not-allowed'
                                                    : 'hover:shadow-lg hover:scale-105'
                                            }`}
                                            onClick={() => !isOutOfStock && addToCart(product)}
                                        >
                                            <div className="relative h-32 bg-gray-100 dark:bg-gray-600 rounded mb-2 flex items-center justify-center overflow-hidden">
                                                {product.image_url ? (
                                                    <img
                                                        src={product.image_url}
                                                        alt={product.name}
                                                        className="w-full h-full object-cover"
                                                        onError={(e) => {
                                                            e.target.style.display = 'none';
                                                            e.target.nextSibling.style.display = 'flex';
                                                        }}
                                                    />
                                                ) : null}
                                                <div className="hidden h-full w-full items-center justify-center">
                                                    <ImageIcon className="h-12 w-12 text-gray-400 dark:text-gray-500" />
                                                </div>
                                                {inCart && (
                                                    <div className="absolute top-2 right-2 bg-amber-600 text-white rounded-full p-1">
                                                        <CheckCircle className="h-4 w-4" />
                                                    </div>
                                                )}
                                                {isOutOfStock && (
                                                    <div className="absolute inset-0 bg-black/50 flex items-center justify-center">
                                                        <span className="text-white text-xs font-bold">Rupture</span>
                                                    </div>
                                                )}
                                            </div>
                                            <h3 className="font-semibold text-sm text-gray-900 dark:text-white truncate mb-1">
                                                {product.name}
                                            </h3>
                                            <div className="flex items-center justify-between">
                                                <p className="text-sm font-bold text-amber-600 dark:text-amber-400">
                                                    {product.currency_symbol} {parseFloat(product.selling_price).toFixed(2)}
                                                </p>
                                                {showStockAlert && (
                                                    <div className="flex items-center gap-1">
                                                        {isOutOfStock ? (
                                                            <AlertCircle className="h-3 w-3 text-red-500" />
                                                        ) : isLowStock ? (
                                                            <AlertCircle className="h-3 w-3 text-yellow-500" />
                                                        ) : null}
                                                        <span className={`text-xs ${
                                                            isOutOfStock ? 'text-red-500' :
                                                            isLowStock ? 'text-yellow-500' :
                                                            'text-gray-500 dark:text-gray-400'
                                                        }`}>
                                                            {product.available_stock}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        )}
                    </div>
                </div>

                {/* Cart Section */}
                <div className="w-full lg:w-96 bg-white dark:bg-gray-800 rounded-lg shadow flex flex-col overflow-hidden">
                    <div className="p-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <ShoppingCart className="h-5 w-5" />
                            Panier ({cart.length})
                        </h2>
                    </div>

                    <div className="flex-1 overflow-y-auto p-4 space-y-3">
                        {cart.length === 0 ? (
                            <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                <ShoppingCart className="h-12 w-12 mx-auto mb-2 opacity-50" />
                                <p>Le panier est vide</p>
                            </div>
                        ) : (
                            cart.map((item) => {
                                const product = products.find(p => p.id === item.id);
                                const itemSubtotal = (item.unit_price * item.quantity) - (item.discount_amount || 0);
                                const itemTax = itemSubtotal * ((item.tax_rate || 0) / 100);
                                const itemTotal = itemSubtotal + itemTax;

                                return (
                                    <div
                                        key={item.id}
                                        className="bg-gray-50 dark:bg-gray-700 rounded-lg p-3 space-y-2"
                                    >
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1 min-w-0">
                                                <h4 className="font-semibold text-sm text-gray-900 dark:text-white truncate">
                                                    {item.name}
                                                </h4>
                                                <p className="text-xs text-gray-600 dark:text-gray-400">
                                                    {item.currency_symbol} {parseFloat(item.unit_price).toFixed(2)} × {item.quantity}
                                                </p>
                                                {product && item.quantity > product.available_stock && (
                                                    <p className="text-xs text-red-600 dark:text-red-400 mt-1">
                                                        Stock: {product.available_stock}
                                                    </p>
                                                )}
                                            </div>
                                            <button
                                                onClick={() => removeFromCart(item.id)}
                                                className="p-1 text-red-600 hover:bg-red-100 dark:hover:bg-red-900/30 rounded transition-colors"
                                                title="Retirer du panier"
                                            >
                                                <X className="h-4 w-4" />
                                            </button>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <button
                                                onClick={() => updateQuantity(item.id, item.quantity - 1)}
                                                className="p-1 rounded bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
                                                disabled={item.quantity <= 1}
                                            >
                                                <Minus className="h-4 w-4" />
                                            </button>
                                            <input
                                                type="number"
                                                min="1"
                                                max={product?.available_stock || 999}
                                                value={item.quantity}
                                                onChange={(e) => {
                                                    const qty = parseInt(e.target.value) || 1;
                                                    updateQuantity(item.id, qty);
                                                }}
                                                className="w-16 text-center text-sm font-semibold border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-800 text-gray-900 dark:text-white"
                                            />
                                            <button
                                                onClick={() => updateQuantity(item.id, item.quantity + 1)}
                                                className="p-1 rounded bg-gray-200 dark:bg-gray-600 hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors"
                                                disabled={product && item.quantity >= product.available_stock}
                                            >
                                                <Plus className="h-4 w-4" />
                                            </button>
                                        </div>

                                        <div className="text-right">
                                            <p className="text-sm font-bold text-amber-600 dark:text-amber-400">
                                                {item.currency_symbol} {itemTotal.toFixed(2)}
                                            </p>
                                        </div>
                                    </div>
                                );
                            })
                        )}
                    </div>

                    {/* Cart Summary */}
                    {cart.length > 0 && (
                        <form onSubmit={handleSubmit} className="p-4 border-t border-gray-200 dark:border-gray-700 space-y-3">
                            <div className="space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Sous-total HT:</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {currencySymbol} {cartTotals.subtotal.toFixed(2)}
                                    </span>
                                </div>
                                {cartTotals.tax_amount > 0 && (
                                    <div className="flex justify-between text-sm">
                                        <span className="text-gray-600 dark:text-gray-400">TVA:</span>
                                        <span className="font-semibold text-gray-900 dark:text-white">
                                            {currencySymbol} {cartTotals.tax_amount.toFixed(2)}
                                        </span>
                                    </div>
                                )}
                                {cartTotals.discount_amount > 0 && (
                                    <div className="flex justify-between text-sm text-green-600 dark:text-green-400">
                                        <span>Remise:</span>
                                        <span className="font-semibold">
                                            -{currencySymbol} {cartTotals.discount_amount.toFixed(2)}
                                        </span>
                                    </div>
                                )}
                                <div className="flex justify-between text-lg font-bold pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span className="text-gray-900 dark:text-white">Total:</span>
                                    <span className="text-amber-600 dark:text-amber-400">
                                        {currencySymbol} {cartTotals.total.toFixed(2)}
                                    </span>
                                </div>
                            </div>

                            {errors.items && (
                                <div className="text-sm text-red-600 dark:text-red-400">
                                    {errors.items}
                                </div>
                            )}

                            <button
                                type="submit"
                                disabled={processing || cart.length === 0}
                                className="w-full px-4 py-3 bg-amber-600 hover:bg-amber-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white rounded-lg font-semibold transition-colors"
                            >
                                {processing ? 'Enregistrement...' : 'Valider la vente'}
                            </button>
                        </form>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
