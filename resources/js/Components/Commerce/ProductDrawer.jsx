import React, { useEffect, useState } from 'react';
import { useForm, usePage, router } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { Package, Hash, Image as ImageIcon, Trash2, RefreshCw } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function CommerceProductDrawer({ isOpen, onClose, product = null, categories = [] }) {
    const isEditing = !!product;
    const { props } = usePage();
    const shopCurrency = props.shop?.currency || 'USD';
    const shop = props.shop || {};

    const [imagePreview, setImagePreview] = useState(product?.image_url || null);

    const { data, setData, post, processing, errors, reset } = useForm({
        // Important: pour les formulaires multipart (image), PHP/Laravel ne parse pas
        // de façon fiable le body en PUT/PATCH. On utilise donc POST + _method=PUT.
        _method: isEditing ? 'put' : undefined,
        sku: product?.sku || '',
        barcode: product?.barcode || '',
        name: product?.name || '',
        description: product?.description || '',
        category_id: product?.category_id || '',
        purchase_price: product?.purchase_price ?? '',
        sale_price: product?.sale_price ?? product?.sale_price_amount ?? '',
        initial_stock: '',
        minimum_stock: product?.minimum_stock ?? 0,
        currency: product?.currency || product?.sale_price_currency || shopCurrency,
        is_weighted: product?.is_weighted ?? false,
        has_expiration: product?.has_expiration ?? false,
        wholesale_price: product?.wholesale_price ?? '',
        min_sale_price: product?.min_sale_price ?? product?.min_sale_price_amount ?? '',
        min_wholesale_price: product?.min_wholesale_price ?? product?.min_wholesale_price_amount ?? '',
        discount_percent: product?.discount_percent ?? '',
        price_non_negotiable: product?.price_non_negotiable ?? false,
        product_type: product?.product_type ?? '',
        unit: product?.unit ?? '',
        weight: product?.weight ?? '',
        length: product?.length ?? '',
        width: product?.width ?? '',
        height: product?.height ?? '',
        tax_rate: product?.tax_rate ?? '',
        tax_type: product?.tax_type ?? '',
        status: product?.status ?? (product?.is_active ? 'active' : 'inactive'),
        image: null,
        remove_image: false,
    });

    useEffect(() => {
        if (product && isEditing) {
            setData((prev) => ({
                ...prev,
                _method: 'put',
                sku: product.sku || '',
                barcode: product.barcode || '',
                name: product.name || '',
                description: product.description || '',
                category_id: product.category_id || '',
                purchase_price: product.purchase_price ?? '',
                sale_price: product.sale_price ?? product.sale_price_amount ?? '',
                minimum_stock: product.minimum_stock ?? 0,
                currency: product.currency || product.sale_price_currency || shopCurrency,
                is_weighted: product.is_weighted ?? false,
                has_expiration: product.has_expiration ?? false,
                wholesale_price: product.wholesale_price ?? '',
                min_sale_price: product.min_sale_price ?? product.min_sale_price_amount ?? '',
                min_wholesale_price: product.min_wholesale_price ?? product.min_wholesale_price_amount ?? '',
                discount_percent: product.discount_percent ?? '',
                price_non_negotiable: product.price_non_negotiable ?? false,
                product_type: product.product_type ?? '',
                unit: product.unit ?? '',
                weight: product.weight ?? '',
                length: product.length ?? '',
                width: product.width ?? '',
                height: product.height ?? '',
                tax_rate: product.tax_rate ?? '',
                tax_type: product.tax_type ?? '',
                status: product.status ?? (product.is_active !== false ? 'active' : 'inactive'),
                image: null,
                remove_image: false,
            }));
            setImagePreview(product.image_url || null);
        } else if (isOpen && !product) {
            // Mode création (drawer ouvert mais pas de produit sélectionné)
            setData((prev) => ({
                ...prev,
                _method: undefined,
                sku: '',
                barcode: '',
                name: '',
                description: '',
                category_id: '',
                purchase_price: '',
                sale_price: '',
                initial_stock: '',
                minimum_stock: 0,
                currency: shopCurrency,
                is_weighted: false,
                has_expiration: false,
                wholesale_price: '',
                min_sale_price: '',
                min_wholesale_price: '',
                discount_percent: '',
                price_non_negotiable: false,
                product_type: '',
                unit: '',
                weight: '',
                length: '',
                width: '',
                height: '',
                tax_rate: '',
                tax_type: '',
                status: 'active',
                image: null,
                remove_image: false,
            }));
            setImagePreview(null);
        } else if (!isOpen) {
            reset();
            setImagePreview(null);
        }
    }, [product?.id, product?.category_id, isEditing, isOpen]);

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        if (file.size > 2 * 1024 * 1024) {
            toast.error("L'image ne doit pas dépasser 2 Mo");
            return;
        }

        if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/)) {
            toast.error('Format d’image non supporté. Utilisez JPG, PNG ou WebP.');
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

    const handleGenerateSku = () => {
        if (!data.name) {
            toast.error('Veuillez d\'abord saisir le nom du produit.');
            return;
        }

        const base = data.name
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-zA-Z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .toUpperCase()
            .slice(0, 10);

        const random = Math.floor(100 + Math.random() * 900); // 3 chiffres
        const code = `${base || 'PROD'}-${random}`;

        setData('sku', code);
        toast.success('Code produit généré automatiquement.');
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        if (isEditing) {
            post(route('commerce.products.update', product.id), {
                forceFormData: true,
                preserveScroll: false,
                onSuccess: () => {
                    toast.success('Produit mis à jour');
                    reset();
                    onClose();
                    router.reload({ only: ['products'] });
                },
                onError: (errs) => {
                    const firstError =
                        errs?.message ||
                        (errs && typeof errs === 'object' ? Object.values(errs)[0] : null);
                    if (firstError) {
                        toast.error(String(firstError));
                    } else {
                        toast.error('Erreur lors de la mise à jour du produit.');
                    }
                },
            });
        } else {
            post(route('commerce.products.store'), {
                forceFormData: true,
                preserveScroll: false,
                onSuccess: () => {
                    toast.success('Produit créé');
                    reset();
                    onClose();
                    router.reload({ only: ['products'] });
                },
                onError: (errs) => {
                    const firstError =
                        errs?.message ||
                        (errs && typeof errs === 'object' ? Object.values(errs)[0] : null);
                    if (firstError) {
                        toast.error(String(firstError));
                    } else {
                        toast.error('Erreur lors de la création du produit.');
                    }
                },
            });
        }
    };

    const handleClose = () => {
        reset();
        onClose();
    };

    return (
        <Drawer
            isOpen={isOpen}
            onClose={handleClose}
            title={isEditing ? 'Modifier le produit' : 'Nouveau produit'}
            size="xl"
        >
            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <Package className="h-5 w-5" />
                        Informations produit
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nom *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Nom du produit"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                                required
                            />
                            {errors.name && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.name}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sku">Code / SKU *</Label>
                            <div className="relative flex gap-2">
                                <div className="relative flex-1">
                                    <Hash className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                    <Input
                                        id="sku"
                                        value={data.sku}
                                        onChange={(e) => setData('sku', e.target.value)}
                                        placeholder="ex: PROD-001"
                                        className="pl-10 bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                                        required
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="icon"
                                    onClick={handleGenerateSku}
                                    className="shrink-0"
                                    title="Générer un code produit"
                                >
                                    <RefreshCw className="h-4 w-4" />
                                </Button>
                            </div>
                            {errors.sku && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.sku}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="barcode">Code-barres</Label>
                            <Input
                                id="barcode"
                                value={data.barcode}
                                onChange={(e) => setData('barcode', e.target.value)}
                                placeholder="Code-barres"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            {errors.barcode && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.barcode}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="category_id">Catégorie *</Label>
                            <select
                                id="category_id"
                                value={String(data.category_id ?? '')}
                                onChange={(e) => setData('category_id', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm"
                                required
                            >
                                <option value="">Sélectionner</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={String(c.id ?? '')}>
                                        {c.name}
                                    </option>
                                ))}
                            </select>
                            {errors.category_id && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.category_id}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="unit">Unité *</Label>
                            <select
                                id="unit"
                                value={data.unit || ''}
                                onChange={(e) => setData('unit', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm"
                                required
                            >
                                <option value="">Sélectionner une unité</option>
                                <option value="PIECE">Pièce</option>
                                <option value="CARTON">Carton</option>
                                <option value="BOITE">Boîte</option>
                                <option value="LOT">Lot</option>
                                <option value="KG">Kilogramme (KG)</option>
                                <option value="G">Gramme (G)</option>
                                <option value="LITRE">Litre</option>
                                <option value="ML">Millilitre (ML)</option>
                                <option value="METRE">Mètre</option>
                                <option value="CM">Centimètre (CM)</option>
                                <option value="M2">Mètre carré (M²)</option>
                                <option value="M3">Mètre cube (M³)</option>
                                <option value="UNITE">Unité</option>
                                <option value="PAQUET">Paquet</option>
                                <option value="SACHET">Sachet</option>
                                <option value="BOUTEILLE">Bouteille</option>
                                <option value="CANETTE">Canette</option>
                            </select>
                            {errors.unit && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.unit}
                                </p>
                            )}
                        </div>
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Description du produit"
                            className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white min-h-[80px]"
                        />
                        {errors.description && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.description}
                            </p>
                        )}
                    </div>
                </div>

                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Prix & stock
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="purchase_price">Prix d&apos;achat *</Label>
                            <Input
                                id="purchase_price"
                                type="number"
                                step="0.01"
                                value={data.purchase_price}
                                onChange={(e) => setData('purchase_price', e.target.value)}
                                placeholder="0.00"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                                required
                            />
                            {errors.purchase_price && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.purchase_price}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="sale_price">Prix de vente *</Label>
                            <Input
                                id="sale_price"
                                type="number"
                                step="0.01"
                                value={data.sale_price}
                                onChange={(e) => setData('sale_price', e.target.value)}
                                placeholder="0.00"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                                required
                            />
                            {errors.sale_price && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.sale_price}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="currency">Devise *</Label>
                            <select
                                id="currency"
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                {shop?.currencies && shop.currencies.length > 0 ? (
                                    shop.currencies.map((c) => (
                                        <option key={c.code} value={c.code}>
                                            {c.code} - {c.name}
                                        </option>
                                    ))
                                ) : (
                                    <>
                                        <option value="CDF">CDF - Franc Congolais</option>
                                        <option value="USD">USD - Dollar US</option>
                                        <option value="EUR">EUR - Euro</option>
                                    </>
                                )}
                            </select>
                            {errors.currency && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.currency}
                                </p>
                            )}
                        </div>
                        {!isEditing && (
                            <div className="space-y-2">
                                <Label htmlFor="initial_stock">Stock initial</Label>
                                <Input
                                    id="initial_stock"
                                    type="number"
                                    step="0.0001"
                                    value={data.initial_stock}
                                    onChange={(e) => setData('initial_stock', e.target.value)}
                                    placeholder="0"
                                    className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                                />
                                {errors.initial_stock && (
                                    <p className="text-sm text-red-600 dark:text-red-400">
                                        {errors.initial_stock}
                                    </p>
                                )}
                            </div>
                        )}
                        <div className="space-y-2">
                            <Label htmlFor="minimum_stock">Stock minimum</Label>
                            <Input
                                id="minimum_stock"
                                type="number"
                                step="0.0001"
                                value={data.minimum_stock}
                                onChange={(e) => setData('minimum_stock', e.target.value)}
                                placeholder="0"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            {errors.minimum_stock && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.minimum_stock}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Options avancées (facultatif)
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2 md:col-span-2">
                            <Label className="inline-flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_weighted}
                                    onChange={(e) => setData('is_weighted', e.target.checked)}
                                    className="rounded border-gray-300 dark:border-slate-600"
                                />
                                <span>Produit divisible</span>
                            </Label>
                            <p className="text-xs text-gray-500 dark:text-gray-400 ml-6">
                                Permet de vendre par fraction (ex: 0,5 ou 0,3). Le stock diminue proportionnellement et le prix est calculé au prorata (prix unitaire × quantité).
                            </p>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="wholesale_price">Prix de gros</Label>
                            <Input
                                id="wholesale_price"
                                type="number"
                                step="0.01"
                                value={data.wholesale_price}
                                onChange={(e) => setData('wholesale_price', e.target.value)}
                                placeholder="0.00"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="min_sale_price">Prix minimum (non négociable) — détail</Label>
                            <Input
                                id="min_sale_price"
                                type="number"
                                step="0.01"
                                value={data.min_sale_price}
                                onChange={(e) => setData('min_sale_price', e.target.value)}
                                placeholder="ex: 900.00"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            {errors.min_sale_price && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.min_sale_price}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="min_wholesale_price">Prix minimum (non négociable) — gros</Label>
                            <Input
                                id="min_wholesale_price"
                                type="number"
                                step="0.01"
                                value={data.min_wholesale_price}
                                onChange={(e) => setData('min_wholesale_price', e.target.value)}
                                placeholder="ex: 850.00"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            {errors.min_wholesale_price && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.min_wholesale_price}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="discount_percent">
                                Remise (%) sur le prix de vente
                            </Label>
                            <Input
                                id="discount_percent"
                                type="number"
                                step="0.01"
                                value={data.discount_percent}
                                onChange={(e) => setData('discount_percent', e.target.value)}
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label className="inline-flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={data.price_non_negotiable}
                                    onChange={(e) =>
                                        setData('price_non_negotiable', e.target.checked)
                                    }
                                    className="rounded border-gray-300 dark:border-slate-600"
                                />
                                <span>Prix non négociable</span>
                            </Label>
                        </div>
                        <div className="space-y-2 md:col-span-2">
                            <Label htmlFor="image">Image du produit</Label>
                            <div className="flex flex-col sm:flex-row sm:items-center gap-3">
                                <div className="flex items-center gap-3">
                                    <label className="inline-flex items-center gap-2 px-3 py-2 border border-dashed border-gray-300 dark:border-slate-600 rounded-md cursor-pointer hover:bg-gray-50 dark:hover:bg-slate-800">
                                        <ImageIcon className="h-4 w-4 text-gray-500 dark:text-gray-400" />
                                        <span className="text-sm text-gray-700 dark:text-gray-200">
                                            Choisir une image
                                        </span>
                                        <input
                                            id="image"
                                            type="file"
                                            accept="image/jpeg,image/jpg,image/png,image/webp"
                                            className="hidden"
                                            onChange={handleImageChange}
                                        />
                                    </label>
                                    {imagePreview && (
                                        <button
                                            type="button"
                                            onClick={handleRemoveImage}
                                            className="inline-flex items-center gap-1 text-xs text-red-600 dark:text-red-400 hover:underline"
                                        >
                                            <Trash2 className="h-3 w-3" />
                                            Retirer
                                        </button>
                                    )}
                                </div>
                                {imagePreview && (
                                    <div className="w-20 h-20 rounded-md overflow-hidden border border-gray-200 dark:border-slate-700">
                                        <img
                                            src={imagePreview}
                                            alt="Aperçu produit"
                                            className="w-full h-full object-cover"
                                        />
                                    </div>
                                )}
                            </div>
                            {errors.image && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.image}
                                </p>
                            )}
                        </div>
                    </div>
                </div>

                <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={handleClose}
                        className="w-full sm:w-auto"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="w-full sm:w-auto"
                    >
                        {isEditing ? 'Mettre à jour' : 'Enregistrer'}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}

