import React, { useState } from 'react';
import { Head, router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowRightLeft,
    ArrowLeft,
    Plus,
    Trash2,
    Save,
    Search,
    Package,
    Building2,
    ArrowRight,
    AlertCircle,
    Loader2,
    CheckCircle,
    XCircle,
    Clock,
    FileText,
    Calendar,
    User,
    Edit2
} from 'lucide-react';
import axios from 'axios';
import { toast } from 'react-hot-toast';

export default function TransferShow({ transfer, products }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canEdit = hasPermission('transfer.create') && transfer.status === 'draft';
    const canValidate = hasPermission('transfer.validate') && transfer.status === 'draft';
    const canCancel = hasPermission('transfer.cancel') && transfer.status === 'draft';
    const canPrint = hasPermission('transfer.print');

    const [loading, setLoading] = useState(false);
    const [actionLoading, setActionLoading] = useState('');
    const [searchTerm, setSearchTerm] = useState('');
    const [addQuantity, setAddQuantity] = useState(1);
    const [editingItem, setEditingItem] = useState(null);
    const [editQuantity, setEditQuantity] = useState(0);

    // Filtrer les produits non ajoutés
    const availableProducts = products.filter(
        p => !transfer.items.find(i => i.product_id === p.id)
    ).filter(p =>
        p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (p.code && p.code.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    // Ajouter un produit
    const handleAddItem = async (product) => {
        setLoading(true);
        try {
            const response = await axios.post(route('pharmacy.transfers.items.add', transfer.id), {
                product_id: product.id,
                quantity: addQuantity
            });

            if (response.data.success) {
                toast.success('Produit ajouté');
                router.reload();
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur');
        } finally {
            setLoading(false);
            setSearchTerm('');
            setAddQuantity(1);
        }
    };

    // Mettre à jour la quantité
    const handleUpdateQuantity = async (itemId) => {
        if (editQuantity < 1) {
            toast.error('La quantité doit être au moins 1');
            return;
        }

        setActionLoading(`update-${itemId}`);
        try {
            const response = await axios.put(
                route('pharmacy.transfers.items.update', { id: transfer.id, itemId }),
                { quantity: editQuantity }
            );

            if (response.data.success) {
                toast.success('Quantité mise à jour');
                setEditingItem(null);
                router.reload();
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur');
        } finally {
            setActionLoading('');
        }
    };

    // Supprimer un item
    const handleRemoveItem = async (itemId) => {
        if (!confirm('Supprimer ce produit du transfert ?')) return;

        setActionLoading(`remove-${itemId}`);
        try {
            const response = await axios.delete(
                route('pharmacy.transfers.items.remove', { id: transfer.id, itemId })
            );

            if (response.data.success) {
                toast.success('Produit retiré');
                router.reload();
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur');
        } finally {
            setActionLoading('');
        }
    };

    // Valider le transfert
    const handleValidate = async () => {
        if (!confirm('Valider ce transfert ? Cette action est irréversible et effectuera les mouvements de stock.')) return;

        setActionLoading('validate');
        try {
            const response = await axios.post(route('pharmacy.transfers.validate', transfer.id));

            if (response.data.success) {
                toast.success('Transfert validé avec succès');
                router.reload();
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors de la validation');
        } finally {
            setActionLoading('');
        }
    };

    // Annuler le transfert
    const handleCancel = async () => {
        if (!confirm('Annuler ce transfert ?')) return;

        setActionLoading('cancel');
        try {
            const response = await axios.post(route('pharmacy.transfers.cancel', transfer.id));

            if (response.data.success) {
                toast.success('Transfert annulé');
                router.reload();
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors de l\'annulation');
        } finally {
            setActionLoading('');
        }
    };

    const getStatusBadge = (status) => {
        switch (status) {
            case 'draft':
                return (
                    <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 text-sm px-3 py-1">
                        <Clock className="h-4 w-4 mr-1" />
                        Brouillon
                    </Badge>
                );
            case 'validated':
                return (
                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300 text-sm px-3 py-1">
                        <CheckCircle className="h-4 w-4 mr-1" />
                        Validé
                    </Badge>
                );
            case 'cancelled':
                return (
                    <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 text-sm px-3 py-1">
                        <XCircle className="h-4 w-4 mr-1" />
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
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Transfert {transfer.reference}
                        </h2>
                        <div className="mt-1">
                            {getStatusBadge(transfer.status)}
                        </div>
                    </div>
                    <div className="flex gap-3">
                        {canPrint && transfer.status === 'validated' && (
                            <a href={route('pharmacy.transfers.pdf', transfer.id)} target="_blank" rel="noreferrer">
                                <Button variant="outline">
                                    <FileText className="h-4 w-4 mr-2" />
                                    Imprimer PDF
                                </Button>
                            </a>
                        )}
                        <Link href={route('pharmacy.transfers.index')}>
                            <Button variant="outline">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Retour
                            </Button>
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title={`Transfert ${transfer.reference}`} />

            <div className="py-6">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Informations du transfert */}
                    <div className="grid md:grid-cols-2 gap-6">
                        {/* Magasins */}
                        <Card className="bg-white dark:bg-slate-800">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <ArrowRightLeft className="h-5 w-5 text-blue-600" />
                                    Direction du Transfert
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="flex items-center gap-4">
                                    {/* Source */}
                                    <div className="flex-1 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                                        <div className="flex items-center gap-2 text-red-600 dark:text-red-400 text-sm font-medium mb-1">
                                            <Building2 className="h-4 w-4" />
                                            Magasin Source
                                        </div>
                                        <div className="text-lg font-bold text-gray-900 dark:text-white">
                                            {transfer.from_shop_name}
                                        </div>
                                    </div>

                                    <ArrowRight className="h-6 w-6 text-blue-500 flex-shrink-0" />

                                    {/* Destination */}
                                    <div className="flex-1 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                                        <div className="flex items-center gap-2 text-green-600 dark:text-green-400 text-sm font-medium mb-1">
                                            <Building2 className="h-4 w-4" />
                                            Magasin Destination
                                        </div>
                                        <div className="text-lg font-bold text-gray-900 dark:text-white">
                                            {transfer.to_shop_name}
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Informations */}
                        <Card className="bg-white dark:bg-slate-800">
                            <CardHeader>
                                <CardTitle>Informations</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div className="flex justify-between items-center">
                                    <span className="text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                        <Calendar className="h-4 w-4" />
                                        Créé le
                                    </span>
                                    <span className="font-medium text-gray-900 dark:text-white">
                                        {transfer.created_at_formatted}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                        <User className="h-4 w-4" />
                                        Créé par
                                    </span>
                                    <span className="font-medium text-gray-900 dark:text-white">
                                        {transfer.created_by_name}
                                    </span>
                                </div>
                                {transfer.validated_at && (
                                    <>
                                        <div className="flex justify-between items-center">
                                            <span className="text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                                <CheckCircle className="h-4 w-4" />
                                                Validé le
                                            </span>
                                            <span className="font-medium text-gray-900 dark:text-white">
                                                {transfer.validated_at_formatted}
                                            </span>
                                        </div>
                                        <div className="flex justify-between items-center">
                                            <span className="text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                                <User className="h-4 w-4" />
                                                Validé par
                                            </span>
                                            <span className="font-medium text-gray-900 dark:text-white">
                                                {transfer.validated_by_name}
                                            </span>
                                        </div>
                                    </>
                                )}
                                <div className="flex justify-between items-center border-t pt-3 border-gray-200 dark:border-slate-700">
                                    <span className="text-gray-500 dark:text-gray-400 flex items-center gap-2">
                                        <Package className="h-4 w-4" />
                                        Total produits
                                    </span>
                                    <span className="text-lg font-bold text-blue-600 dark:text-blue-400">
                                        {transfer.total_items} ({transfer.total_quantity} unités)
                                    </span>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Notes */}
                    {transfer.notes && (
                        <Card className="bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800">
                            <CardContent className="pt-4">
                                <div className="text-sm font-medium text-amber-800 dark:text-amber-300 mb-1">Notes :</div>
                                <p className="text-amber-900 dark:text-amber-200">{transfer.notes}</p>
                            </CardContent>
                        </Card>
                    )}

                    {/* Produits */}
                    <Card className="bg-white dark:bg-slate-800">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Package className="h-5 w-5 text-blue-600" />
                                Produits à transférer
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {/* Ajout de produit (si brouillon) */}
                            {canEdit && (
                                <div className="relative mb-6">
                                    <div className="flex gap-3">
                                        <div className="flex-1 relative">
                                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                            <Input
                                                type="text"
                                                placeholder="Ajouter un produit..."
                                                value={searchTerm}
                                                onChange={(e) => setSearchTerm(e.target.value)}
                                                className="pl-10"
                                            />
                                        </div>
                                        <Input
                                            type="number"
                                            min="1"
                                            value={addQuantity}
                                            onChange={(e) => setAddQuantity(parseInt(e.target.value) || 1)}
                                            className="w-24"
                                            placeholder="Qté"
                                        />
                                    </div>

                                    {/* Dropdown */}
                                    {searchTerm && (
                                        <div className="absolute z-10 w-full mt-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            {availableProducts.length === 0 ? (
                                                <div className="p-3 text-center text-gray-500">Aucun produit trouvé</div>
                                            ) : (
                                                availableProducts.slice(0, 10).map(product => (
                                                    <div
                                                        key={product.id}
                                                        onClick={() => handleAddItem(product)}
                                                        className="p-3 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer border-b border-gray-100 dark:border-slate-700 last:border-0 flex justify-between items-center"
                                                    >
                                                        <div>
                                                            <span className="font-medium">{product.name}</span>
                                                            {product.code && <span className="ml-2 text-xs text-gray-500">({product.code})</span>}
                                                        </div>
                                                        <Badge>Stock: {product.stock}</Badge>
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Liste des produits */}
                            {transfer.items.length === 0 ? (
                                <div className="text-center py-12 border-2 border-dashed border-gray-200 dark:border-slate-700 rounded-lg">
                                    <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                                    <p className="text-gray-500 dark:text-gray-400">Aucun produit dans ce transfert</p>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead>
                                            <tr className="border-b border-gray-200 dark:border-slate-700">
                                                <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">#</th>
                                                <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">Code</th>
                                                <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">Produit</th>
                                                <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 uppercase">Stock actuel</th>
                                                <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 uppercase">Quantité</th>
                                                {canEdit && <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 uppercase">Actions</th>}
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-slate-700">
                                            {transfer.items.map((item, index) => (
                                                <tr key={item.id}>
                                                    <td className="py-3 px-4 text-gray-500">{index + 1}</td>
                                                    <td className="py-3 px-4 font-mono text-sm">{item.product_code || '—'}</td>
                                                    <td className="py-3 px-4 font-medium text-gray-900 dark:text-white">{item.product_name}</td>
                                                    <td className="py-3 px-4 text-center">
                                                        <Badge variant={item.current_stock >= item.quantity ? 'default' : 'destructive'}>
                                                            {item.current_stock}
                                                        </Badge>
                                                    </td>
                                                    <td className="py-3 px-4 text-center">
                                                        {editingItem === item.id ? (
                                                            <div className="flex items-center justify-center gap-2">
                                                                <Input
                                                                    type="number"
                                                                    min="1"
                                                                    value={editQuantity}
                                                                    onChange={(e) => setEditQuantity(parseInt(e.target.value) || 1)}
                                                                    className="w-20 text-center"
                                                                />
                                                                <Button
                                                                    size="sm"
                                                                    onClick={() => handleUpdateQuantity(item.id)}
                                                                    disabled={actionLoading === `update-${item.id}`}
                                                                >
                                                                    {actionLoading === `update-${item.id}` ? (
                                                                        <Loader2 className="h-4 w-4 animate-spin" />
                                                                    ) : (
                                                                        <CheckCircle className="h-4 w-4" />
                                                                    )}
                                                                </Button>
                                                                <Button
                                                                    size="sm"
                                                                    variant="outline"
                                                                    onClick={() => setEditingItem(null)}
                                                                >
                                                                    <XCircle className="h-4 w-4" />
                                                                </Button>
                                                            </div>
                                                        ) : (
                                                            <span className="font-bold text-lg">{item.quantity}</span>
                                                        )}
                                                    </td>
                                                    {canEdit && (
                                                        <td className="py-3 px-4">
                                                            {editingItem !== item.id && (
                                                                <div className="flex justify-center gap-2">
                                                                    <Button
                                                                        size="sm"
                                                                        variant="outline"
                                                                        onClick={() => {
                                                                            setEditingItem(item.id);
                                                                            setEditQuantity(item.quantity);
                                                                        }}
                                                                    >
                                                                        <Edit2 className="h-4 w-4" />
                                                                    </Button>
                                                                    <Button
                                                                        size="sm"
                                                                        variant="ghost"
                                                                        onClick={() => handleRemoveItem(item.id)}
                                                                        disabled={actionLoading === `remove-${item.id}`}
                                                                        className="text-red-500 hover:text-red-700 hover:bg-red-50"
                                                                    >
                                                                        {actionLoading === `remove-${item.id}` ? (
                                                                            <Loader2 className="h-4 w-4 animate-spin" />
                                                                        ) : (
                                                                            <Trash2 className="h-4 w-4" />
                                                                        )}
                                                                    </Button>
                                                                </div>
                                                            )}
                                                        </td>
                                                    )}
                                                </tr>
                                            ))}
                                        </tbody>
                                        <tfoot>
                                            <tr className="bg-gray-50 dark:bg-slate-700/50">
                                                <td colSpan={canEdit ? 4 : 3} className="py-3 px-4 text-right font-bold">
                                                    TOTAL ({transfer.total_items} produit{transfer.total_items > 1 ? 's' : ''})
                                                </td>
                                                <td className="py-3 px-4 text-center font-bold text-xl text-blue-600">
                                                    {transfer.total_quantity}
                                                </td>
                                                {canEdit && <td></td>}
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Actions */}
                    {(canValidate || canCancel) && transfer.status === 'draft' && (
                        <Card className="bg-white dark:bg-slate-800">
                            <CardContent className="pt-4">
                                <div className="flex justify-end gap-3">
                                    {canCancel && (
                                        <Button
                                            variant="outline"
                                            onClick={handleCancel}
                                            disabled={actionLoading === 'cancel'}
                                            className="border-red-300 text-red-600 hover:bg-red-50"
                                        >
                                            {actionLoading === 'cancel' ? (
                                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                            ) : (
                                                <XCircle className="h-4 w-4 mr-2" />
                                            )}
                                            Annuler le transfert
                                        </Button>
                                    )}
                                    {canValidate && transfer.items.length > 0 && (
                                        <Button
                                            onClick={handleValidate}
                                            disabled={actionLoading === 'validate'}
                                            className="bg-green-600 hover:bg-green-700"
                                        >
                                            {actionLoading === 'validate' ? (
                                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                            ) : (
                                                <CheckCircle className="h-4 w-4 mr-2" />
                                            )}
                                            Valider le transfert
                                        </Button>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
