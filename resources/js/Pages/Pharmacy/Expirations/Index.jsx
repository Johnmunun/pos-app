import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { 
    Calendar,
    Package,
    AlertTriangle,
    AlertCircle,
    CheckCircle,
    Filter,
    Search,
    Clock,
    ArrowLeft,
    Trash2,
    RefreshCw
} from 'lucide-react';
import axios from 'axios';
import toast from 'react-hot-toast';
import ExportButtons from '@/Components/Pharmacy/ExportButtons';

export default function ExpirationsIndex({ 
    batches = { data: [], current_page: 1, last_page: 1, total: 0 }, 
    summary = { expired_count: 0, expiring_soon_count: 0, total_batches: 0 },
    products = [],
    filters = {}
}) {
    const { shop } = usePage().props;
    
    const [search, setSearch] = useState(filters.search || '');
    const [productId, setProductId] = useState(filters.product_id || '');
    const [status, setStatus] = useState(filters.status || '');
    const [fromDate, setFromDate] = useState(filters.from_date || '');
    const [toDate, setToDate] = useState(filters.to_date || '');
    const [deleting, setDeleting] = useState(null);

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(route('pharmacy.expirations.index'), {
            search: search || undefined,
            product_id: productId || undefined,
            status: status || undefined,
            from_date: fromDate || undefined,
            to_date: toDate || undefined,
        }, { preserveState: true });
    };

    const handlePageChange = (page) => {
        router.get(route('pharmacy.expirations.index'), {
            ...filters,
            page,
        }, { preserveState: true });
    };

    const handleDelete = async (batchId) => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce lot ?')) return;
        
        setDeleting(batchId);
        try {
            await axios.delete(route('pharmacy.batches.destroy', batchId));
            toast.success('Lot supprimé avec succès');
            router.reload({ only: ['batches', 'summary'] });
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur lors de la suppression');
        } finally {
            setDeleting(null);
        }
    };

    const getStatusBadge = (status, daysUntil) => {
        switch (status) {
            case 'expired':
                return (
                    <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                        <AlertCircle className="h-3 w-3 mr-1" />
                        Expiré ({Math.abs(daysUntil)} j)
                    </Badge>
                );
            case 'expiring_soon':
                return (
                    <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                        <AlertTriangle className="h-3 w-3 mr-1" />
                        Expire dans {daysUntil} j
                    </Badge>
                );
            default:
                return (
                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                        <CheckCircle className="h-3 w-3 mr-1" />
                        OK ({daysUntil} j)
                    </Badge>
                );
        }
    };

    const formatDate = (dateString) => {
        if (!dateString) return '—';
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild className="hover:bg-gray-100 dark:hover:bg-gray-800">
                        <Link href={route('pharmacy.stock.index')} className="inline-flex items-center gap-2">
                            <ArrowLeft className="h-4 w-4" />
                            <span className="hidden sm:inline">Retour au Stock</span>
                        </Link>
                    </Button>
                    <div className="flex items-center gap-2">
                        <Calendar className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Gestion des Expirations
                        </h2>
                    </div>
                </div>
            }
        >
            <Head title="Gestion des Expirations" />
            
            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <p className="text-gray-500 dark:text-gray-400 text-sm">
                            Suivi des lots et dates de péremption
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <ExportButtons
                            pdfUrl={route('pharmacy.exports.expirations.pdf')}
                            excelUrl={route('pharmacy.exports.expirations.excel')}
                            disabled={!batches.data?.length}
                        />
                        <Button 
                            variant="outline"
                            onClick={() => router.reload()}
                        >
                            <RefreshCw className="h-4 w-4 mr-2" />
                            Actualiser
                        </Button>
                    </div>
                </div>

                {/* Stats Cards */}
                <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    {/* Lots expirés */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Lots expirés</p>
                                <p className="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">{summary.expired_count}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">nécessitent attention</p>
                            </div>
                            <div className="h-12 w-12 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center">
                                <AlertCircle className="h-6 w-6 text-red-600 dark:text-red-400" />
                            </div>
                        </div>
                    </div>

                    {/* Expirations proches */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Expire bientôt</p>
                                <p className="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1">{summary.expiring_soon_count}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">dans les 30 prochains jours</p>
                            </div>
                            <div className="h-12 w-12 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                                <AlertTriangle className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                        </div>
                    </div>

                    {/* Total lots */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-5">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-sm font-medium text-gray-500 dark:text-gray-400">Total lots</p>
                                <p className="text-2xl font-bold text-gray-900 dark:text-gray-100 mt-1">{summary.total_batches}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">lots en stock</p>
                            </div>
                            <div className="h-12 w-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                <Package className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Filtres */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <Filter className="h-5 w-5 text-gray-500" />
                            Filtres
                        </h2>
                    </div>
                    <div className="p-6">
                        <form onSubmit={handleFilter} className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                            <div>
                                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Recherche</label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input 
                                        type="text" 
                                        placeholder="Lot ou produit..."
                                        value={search} 
                                        onChange={(e) => setSearch(e.target.value)} 
                                        className="pl-10 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                                    />
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Produit</label>
                                <select
                                    className="w-full h-10 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 focus:ring-2 focus:ring-primary"
                                    value={productId}
                                    onChange={(e) => setProductId(e.target.value)}
                                >
                                    <option value="">Tous les produits</option>
                                    {products.map(p => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Statut</label>
                                <select
                                    className="w-full h-10 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 px-3 focus:ring-2 focus:ring-primary"
                                    value={status}
                                    onChange={(e) => setStatus(e.target.value)}
                                >
                                    <option value="">Tous</option>
                                    <option value="expired">Expirés</option>
                                    <option value="expiring_soon">Expire bientôt</option>
                                    <option value="ok">OK</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Du</label>
                                <Input 
                                    type="date" 
                                    value={fromDate} 
                                    onChange={(e) => setFromDate(e.target.value)} 
                                    className="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-2 text-gray-700 dark:text-gray-300">Au</label>
                                <Input 
                                    type="date" 
                                    value={toDate} 
                                    onChange={(e) => setToDate(e.target.value)} 
                                    className="bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                                />
                            </div>
                            <div className="flex items-end">
                                <Button type="submit" className="w-full">
                                    <Search className="h-4 w-4 mr-2" />
                                    Filtrer
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>

                {/* Liste des lots */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                            <Package className="h-5 w-5 text-gray-500" />
                            Lots ({batches.total})
                        </h2>
                    </div>

                    {batches.data.length === 0 ? (
                        <div className="py-12 text-center">
                            <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                            <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                Aucun lot trouvé
                            </p>
                            <p className="text-gray-500 dark:text-gray-400">
                                Modifiez vos filtres ou créez des lots via les achats
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Produit
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                N° Lot
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Quantité
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Date d'expiration
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {batches.data.map((batch) => (
                                            <tr 
                                                key={batch.id} 
                                                className={`hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors ${
                                                    batch.status === 'expired' ? 'bg-red-50/50 dark:bg-red-900/10' : 
                                                    batch.status === 'expiring_soon' ? 'bg-amber-50/50 dark:bg-amber-900/10' : ''
                                                }`}
                                            >
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                                            <Package className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-gray-900 dark:text-gray-100">
                                                                {batch.product_name}
                                                            </p>
                                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                                {batch.product_code}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <span className="font-mono text-sm text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                                        {batch.batch_number}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <span className="font-semibold text-gray-900 dark:text-gray-100">
                                                        {batch.quantity}
                                                    </span>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-2">
                                                        <Clock className="h-4 w-4 text-gray-400" />
                                                        <span className="text-gray-700 dark:text-gray-300">
                                                            {formatDate(batch.expiration_date)}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {getStatusBadge(batch.status, batch.days_until_expiration)}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <Button 
                                                        variant="ghost" 
                                                        size="sm"
                                                        onClick={() => handleDelete(batch.id)}
                                                        disabled={deleting === batch.id}
                                                        className="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>

                            {/* Pagination */}
                            {batches.last_page > 1 && (
                                <div className="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/30">
                                    <p className="text-sm text-gray-600 dark:text-gray-400">
                                        Page {batches.current_page} sur {batches.last_page} ({batches.total} lots)
                                    </p>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={batches.current_page <= 1}
                                            onClick={() => handlePageChange(batches.current_page - 1)}
                                        >
                                            Précédent
                                        </Button>
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            disabled={batches.current_page >= batches.last_page}
                                            onClick={() => handlePageChange(batches.current_page + 1)}
                                        >
                                            Suivant
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
