import { useState, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { 
    Save,
    User,
    Mail,
    Phone,
    MapPin,
    ShoppingCart,
    DollarSign,
    X,
    Plus,
    Trash2,
} from 'lucide-react';
import axios from 'axios';

export default function OrderDrawer({
    isOpen,
    onClose,
    order = null,
    products = [],
    initialCart = [],
    shippingMethods = [],
    paymentMethods = [],
    shippingAmount = 0,
    taxRate = 0,
    taxAmount = 0,
    couponDiscount = 0,
    selectedShippingId = '',
    selectedPaymentCode = '',
    selectedPaymentType = '',
    paymentStatusOnSubmit = null,
    readonlyShipping = false,
    onSuccess = null,
    /** Devise commande (ex. vitrine : même code que le panier / sélecteur) */
    orderCurrency = null,
}) {
    const isEditing = !!order;
    const pageProps = usePage().props;
    const { shop } = pageProps;
    const isPublicStorefront = !!pageProps?.storefrontIsPublic;
    const createOrderRoute = isPublicStorefront ? 'public.storefront.orders.store' : 'ecommerce.orders.store';
    const fusionInitRoute = isPublicStorefront
        ? 'public.storefront.payments.fusionpay.initiate'
        : 'api.ecommerce.payments.fusionpay.initiate';
    const defaultCurrency = orderCurrency || shop?.currency || 'USD';
    // En mode vitrine/panier client, les montants doivent rester pilotés par le système
    // (taxes, remise globale, etc.) et non éditables par l'utilisateur final.
    const fromCart = initialCart?.length > 0;

    const getInitialItems = () => {
        if (order && order.items) {
            return order.items;
        }
        if (initialCart && initialCart.length > 0) {
            return initialCart.map(item => ({
                product_id: item.product_id,
                product_name: item.name,
                product_sku: item.sku || null,
                quantity: item.quantity || 1,
                unit_price: item.price || 0,
                discount_amount: 0,
                product_image_url: item.image_url || null,
            }));
        }
        return [];
    };

    const [formData, setFormData] = useState({
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        shipping_address: '',
        billing_address: '',
        subtotal_amount: 0,
        shipping_amount: 0,
        tax_amount: 0,
        discount_amount: 0,
        currency: defaultCurrency,
        payment_method: '',
        notes: '',
        items: getInitialItems(),
    });

    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);
    const [fusionPaymentMethod, setFusionPaymentMethod] = useState('mobile_money');
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [itemQuantity, setItemQuantity] = useState(1);
    const [itemDiscount, setItemDiscount] = useState(0);

    useEffect(() => {
        if (order) {
            setFormData({
                customer_name: order.customer_name || '',
                customer_email: order.customer_email || '',
                customer_phone: order.customer_phone || '',
                shipping_address: order.shipping_address || '',
                billing_address: order.billing_address || '',
                subtotal_amount: order.subtotal_amount || 0,
                shipping_amount: order.shipping_amount || 0,
                tax_amount: order.tax_amount || 0,
                discount_amount: order.discount_amount || 0,
                currency: order.currency || defaultCurrency,
                payment_method: order.payment_method || '',
                notes: order.notes || '',
                items: order.items || [],
            });
        } else {
            const initialItems = getInitialItems();
            const subtotal = initialItems.reduce((s, i) => s + (i.unit_price * i.quantity) - (i.discount_amount || 0), 0);
            const tax = fromCart && taxRate ? subtotal * (taxRate / 100) : 0;
            setFormData({
                customer_name: '',
                customer_email: '',
                customer_phone: '',
                shipping_address: '',
                billing_address: '',
                subtotal_amount: subtotal,
                shipping_amount: fromCart ? (shippingAmount ?? 0) : 0,
                tax_amount: fromCart ? (taxAmount ?? tax) : 0,
                discount_amount: fromCart ? (couponDiscount ?? 0) : 0,
                currency: defaultCurrency,
                payment_method: fromCart ? (selectedPaymentCode ?? '') : '',
                notes: '',
                items: initialItems,
            });
        }
        setErrors({});
    }, [order, isOpen, defaultCurrency, orderCurrency, initialCart, fromCart, shippingAmount, taxAmount, couponDiscount, selectedPaymentCode]);

    const calculateSubtotal = () => {
        return formData.items.reduce((sum, item) => {
            return sum + ((item.unit_price * item.quantity) - (item.discount_amount || 0));
        }, 0);
    };

    const calculateTotal = () => {
        return calculateSubtotal() + formData.shipping_amount + formData.tax_amount - formData.discount_amount;
    };

    const handleAddItem = () => {
        if (!selectedProduct) {
            toast.error('Veuillez sélectionner un produit');
            return;
        }

        if (itemQuantity <= 0) {
            toast.error('La quantité doit être supérieure à 0');
            return;
        }

        const existingItemIndex = formData.items.findIndex(item => item.product_id === selectedProduct.id);

        if (existingItemIndex >= 0) {
            // Mettre à jour la quantité
            const updatedItems = [...formData.items];
            updatedItems[existingItemIndex].quantity += itemQuantity;
            updatedItems[existingItemIndex].discount_amount = (updatedItems[existingItemIndex].discount_amount || 0) + itemDiscount;
            setFormData({ ...formData, items: updatedItems });
        } else {
            // Ajouter un nouvel item
            const newItem = {
                product_id: selectedProduct.id,
                product_name: selectedProduct.name,
                product_sku: selectedProduct.sku || null,
                quantity: itemQuantity,
                unit_price: selectedProduct.price_amount,
                discount_amount: itemDiscount,
                product_image_url: selectedProduct.image_url || null,
            };
            setFormData({ ...formData, items: [...formData.items, newItem] });
        }

        setSelectedProduct(null);
        setItemQuantity(1);
        setItemDiscount(0);
    };

    const handleRemoveItem = (index) => {
        const updatedItems = formData.items.filter((_, i) => i !== index);
        setFormData({ ...formData, items: updatedItems });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        // Validation des items
        if (!formData.items || formData.items.length === 0) {
            toast.error('Veuillez ajouter au moins un produit à la commande');
            setProcessing(false);
            return;
        }

        // S'assurer que tous les items ont les champs requis avec les bons types
        const validItems = formData.items.map((item, index) => {
            const quantity = Number(item.quantity);
            const unitPrice = Number(item.unit_price);
            const discountAmount = Number(item.discount_amount || 0);

            if (!quantity || quantity <= 0) {
                throw new Error(`La quantité de l'item ${index + 1} doit être supérieure à 0`);
            }
            if (!unitPrice || unitPrice < 0) {
                throw new Error(`Le prix unitaire de l'item ${index + 1} est invalide`);
            }
            if (!item.product_id) {
                throw new Error(`L'ID du produit de l'item ${index + 1} est manquant`);
            }
            if (!item.product_name) {
                throw new Error(`Le nom du produit de l'item ${index + 1} est manquant`);
            }

            return {
                product_id: String(item.product_id),
                product_name: String(item.product_name),
                product_sku: item.product_sku || null,
                quantity: quantity,
                unit_price: unitPrice,
                discount_amount: discountAmount,
                product_image_url: item.product_image_url || null,
            };
        });

        const subtotal = calculateSubtotal();
        const total = calculateTotal();

        const resolvedPm = paymentMethods?.find((m) => m.code === formData.payment_method);
        const needsFusionCheckout =
            fromCart && (selectedPaymentType === 'fusionpay' || resolvedPm?.type === 'fusionpay');
        if (needsFusionCheckout && !String(formData.customer_phone || '').trim()) {
            toast.error('Numéro de téléphone requis pour le paiement en ligne sécurisé.');
            setProcessing(false);
            return;
        }

        const payload = {
            customer_name: formData.customer_name,
            customer_email: formData.customer_email,
            customer_phone: formData.customer_phone || null,
            shipping_address: formData.shipping_address,
            billing_address: formData.billing_address || null,
            subtotal_amount: subtotal,
            shipping_amount: Number(formData.shipping_amount) || 0,
            tax_amount: Number(formData.tax_amount) || 0,
            discount_amount: Number(formData.discount_amount) || 0,
            currency: formData.currency,
            payment_method: formData.payment_method || null,
            ...(paymentStatusOnSubmit ? { payment_status: paymentStatusOnSubmit } : {}),
            notes: formData.notes || null,
            items: validItems, // Utiliser 'items', pas 'lines'
        };

        try {
            const response = await axios.post(route(createOrderRoute), payload);
            toast.success(response.data.message || 'Commande créée avec succès');

            if (response.data?.needs_fusion_payment && response.data?.order?.id) {
                try {
                    const fusionRes = await axios.post(route(fusionInitRoute), {
                        order_id: response.data.order.id,
                        payment_method: fusionPaymentMethod,
                        phone: String(formData.customer_phone || '').trim(),
                        customer_name: formData.customer_name,
                    });
                    if (fusionRes.data?.checkout_url) {
                        window.location.href = fusionRes.data.checkout_url;
                        return;
                    }
                    toast.error(fusionRes.data?.message || 'Impossible d’ouvrir la page de paiement sécurisée.');
                } catch (fusionErr) {
                    toast.error(fusionErr.response?.data?.message || 'Erreur lors du lancement du paiement en ligne.');
                }
                setProcessing(false);
                return;
            }

            if (response.data?.redirect_url) {
                window.location.href = response.data.redirect_url;
                return;
            }
            const hasSuccessCallback = typeof onSuccess === 'function';
            if (hasSuccessCallback) {
                try {
                    onSuccess(response.data);
                } catch (callbackError) {
                    console.error('OrderDrawer onSuccess callback error', callbackError);
                }
            }
            onClose();
            if (!hasSuccessCallback) {
                router.reload({ only: ['orders'] });
            }
        } catch (error) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
                const errorMessages = Object.values(error.response.data.errors).flat();
                errorMessages.forEach(msg => toast.error(msg));
            } else {
                toast.error(error.response?.data?.message || error.message || 'Erreur lors de la création de la commande');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Drawer
            isOpen={isOpen}
            onClose={onClose}
            title={isEditing ? 'Modifier la commande' : 'Nouvelle commande'}
            size="xl"
        >
            <form onSubmit={handleSubmit} className="flex flex-col h-full">
                <div className="flex-1 space-y-6 p-6 overflow-y-auto">
                    {/* Informations client */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <User className="h-5 w-5" />
                            Informations client
                        </h3>
                        
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="customer_name">Nom complet *</Label>
                                <Input
                                    id="customer_name"
                                    value={formData.customer_name}
                                    onChange={(e) => setFormData({ ...formData, customer_name: e.target.value })}
                                    required
                                />
                                {errors.customer_name && (
                                    <p className="text-sm text-red-600 dark:text-red-400 mt-1">{errors.customer_name}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="customer_email">Email *</Label>
                                <Input
                                    id="customer_email"
                                    type="email"
                                    value={formData.customer_email}
                                    onChange={(e) => setFormData({ ...formData, customer_email: e.target.value })}
                                    required
                                />
                                {errors.customer_email && (
                                    <p className="text-sm text-red-600 dark:text-red-400 mt-1">{errors.customer_email}</p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="customer_phone">
                                    Téléphone
                                    {fromCart &&
                                    (selectedPaymentType === 'fusionpay' ||
                                        paymentMethods?.find((m) => m.code === formData.payment_method)?.type === 'fusionpay')
                                        ? ' *'
                                        : ''}
                                </Label>
                                <Input
                                    id="customer_phone"
                                    value={formData.customer_phone}
                                    onChange={(e) => setFormData({ ...formData, customer_phone: e.target.value })}
                                    required={
                                        !!fromCart &&
                                        (selectedPaymentType === 'fusionpay' ||
                                            paymentMethods?.find((m) => m.code === formData.payment_method)?.type === 'fusionpay')
                                    }
                                />
                            </div>
                        </div>
                    </div>

                    {/* Adresses */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <MapPin className="h-5 w-5" />
                            Adresses
                        </h3>
                        
                        <div>
                            <Label htmlFor="shipping_address">Adresse de livraison *</Label>
                            <Textarea
                                id="shipping_address"
                                value={formData.shipping_address}
                                onChange={(e) => setFormData({ ...formData, shipping_address: e.target.value })}
                                rows={3}
                                required
                            />
                            {errors.shipping_address && (
                                <p className="text-sm text-red-600 dark:text-red-400 mt-1">{errors.shipping_address}</p>
                            )}
                        </div>

                        <div>
                            <Label htmlFor="billing_address">Adresse de facturation</Label>
                            <Textarea
                                id="billing_address"
                                value={formData.billing_address}
                                onChange={(e) => setFormData({ ...formData, billing_address: e.target.value })}
                                rows={3}
                            />
                        </div>
                    </div>

                    {/* Produits */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <ShoppingCart className="h-5 w-5" />
                            Produits
                        </h3>

                        {/* Ajouter un produit */}
                        <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 space-y-3">
                            <div className="grid grid-cols-1 md:grid-cols-4 gap-3">
                                <div className="md:col-span-2">
                                    <Label>Produit</Label>
                                    <select
                                        className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                        value={selectedProduct?.id || ''}
                                        onChange={(e) => {
                                            const product = products.find(p => p.id === e.target.value);
                                            setSelectedProduct(product || null);
                                        }}
                                    >
                                        <option value="">Sélectionner un produit</option>
                                        {products.map(product => (
                                            <option key={product.id} value={product.id}>
                                                {product.name} - {product.price_amount} {product.price_currency}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <Label>Quantité</Label>
                                    <Input
                                        type="number"
                                        min="0.01"
                                        step="0.01"
                                        value={itemQuantity}
                                        onChange={(e) => setItemQuantity(parseFloat(e.target.value) || 1)}
                                    />
                                </div>

                                <div>
                                    <Label>Remise</Label>
                                    <Input
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={itemDiscount}
                                        onChange={(e) => setItemDiscount(parseFloat(e.target.value) || 0)}
                                    />
                                </div>
                            </div>

                            <Button
                                type="button"
                                onClick={handleAddItem}
                                className="w-full"
                                disabled={!selectedProduct}
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Ajouter au panier
                            </Button>
                        </div>

                        {/* Liste des produits */}
                        {formData.items.length > 0 ? (
                            <div className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700">
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-300">Produit</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-300">Qté</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-300">Prix unit.</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-300">Remise</th>
                                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-600 dark:text-gray-300">Sous-total</th>
                                            <th className="px-4 py-2 text-right text-xs font-medium text-gray-600 dark:text-gray-300">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {formData.items.map((item, index) => {
                                            const itemSubtotal = (item.unit_price * item.quantity) - (item.discount_amount || 0);
                                            return (
                                                <tr key={index}>
                                                    <td className="px-4 py-2 text-sm">{item.product_name}</td>
                                                    <td className="px-4 py-2 text-sm">{item.quantity}</td>
                                                    <td className="px-4 py-2 text-sm">{item.unit_price} {formData.currency}</td>
                                                    <td className="px-4 py-2 text-sm">{item.discount_amount || 0} {formData.currency}</td>
                                                    <td className="px-4 py-2 text-sm font-medium">{itemSubtotal.toFixed(2)} {formData.currency}</td>
                                                    <td className="px-4 py-2 text-right">
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() => handleRemoveItem(index)}
                                                            className="text-red-600 dark:text-red-400"
                                                        >
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                Aucun produit ajouté
                            </div>
                        )}
                    </div>

                    {/* Totaux */}
                    <div className="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-4">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <DollarSign className="h-5 w-5" />
                            Totaux
                        </h3>

                        {paymentMethods?.length > 0 && (
                            <div>
                                <Label className="font-normal">Mode de paiement</Label>
                                <select
                                    value={formData.payment_method}
                                    onChange={(e) => setFormData({ ...formData, payment_method: e.target.value })}
                                    className="w-full mt-1 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                >
                                    <option value="">Sélectionner</option>
                                    {paymentMethods.map((m) => (
                                        <option key={m.id} value={m.code ?? m.name}>
                                            {m.name}
                                        </option>
                                    ))}
                                </select>
                                {fromCart &&
                                    (selectedPaymentType === 'fusionpay' ||
                                        paymentMethods?.find((x) => x.code === formData.payment_method)?.type === 'fusionpay') && (
                                        <div className="mt-3 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/80 dark:bg-amber-900/20 p-3 space-y-2">
                                        <p className="text-xs text-amber-900 dark:text-amber-100">
                                            Le vendeur a configuré au moins un article à paiement immédiat : après validation, vous serez
                                            redirigé vers la page de paiement sécurisée pour finaliser le règlement.
                                        </p>
                                        <Label className="text-xs font-medium">Canal de paiement</Label>
                                        <select
                                            value={fusionPaymentMethod}
                                            onChange={(e) => setFusionPaymentMethod(e.target.value)}
                                            className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                        >
                                            <option value="mobile_money">Mobile Money</option>
                                            <option value="card">Carte bancaire</option>
                                        </select>
                                    </div>
                                )}
                                {fromCart &&
                                    (selectedPaymentType === 'cash_on_delivery' ||
                                        paymentMethods?.find((x) => x.code === formData.payment_method)?.type ===
                                            'cash_on_delivery') && (
                                        <div className="mt-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/60 p-3">
                                            <p className="text-xs text-slate-700 dark:text-slate-200">
                                                Le vendeur a configuré ce type de règlement sur la fiche produit : aucun débit en ligne,
                                                vous payez à la réception. Conservez le numéro de commande reçu par e-mail pour le suivi.
                                            </p>
                                        </div>
                                    )}
                            </div>
                        )}

                        <div className="space-y-2">
                            <div className="flex justify-between">
                                <span className="text-gray-600 dark:text-gray-400">Sous-total</span>
                                <span className="font-medium">{calculateSubtotal().toFixed(2)} {formData.currency}</span>
                            </div>

                            {fromCart && (shippingMethods?.length > 0 || readonlyShipping) ? (
                                <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>Frais de livraison{readonlyShipping ? ' (fixe)' : ''}</span>
                                    <span>{formData.shipping_amount.toFixed(2)} {formData.currency}</span>
                                </div>
                            ) : (
                                <div className="flex justify-between">
                                    <Label htmlFor="shipping_amount" className="font-normal">Frais de livraison</Label>
                                    <div className="flex items-center gap-2">
                                        <Input
                                            id="shipping_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={formData.shipping_amount}
                                            onChange={(e) => setFormData({ ...formData, shipping_amount: parseFloat(e.target.value) || 0 })}
                                            className="w-24"
                                        />
                                        <span>{formData.currency}</span>
                                    </div>
                                </div>
                            )}

                            {fromCart ? (
                                <div className="flex justify-between text-gray-600 dark:text-gray-400">
                                    <span>Taxes ({taxRate}%)</span>
                                    <span>{formData.tax_amount.toFixed(2)} {formData.currency}</span>
                                </div>
                            ) : (
                                <div className="flex justify-between">
                                    <Label htmlFor="tax_amount" className="font-normal">Taxes</Label>
                                    <div className="flex items-center gap-2">
                                        <Input
                                            id="tax_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={formData.tax_amount}
                                            onChange={(e) => setFormData({ ...formData, tax_amount: parseFloat(e.target.value) || 0 })}
                                            className="w-24"
                                        />
                                        <span>{formData.currency}</span>
                                    </div>
                                </div>
                            )}

                            {fromCart && couponDiscount > 0 ? (
                                <div className="flex justify-between text-green-600 dark:text-green-400">
                                    <span>Réduction promo</span>
                                    <span>-{formData.discount_amount.toFixed(2)} {formData.currency}</span>
                                </div>
                            ) : !fromCart && (
                                <div className="flex justify-between">
                                    <Label htmlFor="discount_amount" className="font-normal">Remise globale</Label>
                                    <div className="flex items-center gap-2">
                                        <Input
                                            id="discount_amount"
                                            type="number"
                                            min="0"
                                            step="0.01"
                                            value={formData.discount_amount}
                                            onChange={(e) => setFormData({ ...formData, discount_amount: parseFloat(e.target.value) || 0 })}
                                            className="w-24"
                                        />
                                        <span>{formData.currency}</span>
                                    </div>
                                </div>
                            )}

                            <div className="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                                <span>Total</span>
                                <span>{calculateTotal().toFixed(2)} {formData.currency}</span>
                            </div>
                        </div>
                    </div>

                    {/* Notes */}
                    <div>
                        <Label htmlFor="notes">Notes</Label>
                        <Textarea
                            id="notes"
                            value={formData.notes}
                            onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                            rows={3}
                        />
                    </div>
                </div>

                {/* Footer */}
                <div className="border-t border-gray-200 dark:border-gray-700 p-6 flex gap-3 justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        disabled={processing}
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing || formData.items.length === 0}
                        className="gap-2"
                    >
                        {processing ? (
                            <>
                                <div className="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                Création...
                            </>
                        ) : (
                            <>
                                <Save className="h-4 w-4" />
                                {fromCart &&
                                (selectedPaymentType === 'fusionpay' ||
                                    paymentMethods?.find((x) => x.code === formData.payment_method)?.type === 'fusionpay')
                                    ? 'Créer la commande et payer'
                                    : 'Créer la commande'}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
