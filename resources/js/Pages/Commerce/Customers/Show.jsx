import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import CustomerDrawer from '@/Components/Commerce/CustomerDrawer';
import LoyaltyCustomerSection from '@/Components/Loyalty/LoyaltyCustomerSection';
import {
    ArrowLeft,
    Edit,
    User,
    Mail,
    Phone,
    MapPin,
    CheckCircle,
    XCircle,
    Hash,
} from 'lucide-react';

export default function CommerceCustomersShow({ customer }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (perm) => {
        if (auth?.user?.type === 'ROOT') return true;
        if (permissions.includes('*')) return true;
        return permissions.includes(perm);
    };

    const canEdit = hasPermission('commerce.customer.edit');
    const [drawerOpen, setDrawerOpen] = useState(false);

    if (!customer) return null;

    return (
        <AppLayout>
            <Head title={`Client — ${customer.full_name}`} />

            <div className="container mx-auto py-6 px-4 max-w-4xl">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                    <div className="flex items-center gap-4">
                        <Button variant="outline" size="icon" asChild>
                            <Link href={route('commerce.customers.index')}>
                                <ArrowLeft className="h-4 w-4" />
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold flex items-center gap-2 text-gray-900 dark:text-white">
                                <User className="h-6 w-6" />
                                {customer.full_name}
                            </h1>
                            <div className="flex items-center gap-2 mt-1">
                                {customer.is_active ? (
                                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                        <CheckCircle className="h-3 w-3 mr-1" />
                                        Actif
                                    </Badge>
                                ) : (
                                    <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                        <XCircle className="h-3 w-3 mr-1" />
                                        Inactif
                                    </Badge>
                                )}
                                {customer.code && (
                                    <Badge variant="outline" className="font-mono text-xs">
                                        {customer.code}
                                    </Badge>
                                )}
                            </div>
                        </div>
                    </div>
                    {canEdit && (
                        <Button onClick={() => setDrawerOpen(true)}>
                            <Edit className="h-4 w-4 mr-2" />
                            Modifier
                        </Button>
                    )}
                </div>

                <div className="mb-6">
                    <LoyaltyCustomerSection
                        customerId={customer.id}
                        customerName={customer.full_name}
                        routePrefix="commerce"
                    />
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 space-y-4">
                    <h2 className="text-lg font-semibold text-gray-900 dark:text-white">Coordonnées</h2>
                    {customer.email && (
                        <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <Mail className="h-4 w-4 shrink-0" />
                            {customer.email}
                        </div>
                    )}
                    {customer.phone && (
                        <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <Phone className="h-4 w-4 shrink-0" />
                            {customer.phone}
                        </div>
                    )}
                    {customer.address && (
                        <div className="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <MapPin className="h-4 w-4 shrink-0 mt-0.5" />
                            {customer.address}
                        </div>
                    )}
                    {customer.created_at && (
                        <div className="flex items-center gap-2 text-sm text-gray-500">
                            <Hash className="h-4 w-4" />
                            Client depuis le {customer.created_at}
                        </div>
                    )}
                </div>
            </div>

            <CustomerDrawer
                isOpen={drawerOpen}
                onClose={() => setDrawerOpen(false)}
                customer={customer}
                onSuccess={() => router.reload()}
                canUpdate={canEdit}
            />
        </AppLayout>
    );
}
