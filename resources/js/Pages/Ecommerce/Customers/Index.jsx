import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import CustomerDrawer from '@/Components/Ecommerce/CustomerDrawer';
import { toast } from 'react-hot-toast';
import {
    Plus,
    Search,
    Users,
    Mail,
    Phone,
    RefreshCw,
} from 'lucide-react';

export default function CustomersIndex({ customers = [] }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canCreate = hasPermission('ecommerce.create');
    const canView = hasPermission('ecommerce.view');

    const [search, setSearch] = useState('');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedCustomer, setSelectedCustomer] = useState(null);

    const handleOpenCreate = () => {
        setSelectedCustomer(null);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setSelectedCustomer(null);
    };

    const formatCurrency = (amount, currency) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const filteredCustomers = customers.filter(customer => {
        if (!search) return true;
        const searchLower = search.toLowerCase();
        return (
            customer.full_name.toLowerCase().includes(searchLower) ||
            customer.email.toLowerCase().includes(searchLower) ||
            (customer.phone && customer.phone.toLowerCase().includes(searchLower))
        );
    });

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                            <Users className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Clients Ecommerce
                            </h2>
                        </div>
                    </div>
                    {canCreate && (
                        <Button onClick={handleOpenCreate} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Nouveau client
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Clients Ecommerce" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Barre de recherche */}
                    <div className="mb-6">
                        <div className="flex gap-4">
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Rechercher un client..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button
                                onClick={() => router.reload({ only: ['customers'] })}
                                variant="outline"
                                size="icon"
                            >
                                <RefreshCw className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    {/* Liste des clients */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {filteredCustomers.length === 0 ? (
                            <div className="py-12 text-center">
                                <Users className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                    {search ? 'Aucun client trouvé' : 'Aucun client'}
                                </p>
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    {search
                                        ? 'Essayez avec d\'autres termes de recherche'
                                        : 'Commencez par créer votre premier client'}
                                </p>
                                {canCreate && !search && (
                                    <Button onClick={handleOpenCreate} className="gap-2">
                                        <Plus className="h-4 w-4" />
                                        Nouveau client
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Client
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Contact
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Commandes
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Total dépensé
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {filteredCustomers.map((customer) => (
                                            <tr
                                                key={customer.id}
                                                className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                            >
                                                <td className="px-6 py-4">
                                                    <div className="font-medium text-gray-900 dark:text-gray-100">
                                                        {customer.full_name}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                            <Mail className="h-3 w-3" />
                                                            {customer.email}
                                                        </div>
                                                        {customer.phone && (
                                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                                <Phone className="h-3 w-3" />
                                                                {customer.phone}
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {customer.total_orders}
                                                </td>
                                                <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {formatCurrency(customer.total_spent, 'USD')}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <Badge className={customer.is_active 
                                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300'
                                                    }>
                                                        {customer.is_active ? 'Actif' : 'Inactif'}
                                                    </Badge>
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
                <CustomerDrawer
                    isOpen={drawerOpen}
                    onClose={handleCloseDrawer}
                    customer={selectedCustomer}
                />
            )}
        </AppLayout>
    );
}
