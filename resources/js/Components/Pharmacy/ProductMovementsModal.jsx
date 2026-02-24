import React, { useState, useEffect, useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    X,
    Search,
    FileDown,
    Printer,
    Filter,
    TrendingUp,
    TrendingDown,
    RefreshCw,
    Package,
    Calendar,
    User,
    Hash,
    ArrowUpCircle,
    ArrowDownCircle,
    AlertCircle,
    Loader2,
    ChevronLeft,
    ChevronRight
} from 'lucide-react';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import { formatCurrency } from '@/lib/currency';

const ITEMS_PER_PAGE = 20;

export default function ProductMovementsModal({ 
    isOpen, 
    onClose, 
    product = null,
    initialFilters = {}
}) {
    const { auth, shop } = usePage().props;
    const permissions = auth?.permissions || [];
    const currency = shop?.currency || 'CDF';

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canView = hasPermission('stock.movement.view');
    const canPrint = hasPermission('stock.movement.print');

    // États
    const [loading, setLoading] = useState(false);
    const [movements, setMovements] = useState([]);
    const [stats, setStats] = useState({
        total_movements: 0,
        total_in: 0,
        total_out: 0,
        total_adjustment: 0
    });
    const [currentPage, setCurrentPage] = useState(1);
    const [exporting, setExporting] = useState(false);
    const [exportingSingle, setExportingSingle] = useState(null);

    // Filtres
    const [filters, setFilters] = useState({
        product_id: product?.id || initialFilters.product_id || '',
        product_name: product?.name || initialFilters.product_name || '',
        product_code: initialFilters.product_code || '',
        type: initialFilters.type || '',
        from: initialFilters.from || '',
        to: initialFilters.to || ''
    });

    // Charger les mouvements
    const loadMovements = useCallback(async () => {
        if (!canView) {
            toast.error('Vous n\'avez pas la permission de voir les mouvements');
            return;
        }

        setLoading(true);
        try {
            const params = new URLSearchParams();
            if (filters.product_id) params.append('product_id', filters.product_id);
            if (filters.product_name) params.append('product_name', filters.product_name);
            if (filters.product_code) params.append('product_code', filters.product_code);
            if (filters.type) params.append('type', filters.type);
            if (filters.from) params.append('from', filters.from);
            if (filters.to) params.append('to', filters.to);

            const url = `${route('pharmacy.api.product-movements.index')}?${params.toString()}`;
            const response = await axios.get(url);
            setMovements(response.data.movements || []);
            setStats(response.data.stats || {});
            setCurrentPage(1);
        } catch (error) {
            console.error('Error loading movements:', error);
            toast.error('Erreur lors du chargement des mouvements');
        } finally {
            setLoading(false);
        }
    }, [filters, canView]);

    // Charger au montage et quand le produit change
    useEffect(() => {
        if (isOpen && canView) {
            loadMovements();
        }
    }, [isOpen, canView]);

    // Mettre à jour les filtres quand le produit change
    useEffect(() => {
        if (product) {
            setFilters(prev => ({
                ...prev,
                product_id: product.id,
                product_name: product.name
            }));
        }
    }, [product]);

    // Pagination
    const totalPages = Math.ceil(movements.length / ITEMS_PER_PAGE);
    const paginatedMovements = movements.slice(
        (currentPage - 1) * ITEMS_PER_PAGE,
        currentPage * ITEMS_PER_PAGE
    );

    // Export PDF global
    const handleExportGlobal = async () => {
        if (!canPrint) {
            toast.error('Vous n\'avez pas la permission d\'imprimer');
            return;
        }

        setExporting(true);
        try {
            const params = new URLSearchParams();
            if (filters.product_id) params.append('product_id', filters.product_id);
            if (filters.product_name) params.append('product_name', filters.product_name);
            if (filters.product_code) params.append('product_code', filters.product_code);
            if (filters.type) params.append('type', filters.type);
            if (filters.from) params.append('from', filters.from);
            if (filters.to) params.append('to', filters.to);

            const pdfUrl = `${route('pharmacy.api.product-movements.pdf.global')}?${params.toString()}`;
            const response = await axios.get(pdfUrl, {
                responseType: 'blob'
            });

            const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = blobUrl;
            link.setAttribute('download', `mouvements_stock_${new Date().toISOString().slice(0, 10)}.pdf`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(blobUrl);

            toast.success('Export PDF généré avec succès');
        } catch (error) {
            console.error('Error exporting:', error);
            toast.error('Erreur lors de l\'export PDF');
        } finally {
            setExporting(false);
        }
    };

    // Export PDF individuel
    const handleExportSingle = async (movementId) => {
        if (!canPrint) {
            toast.error('Vous n\'avez pas la permission d\'imprimer');
            return;
        }

        setExportingSingle(movementId);
        try {
            const response = await axios.get(route('pharmacy.api.product-movements.pdf.single', { id: movementId }), {
                responseType: 'blob'
            });

            const blobUrl = window.URL.createObjectURL(new Blob([response.data]));
            const link = document.createElement('a');
            link.href = blobUrl;
            link.setAttribute('download', `mouvement_${movementId.slice(0, 8)}.pdf`);
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(blobUrl);

            toast.success('Fiche de mouvement générée');
        } catch (error) {
            console.error('Error exporting single:', error);
            toast.error('Erreur lors de l\'export');
        } finally {
            setExportingSingle(null);
        }
    };

    // Réinitialiser les filtres
    const resetFilters = () => {
        setFilters({
            product_id: product?.id || '',
            product_name: product?.name || '',
            product_code: '',
            type: '',
            from: '',
            to: ''
        });
    };

    // Type badge avec couleur
    const getTypeBadge = (type) => {
        switch (type) {
            case 'IN':
                return (
                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                        <ArrowUpCircle className="h-3 w-3 mr-1" />
                        Entrée
                    </Badge>
                );
            case 'OUT':
                return (
                    <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                        <ArrowDownCircle className="h-3 w-3 mr-1" />
                        Sortie
                    </Badge>
                );
            case 'ADJUSTMENT':
                return (
                    <Badge className="bg-orange-100 text-orange-800 dark:bg-orange-900/50 dark:text-orange-300">
                        <AlertCircle className="h-3 w-3 mr-1" />
                        Ajustement
                    </Badge>
                );
            case 'RETURN':
                return (
                    <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                        <RefreshCw className="h-3 w-3 mr-1" />
                        Retour
                    </Badge>
                );
            default:
                return <Badge>{type}</Badge>;
        }
    };

    // Quantité avec signe
    const getQuantityDisplay = (type, quantity) => {
        switch (type) {
            case 'IN':
            case 'RETURN':
                return <span className="text-green-600 dark:text-green-400 font-semibold">+{quantity}</span>;
            case 'OUT':
                return <span className="text-red-600 dark:text-red-400 font-semibold">-{quantity}</span>;
            default:
                return <span className="text-orange-600 dark:text-orange-400 font-semibold">{quantity}</span>;
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 overflow-hidden">
            {/* Backdrop */}
            <div 
                className="absolute inset-0 bg-black/50 transition-opacity"
                onClick={onClose}
            />

            {/* Modal */}
            <div className="absolute inset-4 md:inset-8 lg:inset-12 bg-white dark:bg-slate-900 rounded-xl shadow-2xl flex flex-col overflow-hidden">
                {/* Header */}
                <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800">
                    <div className="flex items-center gap-3">
                        <div className="h-10 w-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center">
                            <Package className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                                Historique des Mouvements
                            </h2>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                {product ? `Produit: ${product.name}` : 'Tous les produits'}
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-lg transition-colors"
                    >
                        <X className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    </button>
                </div>

                {/* Statistiques */}
                <div className="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900">
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="bg-gray-50 dark:bg-slate-800 rounded-lg p-3">
                            <p className="text-xs text-gray-500 dark:text-gray-400">Total mouvements</p>
                            <p className="text-xl font-bold text-gray-900 dark:text-white">{stats.total_movements}</p>
                        </div>
                        <div className="bg-green-50 dark:bg-green-900/20 rounded-lg p-3">
                            <p className="text-xs text-green-600 dark:text-green-400">Entrées</p>
                            <p className="text-xl font-bold text-green-700 dark:text-green-300">+{stats.total_in}</p>
                        </div>
                        <div className="bg-red-50 dark:bg-red-900/20 rounded-lg p-3">
                            <p className="text-xs text-red-600 dark:text-red-400">Sorties</p>
                            <p className="text-xl font-bold text-red-700 dark:text-red-300">-{stats.total_out}</p>
                        </div>
                        <div className="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-3">
                            <p className="text-xs text-orange-600 dark:text-orange-400">Ajustements</p>
                            <p className="text-xl font-bold text-orange-700 dark:text-orange-300">{stats.total_adjustment}</p>
                        </div>
                    </div>
                </div>

                {/* Filtres */}
                <div className="px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800/50">
                    <div className="flex flex-wrap items-end gap-4">
                        {!product && (
                            <>
                                <div className="flex-1 min-w-[180px]">
                                    <Label className="text-xs mb-1 block">Nom produit</Label>
                                    <Input
                                        type="text"
                                        placeholder="Rechercher..."
                                        value={filters.product_name}
                                        onChange={(e) => setFilters(prev => ({ ...prev, product_name: e.target.value }))}
                                        className="h-9"
                                    />
                                </div>
                                <div className="w-[140px]">
                                    <Label className="text-xs mb-1 block">Code produit</Label>
                                    <Input
                                        type="text"
                                        placeholder="Code..."
                                        value={filters.product_code}
                                        onChange={(e) => setFilters(prev => ({ ...prev, product_code: e.target.value }))}
                                        className="h-9"
                                    />
                                </div>
                            </>
                        )}
                        <div className="w-[130px]">
                            <Label className="text-xs mb-1 block">Type</Label>
                            <select
                                value={filters.type}
                                onChange={(e) => setFilters(prev => ({ ...prev, type: e.target.value }))}
                                className="w-full h-9 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white text-sm px-2"
                            >
                                <option value="">Tous</option>
                                <option value="IN">Entrée</option>
                                <option value="OUT">Sortie</option>
                                <option value="ADJUSTMENT">Ajustement</option>
                            </select>
                        </div>
                        <div className="w-[140px]">
                            <Label className="text-xs mb-1 block">Date début</Label>
                            <Input
                                type="date"
                                value={filters.from}
                                onChange={(e) => setFilters(prev => ({ ...prev, from: e.target.value }))}
                                className="h-9"
                            />
                        </div>
                        <div className="w-[140px]">
                            <Label className="text-xs mb-1 block">Date fin</Label>
                            <Input
                                type="date"
                                value={filters.to}
                                onChange={(e) => setFilters(prev => ({ ...prev, to: e.target.value }))}
                                className="h-9"
                            />
                        </div>
                        <div className="flex gap-2">
                            <Button
                                onClick={loadMovements}
                                disabled={loading}
                                size="sm"
                                className="h-9"
                            >
                                {loading ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <Search className="h-4 w-4" />
                                )}
                                <span className="ml-1">Filtrer</span>
                            </Button>
                            <Button
                                onClick={resetFilters}
                                variant="outline"
                                size="sm"
                                className="h-9"
                            >
                                <RefreshCw className="h-4 w-4" />
                            </Button>
                        </div>
                        {canPrint && (
                            <Button
                                onClick={handleExportGlobal}
                                disabled={exporting || movements.length === 0}
                                variant="outline"
                                size="sm"
                                className="h-9 ml-auto"
                            >
                                {exporting ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <FileDown className="h-4 w-4" />
                                )}
                                <span className="ml-1">Export PDF</span>
                            </Button>
                        )}
                    </div>
                </div>

                {/* Tableau des mouvements */}
                <div className="flex-1 overflow-auto">
                    {loading ? (
                        <div className="flex items-center justify-center h-64">
                            <Loader2 className="h-8 w-8 animate-spin text-amber-500" />
                        </div>
                    ) : movements.length === 0 ? (
                        <div className="flex flex-col items-center justify-center h-64 text-gray-500 dark:text-gray-400">
                            <Package className="h-16 w-16 mb-4 opacity-30" />
                            <p className="text-lg font-medium">Aucun mouvement trouvé</p>
                            <p className="text-sm">Modifiez vos filtres pour voir les résultats</p>
                        </div>
                    ) : (
                        <table className="w-full">
                            <thead className="bg-gray-50 dark:bg-slate-800 sticky top-0 z-10">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Date
                                    </th>
                                    {!product && (
                                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Produit
                                        </th>
                                    )}
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Référence
                                    </th>
                                    <th className="px-4 py-3 text-center text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Quantité
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Utilisateur
                                    </th>
                                    {canPrint && (
                                        <th className="px-4 py-3 text-center text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                            Action
                                        </th>
                                    )}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-slate-700">
                                {paginatedMovements.map((movement) => (
                                    <tr 
                                        key={movement.id}
                                        className="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors"
                                    >
                                        <td className="px-4 py-3 whitespace-nowrap">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm text-gray-900 dark:text-white">
                                                    {movement.created_at_formatted}
                                                </span>
                                            </div>
                                        </td>
                                        {!product && (
                                            <td className="px-4 py-3">
                                                <div>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {movement.product_name}
                                                    </p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {movement.product_code}
                                                    </p>
                                                </div>
                                            </td>
                                        )}
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <Hash className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                                    {movement.reference || '—'}
                                                </span>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-center">
                                            {getTypeBadge(movement.type)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <span className="text-lg">
                                                {getQuantityDisplay(movement.type, movement.quantity)}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <User className="h-4 w-4 text-gray-400" />
                                                <span className="text-sm text-gray-700 dark:text-gray-300">
                                                    {movement.created_by_name}
                                                </span>
                                            </div>
                                        </td>
                                        {canPrint && (
                                            <td className="px-4 py-3 text-center">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() => handleExportSingle(movement.id)}
                                                    disabled={exportingSingle === movement.id}
                                                    title="Imprimer fiche"
                                                >
                                                    {exportingSingle === movement.id ? (
                                                        <Loader2 className="h-4 w-4 animate-spin" />
                                                    ) : (
                                                        <Printer className="h-4 w-4" />
                                                    )}
                                                </Button>
                                            </td>
                                        )}
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                {/* Pagination */}
                {totalPages > 1 && (
                    <div className="px-6 py-3 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-between">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Page {currentPage} sur {totalPages} ({movements.length} mouvements)
                        </p>
                        <div className="flex gap-2">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setCurrentPage(p => Math.max(1, p - 1))}
                                disabled={currentPage === 1}
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setCurrentPage(p => Math.min(totalPages, p + 1))}
                                disabled={currentPage === totalPages}
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
