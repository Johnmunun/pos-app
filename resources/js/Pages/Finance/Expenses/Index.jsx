import React, { useState, useEffect } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import GrabScroll from '@/Components/GrabScroll';
import {
    DollarSign,
    Filter,
    Plus,
    Search,
    XCircle,
    FileDown,
    Wallet,
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

const cardShell =
    'overflow-hidden rounded-2xl border border-gray-200/80 bg-white/95 shadow-landing-soft backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/80';

const selectClass =
    'w-full h-10 rounded-xl border border-gray-200/90 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500/70 focus:border-emerald-400/50';

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
            return (
                <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 text-xs rounded-lg">
                    Approuvée
                </Badge>
            );
        }
        if (s === 'rejected') {
            return (
                <Badge className="bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 text-xs rounded-lg">
                    Rejetée
                </Badge>
            );
        }
        return (
            <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 text-xs rounded-lg">
                En attente
            </Badge>
        );
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
        <AppLayout
            header={
                <div>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Dépenses
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-0.5 hidden sm:block">
                        Catégories, validation et export comptable.
                    </p>
                </div>
            }
        >
            <Head title="Dépenses" />

            <div className="py-8 sm:py-10 space-y-6 sm:space-y-8">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <DollarSign className="h-7 w-7 text-emerald-500" />
                                Gestion des dépenses
                            </h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400 max-w-2xl leading-relaxed">
                                Suivez vos sorties par catégorie, filtrez par période et statut, enregistrez de nouvelles
                                dépenses.
                            </p>
                        </div>
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
                            className="rounded-xl border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 shrink-0"
                        >
                            <FileDown className="h-4 w-4 mr-2" />
                            Export PDF
                        </Button>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
                        <Card className={cardShell}>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center text-gray-900 dark:text-white text-base sm:text-lg">
                                    <Filter className="h-5 w-5 mr-2 text-emerald-500" />
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
                                            className={`mt-1.5 ${selectClass}`}
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
                                            className={`mt-1.5 ${selectClass}`}
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
                                                className="mt-1.5 rounded-xl bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-gray-100"
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
                                                className="mt-1.5 rounded-xl bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                            />
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2 pt-1">
                                        <Button
                                            type="submit"
                                            className="flex-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                                        >
                                            <Search className="h-4 w-4 mr-2" />
                                            Filtrer
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={resetFilters}
                                            className="rounded-xl border-gray-300 dark:border-slate-600 shrink-0"
                                            title="Réinitialiser"
                                        >
                                            <XCircle className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card className={`lg:col-span-2 ${cardShell}`}>
                            <CardHeader className="pb-3">
                                <CardTitle className="flex items-center text-gray-900 dark:text-white text-base sm:text-lg">
                                    <Plus className="h-5 w-5 mr-2 text-emerald-500" />
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
                                            className="mt-1.5 rounded-xl bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                        />
                                        {errors.amount && (
                                            <p className="text-xs text-red-500 mt-1">{errors.amount}</p>
                                        )}
                                    </div>
                                    <div>
                                        <Label htmlFor="category_create" className="text-gray-700 dark:text-gray-300">
                                            Catégorie
                                        </Label>
                                        <select
                                            id="category_create"
                                            value={data.category}
                                            onChange={(e) => setData('category', e.target.value)}
                                            className={`mt-1.5 ${selectClass}`}
                                        >
                                            <option value="">Sélectionner</option>
                                            {CATEGORIES.filter((c) => c.value).map((c) => (
                                                <option key={c.value} value={c.value}>
                                                    {c.label}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.category && (
                                            <p className="text-xs text-red-500 mt-1">{errors.category}</p>
                                        )}
                                    </div>
                                    <div>
                                        <Label htmlFor="description" className="text-gray-700 dark:text-gray-300">
                                            Description
                                        </Label>
                                        <Input
                                            id="description"
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            className="mt-1.5 rounded-xl bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                        />
                                        {errors.description && (
                                            <p className="text-xs text-red-500 mt-1">{errors.description}</p>
                                        )}
                                    </div>
                                    <div>
                                        <Label htmlFor="supplier_id" className="text-gray-700 dark:text-gray-300">
                                            Fournisseur (optionnel)
                                        </Label>
                                        <Input
                                            id="supplier_id"
                                            value={data.supplier_id}
                                            onChange={(e) => setData('supplier_id', e.target.value)}
                                            className="mt-1.5 rounded-xl bg-white dark:bg-slate-800 border-gray-200 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                        />
                                        {errors.supplier_id && (
                                            <p className="text-xs text-red-500 mt-1">{errors.supplier_id}</p>
                                        )}
                                    </div>
                                    <div className="md:col-span-2 flex justify-end pt-1">
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm"
                                        >
                                            <Plus className="h-4 w-4 mr-2" />
                                            Enregistrer
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </div>

                    <Card className={`${cardShell} mt-6 sm:mt-8`}>
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Wallet className="h-5 w-5 mr-2 text-emerald-500" />
                                Liste des dépenses ({pagination?.total ?? expenses.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {expenses.length === 0 ? (
                                <div className="text-center py-14 px-4 rounded-xl border border-dashed border-gray-200 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-950/30">
                                    <Wallet className="h-12 w-12 text-gray-300 dark:text-slate-600 mx-auto mb-3" />
                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                        Aucune dépense trouvée
                                    </p>
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1 max-w-md mx-auto">
                                        Modifiez les filtres ou enregistrez une première dépense avec le formulaire
                                        ci-dessus.
                                    </p>
                                </div>
                            ) : (
                                <GrabScroll className="rounded-xl border border-gray-100/90 bg-gray-50/30 dark:border-slate-700/60 dark:bg-slate-950/30">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700 text-sm">
                                        <thead className="bg-gray-50 dark:bg-slate-800">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Date
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Catégorie
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Description
                                                </th>
                                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Montant
                                                </th>
                                                <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Statut
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                            {expenses.map((e) => (
                                                <tr
                                                    key={e.id}
                                                    className="hover:bg-gray-50 dark:hover:bg-slate-800/80"
                                                >
                                                    <td className="px-4 py-3 whitespace-nowrap text-gray-900 dark:text-white">
                                                        {e.created_at?.slice(0, 16) || '—'}
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-700 dark:text-gray-300">
                                                        {CATEGORIES.find((c) => c.value === e.category)?.label ||
                                                            e.category}
                                                    </td>
                                                    <td className="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-xs truncate">
                                                        {e.description || '—'}
                                                    </td>
                                                    <td className="px-4 py-3 text-right font-medium text-gray-900 dark:text-white tabular-nums">
                                                        {fmt(e.amount)}
                                                    </td>
                                                    <td className="px-4 py-3 text-center">{statusBadge(e.status)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </GrabScroll>
                            )}

                            {lastPage > 1 && (
                                <div className="mt-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-gray-600 dark:text-gray-400">
                                    <p>
                                        Page{' '}
                                        <span className="font-semibold text-gray-900 dark:text-white">
                                            {currentPage}
                                        </span>{' '}
                                        sur{' '}
                                        <span className="font-semibold text-gray-900 dark:text-white">
                                            {lastPage}
                                        </span>
                                    </p>
                                    <div className="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={currentPage <= 1}
                                            onClick={() => goToPage(currentPage - 1)}
                                            className="rounded-xl border-gray-300 dark:border-slate-600"
                                        >
                                            Précédent
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            disabled={currentPage >= lastPage}
                                            onClick={() => goToPage(currentPage + 1)}
                                            className="rounded-xl border-gray-300 dark:border-slate-600"
                                        >
                                            Suivant
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
