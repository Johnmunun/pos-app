import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { Save, Package, Hash, X, Sparkles } from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';

const TYPE_UNITE_OPTIONS = ['PIECE', 'LOT', 'METRE', 'KG', 'LITRE', 'BOITE', 'CARTON', 'UNITE'];

/**
 * Drawer création/édition produit — Module Quincaillerie.
 * Champs : nom, code, description, catégorie, prix, unité, quantité par unité, divisible, stock minimum.
 * Aucune dépendance Pharmacy.
 */
export default function HardwareProductDrawer({ isOpen, onClose, product = null, categories = [] }) {
    const isEditing = !!product;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: product?.name || '',
        product_code: product?.product_code || '',
        description: product?.description || '',
        category_id: product?.category_id || '',
        price: product?.price_amount ?? '',
        currency: product?.price_currency || 'USD',
        minimum_stock: product?.minimum_stock ?? 0,
        unit: product?.type_unite || 'UNITE',
        type_unite: product?.type_unite || 'UNITE',
        quantite_par_unite: product?.quantite_par_unite ?? 1,
        est_divisible: product?.est_divisible !== false,
    });

    const [isGeneratingCode, setIsGeneratingCode] = useState(false);

    useEffect(() => {
        if (product) {
            setData({
                name: product.name || '',
                product_code: product.product_code || '',
                description: product.description || '',
                category_id: product.category_id || '',
                price: product.price_amount ?? '',
                currency: product.price_currency || 'USD',
                minimum_stock: product.minimum_stock ?? 0,
                unit: product.type_unite || 'UNITE',
                type_unite: product.type_unite || 'UNITE',
                quantite_par_unite: product.quantite_par_unite ?? 1,
                est_divisible: product.est_divisible !== false,
            });
        } else {
            reset();
        }
    }, [product]);

    const handleGenerateCode = async () => {
        if (!data.name) {
            toast.error('Veuillez d\'abord saisir le nom du produit.');
            return;
        }
        try {
            setIsGeneratingCode(true);
            const res = await axios.get(route('hardware.products.generate-code'), { params: { name: data.name } });
            if (res.data?.code) {
                setData('product_code', res.data.code);
                toast.success('Code généré');
            } else {
                toast.error('Impossible de générer le code');
            }
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur génération du code');
        } finally {
            setIsGeneratingCode(false);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEditing) {
            put(route('hardware.products.update', product.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Produit mis à jour');
                    onClose();
                    reset();
                },
                onError: (err) => toast.error(err.message || 'Erreur'),
            });
        } else {
            post(route('hardware.products.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Produit créé');
                    onClose();
                    reset();
                },
                onError: (err) => toast.error(err.message || 'Erreur'),
            });
        }
    };

    return (
        <Drawer isOpen={isOpen} onClose={() => { reset(); onClose(); }} title={isEditing ? 'Modifier le produit' : 'Nouveau produit'} size="xl">
            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <Package className="h-5 w-5 mr-2" /> Informations produit
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nom *</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Nom du produit" required />
                            {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="product_code">Code produit *</Label>
                            <div className="flex gap-2">
                                <div className="relative flex-1">
                                    <Hash className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                    <Input id="product_code" value={data.product_code} onChange={(e) => setData('product_code', e.target.value)} className="pl-10" placeholder="ex: VIS-001" required />
                                </div>
                                <Button type="button" variant="outline" size="sm" onClick={handleGenerateCode} disabled={isGeneratingCode}>
                                    <Sparkles className="h-4 w-4 mr-1" /> {isGeneratingCode ? '...' : 'Auto'}
                                </Button>
                            </div>
                            {errors.product_code && <p className="text-sm text-red-600 dark:text-red-400">{errors.product_code}</p>}
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} placeholder="Description..." />
                        {errors.description && <p className="text-sm text-red-600 dark:text-red-400">{errors.description}</p>}
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="category_id">Catégorie *</Label>
                            <select
                                id="category_id"
                                value={data.category_id}
                                onChange={(e) => setData('category_id', e.target.value)}
                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-amber-500 focus:ring-amber-500"
                                required
                            >
                                <option value="">— Choisir —</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>
                            {errors.category_id && <p className="text-sm text-red-600 dark:text-red-400">{errors.category_id}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="price">Prix *</Label>
                            <div className="flex gap-2">
                                <Input id="price" type="number" step="0.01" min="0" value={data.price} onChange={(e) => setData('price', e.target.value)} placeholder="0.00" required />
                                <select
                                    value={data.currency}
                                    onChange={(e) => setData('currency', e.target.value)}
                                    className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-20"
                                >
                                    <option value="USD">USD</option>
                                    <option value="CDF">CDF</option>
                                    <option value="EUR">EUR</option>
                                </select>
                            </div>
                            {errors.price && <p className="text-sm text-red-600 dark:text-red-400">{errors.price}</p>}
                        </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="type_unite">Unité</Label>
                            <select
                                id="type_unite"
                                value={data.type_unite}
                                onChange={(e) => {
                                    const v = e.target.value;
                                    setData('type_unite', v);
                                    setData('unit', v);
                                }}
                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-amber-500 focus:ring-amber-500"
                            >
                                {TYPE_UNITE_OPTIONS.map((u) => (
                                    <option key={u} value={u}>{u}</option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="quantite_par_unite">Quantité par unité</Label>
                            <Input id="quantite_par_unite" type="number" min={1} value={data.quantite_par_unite} onChange={(e) => setData('quantite_par_unite', e.target.value)} />
                            {errors.quantite_par_unite && <p className="text-sm text-red-600 dark:text-red-400">{errors.quantite_par_unite}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="minimum_stock">Stock minimum</Label>
                            <Input id="minimum_stock" type="number" min={0} value={data.minimum_stock} onChange={(e) => setData('minimum_stock', e.target.value)} />
                            {errors.minimum_stock && <p className="text-sm text-red-600 dark:text-red-400">{errors.minimum_stock}</p>}
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <input
                            type="checkbox"
                            id="est_divisible"
                            checked={data.est_divisible}
                            onChange={(e) => setData('est_divisible', e.target.checked)}
                            className="rounded border-gray-300 text-amber-600 focus:ring-amber-500 dark:border-gray-600 dark:bg-gray-700"
                        />
                        <Label htmlFor="est_divisible">Vente en fraction autorisée</Label>
                    </div>
                </div>
                <div className="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button type="button" variant="outline" onClick={onClose} disabled={processing}>
                        <X className="h-4 w-4 mr-2" /> Annuler
                    </Button>
                    <Button type="submit" className="bg-amber-500 dark:bg-amber-600 text-white hover:bg-amber-600 dark:hover:bg-amber-700" disabled={processing}>
                        <Save className="h-4 w-4 mr-2" /> {processing ? 'Enregistrement...' : isEditing ? 'Enregistrer' : 'Créer'}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
