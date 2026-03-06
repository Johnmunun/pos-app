import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Save, Package } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function CommerceProductsCreate({ categories = [], currency = 'USD' }) {
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
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('commerce.products.store'), {
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
