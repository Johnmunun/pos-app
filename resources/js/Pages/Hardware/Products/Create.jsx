import React, { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Save, Package, Hash, Upload, X, Image as ImageIcon } from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';

const TYPE_UNITE_OPTIONS = ['PIECE', 'LOT', 'METRE', 'KG', 'LITRE', 'BOITE', 'CARTON', 'UNITE'];

export default function HardwareProductCreate({ categories = [] }) {
    const { props } = usePage();
    const currencies = props.shop?.currencies || [];
    const defaultCurrency = props.shop?.currency || (currencies.find(c => c.is_default)?.code || currencies[0]?.code || 'USD');
    const [imagePreview, setImagePreview] = useState(null);
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        product_code: '',
        description: '',
        category_id: '',
        price: '',
        currency: defaultCurrency,
        minimum_stock: 0,
        unit: 'UNITE',
        type_unite: 'UNITE',
        quantite_par_unite: 1,
        est_divisible: true,
        image: null,
        price_normal: '',
        price_reduced: '',
        price_reduction_percent: '',
        price_non_negotiable: '',
        price_wholesale_normal: '',
        price_wholesale_reduced: '',
        price_non_negotiable_1: '',
        price_non_negotiable_2: '',
        price_non_negotiable_3: '',
    });

    const handleGenerateCode = async () => {
        if (!data.name) {
            toast.error('Saisissez d\'abord le nom du produit.');
            return;
        }
        try {
            const res = await axios.get(route('hardware.products.generate-code'), { params: { name: data.name } });
            if (res.data?.code) setData('product_code', res.data.code);
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur');
        }
    };

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        
        // Vérifier la taille (max 2 Mo)
        if (file.size > 2 * 1024 * 1024) {
            toast.error('L\'image ne doit pas dépasser 2 Mo');
            return;
        }
        
        // Vérifier le type
        if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/)) {
            toast.error('Format d\'image non supporté. Utilisez JPG, PNG ou WebP.');
            return;
        }
        
        setData('image', file);
        
        // Prévisualisation
        const reader = new FileReader();
        reader.onloadend = () => {
            setImagePreview(reader.result);
        };
        reader.readAsDataURL(file);
    };

    const handleRemoveImage = () => {
        setData('image', null);
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

    React.useEffect(() => {
        if (data.price_reduction_percent && (data.price_normal || data.price)) {
            calculateReducedPrice();
        }
    }, [data.price_normal, data.price, data.price_reduction_percent]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setData('unit', data.type_unite);
        // Utiliser price_normal comme prix principal si fourni, sinon utiliser price
        if (!data.price_normal && data.price) {
            setData('price_normal', data.price);
        }
        post(route('hardware.products.store'), {
            forceFormData: true,
            onSuccess: () => {
                toast.success('Produit créé');
                router.visit(route('hardware.products'));
            },
            onError: (err) => toast.error(err?.message || 'Erreur'),
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center">
                    <Button variant="ghost" asChild className="mr-4">
                        <Link href={route('hardware.products')}>
                            <ArrowLeft className="h-4 w-4 mr-2" /> Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Nouveau produit — Quincaillerie
                    </h2>
                </div>
            }
        >
            <Head title="Créer un produit - Quincaillerie" />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Package className="h-5 w-5 mr-2" /> Informations produit
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nom *</Label>
                                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Nom du produit" required />
                                        {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="product_code">Code *</Label>
                                        <div className="flex gap-2">
                                            <div className="relative flex-1">
                                                <Hash className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                                <Input id="product_code" value={data.product_code} onChange={(e) => setData('product_code', e.target.value)} className="pl-10" placeholder="ex: VIS-001" required />
                                            </div>
                                            <Button type="button" variant="outline" size="sm" onClick={handleGenerateCode}>
                                                Générer
                                            </Button>
                                        </div>
                                        {errors.product_code && <p className="text-sm text-red-600">{errors.product_code}</p>}
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} placeholder="Description..." />
                                    {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                                </div>
                                
                                {/* Upload Image */}
                                <div className="space-y-2">
                                    <Label htmlFor="image">Image du produit</Label>
                                    <div className="flex items-center gap-4">
                                        {imagePreview ? (
                                            <div className="relative">
                                                <img src={imagePreview} alt="Preview" className="w-32 h-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600" />
                                                <button
                                                    type="button"
                                                    onClick={handleRemoveImage}
                                                    className="absolute -top-2 -right-2 bg-red-500 text-white rounded-full p-1 hover:bg-red-600"
                                                >
                                                    <X className="h-4 w-4" />
                                                </button>
                                            </div>
                                        ) : (
                                            <label
                                                htmlFor="image"
                                                className="flex flex-col items-center justify-center w-32 h-32 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:border-amber-500 transition-colors"
                                            >
                                                <ImageIcon className="h-8 w-8 text-gray-400 mb-2" />
                                                <span className="text-xs text-gray-500">Cliquer pour uploader</span>
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
                                            <p className="text-xs text-gray-500 mt-2">Formats acceptés : JPG, PNG, WebP (max 2 Mo)</p>
                                        </div>
                                    </div>
                                    {errors.image && <p className="text-sm text-red-600">{errors.image}</p>}
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
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
                                        {errors.category_id && <p className="text-sm text-red-600">{errors.category_id}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="type_unite">Unité *</Label>
                                        <select
                                            id="type_unite"
                                            value={data.type_unite}
                                            onChange={(e) => { const v = e.target.value; setData('type_unite', v); setData('unit', v); }}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-amber-500 focus:ring-amber-500"
                                        >
                                            {TYPE_UNITE_OPTIONS.map((u) => (
                                                <option key={u} value={u}>{u}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                                {/* Prix principal */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="price">Prix principal *</Label>
                                        <div className="flex gap-2">
                                            <Input id="price" type="number" step="0.01" min={0} value={data.price} onChange={(e) => { setData('price', e.target.value); if (!data.price_normal) setData('price_normal', e.target.value); }} placeholder="0.00" required />
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
                                        {errors.price && <p className="text-sm text-red-600">{errors.price}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="quantite_par_unite">Quantité par unité *</Label>
                                        <Input id="quantite_par_unite" type="number" min={1} value={data.quantite_par_unite} onChange={(e) => setData('quantite_par_unite', parseInt(e.target.value, 10) || 1)} />
                                        {errors.quantite_par_unite && <p className="text-sm text-red-600">{errors.quantite_par_unite}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="minimum_stock">Stock minimum *</Label>
                                        <Input id="minimum_stock" type="number" min={0} value={data.minimum_stock} onChange={(e) => setData('minimum_stock', parseInt(e.target.value, 10) || 0)} />
                                        {errors.minimum_stock && <p className="text-sm text-red-600">{errors.minimum_stock}</p>}
                                    </div>
                                </div>

                                {/* Prix détaillés */}
                                <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Gestion des prix</h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="price_normal">Prix normal</Label>
                                            <Input id="price_normal" type="number" step="0.01" min={0} value={data.price_normal} onChange={(e) => setData('price_normal', e.target.value)} placeholder="Prix normal" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="price_reduction_percent">Pourcentage de réduction (%)</Label>
                                            <Input id="price_reduction_percent" type="number" step="0.01" min={0} max={100} value={data.price_reduction_percent} onChange={(e) => { setData('price_reduction_percent', e.target.value); calculateReducedPrice(); }} placeholder="0.00" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="price_reduced">Prix réduit (calculé automatiquement)</Label>
                                            <Input id="price_reduced" type="number" step="0.01" min={0} value={data.price_reduced} onChange={(e) => setData('price_reduced', e.target.value)} placeholder="Prix réduit" readOnly={!!data.price_reduction_percent} />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="price_non_negotiable">Prix non discutable</Label>
                                            <Input id="price_non_negotiable" type="number" step="0.01" min={0} value={data.price_non_negotiable} onChange={(e) => setData('price_non_negotiable', e.target.value)} placeholder="Prix non discutable" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="price_wholesale_normal">Prix gros normal</Label>
                                            <Input id="price_wholesale_normal" type="number" step="0.01" min={0} value={data.price_wholesale_normal} onChange={(e) => setData('price_wholesale_normal', e.target.value)} placeholder="Prix gros normal" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="price_wholesale_reduced">Prix gros réduit</Label>
                                            <Input id="price_wholesale_reduced" type="number" step="0.01" min={0} value={data.price_wholesale_reduced} onChange={(e) => setData('price_wholesale_reduced', e.target.value)} placeholder="Prix gros réduit" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="price_non_negotiable_1">Prix non discutable 1</Label>
                                            <Input id="price_non_negotiable_1" type="number" step="0.01" min={0} value={data.price_non_negotiable_1} onChange={(e) => setData('price_non_negotiable_1', e.target.value)} placeholder="Prix non discutable 1" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="price_non_negotiable_2">Prix non discutable 2</Label>
                                            <Input id="price_non_negotiable_2" type="number" step="0.01" min={0} value={data.price_non_negotiable_2} onChange={(e) => setData('price_non_negotiable_2', e.target.value)} placeholder="Prix non discutable 2" />
                                        </div>
                                        <div className="space-y-2">
                                            <Label htmlFor="price_non_negotiable_3">Prix non discutable 3</Label>
                                            <Input id="price_non_negotiable_3" type="number" step="0.01" min={0} value={data.price_non_negotiable_3} onChange={(e) => setData('price_non_negotiable_3', e.target.value)} placeholder="Prix non discutable 3" />
                                        </div>
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="est_divisible"
                                        checked={data.est_divisible}
                                        onChange={(e) => setData('est_divisible', e.target.checked)}
                                        className="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                    />
                                    <Label htmlFor="est_divisible">Vente en fraction autorisée</Label>
                                </div>
                                <div className="flex gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <Button type="button" variant="outline" asChild>
                                        <Link href={route('hardware.products')}>Annuler</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing} className="bg-amber-500 hover:bg-amber-600 text-white">
                                        <Save className="h-4 w-4 mr-2" /> {processing ? 'Création...' : 'Créer le produit'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
