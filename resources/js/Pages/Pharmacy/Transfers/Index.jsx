import React, { useState } from 'react';
import { Head, router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowRightLeft,
    Plus,
    Search,
    Filter,
    Eye,
    FileText,
    CheckCircle,
    XCircle,
    Clock,
    Package,
    Building2,
    RefreshCw,
    Calendar,
    ArrowRight
} from 'lucide-react';

export default function TransfersIndex({ transfers, shops, filters, stats }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canCreate = hasPermission('transfer.create');
    const canView = hasPermission('transfer.view');
    const canPrint = hasPermission('transfer.print');

    const [searchFilters, setSearchFilters] = useState({
        reference: filters?.reference || '',
        status: filters?.status || '',
        from_shop_id: filters?.from_shop_id || '',
        to_shop_id: filters?.to_shop_id || '',
        from: filters?.from || '',
        to: filters?.to || '',
    });

    const handleSearch = (e) => {
        e.preventDefault();
        const params = {};
        Object.keys(searchFilters).forEach(key => {
            if (searchFilters[key]) {
                params[key] = searchFilters[key];
            }
        });
        router.get(route('pharmacy.transfers.index'), params);
    };

    const resetFilters = () => {
        setSearchFilters({
            reference: '',
            status: '',
            from_shop_id: '',
            to_shop_id: '',
            from: '',
            to: '',
        });
        router.get(route('pharmacy.transfers.index'));
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'draft':
                return (
                    <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                        <Clock className="h-3 w-3 mr-1" />
                        Brouillon
                    </Badge>
                );
            case 'validated':
                return (
                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        Validé
                    </Badge>
                );
            case 'cancelled':
                return (
                    <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                        <XCircle className="h-3 w-3 mr-1" />
                        Annulé
                    </Badge>
                );
            default:
                return <Badge>{status}</Badge>;
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Transferts Inter-Magasins
                    </h2>
                    {canCreate && (
                        <Link href={route('pharmacy.transfers.create')}>
                            <Button className="bg-blue-600 hover:bg-blue-700">
                                <Plus className="h-4 w-4 mr-2" />
                                Nouveau Transfert
                            </Button>
                        </Link>
                    )}
                </div>
            }
        >
            <Head title="Transferts Inter-Magasins" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Statistiques */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <Card className="bg-white dark:bg-slate-800">
                            <CardContent className="pt-4">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-lg bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                                        <ArrowRightLeft className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Total</p>
                                        <p className="text-2xl font-bold text-gray-900 dark:text-white">{stats.total}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-800">
                            <CardContent className="pt-4">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                        <Clock className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Brouillons</p>
                                        <p className="text-2xl font-bold text-amber-600 dark:text-amber-400">{stats.draft}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-800">
                            <CardContent className="pt-4">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                        <CheckCircle className="h-5 w-5 text-green-600 dark:text-green-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Validés</p>
                                        <p className="text-2xl font-bold text-green-600 dark:text-green-400">{stats.validated}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-800">
                            <CardContent className="pt-4">
                                <div className="flex items-center gap-3">
                                    <div className="h-10 w-10 rounded-lg bg-red-100 dark:bg-red-900/30 flex items-center justify-center">
                                        <XCircle className="h-5 w-5 text-red-600 dark:text-red-400" />
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Annulés</p>
                                        <p className="text-2xl font-bold text-red-600 dark:text-red-400">{stats.cancelled}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Filtres */}
                    <Card className="bg-white dark:bg-slate-800">
                        <CardContent className="pt-4">
                            <form onSubmit={handleSearch} className="flex flex-wrap items-end gap-4">
                                <div className="w-[150px]">
                                    <label className="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Référence</label>
                                    <Input
                                        type="text"
                                        placeholder="TRF-..."
                                        value={searchFilters.reference}
                                        onChange={(e) => setSearchFilters(prev => ({ ...prev, reference: e.target.value }))}
                                        className="h-9"
                                    />
                                </div>
                                <div className="w-[130px]">
                                    <label className="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Statut</label>
                                    <select
                                        value={searchFilters.status}
                                        onChange={(e) => setSearchFilters(prev => ({ ...prev, status: e.target.value }))}
                                        className="w-full h-9 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm px-2"
                                    >
                                        <option value="">Tous</option>
                                        <option value="draft">Brouillon</option>
                                        <option value="validated">Validé</option>
                                        <option value="cancelled">Annulé</option>
                                    </select>
                                </div>
                                <div className="w-[180px]">
                                    <label className="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Magasin source</label>
                                    <select
                                        value={searchFilters.from_shop_id}
                                        onChange={(e) => setSearchFilters(prev => ({ ...prev, from_shop_id: e.target.value }))}
                                        className="w-full h-9 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm px-2"
                                    >
                                        <option value="">Tous</option>
                                        {shops.map(shop => (
                                            <option key={shop.id} value={shop.id}>{shop.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="w-[180px]">
                                    <label className="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Magasin destination</label>
                                    <select
                                        value={searchFilters.to_shop_id}
                                        onChange={(e) => setSearchFilters(prev => ({ ...prev, to_shop_id: e.target.value }))}
                                        className="w-full h-9 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm px-2"
                                    >
                                        <option value="">Tous</option>
                                        {shops.map(shop => (
                                            <option key={shop.id} value={shop.id}>{shop.name}</option>
                                        ))}
                                    </select>
                                </div>
                                <div className="w-[140px]">
                                    <label className="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Date début</label>
                                    <Input
                                        type="date"
                                        value={searchFilters.from}
                                        onChange={(e) => setSearchFilters(prev => ({ ...prev, from: e.target.value }))}
                                        className="h-9"
                                    />
                                </div>
                                <div className="w-[140px]">
                                    <label className="text-xs text-gray-500 dark:text-gray-400 mb-1 block">Date fin</label>
                                    <Input
                                        type="date"
                                        value={searchFilters.to}
                                        onChange={(e) => setSearchFilters(prev => ({ ...prev, to: e.target.value }))}
                                        className="h-9"
                                    />
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" size="sm" className="h-9">
                                        <Search className="h-4 w-4 mr-1" />
                                        Filtrer
                                    </Button>
                                    <Button type="button" variant="outline" size="sm" className="h-9" onClick={resetFilters}>
                                        <RefreshCw className="h-4 w-4" />
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Liste des transferts */}
                    <Card className="bg-white dark:bg-slate-800">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <ArrowRightLeft className="h-5 w-5 text-blue-600" />
                                Liste des Transferts
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {transfers.length === 0 ? (
                                <div className="text-center py-12">
                                    <Package className="h-16 w-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                    <p className="text-gray-500 dark:text-gray-400">Aucun transfert trouvé</p>
                                    {canCreate && (
                                        <Link href={route('pharmacy.transfers.create')}>
                                            <Button className="mt-4">
                                                <Plus className="h-4 w-4 mr-2" />
                                                Créer un transfert
                                            </Button>
                                        </Link>
                                    )}
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b border-gray-200 dark:border-slate-700">
                                                <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Référence</th>
                                                <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Source → Destination</th>
                                                <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                                <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Produits</th>
                                                <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                                <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Créé par</th>
                                                <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-slate-700">
                                            {transfers.map((transfer) => (
                                                <tr key={transfer.id} className="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                                    <td className="py-3 px-4">
                                                        <span className="font-mono font-medium text-blue-600 dark:text-blue-400">
                                                            {transfer.reference}
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <div className="flex items-center gap-2">
                                                            <div className="flex items-center gap-1">
                                                                <Building2 className="h-4 w-4 text-red-500" />
                                                                <span className="text-sm text-gray-900 dark:text-white">{transfer.from_shop_name}</span>
                                                            </div>
                                                            <ArrowRight className="h-4 w-4 text-gray-400" />
                                                            <div className="flex items-center gap-1">
                                                                <Building2 className="h-4 w-4 text-green-500" />
                                                                <span className="text-sm text-gray-900 dark:text-white">{transfer.to_shop_name}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="py-3 px-4 text-center">
                                                        {getStatusBadge(transfer.status)}
                                                    </td>
                                                    <td className="py-3 px-4 text-center">
                                                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {transfer.total_items}
                                                        </span>
                                                        <span className="text-xs text-gray-500 dark:text-gray-400 ml-1">
                                                            ({transfer.total_quantity} unités)
                                                        </span>
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <div className="flex items-center gap-1 text-sm text-gray-700 dark:text-gray-300">
                                                            <Calendar className="h-4 w-4 text-gray-400" />
                                                            {transfer.created_at_formatted}
                                                        </div>
                                                    </td>
                                                    <td className="py-3 px-4 text-sm text-gray-700 dark:text-gray-300">
                                                        {transfer.created_by_name}
                                                    </td>
                                                    <td className="py-3 px-4">
                                                        <div className="flex justify-center gap-2">
                                                            {canView && (
                                                                <Link href={route('pharmacy.transfers.show', transfer.id)}>
                                                                    <Button variant="outline" size="sm">
                                                                        <Eye className="h-4 w-4" />
                                                                    </Button>
                                                                </Link>
                                                            )}
                                                            {canPrint && transfer.status === 'validated' && (
                                                                <a
                                                                    href={route('pharmacy.transfers.pdf', transfer.id)}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                >
                                                                    <Button variant="outline" size="sm">
                                                                        <FileText className="h-4 w-4" />
                                                                    </Button>
                                                                </a>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
