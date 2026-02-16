import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { 
  Package, 
  AlertTriangle, 
  Calendar, 
  DollarSign,
  TrendingUp,
  ShoppingCart,
  Pill,
  Clock
} from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

export default function Dashboard({ stats }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    const fmt = (amount) => formatCurrency(amount, currency);
    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Pharmacy Dashboard
                </h2>
            }
        >
            <Head title="Pharmacy Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Total Products</CardTitle>
                                <Package className="h-4 w-4 text-gray-400 dark:text-gray-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">{stats.products?.total || 0}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    {stats.products?.active || 0} active products
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Low Stock</CardTitle>
                                <AlertTriangle className="h-4 w-4 text-orange-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-orange-600 dark:text-orange-400">
                                    {stats.inventory?.low_stock_count || 0}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Products need restocking
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Valeur du stock</CardTitle>
                                <DollarSign className="h-4 w-4 text-green-500 dark:text-green-400" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {fmt(stats.inventory?.total_value || 0)}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Valeur totale en stock
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Expiring Soon</CardTitle>
                                <Calendar className="h-4 w-4 text-red-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-red-600 dark:text-red-400">
                                    {stats.expiry?.expiring_soon_count || 0}
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    Within 30 days
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Alerts Section */}
                    {stats.alerts && stats.alerts.length > 0 && (
                        <div className="mb-8">
                            <h3 className="text-lg font-semibold mb-4 text-gray-900 dark:text-white">System Alerts</h3>
                            <div className="space-y-3">
                                {stats.alerts.map((alert, index) => (
                                    <div 
                                        key={index}
                                        className={`p-4 rounded-lg border ${
                                            alert.type === 'danger' 
                                                ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' 
                                                : 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'
                                        }`}
                                    >
                                        <div className="flex items-center">
                                            <AlertTriangle className={`h-5 w-5 mr-3 ${
                                                alert.type === 'danger' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400'
                                            }`} />
                                            <div>
                                                <p className={`font-medium ${
                                                    alert.type === 'danger' ? 'text-red-800 dark:text-red-300' : 'text-yellow-800 dark:text-yellow-300'
                                                }`}>
                                                    {alert.message}
                                                </p>
                                                <p className={`text-sm ${
                                                    alert.type === 'danger' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400'
                                                }`}>
                                                    Priority: {alert.priority}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Quick Actions */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader>
                                <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                    <Pill className="h-5 w-5 mr-2 text-amber-500" />
                                    Products
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Button className="w-full bg-amber-500 hover:bg-amber-600 text-white" asChild>
                                    <a href={route('pharmacy.products')}>
                                        View Products
                                    </a>
                                </Button>
                                <Button variant="outline" className="w-full border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800" asChild>
                                    <a href={route('pharmacy.products.create')}>
                                        Add New Product
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader>
                                <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                    <ShoppingCart className="h-5 w-5 mr-2 text-blue-500" />
                                    Inventory
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Button variant="outline" className="w-full border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800" asChild>
                                    <a href={route('pharmacy.stock.index')}>
                                        Manage Stock
                                    </a>
                                </Button>
                                <Button variant="outline" className="w-full border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800" asChild>
                                    <a href={route('pharmacy.stock.movements.index')}>
                                        Historique des mouvements
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader>
                                <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                    <Clock className="h-5 w-5 mr-2 text-purple-500" />
                                    Categories
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Button variant="outline" className="w-full border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800" asChild>
                                    <a href={route('pharmacy.categories.index')}>
                                        Manage Categories
                                    </a>
                                </Button>
                                <Button variant="outline" className="w-full border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800" asChild>
                                    <a href={route('pharmacy.categories.index')}>
                                        Add Category
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
