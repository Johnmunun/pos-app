import React from 'react';
import { Head } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

export default function Dashboard({ auth, stats }) {
    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                    Pharmacy Dashboard
                </h2>
            }
        >
            <Head title="Pharmacy Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Total Products</CardTitle>
                                <Package className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.products?.total || 0}</div>
                                <p className="text-xs text-muted-foreground">
                                    {stats.products?.active || 0} active products
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Low Stock</CardTitle>
                                <AlertTriangle className="h-4 w-4 text-orange-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-orange-600">
                                    {stats.inventory?.low_stock_count || 0}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Products need restocking
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Inventory Value</CardTitle>
                                <DollarSign className="h-4 w-4 text-muted-foreground" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">
                                    ${stats.inventory?.total_value?.toFixed(2) || '0.00'}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Total stock value
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">Expiring Soon</CardTitle>
                                <Calendar className="h-4 w-4 text-red-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-red-600">
                                    {stats.expiry?.expiring_soon_count || 0}
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Within 30 days
                                </p>
                            </CardContent>
                        </Card>
                    </div>

                    {/* Alerts Section */}
                    {stats.alerts && stats.alerts.length > 0 && (
                        <div className="mb-8">
                            <h3 className="text-lg font-semibold mb-4">System Alerts</h3>
                            <div className="space-y-3">
                                {stats.alerts.map((alert, index) => (
                                    <div 
                                        key={index}
                                        className={`p-4 rounded-lg border ${
                                            alert.type === 'danger' 
                                                ? 'bg-red-50 border-red-200' 
                                                : 'bg-yellow-50 border-yellow-200'
                                        }`}
                                    >
                                        <div className="flex items-center">
                                            <AlertTriangle className={`h-5 w-5 mr-3 ${
                                                alert.type === 'danger' ? 'text-red-600' : 'text-yellow-600'
                                            }`} />
                                            <div>
                                                <p className={`font-medium ${
                                                    alert.type === 'danger' ? 'text-red-800' : 'text-yellow-800'
                                                }`}>
                                                    {alert.message}
                                                </p>
                                                <p className={`text-sm ${
                                                    alert.type === 'danger' ? 'text-red-600' : 'text-yellow-600'
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
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Pill className="h-5 w-5 mr-2" />
                                    Products
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Button className="w-full" asChild>
                                    <a href={route('pharmacy.products.index')}>
                                        View Products
                                    </a>
                                </Button>
                                <Button variant="outline" className="w-full" asChild>
                                    <a href={route('pharmacy.products.create')}>
                                        Add New Product
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <ShoppingCart className="h-5 w-5 mr-2" />
                                    Inventory
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Button variant="outline" className="w-full" asChild>
                                    <a href={route('pharmacy.stock')}>
                                        Manage Stock
                                    </a>
                                </Button>
                                <Button variant="outline" className="w-full" asChild>
                                    <a href={route('pharmacy.expiry')}>
                                        Check Expiry
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Clock className="h-5 w-5 mr-2" />
                                    Categories
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <Button variant="outline" className="w-full" asChild>
                                    <a href={route('pharmacy.categories.index')}>
                                        Manage Categories
                                    </a>
                                </Button>
                                <Button variant="outline" className="w-full" asChild>
                                    <a href={route('pharmacy.categories.create')}>
                                        Add Category
                                    </a>
                                </Button>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}