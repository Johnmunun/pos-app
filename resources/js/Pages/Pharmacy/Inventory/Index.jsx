import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    ClipboardList,
    Plus,
    Search,
    Filter,
    Eye,
    Calendar,
    User,
    CheckCircle,
    XCircle,
    Clock,
    FileText,
    Package
} from 'lucide-react';

const statusConfig = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', icon: FileText },
    in_progress: { label: 'En cours', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', icon: Clock },
    validated: { label: 'Validé', color: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', icon: CheckCircle },
    cancelled: { label: 'Annulé', color: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', icon: XCircle },
};

export default function InventoryIndex({ inventories, depots = [], filters = {}, pagination, permissions }) {
    const [searchRef, setSearchRef] = useState(filters.reference || '');
    const [selectedStatus, setSelectedStatus] = useState(filters.status || '');
    const [selectedDepotId, setSelectedDepotId] = useState(filters.depot_id ?? '');
    const [fromDate, setFromDate] = useState(filters.from || '');
    const [toDate, setToDate] = useState(filters.to || '');

    const handleFilterSubmit = (e) => {
        e.preventDefault();
        router.get(route('pharmacy.inventories.index'), {
            reference: searchRef || undefined,
            status: selectedStatus || undefined,
            depot_id: selectedDepotId || undefined,
            from: fromDate || undefined,
            to: toDate || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleResetFilters = () => {
        setSearchRef('');
        setSelectedStatus('');
        setSelectedDepotId('');
        setFromDate('');
        setToDate('');
        router.get(route('pharmacy.inventories.index'));
    };

    const handlePageChange = (page) => {
        router.get(route('pharmacy.inventories.index'), {
            ...filters,
            page,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleCreateInventory = () => {
        const data = selectedDepotId ? { depot_id: selectedDepotId } : {};
        router.post(route('pharmacy.inventories.store'), data);
    };

    const getStatusBadge = (status) => {
        const config = statusConfig[status] || statusConfig.draft;
        const Icon = config.icon;
        return (
            <span className={`inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ${config.color}`}>
                <Icon className="h-3.5 w-3.5" />
                {config.label}
            </span>
        );
    };

    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Inventaires
                </h2>
            }
        >
            <Head title="Inventaires" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header avec bouton créer */}
                    <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <ClipboardList className="h-7 w-7 text-amber-500" />
                                Inventaires physiques
                            </h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Gérez vos inventaires de stock et contrôlez les écarts
                            </p>
                        </div>
                        {permissions?.create && (
                            <Button
                                onClick={handleCreateInventory}
                                className="bg-amber-500 hover:bg-amber-600 text-white"
                            >
                                <Plus className="h-4 w-4 mr-2" />
                                Nouvel inventaire
                            </Button>
                        )}
                    </div>

                    {/* Filtres */}
                    <Card className="mb-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Filter className="h-5 w-5 mr-2 text-amber-500" />
                                Filtres
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleFilterSubmit} className="grid grid-cols-1 md:grid-cols-6 gap-4">
                                <div>
                                    <Label htmlFor="reference" className="text-gray-700 dark:text-gray-300">Référence</Label>
                                    <Input
                                        id="reference"
                                        placeholder="INV-..."
                                        value={searchRef}
                                        onChange={(e) => setSearchRef(e.target.value)}
                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="status" className="text-gray-700 dark:text-gray-300">Statut</Label>
                                    <select
                                        id="status"
                                        value={selectedStatus}
                                        onChange={(e) => setSelectedStatus(e.target.value)}
                                        className="w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    >
                                        <option value="">Tous</option>
                                        <option value="draft">Brouillon</option>
                                        <option value="in_progress">En cours</option>
                                        <option value="validated">Validé</option>
                                        <option value="cancelled">Annulé</option>
                                    </select>
                                </div>
                                <div>
                                    <Label htmlFor="depot_id" className="text-gray-700 dark:text-gray-300">Dépôt</Label>
                                    <select
                                        id="depot_id"
                                        value={selectedDepotId}
                                        onChange={(e) => setSelectedDepotId(e.target.value)}
                                        className="w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                    >
                                        <option value="">Tous les dépôts</option>
                                        {(depots || []).map((depot) => (
                                            <option key={depot.id} value={depot.id}>{depot.name} {depot.code ? `(${depot.code})` : ''}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label htmlFor="from" className="text-gray-700 dark:text-gray-300">Du</Label>
                                    <Input
                                        id="from"
                                        type="date"
                                        value={fromDate}
                                        onChange={(e) => setFromDate(e.target.value)}
                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="to" className="text-gray-700 dark:text-gray-300">Au</Label>
                                    <Input
                                        id="to"
                                        type="date"
                                        value={toDate}
                                        onChange={(e) => setToDate(e.target.value)}
                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div className="flex items-end gap-2">
                                    <Button type="submit" className="bg-amber-500 hover:bg-amber-600 text-white flex-1">
                                        <Search className="h-4 w-4 mr-2" />
                                        Filtrer
                                    </Button>
                                    <Button type="button" variant="outline" onClick={handleResetFilters} className="border-gray-300 dark:border-slate-600">
                                        <XCircle className="h-4 w-4" />
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Liste des inventaires */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <ClipboardList className="h-5 w-5 mr-2 text-blue-500" />
                                Liste des inventaires ({pagination?.total ?? inventories.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {inventories.length === 0 ? (
                                <div className="text-center py-12">
                                    <ClipboardList className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                        Aucun inventaire trouvé
                                    </h3>
                                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                                        {Object.keys(filters).length > 0
                                            ? 'Essayez de modifier vos filtres'
                                            : 'Commencez par créer un nouvel inventaire'}
                                    </p>
                                    {permissions?.create && Object.keys(filters).length === 0 && (
                                        <Button onClick={handleCreateInventory} className="bg-amber-500 hover:bg-amber-600 text-white">
                                            <Plus className="h-4 w-4 mr-2" />
                                            Créer un inventaire
                                        </Button>
                                    )}
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                        <thead className="bg-gray-50 dark:bg-slate-800">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Référence
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Dépôt
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Statut
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Produits
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Créé le
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Par
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                            {inventories.map((inventory) => (
                                                <tr key={inventory.id} className="hover:bg-gray-50 dark:hover:bg-slate-800">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {inventory.reference}
                                                        </div>
                                                        {inventory.validated_at && (
                                                            <div className="text-xs text-gray-500 dark:text-gray-400">
                                                                Validé le {inventory.validated_at}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {inventory.depot ? (
                                                            <span className="inline-flex items-center gap-1 text-sm text-gray-700 dark:text-gray-300">
                                                                <Package className="h-4 w-4 text-amber-500" />
                                                                {inventory.depot.name}
                                                                {inventory.depot.code && <span className="text-xs text-gray-500">({inventory.depot.code})</span>}
                                                            </span>
                                                        ) : (
                                                            <span className="text-xs text-gray-400">—</span>
                                                        )}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {getStatusBadge(inventory.status)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                        {inventory.items_count} produit(s)
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center text-sm text-gray-700 dark:text-gray-300">
                                                            <Calendar className="h-4 w-4 mr-1.5 text-gray-400" />
                                                            {inventory.created_at}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center text-sm text-gray-700 dark:text-gray-300">
                                                            <User className="h-4 w-4 mr-1.5 text-gray-400" />
                                                            {inventory.creator?.name ?? 'Inconnu'}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        {permissions?.view && (
                                                            <Link
                                                                href={route('pharmacy.inventories.show', inventory.id)}
                                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 text-sm font-medium rounded-md bg-blue-500 hover:bg-blue-600 text-white transition-colors"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                                Voir
                                                            </Link>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Pagination */}
                    {pagination && pagination.last_page > 1 && (
                        <div className="mt-4 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                            <div>
                                Affichage de <span className="font-medium text-gray-900 dark:text-white">{pagination.from}</span> à{' '}
                                <span className="font-medium text-gray-900 dark:text-white">{pagination.to}</span> sur{' '}
                                <span className="font-medium text-gray-900 dark:text-white">{pagination.total}</span> inventaire(s)
                            </div>
                            <div className="flex items-center space-x-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={pagination.current_page <= 1}
                                    onClick={() => handlePageChange(pagination.current_page - 1)}
                                    className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200"
                                >
                                    Précédent
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    disabled={pagination.current_page >= pagination.last_page}
                                    onClick={() => handlePageChange(pagination.current_page + 1)}
                                    className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200"
                                >
                                    Suivant
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
