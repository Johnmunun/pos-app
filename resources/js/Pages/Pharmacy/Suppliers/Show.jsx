import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { 
    Truck, 
    ArrowLeft, 
    Edit, 
    Building, 
    User, 
    Phone, 
    Mail, 
    MapPin,
    Calendar,
    Package,
    CheckCircle,
    XCircle,
    FileText,
    Clock,
    DollarSign,
    Plus,
    Trash2,
    Pencil
} from 'lucide-react';
import axios from 'axios';
import toast from 'react-hot-toast';
import SupplierDrawer from '@/Components/Pharmacy/SupplierDrawer';
import SupplierPricingDrawer from '@/Components/Pharmacy/SupplierPricingDrawer';
import { formatCurrency } from '@/lib/currency';

export default function ShowSupplier({ supplier, supplierPrices = [], products = [] }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];
    
    // Drawer state
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [pricingDrawerOpen, setPricingDrawerOpen] = useState(false);
    const [editingPrice, setEditingPrice] = useState(null);

    const hasPermission = (perm) => {
        if (permissions.includes('*')) return true;
        return perm.split('|').some(p => permissions.includes(p));
    };

    const handleDeletePrice = async (priceId) => {
        if (!confirm('Supprimer ce prix fournisseur ?')) return;
        
        try {
            const response = await axios.delete(route('pharmacy.suppliers.prices.destroy', priceId));
            if (response.data.success) {
                toast.success('Prix supprimé avec succès');
                router.reload();
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error('Erreur lors de la suppression');
        }
    };

    const openAddPricing = () => {
        setEditingPrice(null);
        setPricingDrawerOpen(true);
    };

    const openEditPricing = (price) => {
        setEditingPrice(price);
        setPricingDrawerOpen(true);
    };

    const handleToggleStatus = async () => {
        const action = supplier.status === 'active' ? 'deactivate' : 'activate';
        const permission = supplier.status === 'active' 
            ? 'pharmacy.supplier.deactivate' 
            : 'pharmacy.supplier.activate';

        if (!hasPermission(permission)) {
            toast.error('Vous n\'avez pas la permission pour cette action.');
            return;
        }

        try {
            const response = await axios.post(route(`pharmacy.suppliers.${action}`, supplier.id));
            if (response.data.success) {
                toast.success(response.data.message);
                router.reload();
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error('Une erreur est survenue.');
        }
    };

    const getStatusBadge = (s) => {
        if (s === 'active') {
            return <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Actif</Badge>;
        }
        return <Badge className="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactif</Badge>;
    };

    const getOrderStatusBadge = (s) => {
        const statusMap = {
            'DRAFT': { class: 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300', label: 'Brouillon' },
            'CONFIRMED': { class: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300', label: 'Confirmé' },
            'PARTIALLY_RECEIVED': { class: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300', label: 'Partiel' },
            'RECEIVED': { class: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300', label: 'Reçu' },
            'CANCELLED': { class: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300', label: 'Annulé' },
        };
        const status = statusMap[s] || statusMap['DRAFT'];
        return <Badge className={status.class}>{status.label}</Badge>;
    };

    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Détails du Fournisseur
                </h2>
            }
        >
            <Head title={supplier.name} />
            <div className="py-12">
                <div className="max-w-5xl mx-auto sm:px-6 lg:px-8">
                    
                    {/* Back button & Actions */}
                    <div className="flex justify-between items-center mb-6">
                        <Button variant="outline" asChild className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700">
                            <Link href={route('pharmacy.suppliers.index')} className="inline-flex items-center gap-2">
                                <ArrowLeft className="h-4 w-4" />
                                <span>Retour à la liste</span>
                            </Link>
                        </Button>
                        <div className="flex gap-2">
                            {hasPermission('pharmacy.supplier.edit') && (
                                <Button 
                                    onClick={() => setDrawerOpen(true)}
                                    className="bg-amber-500 hover:bg-amber-600 text-white inline-flex items-center gap-2"
                                >
                                    <Edit className="h-4 w-4" />
                                    <span>Modifier</span>
                                </Button>
                            )}
                            {supplier.status === 'active' && hasPermission('pharmacy.supplier.deactivate') && (
                                <Button 
                                    variant="outline"
                                    onClick={handleToggleStatus}
                                    className="border-orange-300 dark:border-orange-600 text-orange-700 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                                >
                                    <XCircle className="h-4 w-4 mr-2" />
                                    Désactiver
                                </Button>
                            )}
                            {supplier.status === 'inactive' && hasPermission('pharmacy.supplier.activate') && (
                                <Button 
                                    variant="outline"
                                    onClick={handleToggleStatus}
                                    className="border-green-300 dark:border-green-600 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20"
                                >
                                    <CheckCircle className="h-4 w-4 mr-2" />
                                    Activer
                                </Button>
                            )}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Info */}
                        <div className="lg:col-span-2 space-y-6">
                            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between text-gray-900 dark:text-white">
                                        <span className="flex items-center">
                                            <Truck className="h-5 w-5 mr-2 text-amber-500" />
                                            Informations générales
                                        </span>
                                        {getStatusBadge(supplier.status)}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-start gap-4">
                                        <div className="h-16 w-16 flex-shrink-0 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                                            <Building className="h-8 w-8 text-amber-600 dark:text-amber-400" />
                                        </div>
                                        <div>
                                            <h3 className="text-xl font-semibold text-gray-900 dark:text-white">{supplier.name}</h3>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                {supplier.total_orders} commande(s) au total
                                            </p>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                                        {supplier.contact_person && (
                                            <div className="flex items-center gap-3">
                                                <User className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">Contact</p>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white">{supplier.contact_person}</p>
                                                </div>
                                            </div>
                                        )}

                                        {supplier.phone && (
                                            <div className="flex items-center gap-3">
                                                <Phone className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">Téléphone</p>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white">{supplier.phone}</p>
                                                </div>
                                            </div>
                                        )}

                                        {supplier.email && (
                                            <div className="flex items-center gap-3">
                                                <Mail className="h-5 w-5 text-gray-400" />
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">Email</p>
                                                    <a href={`mailto:${supplier.email}`} className="text-sm font-medium text-amber-600 dark:text-amber-400 hover:underline">
                                                        {supplier.email}
                                                    </a>
                                                </div>
                                            </div>
                                        )}

                                        {supplier.address && (
                                            <div className="flex items-start gap-3 md:col-span-2">
                                                <MapPin className="h-5 w-5 text-gray-400 mt-0.5" />
                                                <div>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">Adresse</p>
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white">{supplier.address}</p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Recent Orders */}
                            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                                <CardHeader>
                                    <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                        <Package className="h-5 w-5 mr-2 text-blue-500" />
                                        Commandes récentes
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {!supplier.recent_orders || supplier.recent_orders.length === 0 ? (
                                        <div className="py-8 text-center">
                                            <FileText className="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                            <p className="text-gray-500 dark:text-gray-400">
                                                Aucune commande pour ce fournisseur.
                                            </p>
                                        </div>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                                <thead className="bg-gray-50 dark:bg-slate-800">
                                                    <tr>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Référence</th>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                                        <th className="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                                        <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Montant</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-200 dark:divide-slate-700">
                                                    {supplier.recent_orders.map((order) => (
                                                        <tr key={order.id} className="hover:bg-gray-50 dark:hover:bg-slate-800">
                                                            <td className="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                                                {order.reference}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                                {order.created_at}
                                                            </td>
                                                            <td className="px-4 py-3 text-center">
                                                                {getOrderStatusBadge(order.status)}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm text-right font-medium text-gray-900 dark:text-white">
                                                                {Number(order.total_amount).toFixed(2)}
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Prix Fournisseur */}
                            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                                <CardHeader>
                                    <CardTitle className="flex items-center justify-between text-gray-900 dark:text-white">
                                        <span className="flex items-center">
                                            <DollarSign className="h-5 w-5 mr-2 text-green-500" />
                                            Prix négociés ({supplierPrices.length})
                                        </span>
                                        {hasPermission('pharmacy.supplier.pricing.manage') && (
                                            <Button 
                                                size="sm" 
                                                onClick={openAddPricing}
                                                className="bg-green-500 hover:bg-green-600 text-white"
                                            >
                                                <Plus className="h-4 w-4 mr-1" />
                                                Ajouter
                                            </Button>
                                        )}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {supplierPrices.length === 0 ? (
                                        <div className="py-8 text-center">
                                            <DollarSign className="h-10 w-10 mx-auto text-gray-300 dark:text-gray-600 mb-2" />
                                            <p className="text-gray-500 dark:text-gray-400">
                                                Aucun prix négocié avec ce fournisseur.
                                            </p>
                                            {hasPermission('pharmacy.supplier.pricing.manage') && (
                                                <Button 
                                                    size="sm" 
                                                    variant="outline" 
                                                    onClick={openAddPricing}
                                                    className="mt-3"
                                                >
                                                    <Plus className="h-4 w-4 mr-1" />
                                                    Définir un prix
                                                </Button>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="overflow-x-auto">
                                            <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                                <thead className="bg-gray-50 dark:bg-slate-800">
                                                    <tr>
                                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Produit</th>
                                                        <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Prix normal</th>
                                                        <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Prix convenu</th>
                                                        <th className="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">TTC</th>
                                                        <th className="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody className="divide-y divide-gray-200 dark:divide-slate-700">
                                                    {supplierPrices.map((price) => (
                                                        <tr key={price.id} className="hover:bg-gray-50 dark:hover:bg-slate-800">
                                                            <td className="px-4 py-3">
                                                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                                    {price.product_name}
                                                                </p>
                                                                {price.product_code && (
                                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                                        {price.product_code}
                                                                    </p>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm text-right text-gray-600 dark:text-gray-300">
                                                                {formatCurrency(price.normal_price, currency)}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm text-right">
                                                                {price.agreed_price ? (
                                                                    <span className="font-medium text-green-600 dark:text-green-400">
                                                                        {formatCurrency(price.agreed_price, currency)}
                                                                    </span>
                                                                ) : (
                                                                    <span className="text-gray-400">—</span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-3 text-sm text-right font-semibold text-gray-900 dark:text-white">
                                                                {formatCurrency(price.price_with_tax, currency)}
                                                            </td>
                                                            <td className="px-4 py-3 text-center">
                                                                <div className="flex items-center justify-center gap-1">
                                                                    {hasPermission('pharmacy.supplier.pricing.manage') && (
                                                                        <>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => openEditPricing(price)}
                                                                                className="h-8 w-8 p-0 text-gray-500 hover:text-blue-600"
                                                                            >
                                                                                <Pencil className="h-4 w-4" />
                                                                            </Button>
                                                                            <Button
                                                                                variant="ghost"
                                                                                size="sm"
                                                                                onClick={() => handleDeletePrice(price.id)}
                                                                                className="h-8 w-8 p-0 text-gray-500 hover:text-red-600"
                                                                            >
                                                                                <Trash2 className="h-4 w-4" />
                                                                            </Button>
                                                                        </>
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

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Stats */}
                            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                                <CardHeader>
                                    <CardTitle className="text-gray-900 dark:text-white text-base">
                                        Statistiques
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-500 dark:text-gray-400">Total commandes</span>
                                        <span className="text-lg font-semibold text-gray-900 dark:text-white">{supplier.total_orders}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm text-gray-500 dark:text-gray-400">Statut</span>
                                        {getStatusBadge(supplier.status)}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Dates */}
                            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                                <CardHeader>
                                    <CardTitle className="flex items-center text-gray-900 dark:text-white text-base">
                                        <Clock className="h-4 w-4 mr-2 text-gray-400" />
                                        Historique
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="flex items-center gap-3">
                                        <Calendar className="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Créé le</p>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">{supplier.created_at}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <Calendar className="h-4 w-4 text-gray-400" />
                                        <div>
                                            <p className="text-xs text-gray-500 dark:text-gray-400">Modifié le</p>
                                            <p className="text-sm font-medium text-gray-900 dark:text-white">{supplier.updated_at}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Quick Actions */}
                            {hasPermission('pharmacy.purchases.manage') && (
                                <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                                    <CardHeader>
                                        <CardTitle className="text-gray-900 dark:text-white text-base">
                                            Actions rapides
                                        </CardTitle>
                                    </CardHeader>
                                    <CardContent>
                                        <Button asChild className="w-full bg-blue-500 hover:bg-blue-600 text-white">
                                            <Link href={route('pharmacy.purchases.create')} className="inline-flex items-center justify-center gap-2">
                                                <Package className="h-4 w-4" />
                                                <span>Nouvelle commande</span>
                                            </Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Supplier Drawer */}
            <SupplierDrawer
                isOpen={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                supplier={supplier}
                onSuccess={() => router.reload()}
                canCreate={false}
                canUpdate={hasPermission('pharmacy.supplier.edit')}
            />

            {/* Supplier Pricing Drawer */}
            <SupplierPricingDrawer
                isOpen={pricingDrawerOpen}
                onClose={() => {
                    setPricingDrawerOpen(false);
                    setEditingPrice(null);
                }}
                supplierId={supplier.id}
                supplierName={supplier.name}
                existingPrice={editingPrice}
                onSuccess={() => router.reload()}
                canManage={hasPermission('pharmacy.supplier.pricing.manage')}
            />
        </AppLayout>
    );
}
