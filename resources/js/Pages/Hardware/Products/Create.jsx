import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Save, Package, Hash } from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';

const TYPE_UNITE_OPTIONS = ['PIECE', 'LOT', 'METRE', 'KG', 'LITRE', 'BOITE', 'CARTON', 'UNITE'];

export default function HardwareProductCreate({ categories = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        product_code: '',
        description: '',
        category_id: '',
        price: '',
        currency: 'USD',
        minimum_stock: 0,
        unit: 'UNITE',
        type_unite: 'UNITE',
        quantite_par_unite: 1,
        est_divisible: true,
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

    const handleSubmit = (e) => {
        e.preventDefault();
        setData('unit', data.type_unite);
        post(route('hardware.products.store'), {
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
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="price">Prix *</Label>
                                        <div className="flex gap-2">
                                            <Input id="price" type="number" step="0.01" min={0} value={data.price} onChange={(e) => setData('price', e.target.value)} placeholder="0.00" required />
                                            <select
                                                value={data.currency}
                                                onChange={(e) => setData('currency', e.target.value)}
                                                className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white w-24"
                                            >
                                                <option value="USD">USD</option>
                                                <option value="CDF">CDF</option>
                                                <option value="EUR">EUR</option>
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
