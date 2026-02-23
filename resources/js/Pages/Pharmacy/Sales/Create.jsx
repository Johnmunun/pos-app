import React, { useState, useMemo, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { 
    ShoppingCart, 
    Plus, 
    Minus, 
    Trash2, 
    Search, 
    Grid3X3,
    List,
    Pause,
    Receipt,
    CreditCard,
    Banknote,
    Smartphone,
    ArrowLeft,
    Package,
    Percent,
    X,
    ChevronRight,
    Store
} from 'lucide-react';
import axios from 'axios';
import toast from 'react-hot-toast';
import { formatCurrency, getCurrencySymbol } from '@/lib/currency';

export default function POSCreate({ products = [], categories = [], customers = [], canUseWholesale = false }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    
    // Currency formatter bound to the shop's currency
    const fmt = (amount) => formatCurrency(amount, currency);
    const currencySymbol = getCurrencySymbol(currency);
    
    const [viewMode, setViewMode] = useState('thumbnails'); // thumbnails or list
    const [search, setSearch] = useState('');
    const [selectedCategory, setSelectedCategory] = useState(null);
    const [customerId, setCustomerId] = useState('');
    const [cart, setCart] = useState([]);
    const [paidAmount, setPaidAmount] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [holdOrders, setHoldOrders] = useState([]);
    const [currentOrderNumber, setCurrentOrderNumber] = useState(generateOrderNumber());
    const [showPaymentModal, setShowPaymentModal] = useState(false);
    const [paymentMethod, setPaymentMethod] = useState('cash');
    const [discount, setDiscount] = useState({ type: 'percent', value: 0 });
    const [taxRate, setTaxRate] = useState(0); // Percentage
    const [saleMode, setSaleMode] = useState('retail'); // 'retail' | 'wholesale'

    function generateOrderNumber() {
        return 'A01-' + String(Math.floor(Math.random() * 900000000) + 100000000).padStart(10, '0');
    }

    // Filter products
    const filteredProducts = useMemo(() => {
        let result = products;
        
        if (selectedCategory) {
            result = result.filter(p => p.category_id === selectedCategory);
        }
        
        if (search.trim()) {
            const s = search.toLowerCase();
            result = result.filter(p => 
                (p.name || '').toLowerCase().includes(s) || 
                (p.code || '').toLowerCase().includes(s)
            );
        }
        
        return result;
    }, [products, search, selectedCategory]);

    const getProductPrice = (product) => {
        if (saleMode === 'wholesale' && canUseWholesale && product.wholesale_price_amount != null) {
            return product.wholesale_price_amount;
        }
        return product.price_amount ?? 0;
    };

    useEffect(() => {
        if (cart.length === 0) return;
        setCart(prev => prev.map(item => {
            const product = products.find(p => p.id === item.product_id);
            if (!product) return item;
            return { ...item, price: getProductPrice(product) };
        }));
    }, [saleMode]);

    const addToCart = (product) => {
        if ((product.stock || 0) < 1) {
            toast.error('Stock insuffisant');
            return;
        }
        
        const existing = cart.find(item => item.product_id === product.id);
        if (existing) {
            if (existing.quantity >= product.stock) {
                toast.error('Stock insuffisant');
                return;
            }
            setCart(cart.map(item => 
                item.product_id === product.id 
                    ? { ...item, quantity: item.quantity + 1 }
                    : item
            ));
        } else {
            setCart([...cart, {
                product_id: product.id,
                name: product.name,
                code: product.code,
                price: getProductPrice(product),
                currency,
                quantity: 1,
                max_stock: product.stock,
                discount_percent: 0,
                image_url: product.image_url
            }]);
        }
    };

    const updateQuantity = (productId, newQuantity) => {
        if (newQuantity < 1) {
            removeFromCart(productId);
            return;
        }
        
        const item = cart.find(i => i.product_id === productId);
        if (item && newQuantity > item.max_stock) {
            toast.error('Stock insuffisant');
            return;
        }
        
        setCart(cart.map(item =>
            item.product_id === productId
                ? { ...item, quantity: newQuantity }
                : item
        ));
    };

    const removeFromCart = (productId) => {
        setCart(cart.filter(item => item.product_id !== productId));
    };

    const clearCart = () => {
        setCart([]);
        setDiscount({ type: 'percent', value: 0 });
        setCurrentOrderNumber(generateOrderNumber());
    };

    // Cart calculations
    const subtotal = useMemo(() => 
        cart.reduce((sum, item) => {
            const itemTotal = item.price * item.quantity;
            const itemDiscount = item.discount_percent ? (itemTotal * item.discount_percent / 100) : 0;
            return sum + (itemTotal - itemDiscount);
        }, 0)
    , [cart]);

    const taxAmount = useMemo(() => (subtotal * taxRate) / 100, [subtotal, taxRate]);
    
    const orderDiscount = useMemo(() => {
        if (discount.type === 'percent') {
            return (subtotal * discount.value) / 100;
        }
        return discount.value;
    }, [subtotal, discount]);

    const total = useMemo(() => Math.max(0, subtotal + taxAmount - orderDiscount), [subtotal, taxAmount, orderDiscount]);
    const paid = Number(paidAmount) || 0;
    const balance = Math.max(0, total - paid);
    const change = paid > total ? paid - total : 0;

    // Hold order
    const holdCurrentOrder = () => {
        if (cart.length === 0) {
            toast.error('Panier vide');
            return;
        }
        
        setHoldOrders([...holdOrders, {
            orderNumber: currentOrderNumber,
            cart: [...cart],
            customerId,
            discount,
            timestamp: new Date()
        }]);
        
        setCart([]);
        setCustomerId('');
        setDiscount({ type: 'percent', value: 0 });
        setCurrentOrderNumber(generateOrderNumber());
        toast.success('Commande mise en attente');
    };

    const resumeOrder = (index) => {
        const order = holdOrders[index];
        setCart(order.cart);
        setCustomerId(order.customerId);
        setDiscount(order.discount);
        setCurrentOrderNumber(order.orderNumber);
        setHoldOrders(holdOrders.filter((_, i) => i !== index));
    };

    // Submit order
    const handleFinalize = async () => {
        if (cart.length === 0) {
            toast.error('Panier vide. Ajoutez au moins un produit.');
            return;
        }
        
        setSubmitting(true);
        try {
            // Create sale
            const storeRes = await axios.post(route('pharmacy.sales.store'), {
                customer_id: customerId || null,
                currency,
                lines: cart.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    unit_price: item.price,
                    discount_percent: item.discount_percent || null
                })),
            });
            
            const saleId = storeRes.data.sale.id;
            
            // Finalize with payment
            await axios.post(route('pharmacy.sales.finalize', saleId), {
                paid_amount: paidAmount || total
            });
            
            toast.success('Vente finalisée avec succès!');
            setShowPaymentModal(false);
            clearCart();
            setPaidAmount('');
            
            // Option: redirect to receipt or stay on POS
            // router.visit(route('pharmacy.sales.show', saleId));
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur lors de la vente');
        } finally {
            setSubmitting(false);
        }
    };

    const handleSaveDraft = async () => {
        if (cart.length === 0) {
            toast.error('Panier vide');
            return;
        }
        
        setSubmitting(true);
        try {
            const res = await axios.post(route('pharmacy.sales.store'), {
                customer_id: customerId || null,
                currency,
                lines: cart.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    unit_price: item.price,
                    discount_percent: item.discount_percent || null
                })),
            });
            
            toast.success('Brouillon enregistré');
            router.visit(route('pharmacy.sales.show', res.data.sale.id));
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout>
            <Head title="Point de Vente" />
            
            <div className="h-[calc(100vh-64px)] flex">
                {/* Left Panel - Products */}
                <div className="flex-1 flex flex-col bg-gray-50 dark:bg-slate-950">
                    {/* Header with Search and View Toggle */}
                    <div className="p-4 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-700">
                        <div className="flex items-center gap-4">
                            <Button 
                                variant="ghost" 
                                size="sm" 
                                asChild
                                className="text-gray-600 dark:text-gray-300"
                            >
                                <Link href={route('pharmacy.sales.index')}>
                                    <ArrowLeft className="h-4 w-4 mr-1" />
                                    Retour
                                </Link>
                            </Button>
                            
                            {canUseWholesale && (
                                <div className="flex items-center bg-amber-50 dark:bg-amber-900/20 rounded-lg p-1 border border-amber-200 dark:border-amber-800">
                                    <button
                                        onClick={() => setSaleMode('retail')}
                                        className={`px-3 py-1.5 rounded text-sm font-medium transition-colors flex items-center gap-1 ${
                                            saleMode === 'retail'
                                                ? 'bg-amber-500 text-white shadow-sm'
                                                : 'text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40'
                                        }`}
                                    >
                                        <Store className="h-4 w-4" />
                                        Détail
                                    </button>
                                    <button
                                        onClick={() => setSaleMode('wholesale')}
                                        className={`px-3 py-1.5 rounded text-sm font-medium transition-colors flex items-center gap-1 ${
                                            saleMode === 'wholesale'
                                                ? 'bg-amber-500 text-white shadow-sm'
                                                : 'text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40'
                                        }`}
                                    >
                                        <Package className="h-4 w-4" />
                                        Gros
                                    </button>
                                </div>
                            )}
                            <div className="flex items-center bg-gray-100 dark:bg-slate-800 rounded-lg p-1">
                                <button
                                    onClick={() => setViewMode('list')}
                                    className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                                        viewMode === 'list'
                                            ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400'
                                    }`}
                                >
                                    List Item
                                </button>
                                <button
                                    onClick={() => setViewMode('thumbnails')}
                                    className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                                        viewMode === 'thumbnails'
                                            ? 'bg-white dark:bg-slate-700 text-gray-900 dark:text-white shadow-sm'
                                            : 'text-gray-600 dark:text-gray-400'
                                    }`}
                                >
                                    Thumbnails
                                </button>
                            </div>
                            
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input 
                                    type="text"
                                    placeholder="Search Here"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10 bg-gray-100 dark:bg-slate-800 border-0"
                                />
                            </div>
                            
                            <Button 
                                variant="outline" 
                                size="icon"
                                className="bg-blue-500 hover:bg-blue-600 text-white border-0"
                            >
                                <Grid3X3 className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    {/* Categories */}
                    <div className="p-3 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-700 overflow-x-auto">
                        <div className="flex gap-2">
                            <button
                                onClick={() => setSelectedCategory(null)}
                                className={`px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors ${
                                    !selectedCategory 
                                        ? 'bg-amber-500 text-white' 
                                        : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700'
                                }`}
                            >
                                Tous
                            </button>
                            {categories.map(cat => (
                                <button
                                    key={cat.id}
                                    onClick={() => setSelectedCategory(cat.id)}
                                    className={`px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-colors ${
                                        selectedCategory === cat.id 
                                            ? 'bg-amber-500 text-white' 
                                            : 'bg-gray-100 dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700'
                                    }`}
                                >
                                    {cat.name}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Products Grid/List */}
                    <div className="flex-1 overflow-y-auto p-4">
                        {viewMode === 'thumbnails' ? (
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
                                {filteredProducts.map(product => (
                                    <button
                                        key={product.id}
                                        onClick={() => addToCart(product)}
                                        disabled={product.stock < 1}
                                        className={`bg-white dark:bg-slate-800 rounded-xl p-3 text-center shadow-sm hover:shadow-md transition-all border border-gray-200 dark:border-slate-700 ${
                                            product.stock < 1 ? 'opacity-50 cursor-not-allowed' : 'hover:border-amber-300 dark:hover:border-amber-500'
                                        }`}
                                    >
                                        <div className="aspect-square mb-2 rounded-lg bg-gray-100 dark:bg-slate-700 overflow-hidden flex items-center justify-center">
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
                                            <div className={`flex items-center justify-center ${product.image_url ? 'hidden' : ''}`}>
                                                <Package className="h-10 w-10 text-gray-400" />
                                            </div>
                                        </div>
                                        <p className="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 mb-1">
                                            {product.name}
                                        </p>
                                        <p className="text-xs text-amber-600 dark:text-amber-400 font-semibold">
                                            {fmt(getProductPrice(product))}
                                        </p>
                                        {product.stock < 5 && product.stock > 0 && (
                                            <Badge variant="outline" className="mt-1 text-xs text-orange-600 border-orange-300">
                                                Stock: {product.stock}
                                            </Badge>
                                        )}
                                        {product.stock < 1 && (
                                            <Badge variant="destructive" className="mt-1 text-xs">
                                                Rupture
                                            </Badge>
                                        )}
                                    </button>
                                ))}
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {filteredProducts.map(product => (
                                    <button
                                        key={product.id}
                                        onClick={() => addToCart(product)}
                                        disabled={product.stock < 1}
                                        className={`w-full flex items-center gap-4 bg-white dark:bg-slate-800 rounded-lg p-3 shadow-sm hover:shadow-md transition-all border border-gray-200 dark:border-slate-700 ${
                                            product.stock < 1 ? 'opacity-50 cursor-not-allowed' : 'hover:border-amber-300 dark:hover:border-amber-500'
                                        }`}
                                    >
                                        <div className="w-16 h-16 rounded-lg bg-gray-100 dark:bg-slate-700 overflow-hidden flex items-center justify-center flex-shrink-0">
                                            {product.image_url ? (
                                                <img 
                                                    src={product.image_url} 
                                                    alt={product.name}
                                                    className="w-full h-full object-cover"
                                                />
                                            ) : (
                                                <Package className="h-8 w-8 text-gray-400" />
                                            )}
                                        </div>
                                        <div className="flex-1 text-left">
                                            <p className="font-medium text-gray-900 dark:text-white">{product.name}</p>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">{product.code}</p>
                                        </div>
                                        <div className="text-right">
                                            <p className="font-semibold text-amber-600 dark:text-amber-400">
                                                {fmt(getProductPrice(product))}
                                            </p>
                                            {canUseWholesale && product.wholesale_price_amount != null && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {saleMode === 'wholesale' ? 'Prix gros' : `Gros: ${fmt(product.wholesale_price_amount)}`}
                                                </p>
                                            )}
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Stock: {product.stock}</p>
                                        </div>
                                        <Plus className="h-5 w-5 text-amber-500" />
                                    </button>
                                ))}
                            </div>
                        )}
                        
                        {filteredProducts.length === 0 && (
                            <div className="flex flex-col items-center justify-center py-12 text-gray-500 dark:text-gray-400">
                                <Package className="h-16 w-16 mb-4 opacity-50" />
                                <p>Aucun produit trouvé</p>
                            </div>
                        )}
                    </div>
                </div>

                {/* Right Panel - Cart */}
                <div className="w-96 bg-white dark:bg-slate-900 border-l border-gray-200 dark:border-slate-700 flex flex-col">
                    {/* Cart Header */}
                    <div className="p-4 border-b border-gray-200 dark:border-slate-700">
                        <div className="flex items-center justify-between mb-3">
                            <Button 
                                variant="outline" 
                                size="sm"
                                onClick={holdCurrentOrder}
                                disabled={cart.length === 0}
                                className="text-gray-600 dark:text-gray-300"
                            >
                                <Pause className="h-4 w-4 mr-1" />
                                Hold
                            </Button>
                            <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 px-3 py-1">
                                {currentOrderNumber}
                            </Badge>
                            <Button 
                                variant="outline"
                                size="sm"
                                onClick={clearCart}
                                className="text-amber-600 border-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                            >
                                <Plus className="h-4 w-4 mr-1" />
                                New
                            </Button>
                        </div>
                        
                        {/* Hold Orders */}
                        {holdOrders.length > 0 && (
                            <div className="flex gap-2 overflow-x-auto py-2">
                                {holdOrders.map((order, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => resumeOrder(idx)}
                                        className="flex-shrink-0 px-3 py-1.5 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded-full text-xs font-medium hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-colors"
                                    >
                                        {order.orderNumber.slice(-6)} ({order.cart.length})
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Cart Items */}
                    <div className="flex-1 overflow-y-auto p-4">
                        {cart.length === 0 ? (
                            <div className="flex flex-col items-center justify-center h-full text-gray-400">
                                <ShoppingCart className="h-16 w-16 mb-4 opacity-50" />
                                <p>Panier vide</p>
                                <p className="text-sm">Ajoutez des produits</p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {cart.map((item, index) => (
                                    <div 
                                        key={item.product_id}
                                        className="flex items-start gap-3 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg"
                                    >
                                        <div className="w-12 h-12 rounded-lg bg-gray-200 dark:bg-slate-700 overflow-hidden flex-shrink-0">
                                            {item.image_url ? (
                                                <img 
                                                    src={item.image_url} 
                                                    alt={item.name}
                                                    className="w-full h-full object-cover"
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center">
                                                    <Package className="h-6 w-6 text-gray-400" />
                                                </div>
                                            )}
                                        </div>
                                        
                                        <div className="flex-1 min-w-0">
                                            <p className="font-medium text-gray-900 dark:text-white text-sm truncate">
                                                {item.name}
                                            </p>
                                            {item.discount_percent > 0 && (
                                                <p className="text-xs text-green-600 dark:text-green-400">
                                                    -{item.discount_percent}% Discount
                                                </p>
                                            )}
                                            
                                            <div className="flex items-center gap-2 mt-2">
                                                <button
                                                    onClick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                                    className="w-6 h-6 rounded-full bg-gray-200 dark:bg-slate-700 flex items-center justify-center hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors"
                                                >
                                                    <Minus className="h-3 w-3" />
                                                </button>
                                                <span className="w-8 text-center font-medium text-sm">{item.quantity}</span>
                                                <button
                                                    onClick={() => updateQuantity(item.product_id, item.quantity + 1)}
                                                    className="w-6 h-6 rounded-full bg-gray-200 dark:bg-slate-700 flex items-center justify-center hover:bg-gray-300 dark:hover:bg-slate-600 transition-colors"
                                                >
                                                    <Plus className="h-3 w-3" />
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div className="text-right">
                                            <p className="font-semibold text-gray-900 dark:text-white">
                                                {fmt(item.price * item.quantity)}
                                            </p>
                                            <button
                                                onClick={() => removeFromCart(item.product_id)}
                                                className="text-red-500 hover:text-red-600 mt-1"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Cart Footer */}
                    <div className="border-t border-gray-200 dark:border-slate-700 p-4 space-y-3">
                        {/* Discount */}
                        <button 
                            className="w-full flex items-center justify-between py-2 text-sm text-blue-600 dark:text-blue-400 hover:underline"
                            onClick={() => {
                                const val = prompt('Remise (%) ?', String(discount.value));
                                if (val !== null) {
                                    setDiscount({ type: 'percent', value: parseFloat(val) || 0 });
                                }
                            }}
                        >
                            <span className="flex items-center gap-2">
                                <Percent className="h-4 w-4" />
                                Add Order Discount
                            </span>
                            {discount.value > 0 && (
                                <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                    {discount.value}%
                                </Badge>
                            )}
                        </button>
                        
                        {/* Summary */}
                        <div className="space-y-2 text-sm">
                            <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                <span>Sous-total <span className="text-xs">({cart.length} articles)</span></span>
                                <span className="font-medium text-gray-900 dark:text-white">{fmt(subtotal)}</span>
                            </div>
                            
                            {taxRate > 0 && (
                                <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span className="text-blue-600 dark:text-blue-400">Taxe ({taxRate}% TVA incluse)</span>
                                    <span>{fmt(taxAmount)}</span>
                                </div>
                            )}
                            
                            {orderDiscount > 0 && (
                                <div className="flex justify-between text-green-600 dark:text-green-400">
                                    <span>Remise</span>
                                    <span>-{fmt(orderDiscount)}</span>
                                </div>
                            )}
                        </div>
                        
                        {/* Payment Mode */}
                        <button 
                            className="w-full flex items-center justify-between py-2 text-sm text-gray-700 dark:text-gray-300 border-t border-gray-200 dark:border-slate-700"
                            onClick={() => setShowPaymentModal(true)}
                        >
                            <span>Select payment mode</span>
                            <ChevronRight className="h-4 w-4" />
                        </button>
                        
                        {/* Total & Action */}
                        <div className="bg-blue-500 dark:bg-blue-600 rounded-xl p-4 text-white">
                            <div className="flex items-center justify-between">
                                <span className="text-lg font-medium">Total</span>
                                <span className="text-2xl font-bold">{fmt(total)}</span>
                            </div>
                        </div>
                        
                        <Button 
                            onClick={() => setShowPaymentModal(true)}
                            disabled={cart.length === 0 || submitting}
                            className="w-full bg-amber-500 hover:bg-amber-600 text-white py-6 text-lg font-semibold"
                        >
                            <Receipt className="h-5 w-5 mr-2" />
                            {submitting ? 'Traitement...' : 'Finaliser la vente'}
                        </Button>
                    </div>
                </div>
            </div>

            {/* Payment Modal */}
            {showPaymentModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="bg-white dark:bg-slate-900 rounded-2xl w-full max-w-md mx-4 overflow-hidden">
                        <div className="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between">
                            <h3 className="text-xl font-semibold text-gray-900 dark:text-white">Mode de paiement</h3>
                            <button 
                                onClick={() => setShowPaymentModal(false)}
                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                            >
                                <X className="h-6 w-6" />
                            </button>
                        </div>
                        
                        <div className="p-6 space-y-4">
                            {/* Payment Methods */}
                            <div className="grid grid-cols-3 gap-3">
                                {[
                                    { id: 'cash', icon: Banknote, label: 'Espèces' },
                                    { id: 'card', icon: CreditCard, label: 'Carte' },
                                    { id: 'mobile', icon: Smartphone, label: 'Mobile' },
                                ].map(method => (
                                    <button
                                        key={method.id}
                                        onClick={() => setPaymentMethod(method.id)}
                                        className={`p-4 rounded-xl border-2 flex flex-col items-center gap-2 transition-all ${
                                            paymentMethod === method.id
                                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                                : 'border-gray-200 dark:border-slate-700 hover:border-gray-300 dark:hover:border-slate-600'
                                        }`}
                                    >
                                        <method.icon className={`h-6 w-6 ${
                                            paymentMethod === method.id ? 'text-amber-500' : 'text-gray-400'
                                        }`} />
                                        <span className={`text-sm font-medium ${
                                            paymentMethod === method.id ? 'text-amber-600 dark:text-amber-400' : 'text-gray-600 dark:text-gray-400'
                                        }`}>
                                            {method.label}
                                        </span>
                                    </button>
                                ))}
                            </div>
                            
                            {/* Amount */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Montant reçu
                                </label>
                                <Input
                                    type="number"
                                    step="0.01"
                                    min={0}
                                    value={paidAmount}
                                    onChange={(e) => setPaidAmount(e.target.value)}
                                    placeholder={total.toFixed(2)}
                                    className="text-2xl h-14 text-center font-semibold"
                                />
                            </div>
                            
                            {/* Quick amounts */}
                            <div className="grid grid-cols-4 gap-2">
                                {[total, Math.ceil(total / 10) * 10, Math.ceil(total / 50) * 50, Math.ceil(total / 100) * 100].map((amount, idx) => (
                                    <button
                                        key={idx}
                                        onClick={() => setPaidAmount(String(amount))}
                                        className="py-2 px-3 bg-gray-100 dark:bg-slate-800 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                                    >
                                        {fmt(amount)}
                                    </button>
                                ))}
                            </div>
                            
                            {/* Summary */}
                            <div className="bg-gray-50 dark:bg-slate-800 rounded-xl p-4 space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Total</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">{fmt(total)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Reçu</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">{fmt(Number(paidAmount) || 0)}</span>
                                </div>
                                {change > 0 && (
                                    <div className="flex justify-between text-sm border-t border-gray-200 dark:border-slate-700 pt-2 mt-2">
                                        <span className="text-green-600 dark:text-green-400">Monnaie à rendre</span>
                                        <span className="font-bold text-green-600 dark:text-green-400">{fmt(change)}</span>
                                    </div>
                                )}
                                {balance > 0 && paid > 0 && (
                                    <div className="flex justify-between text-sm border-t border-gray-200 dark:border-slate-700 pt-2 mt-2">
                                        <span className="text-orange-600 dark:text-orange-400">Reste à payer</span>
                                        <span className="font-bold text-orange-600 dark:text-orange-400">{fmt(balance)}</span>
                                    </div>
                                )}
                            </div>
                            
                            {/* Client */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Client (optionnel)
                                </label>
                                <select
                                    className="w-full rounded-lg border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white"
                                    value={customerId}
                                    onChange={(e) => setCustomerId(e.target.value)}
                                >
                                    <option value="">Sans client</option>
                                    {customers.map(c => (
                                        <option key={c.id} value={c.id}>{c.full_name}</option>
                                    ))}
                                </select>
                            </div>
                        </div>
                        
                        <div className="p-6 bg-gray-50 dark:bg-slate-800 flex gap-3">
                            <Button
                                variant="outline"
                                onClick={handleSaveDraft}
                                disabled={submitting}
                                className="flex-1"
                            >
                                Enregistrer brouillon
                            </Button>
                            <Button
                                onClick={handleFinalize}
                                disabled={submitting || cart.length === 0}
                                className="flex-1 bg-amber-500 hover:bg-amber-600 text-white"
                            >
                                {submitting ? 'Traitement...' : 'Valider la vente'}
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
