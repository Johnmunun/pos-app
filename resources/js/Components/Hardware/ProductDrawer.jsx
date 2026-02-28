import React, { useState, useEffect } from 'react';
import { useForm, usePage, router } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { Save, Package, Hash, X, Sparkles, Image as ImageIcon } from 'lucide-react';
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
    const { props } = usePage();
    const currencies = props.shop?.currencies || [];
    const defaultCurrency = props.shop?.currency || (currencies.find(c => c.is_default)?.code || currencies[0]?.code || 'USD');
    const [imagePreview, setImagePreview] = useState(product?.image_url || null);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: product?.name || '',
        product_code: product?.product_code || '',
        description: product?.description || '',
        category_id: product?.category_id || '',
        price: product?.price_amount ?? '',
        currency: product?.price_currency || defaultCurrency,
        minimum_stock: product?.minimum_stock ?? 0,
        unit: product?.type_unite || 'UNITE',
        type_unite: product?.type_unite || 'UNITE',
        quantite_par_unite: product?.quantite_par_unite ?? 1,
        est_divisible: product?.est_divisible !== false,
        image: null,
        remove_image: false,
        price_normal: product?.price_normal ?? product?.price_amount ?? '',
        price_reduced: product?.price_reduced ?? '',
        price_reduction_percent: product?.price_reduction_percent ?? '',
        price_non_negotiable: product?.price_non_negotiable ?? '',
        price_wholesale_normal: product?.price_wholesale_normal ?? '',
        price_wholesale_reduced: product?.price_wholesale_reduced ?? '',
        price_non_negotiable_wholesale: product?.price_non_negotiable_wholesale ?? '',
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
                currency: product.price_currency || defaultCurrency,
                minimum_stock: product.minimum_stock ?? 0,
                unit: product.type_unite || 'UNITE',
                type_unite: product.type_unite || 'UNITE',
                quantite_par_unite: product.quantite_par_unite ?? 1,
                est_divisible: product.est_divisible !== false,
                image: null,
                remove_image: false,
                price_normal: product.price_normal ?? product.price_amount ?? '',
                price_reduced: product.price_reduced ?? '',
                price_reduction_percent: product.price_reduction_percent ?? '',
                price_non_negotiable: product.price_non_negotiable ?? '',
                price_wholesale_normal: product.price_wholesale_normal ?? '',
                price_wholesale_reduced: product.price_wholesale_reduced ?? '',
                price_non_negotiable_wholesale: product.price_non_negotiable_wholesale ?? '',
            });
            setImagePreview(product.image_url || null);
        } else {
            reset();
            setImagePreview(null);
        }
    }, [product]);

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        
        if (file.size > 2 * 1024 * 1024) {
            toast.error('L\'image ne doit pas dépasser 2 Mo');
            return;
        }
        
        if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/)) {
            toast.error('Format d\'image non supporté. Utilisez JPG, PNG ou WebP.');
            return;
        }
        
        setData('image', file);
        setData('remove_image', false);
        
        const reader = new FileReader();
        reader.onloadend = () => {
            setImagePreview(reader.result);
        };
        reader.readAsDataURL(file);
    };

    const handleRemoveImage = () => {
        setData('image', null);
        setData('remove_image', true);
        setImagePreview(null);
    };

    const calculateReducedPrice = () => {
        const normal = parseFloat(data.price_normal || data.price) || 0;
        const percent = parseFloat(data.price_reduction_percent) || 0;
        if (normal > 0 && percent > 0) {
            const reduced = normal * (1 - percent / 100);
            setData('price_reduced', reduced.toFixed(2));
        } else {
            setData('price_reduced', '');
        }
    };

    useEffect(() => {
        if (data.price_reduction_percent && (data.price_normal || data.price)) {
            calculateReducedPrice();
        }
    }, [data.price_normal, data.price, data.price_reduction_percent]);

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
        setData('unit', data.type_unite);
        if (!data.price_normal && data.price) {
            setData('price_normal', data.price);
        }
        if (isEditing) {
            put(route('hardware.products.update', product.id), {
                forceFormData: true,
                preserveScroll: false,
                onSuccess: () => {
                    toast.success('Produit mis à jour');
                    onClose();
                    reset();
                    setImagePreview(null);
                    // Recharger la page pour afficher l'image mise à jour
                    router.reload({ only: ['products'] });
                },
                onError: (err) => {
                    const errorMessage = err.message || (typeof err === 'string' ? err : 'Erreur lors de la mise à jour');
                    toast.error(errorMessage);
                },
            });
        } else {
            post(route('hardware.products.store'), {
                forceFormData: true,
                preserveScroll: false,
                onSuccess: () => {
                    toast.success('Produit créé');
                    onClose();
                    reset();
                    setImagePreview(null);
                    // Recharger la page pour afficher le nouveau produit avec son image
                    router.reload({ only: ['products'] });
                },
                onError: (err) => {
                    const errorMessage = err.message || (typeof err === 'string' ? err : 'Erreur lors de la création');
                    toast.error(errorMessage);
                },
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
                    
                    {/* Upload Image */}
                    <div className="space-y-2">
                        <Label htmlFor="image">Image du produit</Label>
                        <div className="flex items-center gap-4">
                            {imagePreview ? (
                                <div className="relative group">
                                    <img src={imagePreview} alt="Preview" className="w-24 h-24 object-cover rounded-lg border border-gray-300 dark:border-gray-600" />
                                    <button
                                        type="button"
                                        onClick={handleRemoveImage}
                                        className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 z-10"
                                        title="Supprimer l'image"
                                    >
                                        <X className="h-3 w-3" />
                                    </button>
                                    <label
                                        htmlFor="image"
                                        className="absolute inset-0 flex items-center justify-center bg-black bg-opacity-0 group-hover:bg-opacity-50 rounded-lg cursor-pointer transition-all"
                                        title="Remplacer l'image"
                                    >
                                        <ImageIcon className="h-6 w-6 text-white opacity-0 group-hover:opacity-100 transition-opacity" />
                                    </label>
                                </div>
                            ) : (
                                <label
                                    htmlFor="image"
                                    className="flex flex-col items-center justify-center w-24 h-24 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-amber-500 transition-colors"
                                >
                                    <ImageIcon className="h-6 w-6 text-gray-400 mb-1" />
                                    <span className="text-xs text-gray-500">Image</span>
                                </label>
                            )}
                            <div className="flex-1">
                                <input
                                    type="file"
                                    id="image"
                                    accept="image/jpeg,image/jpg,image/png,image/webp"
                                    onChange={handleImageChange}
                                    className="hidden"
                                />
                                {imagePreview && (
                                    <p className="text-xs text-amber-600 dark:text-amber-400 mb-1">
                                        Cliquez sur l'image pour la remplacer
                                    </p>
                                )}
                                <p className="text-xs text-gray-500 dark:text-gray-400">JPG, PNG, WebP (max 2 Mo)</p>
                            </div>
                        </div>
                        {errors.image && <p className="text-sm text-red-600 dark:text-red-400">{errors.image}</p>}
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
                            <Label htmlFor="price">Prix principal *</Label>
                            <div className="flex gap-2">
                                <Input id="price" type="number" step="0.01" min="0" value={data.price} onChange={(e) => { setData('price', e.target.value); if (!data.price_normal) setData('price_normal', e.target.value); }} placeholder="0.00" required />
                                <select
                                    value={data.currency}
                                    onChange={(e) => setData('currency', e.target.value)}
                                    className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-24"
                                >
                                    {currencies.length > 0 ? (
                                        currencies.map((curr) => (
                                            <option key={curr.code} value={curr.code}>
                                                {curr.code}
                                            </option>
                                        ))
                                    ) : (
                                        <>
                                            <option value="USD">USD</option>
                                            <option value="CDF">CDF</option>
                                            <option value="EUR">EUR</option>
                                        </>
                                    )}
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

                {/* Section Prix détaillés */}
                <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Gestion des prix</h3>
                    
                    {/* Prix détail */}
                    <div className="space-y-4">
                        <h4 className="text-md font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">Prix de vente au détail</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="price_normal">Prix normal</Label>
                                <Input id="price_normal" type="number" step="0.01" min={0} value={data.price_normal} onChange={(e) => setData('price_normal', e.target.value)} placeholder="Prix normal" />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="price_reduction_percent">Pourcentage de réduction (%)</Label>
                                <Input id="price_reduction_percent" type="number" step="0.01" min={0} max={100} value={data.price_reduction_percent} onChange={(e) => { setData('price_reduction_percent', e.target.value); calculateReducedPrice(); }} placeholder="0.00" />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="price_reduced">Prix réduit (calculé)</Label>
                                <Input id="price_reduced" type="number" step="0.01" min={0} value={data.price_reduced} onChange={(e) => setData('price_reduced', e.target.value)} placeholder="Prix réduit" readOnly={!!data.price_reduction_percent} />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="price_non_negotiable">Prix non discutable (détail)</Label>
                            <Input id="price_non_negotiable" type="number" step="0.01" min={0} value={data.price_non_negotiable} onChange={(e) => setData('price_non_negotiable', e.target.value)} placeholder="Ex: 5.00" />
                            <p className="text-xs text-gray-500 dark:text-gray-400">Prix minimum de vente au détail (non négociable)</p>
                        </div>
                    </div>

                    {/* Prix gros */}
                    <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <h4 className="text-md font-semibold text-gray-800 dark:text-white border-b border-gray-200 dark:border-gray-700 pb-2">Prix de vente en gros</h4>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="price_wholesale_normal">Prix gros normal</Label>
                                <Input id="price_wholesale_normal" type="number" step="0.01" min={0} value={data.price_wholesale_normal} onChange={(e) => setData('price_wholesale_normal', e.target.value)} placeholder="Prix gros normal" />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="price_wholesale_reduced">Prix gros réduit</Label>
                                <Input id="price_wholesale_reduced" type="number" step="0.01" min={0} value={data.price_wholesale_reduced} onChange={(e) => setData('price_wholesale_reduced', e.target.value)} placeholder="Prix gros réduit" />
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="price_non_negotiable_wholesale">Prix non discutable (gros)</Label>
                            <Input id="price_non_negotiable_wholesale" type="number" step="0.01" min={0} value={data.price_non_negotiable_wholesale} onChange={(e) => setData('price_non_negotiable_wholesale', e.target.value)} placeholder="Ex: 4.50" />
                            <p className="text-xs text-gray-500 dark:text-gray-400">Prix minimum de vente en gros (non négociable)</p>
                        </div>
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
