import React, { useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { useToast } from '@/Components/ui/use-toast';
import axios from 'axios';
import {
    ClipboardList,
    ArrowLeft,
    Play,
    CheckCircle,
    XCircle,
    Clock,
    FileText,
    Save,
    Download,
    TrendingUp,
    TrendingDown,
    Minus,
    Search,
    Package,
    AlertTriangle,
    User,
    Calendar
} from 'lucide-react';

const statusConfig = {
    draft: { label: 'Brouillon', color: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', icon: FileText },
    in_progress: { label: 'En cours', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', icon: Clock },
    validated: { label: 'Validé', color: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', icon: CheckCircle },
    cancelled: { label: 'Annulé', color: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', icon: XCircle },
};

export default function InventoryShow({ inventory, items, stats, availableProducts = [], categories = [], permissions }) {
    const { toast } = useToast();
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedCategory, setSelectedCategory] = useState('');
    const [showOnlyDifferences, setShowOnlyDifferences] = useState(false);
    const [counts, setCounts] = useState(() => {
        const initial = {};
        items.forEach(item => {
            initial[item.product_id] = item.counted_quantity ?? '';
        });
        return initial;
    });
    const [isSaving, setIsSaving] = useState(false);
    const [selectedProducts, setSelectedProducts] = useState([]);
    const [showValidateModal, setShowValidateModal] = useState(false);
    const [showCancelModal, setShowCancelModal] = useState(false);

    const canEdit = permissions?.edit && ['draft', 'in_progress'].includes(inventory.status);
    const canValidate = permissions?.validate && inventory.status === 'in_progress';
    const canCancel = permissions?.cancel && inventory.status !== 'validated';
    const isDraft = inventory.status === 'draft';
    const canExportPdf = permissions?.view;

    // Filtrer les items
    const filteredItems = useMemo(() => {
        return items.filter(item => {
            if (searchTerm) {
                const search = searchTerm.toLowerCase();
                if (!item.product_name.toLowerCase().includes(search) &&
                    !item.product_code.toLowerCase().includes(search)) {
                    return false;
                }
            }
            if (selectedCategory && item.category_name !== selectedCategory) {
                return false;
            }
            if (showOnlyDifferences && item.difference === 0) {
                return false;
            }
            return true;
        });
    }, [items, searchTerm, selectedCategory, showOnlyDifferences]);

    // Calculer les stats en temps réel
    const realTimeStats = useMemo(() => {
        let totalPositive = 0;
        let totalNegative = 0;
        let countedItems = 0;
        let itemsWithDiff = 0;

        items.forEach(item => {
            const counted = counts[item.product_id];
            if (counted !== '' && counted !== null && counted !== undefined) {
                countedItems++;
                const diff = parseInt(counted, 10) - item.system_quantity;
                if (diff > 0) {
                    totalPositive += diff;
                    itemsWithDiff++;
                } else if (diff < 0) {
                    totalNegative += Math.abs(diff);
                    itemsWithDiff++;
                }
            }
        });

        return {
            total_items: items.length,
            counted_items: countedItems,
            items_with_difference: itemsWithDiff,
            total_positive: totalPositive,
            total_negative: totalNegative,
        };
    }, [items, counts]);

    const handleCountChange = (productId, value) => {
        setCounts(prev => ({
            ...prev,
            [productId]: value,
        }));
    };

    const handleSaveCounts = async () => {
        setIsSaving(true);
        try {
            const countsToSave = Object.entries(counts)
                .filter(([_, value]) => value !== '' && value !== null && value !== undefined)
                .map(([productId, countedQuantity]) => ({
                    product_id: productId,
                    counted_quantity: parseInt(countedQuantity, 10),
                }));

            if (countsToSave.length === 0) {
                toast({
                    title: 'Attention',
                    description: 'Aucune quantité à sauvegarder.',
                    variant: 'warning',
                });
                return;
            }

            await axios.post(route('pharmacy.inventories.counts', inventory.id), {
                counts: countsToSave,
            });

            toast({
                title: 'Succès',
                description: 'Quantités sauvegardées avec succès.',
            });

            router.reload();
        } catch (error) {
            toast({
                title: 'Erreur',
                description: error.response?.data?.message || 'Erreur lors de la sauvegarde.',
                variant: 'destructive',
            });
        } finally {
            setIsSaving(false);
        }
    };

    const handleStart = () => {
        const productIds = selectedProducts.length > 0 ? selectedProducts : null;
        router.post(route('pharmacy.inventories.start', inventory.id), {
            product_ids: productIds,
        });
    };

    const handleValidateClick = () => setShowValidateModal(true);
    const handleValidateConfirm = () => {
        setShowValidateModal(false);
        router.post(route('pharmacy.inventories.validate', inventory.id));
    };

    const handleCancelClick = () => setShowCancelModal(true);
    const handleCancelConfirm = () => {
        setShowCancelModal(false);
        router.post(route('pharmacy.inventories.cancel', inventory.id));
    };

    const getDifferenceDisplay = (item) => {
        const counted = counts[item.product_id];
        if (counted === '' || counted === null || counted === undefined) {
            return <Minus className="h-4 w-4 text-gray-400" />;
        }
        const diff = parseInt(counted, 10) - item.system_quantity;
        if (diff === 0) {
            return <span className="text-gray-500">0</span>;
        }
        if (diff > 0) {
            return (
                <span className="flex items-center text-green-600 dark:text-green-400 font-medium">
                    <TrendingUp className="h-4 w-4 mr-1" />
                    +{diff}
                </span>
            );
        }
        return (
            <span className="flex items-center text-red-600 dark:text-red-400 font-medium">
                <TrendingDown className="h-4 w-4 mr-1" />
                {diff}
            </span>
        );
    };

    const getStatusBadge = (status) => {
        const config = statusConfig[status] || statusConfig.draft;
        const Icon = config.icon;
        return (
            <span className={`inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium ${config.color}`}>
                <Icon className="h-4 w-4" />
                {config.label}
            </span>
        );
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Link
                        href={route('pharmacy.inventories.index')}
                        className="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <ArrowLeft className="h-5 w-5 text-gray-600 dark:text-gray-300" />
                    </Link>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Inventaire {inventory.reference}
                    </h2>
                </div>
            }
        >
            <Head title={`Inventaire ${inventory.reference}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header avec infos inventaire */}
                    <Card className="mb-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardContent className="p-6">
                            <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div>
                                    <div className="flex items-center gap-3 mb-2">
                                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                            {inventory.reference}
                                        </h1>
                                        {getStatusBadge(inventory.status)}
                                    </div>
                                    <div className="flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                        {inventory.depot && (
                                            <span className="flex items-center gap-1">
                                                <Package className="h-4 w-4 text-amber-500" />
                                                Dépôt : {inventory.depot.name}
                                                {inventory.depot.code && ` (${inventory.depot.code})`}
                                            </span>
                                        )}
                                        <span className="flex items-center gap-1">
                                            <Calendar className="h-4 w-4" />
                                            Créé le {inventory.created_at}
                                        </span>
                                        <span className="flex items-center gap-1">
                                            <User className="h-4 w-4" />
                                            Par {inventory.creator?.name ?? 'Inconnu'}
                                        </span>
                                        {inventory.validated_at && (
                                            <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                                <CheckCircle className="h-4 w-4" />
                                                Validé le {inventory.validated_at}
                                            </span>
                                        )}
                                    </div>
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    {canExportPdf && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => window.open(route('pharmacy.exports.inventories.pdf', inventory.id), '_blank')}
                                            className="border-gray-300 dark:border-slate-600"
                                        >
                                            <Download className="h-4 w-4 mr-2" />
                                            PDF
                                        </Button>
                                    )}
                                    {isDraft && canEdit && (
                                        <Button
                                            onClick={handleStart}
                                            className="bg-blue-500 hover:bg-blue-600 text-white"
                                        >
                                            <Play className="h-4 w-4 mr-2" />
                                            Démarrer
                                        </Button>
                                    )}
                                    {canEdit && !isDraft && (
                                        <Button
                                            onClick={handleSaveCounts}
                                            disabled={isSaving}
                                            className="bg-amber-500 hover:bg-amber-600 text-white"
                                        >
                                            <Save className="h-4 w-4 mr-2" />
                                            {isSaving ? 'Sauvegarde...' : 'Sauvegarder'}
                                        </Button>
                                    )}
                                    {canValidate && (
                                        <Button
                                            onClick={handleValidateClick}
                                            className="bg-green-500 hover:bg-green-600 text-white"
                                        >
                                            <CheckCircle className="h-4 w-4 mr-2" />
                                            Valider
                                        </Button>
                                    )}
                                    {canCancel && (
                                        <Button
                                            onClick={handleCancelClick}
                                            variant="outline"
                                            className="border-red-300 text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20"
                                        >
                                            <XCircle className="h-4 w-4 mr-2" />
                                            Annuler
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Stats */}
                    <div className="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardContent className="p-4 text-center">
                                <Package className="h-6 w-6 text-blue-500 mx-auto mb-2" />
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {realTimeStats.total_items}
                                </div>
                                <div className="text-xs text-gray-500 dark:text-gray-400">Produits</div>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardContent className="p-4 text-center">
                                <CheckCircle className="h-6 w-6 text-green-500 mx-auto mb-2" />
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {realTimeStats.counted_items}
                                </div>
                                <div className="text-xs text-gray-500 dark:text-gray-400">Comptés</div>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardContent className="p-4 text-center">
                                <AlertTriangle className="h-6 w-6 text-orange-500 mx-auto mb-2" />
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {realTimeStats.items_with_difference}
                                </div>
                                <div className="text-xs text-gray-500 dark:text-gray-400">Écarts</div>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardContent className="p-4 text-center">
                                <TrendingUp className="h-6 w-6 text-green-500 mx-auto mb-2" />
                                <div className="text-2xl font-bold text-green-600 dark:text-green-400">
                                    +{realTimeStats.total_positive}
                                </div>
                                <div className="text-xs text-gray-500 dark:text-gray-400">Surplus</div>
                            </CardContent>
                        </Card>
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardContent className="p-4 text-center">
                                <TrendingDown className="h-6 w-6 text-red-500 mx-auto mb-2" />
                                <div className="text-2xl font-bold text-red-600 dark:text-red-400">
                                    -{realTimeStats.total_negative}
                                </div>
                                <div className="text-xs text-gray-500 dark:text-gray-400">Manquants</div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Liste des items */}
                    {isDraft ? (
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader>
                                <CardTitle className="text-gray-900 dark:text-white">
                                    Démarrer l'inventaire
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-gray-600 dark:text-gray-400 mb-4">
                                    Cliquez sur "Démarrer" pour créer le snapshot du stock actuel.
                                    Tous les produits actifs seront inclus dans l'inventaire.
                                </p>
                                {canEdit && (
                                    <Button
                                        onClick={handleStart}
                                        className="bg-blue-500 hover:bg-blue-600 text-white"
                                    >
                                        <Play className="h-4 w-4 mr-2" />
                                        Démarrer l'inventaire
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    ) : (
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader>
                                <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                    <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                        <ClipboardList className="h-5 w-5 mr-2 text-amber-500" />
                                        Produits à inventorier
                                    </CardTitle>
                                    <div className="flex flex-wrap items-center gap-3">
                                        <div className="relative">
                                            <Search className="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                            <Input
                                                placeholder="Rechercher..."
                                                value={searchTerm}
                                                onChange={(e) => setSearchTerm(e.target.value)}
                                                className="pl-9 w-48 bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                            />
                                        </div>
                                        <select
                                            value={selectedCategory}
                                            onChange={(e) => setSelectedCategory(e.target.value)}
                                            className="h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                        >
                                            <option value="">Toutes catégories</option>
                                            {categories.map(cat => (
                                                <option key={cat.id} value={cat.name}>{cat.name}</option>
                                            ))}
                                        </select>
                                        <label className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <input
                                                type="checkbox"
                                                checked={showOnlyDifferences}
                                                onChange={(e) => setShowOnlyDifferences(e.target.checked)}
                                                className="rounded border-gray-300 dark:border-slate-600 text-amber-500 focus:ring-amber-500"
                                            />
                                            Écarts uniquement
                                        </label>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                        <thead className="bg-gray-50 dark:bg-slate-800">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Produit
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Catégorie
                                                </th>
                                                <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Stock système
                                                </th>
                                                <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Qté comptée
                                                </th>
                                                <th className="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Écart
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                            {filteredItems.map((item) => (
                                                <tr key={item.id} className="hover:bg-gray-50 dark:hover:bg-slate-800">
                                                    <td className="px-4 py-3 whitespace-nowrap">
                                                        <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {item.product_name}
                                                        </div>
                                                        <div className="text-xs text-gray-500 dark:text-gray-400">
                                                            {item.product_code}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                        {item.category_name || '-'}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-center">
                                                        <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                            {item.system_quantity}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-center">
                                                        {canEdit ? (
                                                            <Input
                                                                type="number"
                                                                min="0"
                                                                value={counts[item.product_id] ?? ''}
                                                                onChange={(e) => handleCountChange(item.product_id, e.target.value)}
                                                                className="w-24 mx-auto text-center bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                                                placeholder="—"
                                                            />
                                                        ) : (
                                                            <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                                {item.counted_quantity ?? '—'}
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 whitespace-nowrap text-center">
                                                        {getDifferenceDisplay(item)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {filteredItems.length === 0 && (
                                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                                        Aucun produit ne correspond aux filtres
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>

            {/* Modal confirmation validation inventaire */}
            {showValidateModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6 border border-gray-200 dark:border-slate-700">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Valider l'inventaire
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Êtes-vous sûr de vouloir valider cet inventaire ? Les ajustements de stock seront appliqués.
                        </p>
                        <div className="flex gap-3 justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowValidateModal(false)}
                                className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300"
                            >
                                Annuler
                            </Button>
                            <Button
                                type="button"
                                onClick={handleValidateConfirm}
                                className="bg-green-500 hover:bg-green-600 text-white"
                            >
                                Valider
                            </Button>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal confirmation annulation inventaire */}
            {showCancelModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="bg-white dark:bg-slate-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6 border border-gray-200 dark:border-slate-700">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            Annuler l'inventaire
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Êtes-vous sûr de vouloir annuler cet inventaire ?
                        </p>
                        <div className="flex gap-3 justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setShowCancelModal(false)}
                                className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-300"
                            >
                                Non
                            </Button>
                            <Button
                                type="button"
                                onClick={handleCancelConfirm}
                                variant="outline"
                                className="border-red-300 text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20"
                            >
                                Oui, annuler
                            </Button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
