import React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Package, AlertTriangle, BarChart, Search } from 'lucide-react';
import ExportButtons from '@/Components/Pharmacy/ExportButtons';
import axios from 'axios';
import { toast } from 'react-hot-toast';

export default function CommerceStockIndex({
    products = [],
    pagination,
    filters = {},
    lowStockCount = 0,
}) {
    const [search, setSearch] = React.useState(filters.search || '');
    const [stockStatus, setStockStatus] = React.useState(filters.stock_status || '');
    const [adjusting, setAdjusting] = React.useState(null);
    const [adjustType, setAdjustType] = React.useState('IN');
    const [adjustQty, setAdjustQty] = React.useState('');

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(
            route('commerce.stock.index'),
            {
                search: search || undefined,
                stock_status: stockStatus || undefined,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const handlePageChange = (page) => {
        router.get(
            route('commerce.stock.index'),
            {
                search: filters.search || undefined,
                stock_status: filters.stock_status || undefined,
                page,
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const getStatus = (p) => {
        if (p.stock <= 0) return { label: 'Rupture', variant: 'destructive' };
        if (p.stock <= p.minimum_stock) return { label: 'Faible', variant: 'warning' };
        return { label: 'OK', variant: 'default' };
    };

    const openAdjust = (product) => {
        setAdjusting(product);
        setAdjustType('IN');
        setAdjustQty('');
    };

    const submitAdjust = async () => {
        if (!adjusting) return;
        const qty = Number(adjustQty);
        if (!qty || qty <= 0) {
            toast.error('Quantité invalide.');
            return;
        }
        setAdjusting((prev) => ({ ...prev, _loading: true }));
        try {
            await axios.post(route('commerce.stock.adjust', adjusting.id), {
                type: adjustType,
                quantity: qty,
            });
            toast.success('Stock mis à jour.');
            router.reload({ only: ['products', 'lowStockCount', 'pagination', 'filters'] });
            setAdjusting(null);
        } catch (e) {
            const msg =
                e.response?.data?.message ||
                "Erreur lors de la mise à jour du stock.";
            toast.error(msg);
            setAdjusting((prev) => prev && { ...prev, _loading: false });
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2">
                    <BarChart className="h-5 w-5 text-amber-500" />
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Stock — Commerce
                    </h2>
                </div>
            }
        >
            <Head title="Stock - Commerce" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <Card className="bg-white dark:bg-slate-900">
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                    <span>Total produits</span>
                                    <Package className="h-4 w-4 text-blue-500" />
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {pagination?.total ?? products.length}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    produits actifs en stock
                                </p>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-900">
                            <CardHeader>
                                <CardTitle className="flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                    <span>Stock faible</span>
                                    <AlertTriangle className="h-4 w-4 text-amber-500" />
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-amber-600 dark:text-amber-400">
                                    {lowStockCount}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    produits à surveiller
                                </p>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-900">
                            <CardHeader>
                                <CardTitle className="text-sm text-gray-700 dark:text-gray-200">
                                    Vue d’ensemble
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Cette vue donne un aperçu simple des niveaux de stock Commerce. Les détails
                                    et mouvements complets sont accessibles depuis la page Produits.
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-sm text-gray-900 dark:text-white">
                                <span>Filtres</span>
                                <ExportButtons
                                    pdfUrl={route('commerce.exports.stock.pdf')}
                                    excelUrl={route('commerce.exports.stock.excel')}
                                    disabled={!products.length}
                                />
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={handleFilter}
                                className="flex flex-col md:flex-row gap-3 items-stretch md:items-end"
                            >
                                <div className="flex-1">
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Recherche
                                    </label>
                                    <div className="relative">
                                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                        <Input
                                            value={search}
                                            onChange={(e) => setSearch(e.target.value)}
                                            placeholder="Nom, SKU, code-barres…"
                                            className="pl-10"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Statut stock
                                    </label>
                                    <select
                                        value={stockStatus}
                                        onChange={(e) => setStockStatus(e.target.value)}
                                        className="h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3"
                                    >
                                        <option value="">Tous</option>
                                        <option value="low">Stock faible</option>
                                        <option value="out">Rupture</option>
                                    </select>
                                </div>
                                <Button type="submit" className="md:self-end">
                                    Appliquer
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Package className="h-5 w-5 mr-2" />
                                <span>Niveaux de stock</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto -mx-2 sm:mx-0">
                                <table className="min-w-full text-sm bg-white dark:bg-slate-900">
                                    <thead>
                                        <tr className="border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/70">
                                            <th className="text-left py-2 px-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                SKU
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                Nom
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                Catégorie
                                            </th>
                                            <th className="text-right py-2 px-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                Stock
                                            </th>
                                            <th className="text-right py-2 px-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                Min.
                                            </th>
                                            <th className="text-left py-2 px-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                Statut
                                            </th>
                                            <th className="text-right py-2 px-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                                                Action
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {products.map((p) => {
                                            const s = getStatus(p);
                                            return (
                                                <tr
                                                    key={p.id}
                                                    className="border-b border-gray-100 dark:border-slate-800"
                                                >
                                                    <td className="py-2 px-2 font-mono text-gray-900 dark:text-gray-100">
                                                        {p.sku}
                                                    </td>
                                                    <td className="py-2 px-2 text-gray-900 dark:text-gray-100">
                                                        {p.name}
                                                    </td>
                                                    <td className="py-2 px-2 text-gray-600 dark:text-gray-300">
                                                        {p.category_name || '—'}
                                                    </td>
                                                    <td className="py-2 px-2 text-right text-gray-900 dark:text-gray-100">
                                                        {p.stock}
                                                    </td>
                                                    <td className="py-2 px-2 text-right text-gray-900 dark:text-gray-100">
                                                        {p.minimum_stock}
                                                    </td>
                                                    <td className="py-2 px-2">
                                                        <Badge variant={s.variant}>{s.label}</Badge>
                                                    </td>
                                                    <td className="py-2 px-2 text-right">
                                                        <Button
                                                            type="button"
                                                            variant="outline"
                                                            size="sm"
                                                            onClick={() => openAdjust(p)}
                                                        >
                                                            Ajuster
                                                        </Button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                        {products.length === 0 && (
                                            <tr>
                                                <td
                                                    colSpan={6}
                                                    className="py-8 text-center text-gray-500 dark:text-gray-400"
                                                >
                                                    Aucun produit trouvé.
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>

                            {pagination && pagination.last_page > 1 && (
                                <div className="mt-4 flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                                    <span>
                                        Page {pagination.current_page} / {pagination.last_page}
                                    </span>
                                    <div className="flex gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={pagination.current_page <= 1}
                                            onClick={() => handlePageChange(pagination.current_page - 1)}
                                        >
                                            Précédent
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={pagination.current_page >= pagination.last_page}
                                            onClick={() => handlePageChange(pagination.current_page + 1)}
                                        >
                                            Suivant
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {adjusting && (
                        <div className="fixed inset-0 z-[90]">
                            <div
                                className="absolute inset-0 bg-black/50"
                                onClick={() => setAdjusting(null)}
                            />
                            <div className="absolute inset-0 flex items-center justify-center p-4">
                                <div
                                    className="w-full max-w-sm rounded-2xl bg-white dark:bg-slate-900 shadow-xl border border-gray-200 dark:border-slate-700 p-5 space-y-4"
                                    onClick={(e) => e.stopPropagation()}
                                >
                                    <h3 className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                        Ajuster stock — {adjusting.name}
                                    </h3>
                                    <div className="space-y-2">
                                        <label className="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                            Type d&apos;ajustement
                                        </label>
                                        <select
                                            value={adjustType}
                                            onChange={(e) => setAdjustType(e.target.value)}
                                            className="w-full h-9 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-2 text-sm"
                                        >
                                            <option value="IN">Entrée (+)</option>
                                            <option value="OUT">Sortie (-)</option>
                                            <option value="ADJUSTMENT">Ajustement (valeur relative)</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <label className="block text-xs font-medium text-gray-600 dark:text-gray-300">
                                            Quantité
                                        </label>
                                        <Input
                                            type="number"
                                            min="0"
                                            step="0.0001"
                                            value={adjustQty}
                                            onChange={(e) => setAdjustQty(e.target.value)}
                                        />
                                    </div>
                                    <div className="flex justify-end gap-2 pt-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setAdjusting(null)}
                                        >
                                            Annuler
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            onClick={submitAdjust}
                                            disabled={adjusting._loading}
                                        >
                                            {adjusting._loading ? 'Traitement…' : 'Valider'}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

