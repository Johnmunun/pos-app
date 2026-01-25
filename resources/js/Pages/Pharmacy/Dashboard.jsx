import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Package, AlertTriangle, Calendar, XCircle } from 'lucide-react';

export default function Dashboard({ stats }) {
    return (
        <AppLayout
            header={
                <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                    Dashboard Pharmacy
                </h2>
            }
        >
            <Head title="Dashboard - Pharmacy" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Total Produits</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
                                        {stats.total_products}
                                    </p>
                                </div>
                                <Package className="h-8 w-8 text-amber-500" />
                            </div>
                        </div>

                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Stock Bas</p>
                                    <p className="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                                        {stats.low_stock}
                                    </p>
                                </div>
                                <AlertTriangle className="h-8 w-8 text-red-500" />
                            </div>
                        </div>

                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Expirant Bientôt</p>
                                    <p className="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">
                                        {stats.expiring_soon}
                                    </p>
                                </div>
                                <Calendar className="h-8 w-8 text-orange-500" />
                            </div>
                        </div>

                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-600 dark:text-gray-400">Expirés</p>
                                    <p className="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                                        {stats.expired}
                                    </p>
                                </div>
                                <XCircle className="h-8 w-8 text-red-500" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}


