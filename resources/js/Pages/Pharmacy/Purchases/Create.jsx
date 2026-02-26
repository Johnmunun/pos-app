import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Package, Plus, Trash2 } from 'lucide-react';
import axios from 'axios';
import { useToast } from '@/Components/ui/use-toast';

export default function PurchasesCreate({ auth, products = [], suppliers = [], routePrefix = 'pharmacy' }) {
    const { toast } = useToast();
    const { shop } = usePage().props;
    const defaultCurrency = shop?.currency || 'CDF';
    
    const [supplierId, setSupplierId] = useState('');
    const [expectedAt, setExpectedAt] = useState('');
    const [currency, setCurrency] = useState(defaultCurrency);
    const [lines, setLines] = useState([{ product_id: '', product_name: '', ordered_quantity: 1, unit_cost: 0 }]);
    const [submitting, setSubmitting] = useState(false);

    const addLine = () => {
        setLines([...lines, { product_id: '', product_name: '', ordered_quantity: 1, unit_cost: 0 }]);
    };

    const setLineProduct = (index, product) => {
        const next = [...lines];
        next[index] = { ...next[index], product_id: product.id, product_name: product.name, unit_cost: product.cost_amount ?? 0 };
        setLines(next);
    };

    const updateLine = (index, field, value) => {
        const next = [...lines];
        next[index] = { ...next[index], [field]: value };
        setLines(next);
    };

    const removeLine = (index) => {
        setLines(lines.filter((_, i) => i !== index));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!supplierId) {
            toast({ title: 'Sélectionnez un fournisseur', variant: 'destructive' });
            return;
        }
        const validLines = lines.filter(l => l.product_id && l.ordered_quantity > 0 && Number(l.unit_cost) >= 0);
        if (validLines.length === 0) {
            toast({ title: 'Ajoutez au moins une ligne avec produit et quantité', variant: 'destructive' });
            return;
        }
        setSubmitting(true);
        try {
            await axios.post(route(`${routePrefix}.purchases.store`), {
                supplier_id: supplierId,
                currency,
                expected_at: expectedAt || null,
                lines: validLines.map(l => ({ product_id: l.product_id, ordered_quantity: Number(l.ordered_quantity), unit_cost: Number(l.unit_cost) })),
            });
            toast({ title: 'Bon de commande créé' });
            router.visit(route(`${routePrefix}.purchases.index`));
        } catch (err) {
            toast({ title: 'Erreur', description: err.response?.data?.message || 'Erreur', variant: 'destructive' });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Nouveau bon de commande</h2>
                    <Button variant="outline" asChild>
                        <Link href={route(`${routePrefix}.purchases.index`)}>Retour</Link>
                    </Button>
                </div>
            }
        >
            <Head title="Nouveau bon de commande" />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit}>
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle>Fournisseur & dates</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium mb-1">Fournisseur *</label>
                                    <select
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        value={supplierId}
                                        onChange={(e) => setSupplierId(e.target.value)}
                                        required
                                    >
                                        <option value="">Choisir...</option>
                                        {suppliers.map((s) => (
                                            <option key={s.id} value={s.id}>{s.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Date livraison prévue</label>
                                        <Input type="date" value={expectedAt} onChange={(e) => setExpectedAt(e.target.value)} />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Devise</label>
                                        <select className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" value={currency} onChange={(e) => setCurrency(e.target.value)}>
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
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="mb-6">
                            <CardHeader>
                                <div className="flex justify-between items-center">
                                    <CardTitle className="flex items-center">
                                        <Package className="h-5 w-5 mr-2" />
                                        Lignes
                                    </CardTitle>
                                    <Button type="button" variant="outline" size="sm" onClick={addLine}><Plus className="h-4 w-4 mr-1" /> Ligne</Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {lines.map((line, index) => (
                                        <div key={index} className="flex flex-wrap items-end gap-4 p-4 border dark:border-gray-700 rounded">
                                            <div className="flex-1 min-w-[200px]">
                                                <label className="block text-sm font-medium mb-1">Produit</label>
                                                <select
                                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    value={line.product_id}
                                                    onChange={(e) => {
                                                        const p = products.find(pr => pr.id === e.target.value);
                                                        if (p) setLineProduct(index, p);
                                                    }}
                                                >
                                                    <option value="">Choisir...</option>
                                                    {products.map((p) => (
                                                        <option key={p.id} value={p.id}>{p.name} ({p.code})</option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div className="w-24">
                                                <label className="block text-sm font-medium mb-1">Qté</label>
                                                <Input type="number" min={1} value={line.ordered_quantity} onChange={(e) => updateLine(index, 'ordered_quantity', parseInt(e.target.value, 10) || 1)} />
                                            </div>
                                            <div className="w-28">
                                                <label className="block text-sm font-medium mb-1">Prix d'achat</label>
                                                <Input type="number" step="0.01" min={0} value={line.unit_cost} onChange={(e) => updateLine(index, 'unit_cost', parseFloat(e.target.value) || 0)} />
                                            </div>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => removeLine(index)} disabled={lines.length <= 1}>
                                                <Trash2 className="h-4 w-4 text-red-500" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" asChild>
                                <Link href={route(`${routePrefix}.purchases.index`)}>Annuler</Link>
                            </Button>
                            <Button type="submit" disabled={submitting}>Enregistrer le bon de commande</Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
