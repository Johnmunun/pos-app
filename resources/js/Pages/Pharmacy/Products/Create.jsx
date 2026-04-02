import React, { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { 
  ArrowLeft, 
  Save,
  Package,
  Hash,
  FileText,
  Tag,
  DollarSign,
  Archive,
  Pill,
  Info,
  Image as ImageIcon,
  X,
  Sparkles
} from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';
import axios from 'axios';

export default function ProductCreate({ auth, categories, routePrefix = 'pharmacy' }) {
    const { toast } = useToast();
    const { shop, auth: pageAuth } = usePage().props;
    const canAiGenerate = pageAuth?.planFeatures?.ai_product_image_generate === true;
    const canAiSeoGenerate = pageAuth?.planFeatures?.ai_product_seo_generate === true;
    const defaultCurrency = shop?.currency || 'CDF';
    
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        product_code: '',
        description: '',
        category_id: '',
        price: '',
        currency: defaultCurrency,
        cost: '',
        minimum_stock: '',
        unit: '',
        medicine_type: '',
        dosage: '',
        prescription_required: false,
        manufacturer: '',
        supplier_id: '',
        type_unite: 'UNITE',
        quantite_par_unite: 1,
        est_divisible: true,
        image: null,
        meta_title: '',
        meta_description: '',
        slug: '',
    });

    const [isMedicine, setIsMedicine] = useState(false);
    const [imagePreview, setImagePreview] = useState(null);
    const [aiImageCandidates, setAiImageCandidates] = useState([]);
    const [aiGenerating, setAiGenerating] = useState(false);
    const [aiSeoGenerating, setAiSeoGenerating] = useState(false);
    const [seoSuggestion, setSeoSuggestion] = useState({ meta_title: '', meta_description: '', slug: '' });

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        if (!file.type.match(/^image\/(jpeg|jpg|png|webp)$/)) {
            toast({ title: 'Erreur', description: 'Format image invalide (JPG/PNG/WebP).', variant: 'destructive' });
            return;
        }
        setData('image', file);
        setAiImageCandidates([]);
        const reader = new FileReader();
        reader.onloadend = () => setImagePreview(reader.result);
        reader.readAsDataURL(file);
    };

    const handleSelectAiCandidate = async (candidate) => {
        if (!candidate?.image_data_url) return;
        const blob = await (await fetch(candidate.image_data_url)).blob();
        const file = new File([blob], candidate.file_name || 'ai-product.png', { type: blob.type || 'image/png' });
        setData('image', file);
        setImagePreview(candidate.image_data_url);
    };

    const handleGenerateImage = async () => {
        if (!canAiGenerate) return;
        if (!String(data.name || '').trim()) {
            toast({ title: 'Erreur', description: 'Saisissez d’abord le nom du produit.', variant: 'destructive' });
            return;
        }
        setAiGenerating(true);
        try {
            const res = await axios.post(route(`${routePrefix}.products.ai.generate-image`), {
                title: data.name,
                description: data.description || '',
                count: 4,
                async: true,
            });
            const requestId = res.data?.request_id;
            if (!requestId) throw new Error('Requête IA invalide');
            const pollStatus = async () => {
                for (let i = 0; i < 60; i += 1) {
                    const statusRes = await axios.get(route(`${routePrefix}.products.ai.generate-image.status`, requestId));
                    const st = String(statusRes.data?.status || '').toLowerCase();
                    if (st === 'completed') return statusRes.data;
                    if (st === 'failed') throw new Error(statusRes.data?.error_message || 'Génération IA échouée.');
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                }
                throw new Error('Génération IA trop longue, veuillez réessayer.');
            };
            const done = await pollStatus();
            const images = Array.isArray(done?.images) && done.images.length > 0
                ? done.images
                : [{ image_data_url: done?.image_data_url, file_name: done?.file_name || 'ai-product.png' }];
            const first = images.find((img) => !!img?.image_data_url);
            if (!first?.image_data_url) {
                throw new Error('Réponse IA invalide');
            }
            setAiImageCandidates(images.slice(0, 4));
            const blob = await (await fetch(first.image_data_url)).blob();
            const file = new File([blob], first.file_name || 'ai-product-1.png', { type: blob.type || 'image/png' });
            setData('image', file);
            setImagePreview(first.image_data_url);
            toast({ title: 'Succès', description: `${Math.min(images.length, 4)} image(s) IA générée(s).` });
        } catch (error) {
            toast({
                title: 'Erreur',
                description: error.response?.data?.message || error.message || 'Génération IA impossible.',
                variant: 'destructive',
            });
        } finally {
            setAiGenerating(false);
        }
    };

    const handleGenerateSeo = async () => {
        if (!canAiSeoGenerate) return;
        if (!String(data.name || '').trim()) {
            toast({ title: 'Erreur', description: 'Saisissez d’abord le nom du produit.', variant: 'destructive' });
            return;
        }
        setAiSeoGenerating(true);
        try {
            const res = await axios.post(route(`${routePrefix}.products.ai.generate-seo`), {
                name: data.name,
                description: data.description || '',
                language: 'fr',
            });
            const seo = res.data?.seo || {};
            setData('meta_title', seo.meta_title || '');
            setData('meta_description', seo.meta_description || '');
            setData('slug', seo.slug || '');
            setSeoSuggestion({
                meta_title: seo.meta_title || '',
                meta_description: seo.meta_description || '',
                slug: seo.slug || '',
            });
            toast({ title: 'Succès', description: 'SEO généré.' });
        } catch (error) {
            toast({
                title: 'Erreur',
                description: error.response?.data?.message || error.message || 'Génération SEO IA impossible.',
                variant: 'destructive',
            });
        } finally {
            setAiSeoGenerating(false);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route(`${routePrefix}.products.store`), {
            forceFormData: true,
            onSuccess: () => {
                toast({
                    title: "Success",
                    description: "Product created successfully",
                });
                router.visit(route(`${routePrefix}.products`));
            },
            onError: (errors) => {
                toast({
                    title: "Error",
                    description: "Please check the form for errors",
                    variant: "destructive",
                });
            }
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center">
                    <Button variant="ghost" asChild className="mr-4">
                        <Link href={route(`${routePrefix}.products`)}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Créer un nouveau produit
                    </h2>
                </div>
            }
        >
            <Head title="Create Product" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Package className="h-5 w-5 mr-2" />
                                    Product Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Basic Information */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Product Name *</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="Enter product name"
                                        />
                                        {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="product_code">Product Code *</Label>
                                        <div className="relative">
                                            <Hash className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                            <Input
                                                id="product_code"
                                                value={data.product_code}
                                                onChange={(e) => setData('product_code', e.target.value)}
                                                placeholder="PROD-001"
                                                className="pl-10"
                                            />
                                        </div>
                                        {errors.product_code && <p className="text-sm text-red-600">{errors.product_code}</p>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Product description..."
                                        rows={3}
                                    />
                                    {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                                </div>
                                {canAiSeoGenerate && (
                                    <div className="space-y-2 rounded-md border border-amber-200 bg-amber-50/60 p-3">
                                        <div className="flex items-center justify-between">
                                            <p className="text-sm font-medium">SEO IA (suggestion)</p>
                                            <Button type="button" variant="secondary" size="sm" onClick={handleGenerateSeo} disabled={aiSeoGenerating}>
                                                <Sparkles className="h-4 w-4 mr-2" />
                                                {aiSeoGenerating ? 'Génération SEO...' : 'Générer SEO IA'}
                                            </Button>
                                        </div>
                                        {seoSuggestion.meta_title && <p className="text-xs"><strong>Meta title:</strong> {seoSuggestion.meta_title}</p>}
                                        {seoSuggestion.meta_description && <p className="text-xs"><strong>Meta description:</strong> {seoSuggestion.meta_description}</p>}
                                        {seoSuggestion.slug && <p className="text-xs"><strong>Slug:</strong> {seoSuggestion.slug}</p>}
                                        <Input value={data.meta_title} onChange={(e) => setData('meta_title', e.target.value)} placeholder="Meta title (max 60)" maxLength={60} />
                                        <Textarea value={data.meta_description} onChange={(e) => setData('meta_description', e.target.value)} placeholder="Meta description (max 160)" rows={2} />
                                        <Input value={data.slug} onChange={(e) => setData('slug', e.target.value)} placeholder="slug-produit" />
                                    </div>
                                )}
                                <div className="space-y-2">
                                    <Label>Image produit</Label>
                                    <div className="flex items-center gap-4">
                                        <div className="h-24 w-24 rounded-lg border border-dashed border-gray-300 flex items-center justify-center overflow-hidden bg-gray-50">
                                            {imagePreview ? <img src={imagePreview} alt="preview" className="h-full w-full object-cover" /> : <ImageIcon className="h-7 w-7 text-gray-400" />}
                                        </div>
                                        <div className="flex-1 space-y-2">
                                            <Input type="file" accept="image/jpeg,image/jpg,image/png,image/webp" onChange={handleImageChange} />
                                            {canAiGenerate && (
                                                <Button type="button" variant="secondary" size="sm" onClick={handleGenerateImage} disabled={aiGenerating}>
                                                    <Sparkles className="h-4 w-4 mr-2" />
                                                    {aiGenerating ? 'Génération...' : 'Générer 4 images IA'}
                                                </Button>
                                            )}
                                        </div>
                                        {imagePreview && (
                                            <button type="button" onClick={() => { setData('image', null); setImagePreview(null); setAiImageCandidates([]); }} className="p-2 rounded border">
                                                <X className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                    {aiImageCandidates.length > 1 && (
                                        <div className="mt-2">
                                            <p className="text-xs text-gray-500 mb-2">Choisir l’image principale :</p>
                                            <div className="flex gap-2 flex-wrap">
                                                {aiImageCandidates.map((img, idx) => (
                                                    <button
                                                        key={`${img.file_name || 'ai'}-${idx}`}
                                                        type="button"
                                                        onClick={() => handleSelectAiCandidate(img)}
                                                        className="h-14 w-14 rounded border overflow-hidden hover:border-amber-500"
                                                        title={`Image IA ${idx + 1}`}
                                                    >
                                                        <img src={img.image_data_url} alt={`IA ${idx + 1}`} className="h-full w-full object-cover" />
                                                    </button>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                    {errors.image && <p className="text-sm text-red-600">{errors.image}</p>}
                                </div>

                                {/* Category and Type */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="category_id">Category *</Label>
                                        <div className="relative">
                                            <Tag className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                            <select
                                                id="category_id"
                                                value={data.category_id}
                                                onChange={(e) => setData('category_id', e.target.value)}
                                                className="w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select category</option>
                                                {categories.map(category => (
                                                    <option key={category.id} value={category.id}>
                                                        {category.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        {errors.category_id && <p className="text-sm text-red-600">{errors.category_id}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="unit">Unit *</Label>
                                        <Input
                                            id="unit"
                                            value={data.unit}
                                            onChange={(e) => setData('unit', e.target.value)}
                                            placeholder="e.g., bottle, box, tablet"
                                        />
                                        {errors.unit && <p className="text-sm text-red-600">{errors.unit}</p>}
                                    </div>
                                </div>

                                {/* Unité de vente (divisible / non divisible) */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
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
                                        {errors.type_unite && <p className="text-sm text-red-600">{errors.type_unite}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="quantite_par_unite">Quantité par unité *</Label>
                                        <Input
                                            id="quantite_par_unite"
                                            type="number"
                                            min={1}
                                            value={data.quantite_par_unite}
                                            onChange={(e) => setData('quantite_par_unite', parseInt(e.target.value, 10) || 1)}
                                            placeholder="ex: 10 comprimés"
                                        />
                                        <p className="text-xs text-gray-500">ex: 10 comprimés par plaquette</p>
                                        {errors.quantite_par_unite && <p className="text-sm text-red-600">{errors.quantite_par_unite}</p>}
                                    </div>
                                    <div className="space-y-2 flex flex-col justify-end">
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id="est_divisible"
                                                checked={data.est_divisible}
                                                onChange={(e) => setData('est_divisible', e.target.checked)}
                                                className="rounded border-gray-300"
                                            />
                                            <Label htmlFor="est_divisible" className="cursor-pointer">Vente en fraction autorisée</Label>
                                        </div>
                                        <p className="text-xs text-gray-500">Désactiver pour boîte/flacon (quantité entière uniquement)</p>
                                        {errors.est_divisible && <p className="text-sm text-red-600">{errors.est_divisible}</p>}
                                    </div>
                                </div>

                                {/* Pricing */}
                                <div className="border-t pt-6">
                                    <h3 className="text-lg font-medium mb-4 flex items-center">
                                        <DollarSign className="h-5 w-5 mr-2" />
                                        Pricing Information
                                    </h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="price">Selling Price *</Label>
                                            <Input
                                                id="price"
                                                type="number"
                                                step="0.01"
                                                value={data.price}
                                                onChange={(e) => setData('price', e.target.value)}
                                                placeholder="0.00"
                                            />
                                            {errors.price && <p className="text-sm text-red-600">{errors.price}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="cost">Cost Price</Label>
                                            <Input
                                                id="cost"
                                                type="number"
                                                step="0.01"
                                                value={data.cost}
                                                onChange={(e) => setData('cost', e.target.value)}
                                                placeholder="0.00"
                                            />
                                            {errors.cost && <p className="text-sm text-red-600">{errors.cost}</p>}
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
                                            {errors.currency && <p className="text-sm text-red-600">{errors.currency}</p>}
                                        </div>
                                    </div>
                                </div>

                                {/* Inventory */}
                                <div className="border-t pt-6">
                                    <h3 className="text-lg font-medium mb-4 flex items-center">
                                        <Archive className="h-5 w-5 mr-2" />
                                        Inventory Settings
                                    </h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="minimum_stock">Minimum Stock Level *</Label>
                                            <Input
                                                id="minimum_stock"
                                                type="number"
                                                value={data.minimum_stock}
                                                onChange={(e) => setData('minimum_stock', e.target.value)}
                                                placeholder="10"
                                            />
                                            {errors.minimum_stock && <p className="text-sm text-red-600">{errors.minimum_stock}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="manufacturer">Manufacturer</Label>
                                            <Input
                                                id="manufacturer"
                                                value={data.manufacturer}
                                                onChange={(e) => setData('manufacturer', e.target.value)}
                                                placeholder="Manufacturer name"
                                            />
                                            {errors.manufacturer && <p className="text-sm text-red-600">{errors.manufacturer}</p>}
                                        </div>
                                    </div>
                                </div>

                                {/* Medicine Specific Fields */}
                                <div className="border-t pt-6">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-medium flex items-center">
                                            <Pill className="h-5 w-5 mr-2" />
                                            Medicine Information
                                        </h3>
                                        <label className="flex items-center">
                                            <input
                                                type="checkbox"
                                                checked={isMedicine}
                                                onChange={(e) => setIsMedicine(e.target.checked)}
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <span className="ml-2 text-sm text-gray-600">This is a medicine product</span>
                                        </label>
                                    </div>

                                    {isMedicine && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-50 p-4 rounded-lg">
                                            <div className="space-y-2">
                                                <Label htmlFor="medicine_type">Medicine Type</Label>
                                                <select
                                                    id="medicine_type"
                                                    value={data.medicine_type}
                                                    onChange={(e) => setData('medicine_type', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                    <option value="">Select type</option>
                                                    <option value="tablet">Tablet</option>
                                                    <option value="capsule">Capsule</option>
                                                    <option value="syrup">Syrup</option>
                                                    <option value="injection">Injection</option>
                                                    <option value="cream">Cream/Ointment</option>
                                                    <option value="other">Other</option>
                                                </select>
                                                {errors.medicine_type && <p className="text-sm text-red-600">{errors.medicine_type}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="dosage">Dosage</Label>
                                                <Input
                                                    id="dosage"
                                                    value={data.dosage}
                                                    onChange={(e) => setData('dosage', e.target.value)}
                                                    placeholder="e.g., 500mg, 10ml"
                                                />
                                                {errors.dosage && <p className="text-sm text-red-600">{errors.dosage}</p>}
                                            </div>

                                            <div className="space-y-2 md:col-span-2">
                                                <div className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        id="prescription_required"
                                                        checked={data.prescription_required}
                                                        onChange={(e) => setData('prescription_required', e.target.checked)}
                                                        className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                    <Label htmlFor="prescription_required" className="ml-2">
                                                        Prescription Required
                                                    </Label>
                                                </div>
                                                {errors.prescription_required && <p className="text-sm text-red-600">{errors.prescription_required}</p>}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Form Actions */}
                                <div className="flex justify-end space-x-4 pt-6 border-t">
                                    <Button
                                        variant="outline"
                                        asChild
                                        disabled={processing}
                                    >
                                        <Link href={route(`${routePrefix}.products`)}>
                                            Cancel
                                        </Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {processing ? 'Creating...' : 'Create Product'}
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