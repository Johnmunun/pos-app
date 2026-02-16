import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import CustomerDrawer from '@/Components/Pharmacy/CustomerDrawer';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import {
    ArrowLeft,
    Edit,
    CheckCircle,
    XCircle,
    Phone,
    Mail,
    MapPin,
    Building2,
    User,
    CreditCard,
    FileText,
    Calendar,
    ShoppingCart,
    Receipt,
} from 'lucide-react';

export default function ShowCustomer({ customer }) {
    const { auth, shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canEdit = hasPermission('pharmacy.customer.edit');
    const canActivate = hasPermission('pharmacy.customer.activate');
    const canDeactivate = hasPermission('pharmacy.customer.deactivate');

    const [drawerOpen, setDrawerOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const handleToggleStatus = async () => {
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

        setProcessing(true);

        try {
            const response = await axios.post(route(`pharmacy.customers.${action}`, customer.id));
            if (response.data.success) {
                toast.success(response.data.message);
                router.reload();
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error(error.response?.data?.message || 'Une erreur est survenue');
        } finally {
            setProcessing(false);
        }
    };

    const formatCurrency = (amount) => {
        if (amount === null || amount === undefined) return '-';
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    };

    const getStatusBadge = (status) => {
        if (status === 'active') {
            return (
                <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                    <CheckCircle className="h-3 w-3 mr-1" />
                    Actif
                </Badge>
            );
        }
        return (
            <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                <XCircle className="h-3 w-3 mr-1" />
                Inactif
            </Badge>
        );
    };

    return (
        <AppLayout>
            <Head title={`Client - ${customer.name}`} />

            <div className="container mx-auto py-6 px-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="icon" asChild className="border-gray-300 dark:border-gray-600">
                            <Link href={route('pharmacy.customers.index')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-gray-100">
                                {customer.customer_type === 'company' ? (
                                    <Building2 className="h-6 w-6" />
                                ) : (
                                    <User className="h-6 w-6" />
                                )}
                                {customer.name}
                            </h1>
                            <div className="flex items-center gap-2 mt-1">
                                {getStatusBadge(customer.status)}
                                <Badge variant="outline" className="border-gray-400 dark:border-gray-500 text-gray-700 dark:text-gray-300">
                                    {customer.customer_type_label}
                                </Badge>
                            </div>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {canEdit && (
                            <Button onClick={() => setDrawerOpen(true)}>
                                <Edit className="h-4 w-4 mr-2" />
                                Modifier
                            </Button>
                        )}
                        {(canActivate || canDeactivate) && (
                            <Button
                                variant={customer.status === 'active' ? 'destructive' : 'default'}
                                onClick={handleToggleStatus}
                                disabled={processing}
                            >
                                {customer.status === 'active' ? (
                                    <>
                                        <XCircle className="h-4 w-4 mr-2" />
                                        Désactiver
                                    </>
                                ) : (
                                    <>
                                        <CheckCircle className="h-4 w-4 mr-2" />
                                        Activer
                                    </>
                                )}
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Informations principales */}
                    <div className="lg:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Informations du client</h2>
                        </div>
                        <div className="p-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-4">
                                    <div className="flex items-start gap-3">
                                        <Phone className="h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" />
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Téléphone</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">{customer.phone || '-'}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-3">
                                        <Mail className="h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" />
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">{customer.email || '-'}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-3">
                                        <MapPin className="h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" />
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Adresse</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">{customer.address || '-'}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    {customer.customer_type === 'company' && customer.tax_number && (
                                        <div className="flex items-start gap-3">
                                            <FileText className="h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" />
                                            <div>
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Numéro fiscal</p>
                                                <p className="font-medium text-gray-900 dark:text-gray-100">{customer.tax_number}</p>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex items-start gap-3">
                                        <CreditCard className="h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" />
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Limite de crédit</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">{formatCurrency(customer.credit_limit)}</p>
                                        </div>
                                    </div>

                                    <div className="flex items-start gap-3">
                                        <Calendar className="h-5 w-5 text-gray-400 dark:text-gray-500 mt-0.5" />
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Date d'inscription</p>
                                            <p className="font-medium text-gray-900 dark:text-gray-100">{customer.created_at}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Statistiques */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">Statistiques</h2>
                        </div>
                        <div className="p-6">
                            <div className="space-y-4">
                                <div className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                    <div className="flex items-center gap-3">
                                        <ShoppingCart className="h-8 w-8 text-primary" />
                                        <div>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Total ventes</p>
                                            <p className="text-2xl font-bold text-gray-900 dark:text-gray-100">{customer.total_sales || 0}</p>
                                        </div>
                                    </div>
                                </div>

                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                    <p>Dernière mise à jour: {customer.updated_at}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Ventes récentes */}
                    <div className="lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <Receipt className="h-5 w-5" />
                                Ventes récentes
                            </h2>
                        </div>
                        <div className="p-0">
                            {customer.recent_sales?.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full">
                                        <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">
                                                    Date
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">
                                                    Montant
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase">
                                                    Statut
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {customer.recent_sales.map((sale) => (
                                                <tr key={sale.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                                                    <td className="px-6 py-4 text-gray-700 dark:text-gray-300">{sale.created_at}</td>
                                                    <td className="px-6 py-4 font-medium text-gray-900 dark:text-gray-100">
                                                        {formatCurrency(sale.total_amount)}
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <Badge variant="outline" className="border-gray-400 dark:border-gray-500 text-gray-700 dark:text-gray-300">
                                                            {sale.status}
                                                        </Badge>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="text-center py-8">
                                    <Receipt className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                                    <p className="text-gray-500 dark:text-gray-400">Aucune vente pour ce client</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Customer Drawer */}
            <CustomerDrawer
                isOpen={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                customer={customer}
                onSuccess={() => router.reload()}
                canCreate={false}
                canUpdate={canEdit}
            />
        </AppLayout>
    );
}
