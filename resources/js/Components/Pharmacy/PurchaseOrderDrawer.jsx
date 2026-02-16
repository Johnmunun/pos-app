import React, { useState, useEffect } from 'react';
import { X, Plus, Trash2, Package, Truck, Search } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import axios from 'axios';
import toast from 'react-hot-toast';

export default function PurchaseOrderDrawer({ 
    isOpen, 
    onClose, 
    purchaseOrder = null, 
    suppliers = [], 
    products = [],
    currency = 'USD',
    onSuccess 
}) {
    const isEditing = !!purchaseOrder;
    
    const [formData, setFormData] = useState({
        supplier_id: '',
        expected_at: '',
        currency: currency,
        notes: ''
    });
    
    const [lines, setLines] = useState([
        { product_id: '', product_name: '', ordered_quantity: 1, unit_cost: 0 }
    ]);
    
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);
    const [productSearch, setProductSearch] = useState('');

    useEffect(() => {
        if (isOpen) {
            if (purchaseOrder) {
                setFormData({
                    supplier_id: purchaseOrder.supplier_id || '',
                    expected_at: purchaseOrder.expected_at ? purchaseOrder.expected_at.split('T')[0] : '',
                    currency: purchaseOrder.currency || currency,
                    notes: purchaseOrder.notes || ''
                });
                if (purchaseOrder.lines && purchaseOrder.lines.length > 0) {
                    setLines(purchaseOrder.lines.map(line => ({
                        product_id: line.product_id,
                        product_name: line.product_name,
                        ordered_quantity: line.ordered_quantity,
                        unit_cost: line.unit_cost
                    })));
                }
            } else {
                setFormData({
                    supplier_id: '',
                    expected_at: '',
                    currency: currency,
                    notes: ''
                });
                setLines([{ product_id: '', product_name: '', ordered_quantity: 1, unit_cost: 0 }]);
            }
            setErrors({});
            setProductSearch('');
        }
    }, [isOpen, purchaseOrder, currency]);

    const addLine = () => {
        setLines([...lines, { product_id: '', product_name: '', ordered_quantity: 1, unit_cost: 0 }]);
    };

    const setLineProduct = (index, product) => {
        const next = [...lines];
        next[index] = { 
            ...next[index], 
            product_id: product.id, 
            product_name: product.name, 
            unit_cost: product.cost_amount ?? 0 
        };
        setLines(next);
    };

    const updateLine = (index, field, value) => {
        const next = [...lines];
        next[index] = { ...next[index], [field]: value };
        setLines(next);
    };

    const removeLine = (index) => {
        if (lines.length > 1) {
            setLines(lines.filter((_, i) => i !== index));
        }
    };

    const calculateTotal = () => {
        return lines.reduce((sum, line) => {
            return sum + (Number(line.ordered_quantity) * Number(line.unit_cost));
        }, 0);
    };

    const filteredProducts = products.filter(p => {
        if (!productSearch.trim()) return true;
        const search = productSearch.toLowerCase();
        return (p.name || '').toLowerCase().includes(search) || 
               (p.code || '').toLowerCase().includes(search);
    });

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!formData.supplier_id) {
            setErrors({ supplier_id: 'Veuillez sélectionner un fournisseur' });
            return;
        }
        
        const validLines = lines.filter(l => l.product_id && l.ordered_quantity > 0);
        if (validLines.length === 0) {
            toast.error('Ajoutez au moins une ligne avec un produit');
            return;
        }

        setSubmitting(true);
        setErrors({});

        try {
            const payload = {
                supplier_id: formData.supplier_id,
                currency: formData.currency,
                expected_at: formData.expected_at || null,
                notes: formData.notes || null,
                lines: validLines.map(l => ({
                    product_id: l.product_id,
                    ordered_quantity: Number(l.ordered_quantity),
                    unit_cost: Number(l.unit_cost)
                }))
            };

            if (isEditing) {
                await axios.put(route('pharmacy.purchases.update', purchaseOrder.id), payload);
                toast.success('Bon de commande modifié avec succès');
            } else {
                await axios.post(route('pharmacy.purchases.store'), payload);
                toast.success('Bon de commande créé avec succès');
            }
            
            onSuccess?.();
            onClose();
        } catch (err) {
            if (err.response?.data?.errors) {
                setErrors(err.response.data.errors);
            } else {
                toast.error(err.response?.data?.message || 'Une erreur est survenue');
            }
        } finally {
            setSubmitting(false);
        }
    };

    if (!isOpen) return null;

    const selectedSupplier = suppliers.find(s => s.id === formData.supplier_id);

    return (
        <>
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-black/50 z-40 transition-opacity"
                onClick={onClose}
            />
            
            {/* Drawer */}
            <div className="fixed inset-y-0 right-0 z-50 w-full max-w-2xl bg-white dark:bg-gray-800 shadow-xl transform transition-transform">
                <div className="h-full flex flex-col">
                    {/* Header */}
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900">
                        <div className="flex items-center gap-3">
                            <div className="h-10 w-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <Truck className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                    {isEditing ? 'Modifier le bon de commande' : 'Nouveau bon de commande'}
                                </h2>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {isEditing ? `#${purchaseOrder.id.slice(0, 8)}` : 'Remplissez les informations ci-dessous'}
                                </p>
                            </div>
                        </div>
                        <button
                            onClick={onClose}
                            className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    {/* Content */}
                    <div className="flex-1 overflow-y-auto p-6">
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Fournisseur et informations */}
                            <div className="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 space-y-4">
                                <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                    <Truck className="h-4 w-4" />
                                    Informations générales
                                </h3>
                                
                                <div>
                                    <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                        Fournisseur <span className="text-red-500">*</span>
                                    </label>
                                    <select
                                        className={`w-full rounded-lg border ${errors.supplier_id ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'} bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-primary`}
                                        value={formData.supplier_id}
                                        onChange={(e) => setFormData({ ...formData, supplier_id: e.target.value })}
                                        disabled={isEditing && purchaseOrder?.status !== 'DRAFT'}
                                    >
                                        <option value="">Sélectionner un fournisseur...</option>
                                        {suppliers.map((s) => (
                                            <option key={s.id} value={s.id}>{s.name}</option>
                                        ))}
                                    </select>
                                    {errors.supplier_id && (
                                        <p className="mt-1 text-sm text-red-500">{errors.supplier_id}</p>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                            Date de livraison prévue
                                        </label>
                                        <Input
                                            type="date"
                                            value={formData.expected_at}
                                            onChange={(e) => setFormData({ ...formData, expected_at: e.target.value })}
                                            className="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                            Devise
                                        </label>
                                        <select
                                            className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-primary"
                                            value={formData.currency}
                                            onChange={(e) => setFormData({ ...formData, currency: e.target.value })}
                                        >
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                            <option value="CDF">CDF</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">
                                        Notes (optionnel)
                                    </label>
                                    <textarea
                                        className="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 focus:ring-2 focus:ring-primary resize-none"
                                        rows={2}
                                        placeholder="Notes ou instructions..."
                                        value={formData.notes}
                                        onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
                                    />
                                </div>
                            </div>

                            {/* Lignes de commande */}
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h3 className="text-sm font-medium text-gray-700 dark:text-gray-300 flex items-center gap-2">
                                        <Package className="h-4 w-4" />
                                        Produits à commander
                                    </h3>
                                    <Button 
                                        type="button" 
                                        variant="outline" 
                                        size="sm" 
                                        onClick={addLine}
                                    >
                                        <Plus className="h-4 w-4 mr-1" />
                                        Ajouter une ligne
                                    </Button>
                                </div>

                                {/* Search products */}
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        type="text"
                                        placeholder="Rechercher un produit..."
                                        value={productSearch}
                                        onChange={(e) => setProductSearch(e.target.value)}
                                        className="pl-10 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                                    />
                                </div>

                                <div className="space-y-3">
                                    {lines.map((line, index) => (
                                        <div 
                                            key={index} 
                                            className="bg-white dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 p-4"
                                        >
                                            <div className="flex flex-wrap items-end gap-3">
                                                <div className="flex-1 min-w-[200px]">
                                                    <label className="block text-xs font-medium mb-1 text-gray-500 dark:text-gray-400">
                                                        Produit
                                                    </label>
                                                    <select
                                                        className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 py-2 text-sm"
                                                        value={line.product_id}
                                                        onChange={(e) => {
                                                            const p = products.find(pr => pr.id === e.target.value);
                                                            if (p) setLineProduct(index, p);
                                                        }}
                                                    >
                                                        <option value="">Sélectionner...</option>
                                                        {filteredProducts.map((p) => (
                                                            <option key={p.id} value={p.id}>
                                                                {p.name} ({p.code})
                                                            </option>
                                                        ))}
                                                    </select>
                                                </div>
                                                <div className="w-24">
                                                    <label className="block text-xs font-medium mb-1 text-gray-500 dark:text-gray-400">
                                                        Quantité
                                                    </label>
                                                    <Input
                                                        type="number"
                                                        min={1}
                                                        value={line.ordered_quantity}
                                                        onChange={(e) => updateLine(index, 'ordered_quantity', parseInt(e.target.value, 10) || 1)}
                                                        className="text-sm"
                                                    />
                                                </div>
                                                <div className="w-28">
                                                    <label className="block text-xs font-medium mb-1 text-gray-500 dark:text-gray-400">
                                                        Prix unitaire
                                                    </label>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        min={0}
                                                        value={line.unit_cost}
                                                        onChange={(e) => updateLine(index, 'unit_cost', parseFloat(e.target.value) || 0)}
                                                        className="text-sm"
                                                    />
                                                </div>
                                                <div className="w-24 text-right">
                                                    <label className="block text-xs font-medium mb-1 text-gray-500 dark:text-gray-400">
                                                        Total
                                                    </label>
                                                    <p className="py-2 font-semibold text-gray-900 dark:text-gray-100">
                                                        {(Number(line.ordered_quantity) * Number(line.unit_cost)).toFixed(2)}
                                                    </p>
                                                </div>
                                                <button
                                                    type="button"
                                                    onClick={() => removeLine(index)}
                                                    disabled={lines.length <= 1}
                                                    className="p-2 text-red-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </form>
                    </div>

                    {/* Footer */}
                    <div className="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        {/* Total */}
                        <div className="flex items-center justify-between mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                            <span className="text-sm font-medium text-gray-700 dark:text-gray-300">Total de la commande</span>
                            <span className="text-xl font-bold text-gray-900 dark:text-gray-100">
                                {formData.currency} {calculateTotal().toFixed(2)}
                            </span>
                        </div>
                        
                        <div className="flex gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={onClose}
                                className="flex-1"
                                disabled={submitting}
                            >
                                Annuler
                            </Button>
                            <Button
                                type="submit"
                                onClick={handleSubmit}
                                className="flex-1"
                                disabled={submitting}
                            >
                                {submitting ? 'Enregistrement...' : (isEditing ? 'Mettre à jour' : 'Créer le bon')}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
