import React, { useState, useEffect } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    DollarSign,
    Filter,
    Plus,
    Search,
    XCircle,
    FileDown,
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

const CATEGORIES = [
    { value: '', label: 'Toutes les catégories' },
    { value: 'stock_purchase', label: 'Achat stock' },
    { value: 'transport', label: 'Transport' },
    { value: 'salary', label: 'Salaire' },
    { value: 'fixed_charge', label: 'Charge fixe' },
    { value: 'utilities', label: 'Services (eau, électricité...)' },
    { value: 'maintenance', label: 'Maintenance' },
    { value: 'other', label: 'Autres' },
];

const STATUS = [
    { value: '', label: 'Tous les statuts' },
    { value: 'pending', label: 'En attente' },
    { value: 'approved', label: 'Approuvée' },
    { value: 'rejected', label: 'Rejetée' },
];

export default function FinanceExpensesIndex({ expenses = [], filters = {}, pagination }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';

    const [category, setCategory] = useState(filters.category || '');
    const [status, setStatus] = useState(filters.status || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');

    useEffect(() => {
        setCategory(filters.category || '');
        setStatus(filters.status || '');
        setFrom(filters.from || '');
        setTo(filters.to || '');
    }, [filters.category, filters.status, filters.from, filters.to]);

    const filterSubmit = (e) => {
        e.preventDefault();
        router.get(
            route('finance.expenses.index'),
            {
                category: category || undefined,
                status: status || undefined,
                from: from || undefined,
                to: to || undefined,
                page: 1,
            },
            { preserveState: true }
        );
    };

    const resetFilters = () => {
        setCategory('');
        setStatus('');
        setFrom('');
        setTo('');
        router.get(route('finance.expenses.index'), {}, { preserveState: false });
    };

    const { data, setData, post, processing, errors, reset } = useForm({
        amount: '',
        currency,
        category: '',
        description: '',
        supplier_id: '',
        attachment_path: '',
    });

    const handleCreate = (e) => {
        e.preventDefault();
        post(route('finance.expenses.store'), {
            onSuccess: () => {
                reset();
            },
        });
    };

    const fmt = (amount) => formatCurrency(amount || 0, currency);

    const statusBadge = (s) => {
        if (s === 'approved') {
            return <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 text-xs">Approuvée</Badge>;
        }
        if (s === 'rejected') {
            return <Badge className="bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 text-xs">Rejetée</Badge>;
        }
        return <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 text-xs">En attente</Badge>;
    };

    const currentPage = pagination?.current_page ?? 1;
    const lastPage = pagination?.last_page ?? 1;

    const goToPage = (page) => {
        router.get(
            route('finance.expenses.index'),
            {
                ...filters,
                page,
            },
            { preserveState: true }
        );
    };

    return (
        <AppLayout>
            <Head title="Dépenses" />

            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                            <DollarSign className="h-6 w-6 text-emerald-500" />
                            Gestion des Dépenses
                        </h1>
                        <p className="text-gray-500 dark:text-gray-400 mt-1">
                            Suivi des dépenses par catégorie, période et statut.
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                window.open(
                                    route('finance.expenses.export.pdf', {
                                        category: category || undefined,
                                        status: status || undefined,
                                        from: from || undefined,
                                        to: to || undefined,
                                    }),
                                    '_blank',
                                    'noopener,noreferrer'
                                )
                            }
                            className="border-gray-300 dark:border-slate-600"
                        >
                            <FileDown className="h-4 w-4 mr-2" />
                            Export PDF
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {/* Filtres */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                <Filter className="h-5 w-5 text-emerald-500" />
                                Filtres
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={filterSubmit} className="space-y-4">
                                <div>
                                    <Label htmlFor="category" className="text-gray-700 dark:text-gray-300">
                                        Catégorie
                                    </Label>
                                    <select
                                        id="category"
                                        value={category}
                                        onChange={(e) => setCategory(e.target.value)}
                                        className="mt-1 w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    >
                                        {CATEGORIES.map((c) => (
                                            <option key={c.value} value={c.value}>
                                                {c.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label htmlFor="status" className="text-gray-700 dark:text-gray-300">
                                        Statut
                                    </Label>
                                    <select
                                        id="status"
                                        value={status}
                                        onChange={(e) => setStatus(e.target.value)}
                                        className="mt-1 w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    >
                                        {STATUS.map((s) => (
                                            <option key={s.value} value={s.value}>
                                                {s.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="from" className="text-gray-700 dark:text-gray-300">
                                            Du
                                        </Label>
                                        <Input
                                            id="from"
                                            type="date"
                                            value={from}
                                            onChange={(e) => setFrom(e.target.value)}
                                            className="mt-1 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="to" className="text-gray-700 dark:text-gray-300">
                                            Au
                                        </Label>
                                        <Input
                                            id="to"
                                            type="date"
                                            value={to}
                                            onChange={(e) => setTo(e.target.value)}
                                            className="mt-1 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                        />
                                    </div>
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button type="submit" className="flex-1 bg-emerald-600 hover:bg-emerald-700 text-white">
                                        <Search className="h-4 w-4 mr-2" />
                                        Filtrer
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={resetFilters}
                                        className="border-gray-300 dark:border-slate-600"
                                    >
                                        <XCircle className="h-4 w-4" />
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Formulaire création */}
                    <Card className="lg:col-span-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                <Plus className="h-5 w-5 text-emerald-500" />
                                Enregistrer une dépense
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleCreate} className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="amount" className="text-gray-700 dark:text-gray-300">
                                        Montant
                                    </Label>
                                    <Input
                                        id="amount"
                                        type="number"
                                        step="0.01"
                                        value={data.amount}
                                        onChange={(e) => setData('amount', e.target.value)}
                                        className="mt-1 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                    {errors.amount && <p className="text-xs text-red-500 mt-1">{errors.amount}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="category_create" className="text-gray-700 dark:text-gray-300">
                                        Catégorie
                                    </Label>
                                    <select
                                        id="category_create"
                                        value={data.category}
                                        onChange={(e) => setData('category', e.target.value)}
                                        className="mt-1 w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                                    >
                                        <option value="">Sélectionner</option>
                                        {CATEGORIES.filter(c => c.value).map((c) => (
                                            <option key={c.value} value={c.value}>
                                                {c.label}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.category && <p className="text-xs text-red-500 mt-1">{errors.category}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="description" className="text-gray-700 dark:text-gray-300">
                                        Description
                                    </Label>
                                    <Input
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        className="mt-1 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                    {errors.description && <p className="text-xs text-red-500 mt-1">{errors.description}</p>}
                                </div>
                                <div>
                                    <Label htmlFor="supplier_id" className="text-gray-700 dark:text-gray-300">
                                        Fournisseur (optionnel)
                                    </Label>
                                    <Input
                                        id="supplier_id"
                                        value={data.supplier_id}
                                        onChange={(e) => setData('supplier_id', e.target.value)}
                                        className="mt-1 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                    {errors.supplier_id && <p className="text-xs text-red-500 mt-1">{errors.supplier_id}</p>}
                                </div>
                                <div className="md:col-span-2 flex justify-end">
                                    <Button
                                        type="submit"
                                        disabled={processing}
                                        className="bg-emerald-600 hover:bg-emerald-700 text-white"
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        Enregistrer
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>

                {/* Liste des dépenses */}
                <Card className="mt-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center justify-between text-gray-900 dark:text-gray-100">
                            <span>Liste des dépenses ({pagination?.total ?? expenses.length})</span>
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {expenses.length === 0 ? (
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Aucune dépense trouvée pour les filtres sélectionnés.
                            </p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full text-sm">
                                    <thead>
                                        <tr className="text-left text-gray-500 dark:text-gray-400 border-b border-gray-200 dark:border-slate-700">
                                            <th className="py-2 pr-4">Date</th>
                                            <th className="py-2 pr-4">Catégorie</th>
                                            <th className="py-2 pr-4">Description</th>
                                            <th className="py-2 pr-4 text-right">Montant</th>
                                            <th className="py-2 pr-4 text-center">Statut</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {expenses.map((e) => (
                                            <tr key={e.id} className="border-b border-gray-100 dark:border-slate-800">
                                                <td className="py-2 pr-4 text-gray-900 dark:text-gray-100">
                                                    {e.created_at?.slice(0, 16) || ''}
                                                </td>
                                                <td className="py-2 pr-4 text-gray-900 dark:text-gray-100">
                                                    {CATEGORIES.find((c) => c.value === e.category)?.label || e.category}
                                                </td>
                                                <td className="py-2 pr-4 text-gray-900 dark:text-gray-100">
                                                    {e.description || '-'}
                                                </td>
                                                <td className="py-2 pr-4 text-right text-gray-900 dark:text-gray-100">
                                                    {fmt(e.amount)}
                                                </td>
                                                <td className="py-2 pr-4 text-center">{statusBadge(e.status)}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {/* Pagination simple */}
                        {lastPage > 1 && (
                            <div className="mt-4 flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                                <div>
                                    Page {currentPage} sur {lastPage}
                                </div>
                                <div className="flex items-center gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={currentPage <= 1}
                                        onClick={() => goToPage(currentPage - 1)}
                                    >
                                        Précédent
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        disabled={currentPage >= lastPage}
                                        onClick={() => goToPage(currentPage + 1)}
                                    >
                                        Suivant
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

