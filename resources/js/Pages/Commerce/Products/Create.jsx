import React, { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Save, Package, Sparkles, Image as ImageIcon, X } from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';

export default function CommerceProductsCreate({ categories = [], currency = 'USD' }) {
    const { props } = usePage();
    const canAiGenerate = props.auth?.planFeatures?.ai_product_image_generate === true;
    const canAiSeoGenerate = props.auth?.planFeatures?.ai_product_seo_generate === true;
    const [imagePreview, setImagePreview] = useState(null);
    const [aiImageCandidates, setAiImageCandidates] = useState([]);
    const [aiGenerating, setAiGenerating] = useState(false);
    const [aiSeoGenerating, setAiSeoGenerating] = useState(false);
    const [seoSuggestion, setSeoSuggestion] = useState({ meta_title: '', meta_description: '', slug: '' });
    const { data, setData, post, processing, errors } = useForm({
        sku: '',
        barcode: '',
        name: '',
        description: '',
        category_id: '',
        purchase_price: '',
        sale_price: '',
        initial_stock: 0,
        minimum_stock: 0,
        currency,
        is_weighted: false,
        has_expiration: false,
        image: null,
        meta_title: '',
        meta_description: '',
        slug: '',
    });

    const handleImageChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
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
            toast.error('Saisissez d’abord le nom du produit.');
            return;
        }
        setAiGenerating(true);
        try {
            const res = await axios.post(route('commerce.products.ai.generate-image'), {
                title: data.name,
                description: data.description || '',
                count: 4,
                async: true,
            });
            const requestId = res.data?.request_id;
            if (!requestId) throw new Error('Requête IA invalide');
            const pollStatus = async () => {
                for (let i = 0; i < 60; i += 1) {
                    const statusRes = await axios.get(route('commerce.products.ai.generate-image.status', requestId));
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
            toast.success(`${Math.min(images.length, 4)} image(s) IA générée(s).`);
        } catch (e) {
            toast.error(e.response?.data?.message || e.message || 'Échec génération image IA.');
        } finally {
            setAiGenerating(false);
        }
    };

    const handleGenerateSeo = async () => {
        if (!canAiSeoGenerate) return;
        if (!String(data.name || '').trim()) {
            toast.error('Saisissez d’abord le nom du produit.');
            return;
        }
        setAiSeoGenerating(true);
        try {
            const res = await axios.post(route('commerce.products.ai.generate-seo'), {
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
            toast.success('SEO généré.');
        } catch (e) {
            toast.error(e.response?.data?.message || e.message || 'Échec génération SEO IA.');
        } finally {
            setAiSeoGenerating(false);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('commerce.products.store'), {
            forceFormData: true,
            onSuccess: () => toast.success('Produit créé'),
            onError: (err) => toast.error(err?.message || 'Erreur'),
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center">
                    <Button variant="ghost" asChild className="mr-4">
                        <Link href={route('commerce.products.index')}>
                            <ArrowLeft className="h-4 w-4 mr-2" /> Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Nouveau produit — GlobalCommerce
                    </h2>
                </div>
            }
        >
            <Head title="Nouveau produit - Commerce" />
            <div className="py-6">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Package className="h-5 w-5 mr-2" /> Informations produit
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="sku">SKU *</Label>
                                        <Input id="sku" value={data.sku} onChange={(e) => setData('sku', e.target.value)} placeholder="ex: PROD-001" required />
                                        {errors.sku && <p className="text-sm text-red-600">{errors.sku}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="barcode">Code-barres</Label>
                                        <Input id="barcode" value={data.barcode} onChange={(e) => setData('barcode', e.target.value)} placeholder="Optionnel" />
                                        {errors.barcode && <p className="text-sm text-red-600">{errors.barcode}</p>}
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nom *</Label>
                                    <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Nom du produit" required />
                                    {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={2} placeholder="Description..." />
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
                                    <div className="flex items-center gap-3">
                                        <div className="h-20 w-20 rounded-lg border border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-slate-800">
                                            {imagePreview ? <img src={imagePreview} alt="Aperçu" className="h-full w-full object-cover" /> : <ImageIcon className="h-7 w-7 text-gray-400" />}
                                        </div>
                                        <div className="flex-1 space-y-2">
                                            <Input type="file" accept="image/jpeg,image/jpg,image/png,image/webp" onChange={handleImageChange} />
                                            {canAiGenerate && (
                                                <Button type="button" variant="secondary" size="sm" onClick={handleGenerateImage} disabled={aiGenerating}>
                                                    <Sparkles className="h-4 w-4 mr-2" /> {aiGenerating ? 'Génération...' : 'Générer 4 images IA'}
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
                                <div className="space-y-2">
                                    <Label htmlFor="category_id">Catégorie *</Label>
                                    <select
                                        id="category_id"
                                        value={data.category_id}
                                        onChange={(e) => setData('category_id', e.target.value)}
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        required
                                    >
                                        <option value="">— Choisir —</option>
                                        {categories.map((c) => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </select>
                                    {errors.category_id && <p className="text-sm text-red-600">{errors.category_id}</p>}
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="purchase_price">Prix d'achat *</Label>
                                        <Input id="purchase_price" type="number" step="0.01" min={0} value={data.purchase_price} onChange={(e) => setData('purchase_price', e.target.value)} required />
                                        {errors.purchase_price && <p className="text-sm text-red-600">{errors.purchase_price}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="sale_price">Prix de vente *</Label>
                                        <Input id="sale_price" type="number" step="0.01" min={0} value={data.sale_price} onChange={(e) => setData('sale_price', e.target.value)} required />
                                        {errors.sale_price && <p className="text-sm text-red-600">{errors.sale_price}</p>}
                                    </div>
                                </div>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="initial_stock">Stock initial</Label>
                                        <Input id="initial_stock" type="number" step="0.0001" min={0} value={data.initial_stock} onChange={(e) => setData('initial_stock', parseFloat(e.target.value) || 0)} />
                                        {errors.initial_stock && <p className="text-sm text-red-600">{errors.initial_stock}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="minimum_stock">Stock minimum</Label>
                                        <Input id="minimum_stock" type="number" step="0.0001" min={0} value={data.minimum_stock} onChange={(e) => setData('minimum_stock', parseFloat(e.target.value) || 0)} />
                                        {errors.minimum_stock && <p className="text-sm text-red-600">{errors.minimum_stock}</p>}
                                    </div>
                                </div>
                                <div className="flex gap-6">
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" checked={data.is_weighted} onChange={(e) => setData('is_weighted', e.target.checked)} className="rounded border-gray-300" />
                                        <span>Vente au poids</span>
                                    </label>
                                    <label className="flex items-center gap-2 cursor-pointer">
                                        <input type="checkbox" checked={data.has_expiration} onChange={(e) => setData('has_expiration', e.target.checked)} className="rounded border-gray-300" />
                                        <span>Gestion date de péremption</span>
                                    </label>
                                </div>
                                <div className="flex gap-3 pt-4">
                                    <Button type="button" variant="outline" asChild>
                                        <Link href={route('commerce.products.index')}>Annuler</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4 mr-2" /> {processing ? 'Création...' : 'Créer'}
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
