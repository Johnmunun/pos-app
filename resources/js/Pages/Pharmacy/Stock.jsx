import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { AlertTriangle, Calendar } from 'lucide-react';

export default function Stock({ lowStock, expiringSoon }) {
    return (
        <AppLayout
            header={
                <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                    Gestion du Stock
                </h2>
            }
        >
            <Head title="Stock - Pharmacy" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                    {/* Low Stock */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <AlertTriangle className="h-5 w-5 text-red-500" />
                                Stock Bas
                            </h2>
                        </div>
                        <div className="p-6">
                            {lowStock.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Produit
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Stock Actuel
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Seuil d'Alerte
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {lowStock.map((product) => (
                                                <tr key={product.id}>
                                                    <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                        {product.name}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-red-600 dark:text-red-400 font-semibold">
                                                        {product.total_stock}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                        {product.stock_alert_level}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-gray-600 dark:text-gray-400">
                                    Aucun produit en stock bas.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Expiring Soon */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <Calendar className="h-5 w-5 text-orange-500" />
                                Expirant Bientôt (30 jours)
                            </h2>
                        </div>
                        <div className="p-6">
                            {expiringSoon.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Produit
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Lot
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Date d'Expiration
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Quantité
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Jours Restants
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {expiringSoon.map((batch) => (
                                                <tr key={batch.id}>
                                                    <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                        {batch.product_name}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                        {batch.batch_number}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                        {batch.expiration_date}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                        {batch.quantity}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-orange-600 dark:text-orange-400 font-semibold">
                                                        {batch.days_until_expiry} jours
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-gray-600 dark:text-gray-400">
                                    Aucun produit n'expire dans les 30 prochains jours.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}


