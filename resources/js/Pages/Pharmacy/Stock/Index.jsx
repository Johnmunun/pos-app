import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import { 
  ArrowLeft,
  Package,
  AlertTriangle,
  Calendar,
  Clock,
  Plus,
  Minus,
  Edit3
} from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';

export default function StockManagement({ auth, products, lowStock, expiringSoon }) {
    const { toast } = useToast();
    const { data, setData, post, processing, errors } = useForm({
        product_id: '',
        type: 'adjust',
        quantity: '',
        batch_number: '',
        expiry_date: '',
        supplier_id: '',
        purchase_order_id: ''
    });

    const [showStockForm, setShowStockForm] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);

    const handleStockUpdate = (e) => {
        e.preventDefault();
        post(route('pharmacy.products.stock.update', selectedProduct.id), {
            onSuccess: () => {
                toast({
                    title: "Success",
                    description: "Stock updated successfully",
                });
                setShowStockForm(false);
                setSelectedProduct(null);
                router.reload();
            },
            onError: (errors) => {
                toast({
                    title: "Error",
                    description: errors.message || "Failed to update stock",
                    variant: "destructive",
                });
            }
        });
    };

    const openStockForm = (product, type = 'adjust') => {
        setSelectedProduct(product);
        setData({
            ...data,
            product_id: product.id,
            type: type
        });
        setShowStockForm(true);
    };

    const getStockStatus = (product) => {
        if (product.current_stock <= 0) return { status: 'out', color: 'destructive' };
        if (product.current_stock <= product.minimum_stock) return { status: 'low', color: 'warning' };
        return { status: 'good', color: 'success' };
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={
                <div className="flex items-center">
                    <Button variant="ghost" asChild className="mr-4">
                        <Link href={route('pharmacy.dashboard')}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 leading-tight">
                        Stock Management
                    </h2>
                </div>
            }
        >
            <Head title="Stock Management" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Low Stock Alert */}
                    {lowStock.length > 0 && (
                        <Card className="mb-6 border-orange-200 bg-orange-50">
                            <CardHeader>
                                <CardTitle className="flex items-center text-orange-800">
                                    <AlertTriangle className="h-5 w-5 mr-2" />
                                    Low Stock Alert ({lowStock.length} items)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {lowStock.map(product => (
                                        <div key={product.id} className="flex items-center justify-between p-3 bg-white rounded-lg border">
                                            <div>
                                                <h4 className="font-medium">{product.name}</h4>
                                                <p className="text-sm text-gray-600">
                                                    Current: {product.current_stock} | Minimum: {product.minimum_stock}
                                                </p>
                                            </div>
                                            <Button size="sm" onClick={() => openStockForm(product, 'add')}>
                                                <Plus className="h-4 w-4 mr-1" />
                                                Add Stock
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Expiring Soon Alert */}
                    {expiringSoon.length > 0 && (
                        <Card className="mb-6 border-red-200 bg-red-50">
                            <CardHeader>
                                <CardTitle className="flex items-center text-red-800">
                                    <Calendar className="h-5 w-5 mr-2" />
                                    Expiring Soon ({expiringSoon.length} batches)
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {expiringSoon.map(batch => (
                                        <div key={batch.id} className="flex items-center justify-between p-3 bg-white rounded-lg border">
                                            <div>
                                                <h4 className="font-medium">{batch.product_name}</h4>
                                                <p className="text-sm text-gray-600">
                                                    Batch: {batch.batch_number} | Expires: {batch.expiry_date}
                                                </p>
                                            </div>
                                            <Badge variant="destructive">
                                                {batch.days_until_expiry} days
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Stock Update Form */}
                    {showStockForm && selectedProduct && (
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle>Update Stock for {selectedProduct.name}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleStockUpdate} className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="type">Operation Type</Label>
                                            <select
                                                id="type"
                                                value={data.type}
                                                onChange={(e) => setData('type', e.target.value)}
                                                className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="add">Add Stock</option>
                                                <option value="remove">Remove Stock</option>
                                                <option value="adjust">Adjust Stock</option>
                                            </select>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="quantity">Quantity *</Label>
                                            <Input
                                                id="quantity"
                                                type="number"
                                                value={data.quantity}
                                                onChange={(e) => setData('quantity', e.target.value)}
                                                placeholder="Enter quantity"
                                            />
                                            {errors.quantity && <p className="text-sm text-red-600">{errors.quantity}</p>}
                                        </div>
                                    </div>

                                    {(data.type === 'add' || data.type === 'adjust') && (
                                        <>
                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="batch_number">Batch Number</Label>
                                                    <Input
                                                        id="batch_number"
                                                        value={data.batch_number}
                                                        onChange={(e) => setData('batch_number', e.target.value)}
                                                        placeholder="BATCH-001"
                                                    />
                                                    {errors.batch_number && <p className="text-sm text-red-600">{errors.batch_number}</p>}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="expiry_date">Expiry Date</Label>
                                                    <Input
                                                        id="expiry_date"
                                                        type="date"
                                                        value={data.expiry_date}
                                                        onChange={(e) => setData('expiry_date', e.target.value)}
                                                    />
                                                    {errors.expiry_date && <p className="text-sm text-red-600">{errors.expiry_date}</p>}
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div className="space-y-2">
                                                    <Label htmlFor="supplier_id">Supplier ID</Label>
                                                    <Input
                                                        id="supplier_id"
                                                        value={data.supplier_id}
                                                        onChange={(e) => setData('supplier_id', e.target.value)}
                                                        placeholder="Supplier ID"
                                                    />
                                                    {errors.supplier_id && <p className="text-sm text-red-600">{errors.supplier_id}</p>}
                                                </div>

                                                <div className="space-y-2">
                                                    <Label htmlFor="purchase_order_id">Purchase Order ID</Label>
                                                    <Input
                                                        id="purchase_order_id"
                                                        value={data.purchase_order_id}
                                                        onChange={(e) => setData('purchase_order_id', e.target.value)}
                                                        placeholder="PO-001"
                                                    />
                                                    {errors.purchase_order_id && <p className="text-sm text-red-600">{errors.purchase_order_id}</p>}
                                                </div>
                                            </div>
                                        </>
                                    )}

                                    <div className="flex justify-end space-x-3 pt-4">
                                        <Button
                                            variant="outline"
                                            onClick={() => {
                                                setShowStockForm(false);
                                                setSelectedProduct(null);
                                            }}
                                            disabled={processing}
                                        >
                                            Cancel
                                        </Button>
                                        <Button type="submit" disabled={processing}>
                                            {processing ? 'Updating...' : 'Update Stock'}
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    )}

                    {/* Products List */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Package className="h-5 w-5 mr-2" />
                                All Products ({products.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Product
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Current Stock
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200">
                                        {products.map((product) => {
                                            const stockStatus = getStockStatus(product);
                                            return (
                                                <tr key={product.id} className="hover:bg-gray-50">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div>
                                                            <div className="text-sm font-medium text-gray-900">
                                                                {product.name}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {product.product_code}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900">
                                                            <span className="font-medium">{product.current_stock}</span>
                                                            <span className="text-gray-500"> / {product.minimum_stock}</span>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <Badge variant={stockStatus.color}>
                                                            {stockStatus.status === 'out' && 'Out of Stock'}
                                                            {stockStatus.status === 'low' && 'Low Stock'}
                                                            {stockStatus.status === 'good' && 'Good'}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div className="flex space-x-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => openStockForm(product, 'add')}
                                                                disabled={processing}
                                                            >
                                                                <Plus className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => openStockForm(product, 'remove')}
                                                                disabled={processing}
                                                            >
                                                                <Minus className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => openStockForm(product, 'adjust')}
                                                                disabled={processing}
                                                            >
                                                                <Edit3 className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}