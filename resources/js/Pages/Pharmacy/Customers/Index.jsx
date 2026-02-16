import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import CustomerDrawer from '@/Components/Pharmacy/CustomerDrawer';
import ExportButtons from '@/Components/Pharmacy/ExportButtons';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import {
    Plus,
    Search,
    Filter,
    Users,
    Eye,
    Edit,
    CheckCircle,
    XCircle,
    Phone,
    Mail,
    Building2,
    User,
    CreditCard,
    ChevronLeft,
    ChevronRight,
} from 'lucide-react';

export default function CustomersIndex({ customers, filters = {} }) {
    const { auth, shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canCreate = hasPermission('pharmacy.customer.create');
    const canEdit = hasPermission('pharmacy.customer.edit');
    const canView = hasPermission('pharmacy.customer.view');
    const canActivate = hasPermission('pharmacy.customer.activate');
    const canDeactivate = hasPermission('pharmacy.customer.deactivate');

    const [search, setSearch] = useState(filters.search || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [typeFilter, setTypeFilter] = useState(filters.customer_type || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedCustomer, setSelectedCustomer] = useState(null);
    const [processing, setProcessing] = useState({});

    const handleSearch = () => {
        router.get(route('pharmacy.customers.index'), {
            search,
            status: statusFilter,
            customer_type: typeFilter,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    const handleOpenCreate = () => {
        setSelectedCustomer(null);
        setDrawerOpen(true);
    };

    const handleOpenEdit = (customer) => {
        setSelectedCustomer(customer);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setSelectedCustomer(null);
    };

    const handleDrawerSuccess = () => {
        router.reload({ only: ['customers'] });
    };

    const handleToggleStatus = async (customer) => {
        const isActivating = customer.status === 'inactive';
        const action = isActivating ? 'activate' : 'deactivate';

        if (isActivating && !canActivate) {
            toast.error('Vous n\'avez pas la permission d\'activer ce client.');
            return;
        }
        if (!isActivating && !canDeactivate) {
            toast.error('Vous n\'avez pas la permission de désactiver ce client.');
            return;
        }

        setProcessing(prev => ({ ...prev, [customer.id]: true }));

        try {
            const response = await axios.post(route(`pharmacy.customers.${action}`, customer.id));
            if (response.data.success) {
                toast.success(response.data.message);
                router.reload({ only: ['customers'] });
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Une erreur est survenue');
        } finally {
            setProcessing(prev => ({ ...prev, [customer.id]: false }));
        }
    };

    const getStatusBadge = (status) => {
        if (status === 'active') {
            return (
                <Badge className="bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Actif
                </Badge>
            );
        }
        return (
            <Badge className="bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                <XCircle className="h-3 w-3 mr-1" />
                Inactif
            </Badge>
        );
    };

    const getTypeBadge = (type) => {
        if (type === 'company') {
            return (
                <Badge variant="outline" className="border-blue-500 text-blue-500">
                    <Building2 className="h-3 w-3 mr-1" />
                    Entreprise
                </Badge>
            );
        }
        return (
            <Badge variant="outline" className="border-gray-500 text-gray-500">
                <User className="h-3 w-3 mr-1" />
                Particulier
            </Badge>
        );
    };

    const formatCurrency = (amount) => {
        if (amount === null || amount === undefined) return '-';
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    };

    return (
        <AppLayout>
            <Head title="Clients" />

            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Users className="h-6 w-6" />
                            Gestion des Clients
                        </h1>
                        <p className="text-gray-500 dark:text-gray-400 mt-1">
                            {customers?.total || 0} client(s) au total
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <ExportButtons
                            pdfUrl={route('pharmacy.exports.customers.pdf')}
                            excelUrl={route('pharmacy.exports.customers.excel')}
                            disabled={!customers?.data?.length}
                        />
                        {canCreate && (
                            <Button onClick={handleOpenCreate}>
                                <Plus className="h-4 w-4 mr-2" />
                                Nouveau client
                            </Button>
                        )}
                    </div>
                </div>

                {/* Filters */}
                <div className="mb-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <div className="p-6">
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    placeholder="Rechercher un client..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={handleKeyPress}
                                    className="pl-10 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100"
                                />
                            </div>
                            <select
                                value={statusFilter}
                                onChange={(e) => setStatusFilter(e.target.value)}
                                className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                                <option value="">Tous les statuts</option>
                                <option value="active">Actifs</option>
                                <option value="inactive">Inactifs</option>
                            </select>
                            <select
                                value={typeFilter}
                                onChange={(e) => setTypeFilter(e.target.value)}
                                className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent"
                            >
                                <option value="">Tous les types</option>
                                <option value="individual">Particuliers</option>
                                <option value="company">Entreprises</option>
                            </select>
                            <Button onClick={handleSearch} variant="secondary">
                                <Filter className="h-4 w-4 mr-2" />
                                Filtrer
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Client
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Type
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Crédit
                                    </th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Statut
                                    </th>
                                    <th className="px-4 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                {customers?.data?.length > 0 ? (
                                    customers.data.map((customer) => (
                                        <tr key={customer.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                            <td className="px-4 py-4">
                                                <div className="flex items-center gap-3">
                                                    <div className="h-10 w-10 rounded-full bg-primary/10 dark:bg-primary/20 flex items-center justify-center">
                                                        {customer.customer_type === 'company' ? (
                                                            <Building2 className="h-5 w-5 text-primary" />
                                                        ) : (
                                                            <User className="h-5 w-5 text-primary" />
                                                        )}
                                                    </div>
                                                    <div>
                                                        <p className="font-medium text-gray-900 dark:text-gray-100">{customer.name}</p>
                                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                                            {customer.total_sales || 0} vente(s)
                                                        </p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="space-y-1">
                                                    {customer.phone && (
                                                        <p className="text-sm flex items-center gap-1 text-gray-700 dark:text-gray-300">
                                                            <Phone className="h-3 w-3 text-gray-400 dark:text-gray-500" />
                                                            {customer.phone}
                                                        </p>
                                                    )}
                                                    {customer.email && (
                                                        <p className="text-sm flex items-center gap-1 text-gray-700 dark:text-gray-300">
                                                            <Mail className="h-3 w-3 text-gray-400 dark:text-gray-500" />
                                                            {customer.email}
                                                        </p>
                                                    )}
                                                    {!customer.phone && !customer.email && (
                                                        <p className="text-sm text-gray-400 dark:text-gray-500">-</p>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                {getTypeBadge(customer.customer_type)}
                                            </td>
                                            <td className="px-4 py-4">
                                                {customer.credit_limit ? (
                                                    <div className="flex items-center gap-1">
                                                        <CreditCard className="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                                        <span className="font-medium text-gray-900 dark:text-gray-100">
                                                            {formatCurrency(customer.credit_limit)}
                                                        </span>
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-400 dark:text-gray-500">-</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4">
                                                {getStatusBadge(customer.status)}
                                            </td>
                                            <td className="px-4 py-4 text-right">
                                                <div className="flex items-center justify-end gap-2">
                                                    {canView && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                                            asChild
                                                        >
                                                            <Link href={route('pharmacy.customers.show', customer.id)}>
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    )}
                                                    {canEdit && (
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            className="text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white"
                                                            onClick={() => handleOpenEdit(customer)}
                                                        >
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                    )}
                                                    {(canActivate || canDeactivate) && (
                                                        <Button
                                                            variant={customer.status === 'active' ? 'destructive' : 'default'}
                                                            size="sm"
                                                            onClick={() => handleToggleStatus(customer)}
                                                            disabled={processing[customer.id]}
                                                        >
                                                            {customer.status === 'active' ? (
                                                                <XCircle className="h-4 w-4" />
                                                            ) : (
                                                                <CheckCircle className="h-4 w-4" />
                                                            )}
                                                        </Button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center">
                                            <Users className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                                            <p className="text-lg font-medium text-gray-600 dark:text-gray-300">Aucun client trouvé</p>
                                            <p className="text-sm mt-1 text-gray-500 dark:text-gray-400">Créez votre premier client pour commencer</p>
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Pagination */}
                    {customers?.last_page > 1 && (
                        <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-700/30">
                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                Page {customers.current_page} sur {customers.last_page}
                            </p>
                            <div className="flex gap-2">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(customers.prev_page_url)}
                                    disabled={!customers.prev_page_url}
                                    className="border-gray-300 dark:border-gray-600"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.get(customers.next_page_url)}
                                    disabled={!customers.next_page_url}
                                    className="border-gray-300 dark:border-gray-600"
                                >
                                    <ChevronRight className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* Customer Drawer */}
            <CustomerDrawer
                isOpen={drawerOpen}
                onClose={handleCloseDrawer}
                customer={selectedCustomer}
                onSuccess={handleDrawerSuccess}
                canCreate={canCreate}
                canUpdate={canEdit}
            />
        </AppLayout>
    );
}
