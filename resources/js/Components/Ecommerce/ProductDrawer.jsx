import React, { useEffect, useState } from 'react';
import { useForm, usePage, router } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Package, Hash, Image as ImageIcon, Trash2, RefreshCw } from 'lucide-react';
import { toast } from 'react-hot-toast';
import RichTextEditor from '@/Components/RichTextEditor';

export default function EcommerceProductDrawer({ isOpen, onClose, product = null, categories = [] }) {
    const isEditing = !!product;
    const { props } = usePage();
    const shopCurrency = props.shop?.currency || 'USD';
    const shop = props.shop || {};

    const [imagePreview, setImagePreview] = useState(product?.image_url || null);
    const [galleryPreviews, setGalleryPreviews] = useState(product?.gallery_urls || []);

    const { data, setData, post, processing, errors, reset } = useForm({
        _method: isEditing ? 'put' : undefined,
        sku: product?.sku || '',
        barcode: product?.barcode || '',
        name: product?.name || '',
        description: product?.description || '',
        category_id: product?.category_id || '',
        purchase_price: product?.purchase_price ?? '',
        sale_price: product?.sale_price ?? product?.sale_price_amount ?? '',
        initial_stock: isEditing && product?.stock != null ? String(product.stock) : '',
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
        unit: product?.unit ?? 'PIECE',
        weight: product?.weight ?? '',
        length: product?.length ?? '',
        width: product?.width ?? '',
        height: product?.height ?? '',
        tax_rate: product?.tax_rate ?? '',
        tax_type: product?.tax_type ?? '',
        status: product?.status ?? (product?.is_active ? 'active' : 'inactive'),
        image: null,
        remove_image: false,
        gallery: [],
        remove_gallery: false,
        download_url: product?.download_url ?? '',
        download_file: null,
        remove_download: false,
        requires_shipping: product?.requires_shipping ?? true,
        couleur: product?.couleur ?? '',
        taille: product?.taille ?? '',
        type_produit: product?.type_produit ?? 'physique',
        mode_paiement: product?.mode_paiement ?? 'paiement_immediat',
        lien_telechargement: product?.lien_telechargement ?? product?.download_url ?? '',
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
                initial_stock: product.stock != null ? String(product.stock) : '',
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
                gallery: [],
                remove_gallery: false,
                download_url: product.download_url ?? '',
                download_file: null,
                remove_download: false,
                requires_shipping: product.requires_shipping ?? true,
                couleur: product.couleur ?? '',
                taille: product.taille ?? '',
                type_produit: product.type_produit ?? 'physique',
                mode_paiement: product.mode_paiement ?? 'paiement_immediat',
                lien_telechargement: product.lien_telechargement ?? product.download_url ?? '',
            }));
            setImagePreview(product.image_url || null);
            setGalleryPreviews(product.gallery_urls || []);
        } else if (isOpen && !product) {
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
                unit: 'PIECE',
                weight: '',
                length: '',
                width: '',
                height: '',
                tax_rate: '',
                tax_type: '',
                status: 'active',
                image: null,
                remove_image: false,
                gallery: [],
                remove_gallery: false,
                download_url: '',
                download_file: null,
                remove_download: false,
                requires_shipping: true,
                couleur: '',
                taille: '',
                type_produit: 'physique',
                mode_paiement: 'paiement_immediat',
                lien_telechargement: '',
            }));
            setImagePreview(null);
            setGalleryPreviews([]);
        } else if (!isOpen) {
            reset();
            setImagePreview(null);
            setGalleryPreviews([]);
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

    const handleGalleryChange = (e) => {
        const files = Array.from(e.target.files || []).slice(0, 3);
        if (!files.length) return;

        const validFiles = [];
        const previews = [];

        for (const file of files) {
            if (file.size > 2 * 1024 * 1024) {
                toast.error("Chaque image de la galerie ne doit pas dépasser 2 Mo");
                continue;
            }
            if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/)) {
                toast.error('Format d’image non supporté pour la galerie. Utilisez JPG, PNG ou WebP.');
                continue;
            }
            validFiles.push(file);
        }

        if (!validFiles.length) return;

        setData('gallery', validFiles);
        setData('remove_gallery', false);

        validFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onloadend = () => {
                setGalleryPreviews((prev) => {
                    const copy = [...prev];
                    copy[index] = reader.result;
                    return copy.slice(0, 3);
                });
            };
            reader.readAsDataURL(file);
        });
    };

    const handleClearGallery = () => {
        setData('gallery', []);
        setData('remove_gallery', true);
        setGalleryPreviews([]);
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

        const random = Math.floor(100 + Math.random() * 900);
        const code = `${base || 'PROD'}-${random}`;

        setData('sku', code);
        toast.success('Code produit généré automatiquement.');
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        const options = {
            forceFormData: true,
            preserveScroll: false,
            onSuccess: () => {
                toast.success(isEditing ? 'Produit mis à jour' : 'Produit créé');
                reset();
                onClose();
                router.reload();
            },
            onError: (errs) => {
                const firstError =
                    errs?.message ||
                    (errs && typeof errs === 'object' ? Object.values(errs)[0] : null);
                if (firstError) {
                    toast.error(String(firstError));
                } else {
                    toast.error(isEditing ? 'Erreur lors de la mise à jour du produit.' : 'Erreur lors de la création du produit.');
                }
            },
        };

        if (isEditing && product?.id) {
            post(route('ecommerce.products.update', product.id), options);
        } else {
            post(route('ecommerce.products.store'), options);
        }
    };

    const handleClose = () => {
        reset();
        onClose();
    };

    const categoryOptions = categories || [];

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
                            <Label htmlFor="sku" className="flex items-center justify-between">
                                <span>Code (SKU) *</span>
                                <button
                                    type="button"
                                    onClick={handleGenerateSku}
                                    className="inline-flex items-center gap-1 text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                >
                                    <RefreshCw className="h-3 w-3" />
                                    Générer
                                </button>
                            </Label>
                            <div className="flex gap-2">
                                <Input
                                    id="sku"
                                    value={data.sku}
                                    onChange={(e) => setData('sku', e.target.value)}
                                    placeholder="SKU-001"
                                    className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                                    required
                                />
                            </div>
                            {errors.sku && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.sku}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <RichTextEditor
                            value={data.description || ''}
                            onChange={(value) => setData('description', value)}
                            placeholder="Décrivez votre produit (caractéristiques, avantages, etc.)"
                        />
                        {errors.description && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.description}
                            </p>
                        )}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="category_id">Catégorie *</Label>
                            <select
                                id="category_id"
                                value={data.category_id}
                                onChange={(e) => setData('category_id', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm"
                                required
                            >
                                <option value="">— Sélectionnez —</option>
                                {categoryOptions.map((c) => (
                                    <option key={c.id} value={c.id}>
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
                                <option value="M">Mètre (M)</option>
                                <option value="M2">Mètre carré (M²)</option>
                                <option value="CANETTE">Canette</option>
                            </select>
                            {errors.unit && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.unit}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="purchase_price">Prix d&apos;achat</Label>
                            <Input
                                id="purchase_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.purchase_price}
                                onChange={(e) => setData('purchase_price', e.target.value)}
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
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
                                min="0"
                                step="0.01"
                                value={data.sale_price}
                                onChange={(e) => setData('sale_price', e.target.value)}
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                                required
                            />
                            {errors.sale_price && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.sale_price}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="initial_stock">
                                {isEditing ? 'Stock actuel' : 'Stock initial'}
                            </Label>
                            <Input
                                id="initial_stock"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.initial_stock}
                                onChange={(e) => setData('initial_stock', e.target.value)}
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            {errors.initial_stock && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.initial_stock}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="minimum_stock">Stock minimum</Label>
                            <Input
                                id="minimum_stock"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.minimum_stock}
                                onChange={(e) => setData('minimum_stock', e.target.value)}
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            {errors.minimum_stock && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.minimum_stock}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="currency">Devise</Label>
                            <select
                                id="currency"
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm"
                            >
                                <option value="USD">USD</option>
                                <option value="EUR">EUR</option>
                                <option value="CDF">CDF</option>
                            </select>
                            {errors.currency && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.currency}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="couleur">Couleur</Label>
                            <Input
                                id="couleur"
                                value={data.couleur}
                                onChange={(e) => setData('couleur', e.target.value)}
                                placeholder="ex: Rouge, Bleu"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            {errors.couleur && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.couleur}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="taille">Taille</Label>
                            <Input
                                id="taille"
                                value={data.taille}
                                onChange={(e) => setData('taille', e.target.value)}
                                placeholder="ex: S, M, L, XL"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            {errors.taille && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.taille}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="type_produit">Type de produit</Label>
                            <select
                                id="type_produit"
                                value={data.type_produit}
                                onChange={(e) => {
                                    const val = e.target.value;
                                    setData('type_produit', val);
                                    if (val === 'numerique') {
                                        setData('requires_shipping', false);
                                        setData('lien_telechargement', data.lien_telechargement || '');
                                    }
                                }}
                                className="mt-1 block w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm"
                            >
                                <option value="physique">Physique (livraison nécessaire)</option>
                                <option value="numerique">Numérique (lien de téléchargement)</option>
                            </select>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                {data.type_produit === 'numerique'
                                    ? 'Aucune livraison. L\'utilisateur reçoit un lien après paiement.'
                                    : 'Livraison nécessaire, processus de commande normal.'}
                            </p>
                            {errors.type_produit && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.type_produit}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="mode_paiement">Mode de paiement</Label>
                            <select
                                id="mode_paiement"
                                value={data.mode_paiement}
                                onChange={(e) => setData('mode_paiement', e.target.value)}
                                className="mt-1 block w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm"
                            >
                                <option value="paiement_immediat">Paiement immédiat</option>
                                <option value="paiement_livraison">Paiement à la livraison</option>
                            </select>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                {data.mode_paiement === 'paiement_livraison'
                                    ? 'Le client peut commander sans payer immédiatement.'
                                    : 'Le client doit payer avant la validation de la commande.'}
                            </p>
                            {errors.mode_paiement && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.mode_paiement}
                                </p>
                            )}
                        </div>
                    </div>

                    {data.type_produit === 'numerique' && (
                        <div className="space-y-2">
                            <Label htmlFor="lien_telechargement">Lien de téléchargement *</Label>
                            <Input
                                id="lien_telechargement"
                                type="url"
                                value={data.lien_telechargement}
                                onChange={(e) => setData('lien_telechargement', e.target.value)}
                                placeholder="https://example.com/fichier.pdf"
                                className="bg-white dark:bg-slate-800 text-gray-900 dark:text-white"
                            />
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                URL du fichier à télécharger après paiement.
                            </p>
                            {errors.lien_telechargement && (
                                <p className="text-sm text-red-600 dark:text-red-400">
                                    {errors.lien_telechargement}
                                </p>
                            )}
                        </div>
                    )}

                    <div className="space-y-2">
                        <Label>Image du produit</Label>
                        <div className="flex items-center gap-4">
                            <div className="h-20 w-20 rounded-lg border border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-slate-800">
                                {imagePreview ? (
                                    <img
                                        src={imagePreview}
                                        alt="Aperçu"
                                        className="h-full w-full object-cover"
                                    />
                                ) : (
                                    <ImageIcon className="h-8 w-8 text-gray-400" />
                                )}
                            </div>
                            <div className="space-y-2">
                                <Input
                                    type="file"
                                    accept="image/jpeg,image/jpg,image/png,image/webp"
                                    onChange={handleImageChange}
                                />
                                {imagePreview && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={handleRemoveImage}
                                        className="inline-flex items-center gap-2"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                        <span>Supprimer l&apos;image</span>
                                    </Button>
                                )}
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    JPG, PNG ou WebP, maximum 2 Mo.
                                </p>
                            </div>
                        </div>
                        {errors.image && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.image}
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label>Galerie (3 images max)</Label>
                        <div className="flex items-center gap-3">
                            {[0, 1, 2].map((idx) => (
                                <div
                                    key={idx}
                                    className="h-16 w-16 rounded-lg border border-dashed border-gray-300 dark:border-slate-600 flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-slate-800 group"
                                >
                                    {galleryPreviews[idx] ? (
                                        <img
                                            src={galleryPreviews[idx]}
                                            alt={`Aperçu ${idx + 1}`}
                                            className="h-full w-full object-cover transform transition-transform duration-200 group-hover:scale-110"
                                        />
                                    ) : (
                                        <ImageIcon className="h-6 w-6 text-gray-400" />
                                    )}
                                </div>
                            ))}
                        </div>
                        <div className="flex flex-wrap items-center gap-2 mt-2">
                            <Input
                                type="file"
                                multiple
                                accept="image/jpeg,image/jpg,image/png,image/webp"
                                onChange={handleGalleryChange}
                                className="max-w-xs"
                            />
                            {galleryPreviews.length > 0 && (
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleClearGallery}
                                    className="inline-flex items-center gap-2"
                                >
                                    <Trash2 className="h-4 w-4" />
                                    <span>Supprimer la galerie</span>
                                </Button>
                            )}
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Jusqu&apos;à 3 images supplémentaires, JPG, PNG ou WebP, maximum 2 Mo chacune.
                        </p>
                        {errors.gallery && (
                            <p className="text-sm text-red-600 dark:text-red-400">
                                {errors.gallery}
                            </p>
                        )}
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
                        {processing ? 'Enregistrement...' : (isEditing ? 'Enregistrer' : 'Créer')}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}

