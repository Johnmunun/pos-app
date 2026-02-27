import React from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Save, Package } from 'lucide-react';
import { toast } from 'react-hot-toast';

const TYPE_UNITE_OPTIONS = ['PIECE', 'LOT', 'METRE', 'KG', 'LITRE', 'BOITE', 'CARTON', 'UNITE'];

/**
 * Édition produit — Module Quincaillerie. Vue dédiée, aucun import Pharmacy.
 */
export default function HardwareProductEdit({ product, categories = [] }) {
    const { data, setData, put, processing, errors } = useForm({
        name: product?.name || '',
        description: product?.description || '',
        category_id: product?.category_id || '',
        price: product?.price_amount ?? '',
        currency: product?.price_currency || 'USD',
        minimum_stock: product?.minimum_stock ?? 0,
        type_unite: product?.type_unite || 'UNITE',
        quantite_par_unite: product?.quantite_par_unite ?? 1,
        est_divisible: product?.est_divisible !== false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('hardware.products.update', product.id), {
            onSuccess: () => {
                toast.success('Produit mis à jour');
                router.visit(route('hardware.products.show', product.id));
            },
            onError: (err) => toast.error(err?.message || 'Erreur'),
        });
    };

    if (!product) {
        return (
            <AppLayout>
                <Head title="Produit - Quincaillerie" />
                <div className="py-6 text-center">
                    <p className="text-gray-500">Produit introuvable.</p>
                    <Button asChild className="mt-4"><Link href={route('hardware.products')}>Retour</Link></Button>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout
            header={
                <div className="flex items-center">
                    <Button variant="ghost" asChild className="mr-4">
                        <Link href={route('hardware.products.show', product.id)}><ArrowLeft className="h-4 w-4 mr-2" /> Retour</Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Modifier — {product.name}
                    </h2>
                </div>
            }
        >
            <Head title={`Modifier ${product.name} - Quincaillerie`} />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Package className="h-5 w-5 mr-2" /> Modifier le produit
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nom *</Label>
                                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                                        {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Code produit</Label>
                                        <p className="text-gray-600 dark:text-gray-400">{product.product_code}</p>
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea id="description" value={data.description} onChange={(e) => setData('description', e.target.value)} rows={3} />
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
                                            onChange={(e) => setData('type_unite', e.target.value)}
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
                                            <Input id="price" type="number" step="0.01" min={0} value={data.price} onChange={(e) => setData('price', e.target.value)} required />
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
                                        <Label htmlFor="minimum_stock">Stock minimum</Label>
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
                                        <Link href={route('hardware.products.show', product.id)}>Annuler</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing} className="bg-amber-500 hover:bg-amber-600 text-white">
                                        <Save className="h-4 w-4 mr-2" /> {processing ? 'Enregistrement...' : 'Enregistrer'}
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
