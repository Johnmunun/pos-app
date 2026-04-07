import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import OrderDrawer from '@/Components/Ecommerce/OrderDrawer';
import OrderDetailModal from '@/Components/Ecommerce/OrderDetailModal';
import Modal from '@/Components/Modal';
import { toast } from 'react-hot-toast';
import {
    Plus,
    Search,
    Package,
    Eye,
    Trash2,
    RefreshCw,
    Filter,
    Calendar,
    Clock,
    CheckCircle,
    XCircle,
    Truck,
    Download,
} from 'lucide-react';
import EcommercePageHeader from '@/Components/Ecommerce/EcommercePageHeader';
import EcommerceActionButton from '@/Components/Ecommerce/EcommerceActionButton';
import { Pagination } from '@/Components/ui/pagination';
import axios from 'axios';

export default function OrdersIndex({ orders = [], stats = {}, financial = {}, filters = {}, pagination }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permissionExpression) => {
        if (auth?.user?.type === 'ROOT') return true;
        if (!permissionExpression) return false;
        const permsToCheck = String(permissionExpression)
            .split('|')
            .map((p) => p.trim())
            .filter(Boolean);
        return permsToCheck.some((p) => permissions.includes(p));
    };

    const canCreate = hasPermission('ecommerce.order.create|ecommerce.create|module.ecommerce');
    const canUpdateStatus = hasPermission('ecommerce.order.status.update|ecommerce.order.update|module.ecommerce');
    const canUpdatePayment = hasPermission('ecommerce.order.payment.update|ecommerce.order.update|module.ecommerce');
    const canDelete = hasPermission('ecommerce.order.delete|ecommerce.delete|module.ecommerce');
    const canView = hasPermission('ecommerce.order.view|ecommerce.view|module.ecommerce');

    const [search, setSearch] = useState(filters.search || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedOrder, setSelectedOrder] = useState(null);
    const [detailModalOpen, setDetailModalOpen] = useState(false);
    const [deleting, setDeleting] = useState(null);
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [dateFrom, setDateFrom] = useState(filters.from || '');
    const [dateTo, setDateTo] = useState(filters.to || '');

    const handleOpenCreate = () => {
        setSelectedOrder(null);
        setDrawerOpen(true);
    };

    const handleOpenDetail = (order) => {
        router.get(route('ecommerce.orders.show', order.id), {}, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: (page) => {
                setSelectedOrder(page.props.order);
                setDetailModalOpen(true);
            },
        });
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setSelectedOrder(null);
    };

    const handleCloseDetailModal = () => {
        setDetailModalOpen(false);
        setSelectedOrder(null);
    };

    const handleDelete = async (order) => {
        if (!confirm(`Êtes-vous sûr de vouloir annuler la commande "${order.order_number}" ?`)) {
            return;
        }

        if (!canDelete) {
            toast.error('Vous n\'avez pas la permission de supprimer des commandes.');
            return;
        }

        setDeleting(order.id);
        try {
            await axios.delete(route('ecommerce.orders.destroy', order.id));
            toast.success('Commande annulée avec succès');
            router.reload({ only: ['orders', 'stats'] });
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors de l\'annulation');
        } finally {
            setDeleting(null);
        }
    };

    const handleUpdateStatus = async (orderId, newStatus) => {
        if (!canUpdateStatus) {
            toast.error('Vous n\'avez pas la permission de modifier les commandes.');
            return;
        }

        try {
            await axios.put(route('ecommerce.orders.update-status', orderId), { status: newStatus });
            toast.success('Statut mis à jour avec succès');
            router.reload({ only: ['orders', 'stats'] });
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour');
        }
    };

    const handleUpdatePaymentStatus = async (orderId, paymentStatus) => {
        if (!canUpdatePayment) {
            toast.error('Vous n\'avez pas la permission de modifier le paiement.');
            return;
        }

        try {
            await axios.put(route('ecommerce.orders.update-payment-status', orderId), { payment_status: paymentStatus });
            toast.success('Paiement mis à jour avec succès');
            router.reload({ only: ['orders', 'stats'] });
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour du paiement');
        }
    };

    const handleFilter = () => {
        router.get(route('ecommerce.orders.index'), {
            status: statusFilter,
            from: dateFrom,
            to: dateTo,
            search: search || undefined,
            page: 1,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getStatusBadge = (status) => {
        const statusMap = {
            'pending': { label: 'En attente', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300', icon: Clock },
            'confirmed': { label: 'Confirmée', color: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300', icon: CheckCircle },
            'processing': { label: 'En traitement', color: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300', icon: Package },
            'shipped': { label: 'Expédiée', color: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300', icon: Truck },
            'delivered': { label: 'Livrée', color: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300', icon: CheckCircle },
            'cancelled': { label: 'Annulée', color: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300', icon: XCircle },
        };
        const statusInfo = statusMap[status] || { label: status, color: 'bg-gray-100 text-gray-800', icon: Package };
        const Icon = statusInfo.icon;
        return (
            <Badge className={statusInfo.color}>
                <Icon className="h-3 w-3 mr-1" />
                {statusInfo.label}
            </Badge>
        );
    };

    const formatCurrency = (amount, currency) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const defaultCurrency = orders?.[0]?.currency || 'USD';

    return (
        <AppLayout
            header={
                <EcommercePageHeader title="Ventes Ecommerce" icon={Package}>
                    <EcommerceActionButton
                        icon={Download}
                        label="Export Excel"
                        variant="outline"
                        className="border-emerald-500 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-400 dark:text-emerald-300 dark:hover:bg-emerald-900/20"
                        onClick={() => window.open(route('ecommerce.exports.orders.excel'), '_blank')}
                    />
                    <EcommerceActionButton
                        icon={Download}
                        label="Export PDF"
                        variant="outline"
                        className="border-red-500 text-red-700 hover:bg-red-50 dark:border-red-400 dark:text-red-300 dark:hover:bg-red-900/20"
                        onClick={() => window.open(route('ecommerce.exports.orders.pdf'), '_blank')}
                    />
                    {canCreate && (
                        <EcommerceActionButton icon={Plus} label="Nouvelle commande" onClick={handleOpenCreate} />
                    )}
                </EcommercePageHeader>
            }
        >
            <Head title="Ventes Ecommerce" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Stats */}
                    {stats && Object.keys(stats).length > 0 && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4 mb-6">
                            <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Total</div>
                                <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{stats.total ?? 0}</div>
                            </div>
                            <div className="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 border border-yellow-200 dark:border-yellow-800">
                                <div className="text-sm font-medium text-yellow-700 dark:text-yellow-400">En attente</div>
                                <div className="mt-1 text-2xl font-bold text-yellow-900 dark:text-yellow-300">{stats.pending ?? 0}</div>
                            </div>
                            <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                                <div className="text-sm font-medium text-blue-700 dark:text-blue-400">Confirmées</div>
                                <div className="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-300">{stats.confirmed ?? 0}</div>
                            </div>
                            <div className="bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-4 border border-indigo-200 dark:border-indigo-800">
                                <div className="text-sm font-medium text-indigo-700 dark:text-indigo-400">Expédiées</div>
                                <div className="mt-1 text-2xl font-bold text-indigo-900 dark:text-indigo-300">{stats.shipped ?? 0}</div>
                            </div>
                            <div className="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800">
                                <div className="text-sm font-medium text-emerald-700 dark:text-emerald-400">Livrées</div>
                                <div className="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-300">{stats.delivered ?? 0}</div>
                            </div>
                            <div className="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                <div className="text-sm font-medium text-red-700 dark:text-red-400">Annulées</div>
                                <div className="mt-1 text-2xl font-bold text-red-900 dark:text-red-300">{stats.cancelled ?? 0}</div>
                            </div>
                        </div>
                    )}

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                        <div className="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800">
                            <div className="text-sm font-medium text-emerald-700 dark:text-emerald-400">Montant payé (filtre)</div>
                            <div className="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-300">
                                {formatCurrency(financial.paid_filtered ?? 0, defaultCurrency)}
                            </div>
                        </div>
                        <div className="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                            <div className="text-sm font-medium text-amber-700 dark:text-amber-400">Montant en attente (filtre)</div>
                            <div className="mt-1 text-2xl font-bold text-amber-900 dark:text-amber-300">
                                {formatCurrency(financial.pending_filtered ?? 0, defaultCurrency)}
                            </div>
                        </div>
                        <div className="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 border border-blue-200 dark:border-blue-800">
                            <div className="text-sm font-medium text-blue-700 dark:text-blue-400">Montant potentiel (filtre)</div>
                            <div className="mt-1 text-2xl font-bold text-blue-900 dark:text-blue-300">
                                {formatCurrency(financial.expected_filtered ?? 0, defaultCurrency)}
                            </div>
                        </div>
                    </div>

                    {/* Filtres */}
                    <div className="mb-6 bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Statut
                                </label>
                                <select
                                    className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                >
                                    <option value="">Tous</option>
                                    <option value="pending">En attente</option>
                                    <option value="confirmed">Confirmée</option>
                                    <option value="processing">En traitement</option>
                                    <option value="shipped">Expédiée</option>
                                    <option value="delivered">Livrée</option>
                                    <option value="cancelled">Annulée</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Date début
                                </label>
                                <Input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Date fin
                                </label>
                                <Input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                            </div>
                                <div className="flex items-end gap-2">
                                <Button onClick={handleFilter} variant="outline" className="gap-2" type="button">
                                    <Filter className="h-4 w-4" />
                                    Filtrer
                                </Button>
                                <Button
                                    onClick={() => router.reload({ only: ['orders', 'stats'] })}
                                    variant="outline"
                                    size="icon"
                                >
                                    <RefreshCw className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Barre de recherche */}
                    <div className="mb-6">
                        <form
                            onSubmit={(e) => { e.preventDefault(); handleFilter(); }}
                            className="flex gap-4"
                        >
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Rechercher par n° commande, client..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button type="submit" variant="outline">Rechercher</Button>
                        </form>
                    </div>

                    {/* Liste des commandes */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {orders.length === 0 ? (
                            <div className="py-12 text-center">
                                <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                    {search ? 'Aucune commande trouvée' : 'Aucune commande'}
                                </p>
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    {search
                                        ? 'Essayez avec d\'autres termes de recherche'
                                        : 'Commencez par créer votre première commande'}
                                </p>
                                {canCreate && !search && (
                                    <Button onClick={handleOpenCreate} className="gap-2">
                                        <Plus className="h-4 w-4" />
                                        Nouvelle commande
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <>
                                {/* Mobile: cartes */}
                                <div className="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                                    {orders.map((order) => (
                                        <div
                                            key={order.id}
                                            className="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="min-w-0">
                                                    <p className="font-medium text-gray-900 dark:text-white">{order.order_number}</p>
                                                    <p className="text-sm text-gray-500 dark:text-gray-400 truncate">{order.customer_name}</p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{order.created_at}</p>
                                                </div>
                                                <div className="text-right shrink-0">
                                                    <span className="font-semibold text-gray-900 dark:text-white">
                                                        {formatCurrency(order.total_amount, order.currency)}
                                                    </span>
                                                    <div className="mt-1">{getStatusBadge(order.status)}</div>
                                                </div>
                                            </div>
                                            <div className="mt-3 flex gap-2">
                                                {canView && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="flex-1"
                                                        onClick={() => handleOpenDetail(order)}
                                                    >
                                                        <Eye className="h-4 w-4 mr-1" />
                                                        Voir
                                                    </Button>
                                                )}
                                                {canDelete && order.status !== 'cancelled' && order.status !== 'delivered' && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() => handleDelete(order)}
                                                        disabled={deleting === order.id}
                                                        className="text-red-600 dark:text-red-400 border-red-200 dark:border-red-800"
                                                    >
                                                        {deleting === order.id ? (
                                                            <RefreshCw className="h-4 w-4 animate-spin" />
                                                        ) : (
                                                            <Trash2 className="h-4 w-4" />
                                                        )}
                                                    </Button>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Desktop: tableau */}
                                <div className="hidden md:block overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Commande
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Client
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Total
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Date
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {orders.map((order) => (
                                            <tr
                                                key={order.id}
                                                className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                            >
                                                <td className="px-6 py-4">
                                                    <div className="font-medium text-gray-900 dark:text-gray-100">
                                                        {order.order_number}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div>
                                                        <p className="font-medium text-gray-900 dark:text-gray-100">
                                                            {order.customer_name}
                                                        </p>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                                            {order.customer_email}
                                                        </p>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {getStatusBadge(order.status)}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="font-medium text-gray-900 dark:text-gray-100">
                                                        {formatCurrency(order.total_amount, order.currency)}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {order.created_at}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        {canView && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleOpenDetail(order)}
                                                                className="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                        {canDelete && order.status !== 'cancelled' && order.status !== 'delivered' && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDelete(order)}
                                                                disabled={deleting === order.id}
                                                                className="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                                                            >
                                                                {deleting === order.id ? (
                                                                    <RefreshCw className="h-4 w-4 animate-spin" />
                                                                ) : (
                                                                    <Trash2 className="h-4 w-4" />
                                                                )}
                                                            </Button>
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                </div>
                            </>
                        )}
                        {pagination && pagination.last_page > 1 && (
                            <Pagination
                                pagination={pagination}
                                filters={{ status: statusFilter, from: dateFrom, to: dateTo, search: search || undefined }}
                                routeName="ecommerce.orders.index"
                            />
                        )}
                    </div>
                </div>
            </div>

            {canCreate && (
                <div className="md:hidden fixed bottom-20 right-4 z-30">
                    <Button
                        onClick={handleOpenCreate}
                        className="h-14 w-14 rounded-full bg-amber-500 hover:bg-amber-600 text-white shadow-lg"
                        size="icon"
                        title="Nouvelle commande"
                    >
                        <Plus className="h-6 w-6" />
                    </Button>
                </div>
            )}

            {/* Drawer pour créer */}
            {drawerOpen && (
                <OrderDrawer
                    isOpen={drawerOpen}
                    onClose={handleCloseDrawer}
                    products={[]} // Sera chargé depuis le catalogue
                />
            )}

            {/* Modal de détail */}
            {selectedOrder && (
                <OrderDetailModal
                    order={selectedOrder}
                    show={detailModalOpen}
                    onClose={handleCloseDetailModal}
                    onStatusUpdate={canUpdateStatus ? handleUpdateStatus : undefined}
                    onPaymentStatusUpdate={canUpdatePayment ? handleUpdatePaymentStatus : undefined}
                />
            )}
        </AppLayout>
    );
}
