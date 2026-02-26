import React, { useState, useEffect } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { 
    Save,
    Package,
    Hash,
    Tag,
    DollarSign,
    Archive,
    Pill,
    X,
    Image as ImageIcon,
    Upload,
    Trash2,
    WifiOff,
    CloudUpload,
    Sparkles
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import offlineStorage from '@/lib/offlineStorage';
import imageCache from '@/lib/imageCache';
import syncService from '@/lib/syncService';

export default function ProductDrawer({ isOpen, onClose, product = null, categories = [], routePrefix = 'pharmacy' }) {
    const isEditing = !!product;
    const { shop } = usePage().props;
    const defaultCurrency = shop?.currency || 'CDF';
    
    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: product?.name || '',
        product_code: product?.product_code || '',
        description: product?.description || '',
        category_id: product?.category_id || '',
        price: product?.price_amount || '',
        currency: product?.price_currency || defaultCurrency,
        cost: product?.cost || '',
        minimum_stock: product?.minimum_stock || '',
        unit: product?.unit || '',
        medicine_type: product?.medicine_type || '',
        dosage: product?.dosage || '',
        prescription_required: product?.prescription_required || false,
        manufacturer: product?.manufacturer || '',
        wholesale_price: product?.wholesale_price_amount ?? '',
        wholesale_min_quantity: product?.wholesale_min_quantity ?? '',
        supplier_id: product?.supplier_id || '',
        image: null,
        image_url: product?.image_url || '',
        remove_image: false,
        type_unite: product?.type_unite || 'UNITE',
        quantite_par_unite: product?.quantite_par_unite ?? 1,
        est_divisible: product?.est_divisible !== false
    });

    const [isMedicine, setIsMedicine] = useState(!!product?.medicine_type);
    const [imagePreview, setImagePreview] = useState(product?.image_url || null);
    const [isGeneratingCode, setIsGeneratingCode] = useState(false);
    const [suppliers, setSuppliers] = useState([]);
    const [loadingSuppliers, setLoadingSuppliers] = useState(false);

    // Load suppliers dynamically
    useEffect(() => {
        const fetchSuppliers = async () => {
            setLoadingSuppliers(true);
            try {
                const response = await axios.get(route(`${routePrefix}.suppliers.active`));
                if (response.data.success) {
                    setSuppliers(response.data.suppliers || []);
                }
            } catch (error) {
                console.error('Error loading suppliers:', error);
            } finally {
                setLoadingSuppliers(false);
            }
        };
        
        if (isOpen) {
            fetchSuppliers();
        }
    }, [isOpen]);

    // Reset form when product changes
    useEffect(() => {
        if (product) {
            setData({
                name: product.name || '',
                product_code: product.product_code || '',
                description: product.description || '',
                category_id: product.category_id || '',
                price: product.price_amount || '',
                currency: product.price_currency || defaultCurrency,
                cost: product.cost || '',
                minimum_stock: product.minimum_stock || '',
                unit: product.unit || '',
                medicine_type: product.medicine_type || '',
                dosage: product.dosage || '',
                prescription_required: product.prescription_required || false,
                manufacturer: product.manufacturer || '',
                wholesale_price: product.wholesale_price_amount ?? '',
                wholesale_min_quantity: product.wholesale_min_quantity ?? '',
                supplier_id: product.supplier_id || '',
                image: null,
                image_url: product.image_url || '',
                remove_image: false,
                type_unite: product.type_unite || 'UNITE',
                quantite_par_unite: product.quantite_par_unite ?? 1,
                est_divisible: product.est_divisible !== false
            });
            setIsMedicine(!!product.medicine_type);
            
            // Charger l'image depuis le cache si disponible
            if (product.image_url) {
                imageCache.getImage(product.image_url).then(cachedUrl => {
                    setImagePreview(cachedUrl);
                }).catch(() => {
                    setImagePreview(product.image_url);
                });
            } else {
                setImagePreview(null);
            }
        } else {
            reset();
            setIsMedicine(false);
            setImagePreview(null);
        }
    }, [product]);

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            // Validation côté client
            const maxSize = 2 * 1024 * 1024; // 2 Mo
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            
            if (file.size > maxSize) {
                toast.error('L\'image ne doit pas dépasser 2 Mo');
                return;
            }
            
            if (!allowedTypes.includes(file.type)) {
                toast.error('Format d\'image non supporté. Utilisez JPG, PNG ou WebP');
                return;
            }

            setData('image', file);
            setData('remove_image', false);
            
            // Preview immédiat
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleRemoveImage = () => {
        setData('image', null);
        setData('image_url', '');
        setData('remove_image', true);
        setImagePreview(null);
    };

    const handleGenerateCode = async () => {
        if (!data.name) {
            toast.error('Veuillez d\'abord saisir le nom du produit.');
            return;
        }

        if (!navigator.onLine) {
            toast.error('La génération automatique du code nécessite une connexion internet.');
            return;
        }

        try {
            setIsGeneratingCode(true);
            const response = await axios.get(route(`${routePrefix}.products.generate-code`), {
                params: { name: data.name },
            });

            if (response.data?.code) {
                setData('product_code', response.data.code);
                toast.success('Code produit généré automatiquement.');
            } else {
                toast.error('Impossible de générer un code produit. Réessayez.');
            }
        } catch (error) {
            console.error(error);
            toast.error(error.response?.data?.message || 'Erreur lors de la génération du code produit.');
        } finally {
            setIsGeneratingCode(false);
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        const isOnline = navigator.onLine;
        
        // Préparer les données du produit
        const productData = {
            name: data.name,
            product_code: data.product_code,
            description: data.description,
            category_id: data.category_id,
            price: data.price,
            currency: data.currency,
            cost: data.cost,
            minimum_stock: data.minimum_stock,
            unit: data.unit,
            medicine_type: data.medicine_type,
            dosage: data.dosage,
            prescription_required: data.prescription_required,
            manufacturer: data.manufacturer,
            wholesale_price: data.wholesale_price || null,
            wholesale_min_quantity: data.wholesale_min_quantity || null,
            supplier_id: data.supplier_id,
            type_unite: data.type_unite || 'UNITE',
            quantite_par_unite: data.quantite_par_unite ?? 1,
            est_divisible: data.est_divisible
        };

        if (isOnline) {
            // Mode en ligne : envoyer directement au backend
            const formData = new FormData();
            Object.keys(productData).forEach(key => {
                const value = productData[key];
                if (value !== null && value !== undefined) {
                    // Convertir les booléens en "1" ou "0" pour FormData
                    if (typeof value === 'boolean') {
                        formData.append(key, value ? '1' : '0');
                    } else {
                        formData.append(key, value);
                    }
                }
            });

            if (data.image) {
                formData.append('image', data.image);
            }

            if (data.remove_image) {
                formData.append('remove_image', '1');
            }

            try {
                if (isEditing) {
                    // Utiliser POST avec _method=PUT pour le method spoofing Laravel
                    // car PHP ne lit pas les fichiers multipart dans les requêtes PUT
                    formData.append('_method', 'PUT');
                    await axios.post(route(`${routePrefix}.products.update`, product.id), formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                    });
                    toast.success('Produit mis à jour avec succès');
                } else {
                    await axios.post(route(`${routePrefix}.products.store`), formData, {
                        headers: {
                            'Content-Type': 'multipart/form-data',
                        },
                    });
                    toast.success('Produit créé avec succès');
                }

                // Mettre en cache l'image si elle existe
                if (data.image && imagePreview) {
                    // L'image sera mise en cache automatiquement par le service
                    await imageCache.cacheImage(imagePreview);
                }

                onClose();
                reset();
                setImagePreview(null);
                window.location.reload();
            } catch (error) {
                toast.error(error.response?.data?.message || 'Erreur lors de l\'enregistrement');
                console.error(error);
            }
        } else {
            // Mode offline : stocker localement
            try {
                const productId = isEditing ? product.id : null;
                const savedId = await offlineStorage.savePendingProduct(
                    { ...productData, id: productId },
                    data.image
                );

                toast.success('Produit enregistré localement. Synchronisation automatique à la reconnexion.', {
                    icon: <WifiOff className="h-5 w-5" />,
                    duration: 5000
                });

                onClose();
                reset();
                setImagePreview(null);
                window.location.reload();
            } catch (error) {
                toast.error('Erreur lors de l\'enregistrement local');
                console.error(error);
            }
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
                {/* Basic Information */}
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <Package className="h-5 w-5 mr-2" />
                        Informations de base
                    </h3>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nom du produit *</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                placeholder="Nom du produit"
                                className="w-full"
                            />
                            {errors.name && <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="product_code">Code produit *</Label>
                            <div className="flex items-center gap-2">
                                <div className="relative flex-1">
                                    <Hash className="absolute left-3 top-3 h-4 w-4 text-gray-400 dark:text-gray-500" />
                                    <Input
                                        id="product_code"
                                        value={data.product_code}
                                        onChange={(e) => setData('product_code', e.target.value)}
                                        placeholder="PROD-001"
                                        className="pl-10 w-full"
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={handleGenerateCode}
                                    disabled={isGeneratingCode}
                                    className="whitespace-nowrap"
                                >
                                    <Sparkles className="h-4 w-4 mr-1" />
                                    {isGeneratingCode ? 'Génération...' : 'Auto'}
                                </Button>
                            </div>
                            {errors.product_code && <p className="text-sm text-red-600 dark:text-red-400">{errors.product_code}</p>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="description">Description</Label>
                        <Textarea
                            id="description"
                            value={data.description}
                            onChange={(e) => setData('description', e.target.value)}
                            placeholder="Description du produit..."
                            rows={3}
                            className="w-full"
                        />
                        {errors.description && <p className="text-sm text-red-600 dark:text-red-400">{errors.description}</p>}
                    </div>

                    {/* Image Upload */}
                    <div className="space-y-2">
                        <Label htmlFor="image">Image du produit</Label>
                        <div className="space-y-3">
                            {/* Preview */}
                            {imagePreview && (
                                <div className="relative inline-block">
                                    <img
                                        src={imagePreview}
                                        alt="Preview"
                                        className="h-32 w-32 object-cover rounded-lg border-2 border-gray-300 dark:border-gray-600"
                                    />
                                    <button
                                        type="button"
                                        onClick={handleRemoveImage}
                                        className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600 transition-colors"
                                        aria-label="Supprimer l'image"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                            )}
                            
                            {/* Upload Button */}
                            {!imagePreview && (
                                <label
                                    htmlFor="image"
                                    className="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors"
                                >
                                    <div className="flex flex-col items-center justify-center pt-5 pb-6">
                                        <Upload className="w-8 h-8 mb-2 text-gray-400 dark:text-gray-500" />
                                        <p className="mb-2 text-sm text-gray-500 dark:text-gray-400">
                                            <span className="font-semibold">Cliquez pour uploader</span> ou glissez-déposez
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">JPG, PNG ou WebP (MAX. 2 Mo)</p>
                                    </div>
                                    <input
                                        id="image"
                                        type="file"
                                        accept="image/jpeg,image/jpg,image/png,image/webp"
                                        className="hidden"
                                        onChange={handleImageChange}
                                    />
                                </label>
                            )}
                            
                            {/* Replace Image Button */}
                            {imagePreview && (
                                <label
                                    htmlFor="image"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer transition-colors"
                                >
                                    <ImageIcon className="h-4 w-4 mr-2" />
                                    Remplacer l'image
                                    <input
                                        id="image"
                                        type="file"
                                        accept="image/jpeg,image/jpg,image/png,image/webp"
                                        className="hidden"
                                        onChange={handleImageChange}
                                    />
                                </label>
                            )}
                        </div>
                        {errors.image && <p className="text-sm text-red-600 dark:text-red-400">{errors.image}</p>}
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="category_id">Catégorie *</Label>
                            <div className="relative">
                                <Tag className="absolute left-3 top-3 h-4 w-4 text-gray-400 dark:text-gray-500" />
                                <select
                                    id="category_id"
                                    value={data.category_id}
                                    onChange={(e) => setData('category_id', e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Sélectionner une catégorie</option>
                                    {categories.map(category => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                            {errors.category_id && <p className="text-sm text-red-600 dark:text-red-400">{errors.category_id}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="unit">Unité *</Label>
                            <Input
                                id="unit"
                                value={data.unit}
                                onChange={(e) => setData('unit', e.target.value)}
                                placeholder="ex: bouteille, boîte, comprimé"
                                className="w-full"
                            />
                            {errors.unit && <p className="text-sm text-red-600 dark:text-red-400">{errors.unit}</p>}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="type_unite">Type d'unité *</Label>
                            <select
                                id="type_unite"
                                value={data.type_unite}
                                onChange={(e) => setData('type_unite', e.target.value)}
                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="PLAQUETTE">Plaquette</option>
                                <option value="BOITE">Boîte</option>
                                <option value="FLACON">Flacon</option>
                                <option value="TUBE">Tube</option>
                                <option value="SACHET">Sachet</option>
                                <option value="UNITE">Unité</option>
                            </select>
                            {errors.type_unite && <p className="text-sm text-red-600 dark:text-red-400">{errors.type_unite}</p>}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="quantite_par_unite">Quantité par unité *</Label>
                            <Input
                                id="quantite_par_unite"
                                type="number"
                                min={1}
                                value={data.quantite_par_unite}
                                onChange={(e) => setData('quantite_par_unite', parseInt(e.target.value, 10) || 1)}
                                placeholder="ex: 10"
                                className="w-full"
                            />
                            <p className="text-xs text-gray-500 dark:text-gray-400">ex: 10 comprimés par plaquette</p>
                            {errors.quantite_par_unite && <p className="text-sm text-red-600 dark:text-red-400">{errors.quantite_par_unite}</p>}
                        </div>
                        <div className="space-y-2 flex flex-col justify-end">
                            <div className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    id="est_divisible"
                                    checked={data.est_divisible}
                                    onChange={(e) => setData('est_divisible', e.target.checked)}
                                    className="rounded border-gray-300 dark:border-gray-600"
                                />
                                <Label htmlFor="est_divisible" className="cursor-pointer">Vente en fraction autorisée</Label>
                            </div>
                            <p className="text-xs text-gray-500 dark:text-gray-400">Désactiver pour boîte/flacon (qté entière uniquement)</p>
                            {errors.est_divisible && <p className="text-sm text-red-600 dark:text-red-400">{errors.est_divisible}</p>}
                        </div>
                    </div>
                </div>

                {/* Pricing */}
                <div className="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <DollarSign className="h-5 w-5 mr-2" />
                        Informations de prix
                    </h3>
                    
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="price">Prix de vente *</Label>
                            <Input
                                id="price"
                                type="number"
                                step="0.01"
                                value={data.price}
                                onChange={(e) => setData('price', e.target.value)}
                                placeholder="0.00"
                                className="w-full"
                            />
                            {errors.price && <p className="text-sm text-red-600 dark:text-red-400">{errors.price}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="cost">Prix de revient</Label>
                            <Input
                                id="cost"
                                type="number"
                                step="0.01"
                                value={data.cost}
                                onChange={(e) => setData('cost', e.target.value)}
                                placeholder="0.00"
                                className="w-full"
                            />
                            {errors.cost && <p className="text-sm text-red-600 dark:text-red-400">{errors.cost}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="currency">Devise *</Label>
                            <select
                                id="currency"
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value)}
                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                {shop?.currencies && shop.currencies.length > 0 ? (
                                    shop.currencies.map(c => (
                                        <option key={c.code} value={c.code}>{c.code} - {c.name}</option>
                                    ))
                                ) : (
                                    <>
                                        <option value="CDF">CDF - Franc Congolais</option>
                                        <option value="USD">USD - Dollar US</option>
                                        <option value="EUR">EUR - Euro</option>
                                    </>
                                )}
                            </select>
                            {errors.currency && <p className="text-sm text-red-600 dark:text-red-400">{errors.currency}</p>}
                        </div>
                    </div>
                </div>

                {/* Prix gros (optionnel) */}
                <div className="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <DollarSign className="h-5 w-5 mr-2" />
                        Vente en gros
                    </h3>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="wholesale_price">Prix gros (optionnel)</Label>
                            <Input
                                id="wholesale_price"
                                type="number"
                                min="0"
                                step="0.01"
                                value={data.wholesale_price}
                                onChange={(e) => setData('wholesale_price', e.target.value)}
                                placeholder="Prix unitaire en gros"
                                className="w-full"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="wholesale_min_quantity">Qté min. pour prix gros</Label>
                            <Input
                                id="wholesale_min_quantity"
                                type="number"
                                min="0"
                                value={data.wholesale_min_quantity}
                                onChange={(e) => setData('wholesale_min_quantity', e.target.value)}
                                placeholder="Ex: 10"
                                className="w-full"
                            />
                        </div>
                    </div>
                </div>

                {/* Inventory */}
                <div className="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                        <Archive className="h-5 w-5 mr-2" />
                        Paramètres de stock
                    </h3>
                    
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="minimum_stock">Stock minimum *</Label>
                            <Input
                                id="minimum_stock"
                                type="number"
                                value={data.minimum_stock}
                                onChange={(e) => setData('minimum_stock', e.target.value)}
                                placeholder="10"
                                className="w-full"
                            />
                            {errors.minimum_stock && <p className="text-sm text-red-600 dark:text-red-400">{errors.minimum_stock}</p>}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="manufacturer">Fabricant</Label>
                            <Input
                                id="manufacturer"
                                value={data.manufacturer}
                                onChange={(e) => setData('manufacturer', e.target.value)}
                                placeholder="Nom du fabricant"
                                className="w-full"
                            />
                            {errors.manufacturer && <p className="text-sm text-red-600 dark:text-red-400">{errors.manufacturer}</p>}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="supplier_id">Fournisseur</Label>
                        <select
                            id="supplier_id"
                            value={data.supplier_id}
                            onChange={(e) => setData('supplier_id', e.target.value)}
                            disabled={loadingSuppliers}
                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">{loadingSuppliers ? 'Chargement...' : 'Sélectionner un fournisseur'}</option>
                            {suppliers.map((supplier) => (
                                <option key={supplier.id} value={supplier.id}>
                                    {supplier.name}
                                </option>
                            ))}
                        </select>
                        {errors.supplier_id && <p className="text-sm text-red-600 dark:text-red-400">{errors.supplier_id}</p>}
                    </div>
                </div>

                {/* Medicine Specific Fields */}
                <div className="space-y-4 border-t border-gray-200 dark:border-gray-700 pt-6">
                    <div className="flex items-center justify-between">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                            <Pill className="h-5 w-5 mr-2" />
                            Informations médicament
                        </h3>
                        <label className="flex items-center">
                            <input
                                type="checkbox"
                                checked={isMedicine}
                                onChange={(e) => setIsMedicine(e.target.checked)}
                                className="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <span className="ml-2 text-sm text-gray-600 dark:text-gray-400">C'est un médicament</span>
                        </label>
                    </div>

                    {isMedicine && (
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <div className="space-y-2">
                                <Label htmlFor="medicine_type">Type de médicament</Label>
                                <select
                                    id="medicine_type"
                                    value={data.medicine_type}
                                    onChange={(e) => setData('medicine_type', e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="">Sélectionner un type</option>
                                    <option value="MEDICINE">Médicament</option>
                                    <option value="PARAPHARMACY">Parapharmacie</option>
                                    <option value="DEVICE">Dispositif médical</option>
                                    <option value="VACCINE">Vaccin</option>
                                    <option value="NUTRITION">Nutrition</option>
                                </select>
                                {errors.medicine_type && <p className="text-sm text-red-600 dark:text-red-400">{errors.medicine_type}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="dosage">Dosage</Label>
                                <Input
                                    id="dosage"
                                    value={data.dosage}
                                    onChange={(e) => setData('dosage', e.target.value)}
                                    placeholder="ex: 500mg, 10ml"
                                    className="w-full"
                                />
                                {errors.dosage && <p className="text-sm text-red-600 dark:text-red-400">{errors.dosage}</p>}
                            </div>

                            <div className="space-y-2 md:col-span-2">
                                <div className="flex items-center">
                                    <input
                                        type="checkbox"
                                        id="prescription_required"
                                        checked={data.prescription_required}
                                        onChange={(e) => setData('prescription_required', e.target.checked)}
                                        className="rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    />
                                    <Label htmlFor="prescription_required" className="ml-2">
                                        Prescription requise
                                    </Label>
                                </div>
                                {errors.prescription_required && <p className="text-sm text-red-600 dark:text-red-400">{errors.prescription_required}</p>}
                            </div>
                        </div>
                    )}
                </div>

                {/* Form Actions */}
                <div className="flex flex-col sm:flex-row justify-end gap-3 sm:gap-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button
                        type="button"
                        onClick={handleClose}
                        disabled={processing}
                        className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 px-4 py-2 h-10 w-full sm:w-auto"
                    >
                        <X className="h-4 w-4 mr-2" />
                        Annuler
                    </button>
                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 px-4 py-2 h-10 shadow-sm hover:shadow-md w-full sm:w-auto"
                    >
                        <Save className="h-4 w-4 mr-2" />
                        <span className="hidden sm:inline">
                            {processing ? (isEditing ? 'Mise à jour...' : 'Création...') : (isEditing ? 'Enregistrer' : 'Créer')}
                        </span>
                        <span className="sm:hidden">
                            {processing ? '...' : (isEditing ? 'Enregistrer' : 'Créer')}
                        </span>
                    </button>
                </div>
            </form>
        </Drawer>
    );
}
