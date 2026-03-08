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
} from 'lucide-react';
import axios from 'axios';

export default function OrdersIndex({ orders = [], stats = {}, filters = {} }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canCreate = hasPermission('ecommerce.create');
    const canUpdate = hasPermission('ecommerce.update');
    const canDelete = hasPermission('ecommerce.delete');
    const canView = hasPermission('ecommerce.view');

    const [search, setSearch] = useState('');
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
        if (!canUpdate) {
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

    const handleFilter = () => {
        router.get(route('ecommerce.orders.index'), {
            status: statusFilter,
            from: dateFrom,
            to: dateTo,
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

    const filteredOrders = orders.filter(order => {
        if (!search) return true;
        const searchLower = search.toLowerCase();
        return (
            order.order_number.toLowerCase().includes(searchLower) ||
            order.customer_name.toLowerCase().includes(searchLower) ||
            order.customer_email.toLowerCase().includes(searchLower)
        );
    });

    const formatCurrency = (amount, currency) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                            <Package className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Commandes Ecommerce
                            </h2>
                        </div>
                    </div>
                    {canCreate && (
                        <Button onClick={handleOpenCreate} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Nouvelle commande
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Commandes Ecommerce" />

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
                                <Button onClick={handleFilter} variant="outline" className="gap-2">
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
                        <div className="flex gap-4">
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Rechercher une commande..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                        </div>
                    </div>

                    {/* Liste des commandes */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {filteredOrders.length === 0 ? (
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
                            <div className="overflow-x-auto">
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
                                        {filteredOrders.map((order) => (
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
                        )}
                    </div>
                </div>
            </div>

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
                    onStatusUpdate={handleUpdateStatus}
                    onPaymentStatusUpdate={handleUpdateStatus}
                />
            )}
        </AppLayout>
    );
}
