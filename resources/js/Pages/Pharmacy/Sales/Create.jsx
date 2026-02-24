import React, { useState, useMemo, useEffect, useRef, useCallback } from 'react';
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

function QuickAddCustomerModal({ onClose, onCreated }) {
    const [name, setName] = useState('');
    const [phone, setPhone] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        if (!name.trim()) {
            setError('Nom requis');
            return;
        }
        setSubmitting(true);
        try {
            const res = await axios.post(route('pharmacy.sales.quick-customer'), {
                name: name.trim(),
                phone: phone.trim() || null,
            });
            if (res.data?.success && res.data?.customer) {
                toast.success('Client créé');
                onCreated({
                    id: String(res.data.customer.id),
                    full_name: res.data.customer.full_name || name.trim(),
                    phone: res.data.customer.phone || '',
                    email: res.data.customer.email || '',
                });
            }
        } catch (err) {
            setError(err.response?.data?.message || 'Erreur');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/50" onClick={onClose}>
            <div className="bg-white dark:bg-slate-900 rounded-xl w-full max-w-sm mx-4 p-6 shadow-xl" onClick={e => e.stopPropagation()}>
                <div className="flex items-center justify-between mb-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Nouveau client</h3>
                    <button type="button" onClick={onClose} className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <X className="h-5 w-5" />
                    </button>
                </div>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Nom</label>
                        <Input
                            type="text"
                            value={name}
                            onChange={(e) => setName(e.target.value)}
                            placeholder="Nom du client"
                            className="w-full text-gray-900 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400 bg-white dark:bg-slate-800"
                            autoFocus
                        />
                    </div>
                    <div>
                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Téléphone</label>
                        <Input
                            type="text"
                            value={phone}
                            onChange={(e) => setPhone(e.target.value)}
                            placeholder="Optionnel"
                            className="w-full text-gray-900 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400 bg-white dark:bg-slate-800"
                        />
                    </div>
                    {error && <p className="text-sm text-red-600 dark:text-red-400">{error}</p>}
                    <div className="flex gap-2 pt-2">
                        <Button type="button" variant="outline" onClick={onClose} className="flex-1">Annuler</Button>
                        <Button type="submit" disabled={submitting} className="flex-1">Créer</Button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default function POSCreate({ products = [], categories = [], customers = [], canUseWholesale = false, cashRegisters = [], currency: pageCurrency, currencies: pageCurrencies = [], exchangeRates: pageExchangeRates = {} }) {
    const { shop } = usePage().props;
    // Devise par défaut (celle des prix produits) — normalisée en majuscules pour les taux
    const defaultCurrency = (pageCurrency ?? shop?.currency ?? 'CDF').toString().toUpperCase();
    const currencies = pageCurrencies?.length ? pageCurrencies : (shop?.currencies ?? []);
    const exchangeRates = Object.keys(pageExchangeRates || {}).length ? pageExchangeRates : { [defaultCurrency]: 1 };
    
    const [selectedCurrency, setSelectedCurrency] = useState(defaultCurrency);
    const currency = selectedCurrency;

    /** Convertit un montant d'une devise vers la devise affichée (selectedCurrency) selon les taux configurés. */
    const convertToSelected = (amount, fromCurrencyCode) => {
        if (amount == null || amount === 0) return 0;
        const from = (fromCurrencyCode || defaultCurrency).toString().toUpperCase();
        const to = (currency || defaultCurrency).toString().toUpperCase();
        const fromRate = exchangeRates[from] ?? exchangeRates[defaultCurrency] ?? 1;
        const toRate = exchangeRates[to] ?? 1;
        if (toRate === 0) return 0;
        return (Number(amount) * toRate) / fromRate;
    };
    
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
    const [selectedCartIndex, setSelectedCartIndex] = useState(0);
    const [showSuccessBanner, setShowSuccessBanner] = useState(null); // { total }
    const [showAddCustomerModal, setShowAddCustomerModal] = useState(false);
    const [customersList, setCustomersList] = useState(customers);
    const searchInputRef = useRef(null);
    const paidAmountInputRef = useRef(null);
    const RECENT_PRODUCTS_KEY = 'pos_recent_product_ids';
    const LOW_STOCK_THRESHOLD = 5;

    useEffect(() => {
        setCustomersList(customers);
    }, [customers]);

    useEffect(() => {
        searchInputRef.current?.focus();
    }, []);

    useEffect(() => {
        const idx = Math.min(selectedCartIndex, Math.max(0, cart.length - 1));
        if (idx !== selectedCartIndex) setSelectedCartIndex(idx);
    }, [cart.length, selectedCartIndex]);

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
            const productCurrency = product.price_currency ?? defaultCurrency;
            return { ...item, price: getProductPrice(product), price_currency: productCurrency };
        }));
    }, [saleMode, defaultCurrency]);

    const [recentProductIds, setRecentProductIds] = useState(() => {
        try {
            const raw = localStorage.getItem(RECENT_PRODUCTS_KEY);
            if (!raw) return [];
            const ids = JSON.parse(raw);
            return Array.isArray(ids) ? ids.slice(0, 12) : [];
        } catch {
            return [];
        }
    });

    const persistRecentProduct = useCallback((productId) => {
        try {
            setRecentProductIds(prev => {
                const next = [productId, ...prev.filter(id => id !== productId)].slice(0, 12);
                localStorage.setItem(RECENT_PRODUCTS_KEY, JSON.stringify(next));
                return next;
            });
        } catch (_) {}
    }, []);

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
            const productCurrency = product.price_currency ?? defaultCurrency;
            setCart([...cart, {
                product_id: product.id,
                name: product.name,
                code: product.code,
                price: getProductPrice(product),
                price_currency: productCurrency,
                quantity: 1,
                max_stock: product.stock,
                discount_percent: 0,
                image_url: product.image_url
            }]);
        }
        persistRecentProduct(product.id);
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

    // Cart calculations (tous les montants en devise affichée via les taux configurés)
    const subtotal = useMemo(() =>
        cart.reduce((sum, item) => {
            const itemTotal = item.price * item.quantity;
            const itemDiscount = item.discount_percent ? (itemTotal * item.discount_percent / 100) : 0;
            const lineTotal = itemTotal - itemDiscount;
            return sum + convertToSelected(lineTotal, item.price_currency ?? defaultCurrency);
        }, 0),
    [cart, currency, exchangeRates, defaultCurrency]);

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

    const hasCartStockExceeded = useMemo(() =>
        cart.some(item => item.quantity > (item.max_stock ?? 0)),
        [cart]
    );
    const canFinalize = cart.length > 0 && !hasCartStockExceeded;

    useEffect(() => {
        if (showPaymentModal || showAddCustomerModal) return;
        const onKey = (e) => {
            const target = e.target;
            const inInput = target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.tagName === 'SELECT' || target.isContentEditable);
            if (inInput && e.key !== 'p' && e.key !== 'P') return;
            if (e.key === 'p' || e.key === 'P') {
                if (cart.length > 0 && !hasCartStockExceeded) {
                    setShowPaymentModal(true);
                    e.preventDefault();
                }
                return;
            }
            if (cart.length === 0) return;
            const item = cart[selectedCartIndex];
            if (!item) return;
            if (e.key === '+' || e.key === '=') {
                updateQuantity(item.product_id, item.quantity + 1);
                e.preventDefault();
            } else if (e.key === '-') {
                updateQuantity(item.product_id, item.quantity - 1);
                e.preventDefault();
            } else if (e.key === 'd' || e.key === 'D') {
                const val = prompt('Remise ligne (%) ?', String(item.discount_percent || 0));
                if (val !== null) {
                    const pct = Math.min(100, Math.max(0, parseFloat(val) || 0));
                    setCart(prev => prev.map(i => i.product_id === item.product_id ? { ...i, discount_percent: pct } : i));
                }
                e.preventDefault();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, [showPaymentModal, showAddCustomerModal, cart, selectedCartIndex, hasCartStockExceeded]);

    const availableCurrencies = useMemo(() => {
        const list = currencies.filter((c) => c.code);
        if (list.length > 0) return list;
        return [{ code: defaultCurrency, name: defaultCurrency, symbol: getCurrencySymbol(defaultCurrency) }];
    }, [currencies, defaultCurrency]);

    // Session de caisse : première caisse avec session ouverte (optionnel)
    const activeCashSession = useMemo(() => {
        const withOpen = (cashRegisters || []).filter((r) => r.open_session);
        if (withOpen.length === 0) return null;
        const reg = withOpen[0];
        return { cash_register_id: reg.id, cash_register_session_id: reg.open_session.id };
    }, [cashRegisters]);

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
        if (hasCartStockExceeded) {
            toast.error('Stock négatif interdit. Ajustez les quantités.');
            return;
        }
        
        setSubmitting(true);
        try {
            const storeRes = await axios.post(route('pharmacy.sales.store'), {
                customer_id: customerId || null,
                currency,
                sale_mode: saleMode,
                lines: cart.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    unit_price: convertToSelected(item.price, item.price_currency ?? defaultCurrency),
                    discount_percent: item.discount_percent || null
                })),
                ...(activeCashSession ? { cash_register_id: activeCashSession.cash_register_id, cash_register_session_id: activeCashSession.cash_register_session_id } : {}),
            });
            
            const saleId = storeRes.data.sale.id;
            
            await axios.post(route('pharmacy.sales.finalize', saleId), {
                paid_amount: paidAmount || total
            });
            
            const saleTotal = total;
            toast.success('Vente finalisée avec succès!');
            setShowPaymentModal(false);
            clearCart();
            setPaidAmount('');
            setShowSuccessBanner({ total: saleTotal });
            setTimeout(() => setShowSuccessBanner(null), 2500);
            
            if (shop?.receipt_auto_print) {
                window.open(route('pharmacy.sales.receipt', saleId), '_blank', 'noopener,noreferrer');
            }
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
                sale_mode: saleMode,
                lines: cart.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity,
                    unit_price: convertToSelected(item.price, item.price_currency ?? defaultCurrency),
                    discount_percent: item.discount_percent || null
                })),
                ...(activeCashSession ? { cash_register_id: activeCashSession.cash_register_id, cash_register_session_id: activeCashSession.cash_register_session_id } : {}),
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
            
            <div className="h-[calc(100vh-64px)] flex relative">
                {/* Bandeau succès après validation */}
                {showSuccessBanner && (
                    <div className="absolute top-0 left-0 right-0 z-40 bg-green-600 dark:bg-green-700 text-white py-3 px-4 text-center shadow-lg animate-in fade-in duration-300">
                        <p className="text-lg font-semibold">Vente enregistrée</p>
                        <p className="text-2xl font-bold mt-0.5">{fmt(showSuccessBanner.total)}</p>
                    </div>
                )}
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
                                <Link href={route('pharmacy.sales.index')} className="inline-flex items-center gap-1.5">
                                    <ArrowLeft className="h-4 w-4 shrink-0" />
                                    Retour
                                </Link>
                            </Button>

                            {availableCurrencies.length >= 1 && (
                                <select
                                    value={currency}
                                    onChange={(e) => setSelectedCurrency(e.target.value)}
                                    className="h-9 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 text-sm font-medium px-3 min-w-[100px]"
                                    title="Devise d'affichage (conversion selon le taux configuré)"
                                >
                                    {availableCurrencies.map((c) => (
                                        <option key={c.code} value={c.code}>
                                            {c.code} {c.symbol && c.symbol !== c.code ? `(${c.symbol})` : ''}
                                        </option>
                                    ))}
                                </select>
                            )}
                            
                            <div className="flex items-center bg-amber-50 dark:bg-amber-900/20 rounded-lg p-1 border border-amber-200 dark:border-amber-800" title={saleMode === 'wholesale' && !canUseWholesale ? 'Droits vente en gros requis' : ''}>
                                <button
                                    type="button"
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
                                    type="button"
                                    onClick={() => canUseWholesale && setSaleMode('wholesale')}
                                    disabled={!canUseWholesale}
                                    className={`px-3 py-1.5 rounded text-sm font-medium transition-colors flex items-center gap-1 ${
                                        saleMode === 'wholesale'
                                            ? 'bg-amber-500 text-white shadow-sm'
                                            : 'text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-900/40'
                                    } ${!canUseWholesale ? 'opacity-60 cursor-not-allowed' : ''}`}
                                    title={!canUseWholesale ? 'Droits vente en gros requis' : 'Vente en gros'}
                                >
                                    <Package className="h-4 w-4" />
                                    Gros
                                </button>
                            </div>
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
                            
                            <span className="text-xs text-gray-400 dark:text-gray-500 whitespace-nowrap hidden sm:inline">Raccourcis: <kbd className="px-1 py-0.5 rounded bg-gray-200 dark:bg-gray-700 font-mono text-[10px]">1</kbd> ventes · <kbd className="px-1 py-0.5 rounded bg-gray-200 dark:bg-gray-700 font-mono text-[10px]">2</kbd> ici · <kbd className="px-1 py-0.5 rounded bg-gray-200 dark:bg-gray-700 font-mono text-[10px]">P</kbd> paiement</span>
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input 
                                    ref={searchInputRef}
                                    type="text"
                                    placeholder="Recherche ou scan code-barres"
                                    value={search}
                                    onChange={(e) => {
                                        const v = e.target.value;
                                        setSearch(v);
                                        if (v.length >= 6) {
                                            const code = v.trim();
                                            const byCode = products.find(p => (p.code || '').toLowerCase() === code.toLowerCase());
                                            if (byCode) {
                                                addToCart(byCode);
                                                setSearch('');
                                            }
                                        }
                                    }}
                                    onKeyDown={(e) => {
                                        if (e.key === 'Enter' && search.trim().length >= 6) {
                                            const byCode = products.find(p => (p.code || '').toLowerCase() === search.trim().toLowerCase());
                                            if (byCode) {
                                                addToCart(byCode);
                                                setSearch('');
                                                e.preventDefault();
                                            }
                                        }
                                    }}
                                    className="pl-10 bg-gray-100 dark:bg-slate-800 border-0 text-gray-900 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400"
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

                    {/* Derniers produits */}
                    {recentProductIds.length > 0 && (
                        <div className="px-4 py-2 bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-700">
                            <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-2">Derniers produits</p>
                            <div className="flex gap-2 overflow-x-auto pb-1">
                                {recentProductIds.map(id => {
                                    const product = products.find(p => p.id === id);
                                    if (!product || (product.stock || 0) < 1) return null;
                                    return (
                                        <button
                                            key={id}
                                            type="button"
                                            onClick={() => addToCart(product)}
                                            className="flex-shrink-0 flex items-center gap-2 px-3 py-2 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 text-left hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors"
                                        >
                                            {product.image_url ? (
                                                <img src={product.image_url} alt="" className="w-8 h-8 rounded object-cover" />
                                            ) : (
                                                <div className="w-8 h-8 rounded bg-gray-200 dark:bg-slate-700 flex items-center justify-center">
                                                    <Package className="h-4 w-4 text-gray-400" />
                                                </div>
                                            )}
                                            <span className="text-sm font-medium text-gray-900 dark:text-white max-w-[100px] truncate">{product.name}</span>
                                            <span className="text-xs text-amber-600 dark:text-amber-400 font-semibold">{fmt(convertToSelected(getProductPrice(product), product.price_currency ?? defaultCurrency))}</span>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>
                    )}

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
                                            {fmt(convertToSelected(getProductPrice(product), product.price_currency ?? defaultCurrency))}
                                        </p>
                                        {product.stock > 0 && product.stock < LOW_STOCK_THRESHOLD && (
                                            <Badge variant="outline" className="mt-1 text-xs text-orange-600 dark:text-orange-400 border-orange-300 dark:border-orange-600">
                                                Rupture bientôt
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
                                                {fmt(convertToSelected(getProductPrice(product), product.price_currency ?? defaultCurrency))}
                                            </p>
                                            {canUseWholesale && product.wholesale_price_amount != null && (
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {saleMode === 'wholesale' ? 'Prix gros' : `Gros: ${fmt(convertToSelected(product.wholesale_price_amount, product.price_currency ?? defaultCurrency))}`}
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
                        <div className="flex items-center gap-2 mb-2">
                            <Badge className={saleMode === 'wholesale' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300'}>
                                {saleMode === 'wholesale' ? <><Package className="h-3 w-3 mr-1 inline" /> Vente en gros</> : <><Store className="h-3 w-3 mr-1 inline" /> Vente au détail</>}
                            </Badge>
                        </div>
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

                    {cart.length > 0 && (
                        <p className="px-4 py-1 text-xs text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-slate-800">
                            Cliquez une ligne · <kbd className="px-1 rounded bg-gray-200 dark:bg-gray-700 font-mono">+</kbd><kbd className="px-1 rounded bg-gray-200 dark:bg-gray-700 font-mono ml-0.5">-</kbd> quantité · <kbd className="px-1 rounded bg-gray-200 dark:bg-gray-700 font-mono">D</kbd> remise · <kbd className="px-1 rounded bg-gray-200 dark:bg-gray-700 font-mono">P</kbd> paiement
                        </p>
                    )}
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
                                        className={`flex items-start gap-3 p-3 rounded-lg border-2 transition-colors ${
                                            index === selectedCartIndex
                                                ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-400 dark:border-amber-500'
                                                : 'bg-gray-50 dark:bg-slate-800 border-transparent'
                                        }`}
                                        onClick={() => setSelectedCartIndex(index)}
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
                                                <Badge className="text-xs bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border-0">
                                                    Promo -{item.discount_percent}%
                                                </Badge>
                                            )}
                                            
                                            <div className="flex items-center gap-2 mt-2">
                                                <button
                                                    onClick={() => updateQuantity(item.product_id, item.quantity - 1)}
                                                    className="w-6 h-6 rounded-full bg-gray-200 dark:bg-slate-600 flex items-center justify-center hover:bg-gray-300 dark:hover:bg-slate-500 transition-colors text-gray-700 dark:text-gray-200"
                                                >
                                                    <Minus className="h-3 w-3" />
                                                </button>
                                                <span className="w-8 text-center font-medium text-sm text-gray-900 dark:text-gray-100 tabular-nums">{item.quantity}</span>
                                                <button
                                                    onClick={() => updateQuantity(item.product_id, item.quantity + 1)}
                                                    className="w-6 h-6 rounded-full bg-gray-200 dark:bg-slate-600 flex items-center justify-center hover:bg-gray-300 dark:hover:bg-slate-500 transition-colors text-gray-700 dark:text-gray-200"
                                                >
                                                    <Plus className="h-3 w-3" />
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div className="text-right">
                                            <p className="font-semibold text-gray-900 dark:text-white">
                                                {fmt(convertToSelected(item.price * item.quantity, item.price_currency ?? defaultCurrency))}
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
                        
                        {hasCartStockExceeded && (
                            <p className="text-sm text-red-600 dark:text-red-400 font-medium">
                                Stock négatif interdit. Ajustez les quantités.
                            </p>
                        )}
                        <Button 
                            onClick={() => setShowPaymentModal(true)}
                            disabled={cart.length === 0 || submitting || hasCartStockExceeded}
                            className="w-full bg-amber-500 hover:bg-amber-600 text-white py-6 text-lg font-semibold disabled:opacity-60"
                        >
                            <Receipt className="h-5 w-5 mr-2" />
                            {submitting ? 'Traitement...' : 'Finaliser la vente'}
                        </Button>
                    </div>
                </div>
            </div>

            {/* Payment Modal */}
            {showPaymentModal && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/50"
                    onKeyDown={(e) => {
                        if (e.key === 'Escape') {
                            setShowPaymentModal(false);
                            e.preventDefault();
                        }
                        if (e.key === 'F2') {
                            setPaidAmount(String(Math.round(total * 100) / 100));
                            setTimeout(() => paidAmountInputRef.current?.focus(), 0);
                            e.preventDefault();
                        }
                        if (e.key === 'Enter' && !e.target.closest('button') && !e.target.closest('select')) {
                            if (paid >= total && canFinalize) {
                                handleFinalize();
                                e.preventDefault();
                            }
                        }
                    }}
                >
                    <div className="bg-white dark:bg-slate-900 rounded-2xl w-full max-w-md mx-4 overflow-hidden">
                        <div className="p-6 border-b border-gray-200 dark:border-slate-700 flex items-center justify-between gap-2">
                            <h3 className="text-xl font-semibold text-gray-900 dark:text-white shrink-0">Mode de paiement</h3>
                            <span className="text-xs text-gray-500 dark:text-gray-400 hidden sm:inline">Échap · F2 tout · Entrée valider</span>
                            <button 
                                onClick={() => setShowPaymentModal(false)}
                                className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 shrink-0"
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
                                    ref={paidAmountInputRef}
                                    type="number"
                                    step="0.01"
                                    min={0}
                                    value={paidAmount}
                                    onChange={(e) => setPaidAmount(e.target.value)}
                                    placeholder={total.toFixed(2)}
                                    className="text-2xl h-14 text-center font-semibold text-gray-900 dark:text-white placeholder:text-gray-500 dark:placeholder:text-gray-400 bg-white dark:bg-slate-800"
                                />
                            </div>
                            
                            {/* Boutons rapides */}
                            <div className="grid grid-cols-2 sm:grid-cols-4 gap-2">
                                <button
                                    type="button"
                                    onClick={() => setPaidAmount(String(Math.round(total * 100) / 100))}
                                    className="py-2.5 px-3 bg-amber-100 dark:bg-amber-900/30 rounded-lg text-sm font-medium text-amber-800 dark:text-amber-200 hover:bg-amber-200 dark:hover:bg-amber-900/50"
                                >
                                    Montant exact
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPaidAmount(String(Math.ceil(total / 100) * 100))}
                                    className="py-2.5 px-3 bg-gray-100 dark:bg-slate-800 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                                >
                                    Arrondi 100
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPaidAmount('50000')}
                                    className="py-2.5 px-3 bg-gray-100 dark:bg-slate-800 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                                >
                                    50 000
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setPaidAmount('100000')}
                                    className="py-2.5 px-3 bg-gray-100 dark:bg-slate-800 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-slate-700"
                                >
                                    100 000
                                </button>
                            </div>
                            
                            {/* Rendu monnaie / Reste à payer — très lisible */}
                            <div className="rounded-xl p-4 min-h-[72px] flex flex-col justify-center">
                                {change > 0 && (
                                    <div className="text-center">
                                        <p className="text-sm text-gray-600 dark:text-gray-400">Monnaie à rendre</p>
                                        <p className="text-3xl font-bold text-green-600 dark:text-green-400 mt-0.5">{fmt(change)}</p>
                                    </div>
                                )}
                                {balance > 0 && paid > 0 && (
                                    <div className="text-center">
                                        <p className="text-sm text-gray-600 dark:text-gray-400">Reste à payer</p>
                                        <p className="text-3xl font-bold text-red-600 dark:text-red-400 mt-0.5">{fmt(balance)}</p>
                                    </div>
                                )}
                                {change <= 0 && balance <= 0 && paid >= total && total > 0 && (
                                    <p className="text-center text-lg font-semibold text-green-600 dark:text-green-400">Montant OK</p>
                                )}
                            </div>
                            
                            {/* Summary compact */}
                            <div className="bg-gray-50 dark:bg-slate-800 rounded-xl p-4 space-y-2">
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Total</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">{fmt(total)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Reçu</span>
                                    <span className="font-semibold text-gray-900 dark:text-white">{fmt(Number(paidAmount) || 0)}</span>
                                </div>
                            </div>
                            
                            {/* Client */}
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Client (optionnel)
                                </label>
                                <div className="flex gap-2">
                                    <select
                                        className="flex-1 rounded-lg border border-gray-300 dark:border-slate-600 dark:bg-slate-800 dark:text-white h-10 px-3"
                                        value={customerId}
                                        onChange={(e) => setCustomerId(e.target.value)}
                                    >
                                        <option value="">Sans client</option>
                                        {customersList.map(c => (
                                            <option key={c.id} value={c.id}>{c.full_name}</option>
                                        ))}
                                    </select>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setShowAddCustomerModal(true)}
                                        className="shrink-0"
                                        title="Créer un client"
                                    >
                                        + Client
                                    </Button>
                                </div>
                            </div>
                        </div>
                        
                        <div className="p-6 bg-gray-50 dark:bg-slate-800 flex gap-3">
                            <Button
                                variant="outline"
                                onClick={handleSaveDraft}
                                disabled={submitting || hasCartStockExceeded}
                                className="flex-1"
                            >
                                Enregistrer brouillon
                            </Button>
                            <Button
                                onClick={handleFinalize}
                                disabled={submitting || !canFinalize}
                                className="flex-1 bg-amber-500 hover:bg-amber-600 text-white"
                            >
                                {submitting ? 'Traitement...' : 'Valider la vente'}
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal création rapide client */}
            {showAddCustomerModal && (
                <QuickAddCustomerModal
                    onClose={() => setShowAddCustomerModal(false)}
                    onCreated={(newCustomer) => {
                        setCustomersList(prev => [...prev, newCustomer]);
                        setCustomerId(newCustomer.id);
                        setShowAddCustomerModal(false);
                    }}
                />
            )}
        </AppLayout>
    );
}
