import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import ProductDrawer from '@/Components/Pharmacy/ProductDrawer';
import { 
  Search, 
  Plus, 
  Edit, 
  Trash2, 
  Package,
  AlertTriangle,
  Eye
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import imageCache from '@/lib/imageCache';
import syncService from '@/lib/syncService';

export default function ProductsIndex({ auth, products, categories, filters }) {
    const [searchTerm, setSearchTerm] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category_id || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('pharmacy.products.index'), {
            search: searchTerm,
            category_id: selectedCategory
        });
    };

    const handleCreate = () => {
        setEditingProduct(null);
        setDrawerOpen(true);
    };

    const handleEdit = (product) => {
        setEditingProduct(product);
        setDrawerOpen(true);
    };

    const handleDelete = (product) => {
        toast.custom((t) => (
            <div className={`${t.visible ? 'animate-enter' : 'animate-leave'} max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5`}>
                <div className="flex-1 w-0 p-4">
                    <div className="flex items-start">
                        <div className="flex-shrink-0">
                            <AlertTriangle className="h-6 w-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div className="ml-3 flex-1">
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                Confirmer la suppression
                            </p>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Êtes-vous sûr de vouloir supprimer "{product.name}" ? Cette action est irréversible.
                            </p>
                        </div>
                    </div>
                    <div className="mt-4 flex gap-2">
                        <button
                            onClick={() => {
                                toast.dismiss(t.id);
                                router.delete(route('pharmacy.products.destroy', product.id), {
                                    preserveScroll: true,
                                    onSuccess: () => {
                                        toast.success('Produit supprimé avec succès');
                                    },
                                    onError: (errors) => {
                                        toast.error(errors.message || 'Erreur lors de la suppression du produit');
                                    }
                                });
                            }}
                            className="flex-1 bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition"
                        >
                            Supprimer
                        </button>
                        <button
                            onClick={() => toast.dismiss(t.id)}
                            className="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        ), {
            duration: Infinity,
        });
    };

    const getStatusBadge = (product) => {
        if (product.stock <= 0) {
            return <Badge variant="destructive">Out of Stock</Badge>;
        }
        if (product.stock <= product.minimum_stock) {
            return <Badge variant="warning">Low Stock</Badge>;
        }
        return <Badge variant="success">In Stock</Badge>;
    };

    return (
        <AppLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Gestion des Produits
                    </h2>
                    <button
                        onClick={handleCreate}
                        className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 px-4 py-2 h-10 shadow-sm hover:shadow-md"
                    >
                        <Plus className="h-4 w-4 mr-2" />
                        <span className="hidden sm:inline">Ajouter un produit</span>
                        <span className="sm:hidden">Ajouter</span>
                    </button>
                </div>
            }
        >
            <Head title="Gestion des Produits" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Search and Filters */}
                    <Card className="mb-6 bg-white dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Search className="h-5 w-5 mr-2" />
                                Search Products
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-4">
                                <div className="flex-1">
                                    <Input
                                        placeholder="Search by name, code..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </div>
                                <div className="flex-1">
                                    <select
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={selectedCategory}
                                        onChange={(e) => setSelectedCategory(e.target.value)}
                                    >
                                        <option value="">All Categories</option>
                                        {categories.map(category => (
                                            <option key={category.id} value={category.id}>
                                                {category.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <Button type="submit">
                                    <Search className="h-4 w-4 mr-2" />
                                    Search
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Products Table */}
                    <Card className="bg-white dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Package className="h-5 w-5 mr-2" />
                                Products List ({products.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {products.length === 0 ? (
                                <div className="text-center py-12">
                                    <Package className="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">No products found</h3>
                                    <p className="text-gray-500 dark:text-gray-400 mb-4">Get started by creating your first product.</p>
                                    <button
                                        onClick={handleCreate}
                                        className="inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-amber-500 text-white hover:bg-amber-600 dark:bg-amber-600 dark:text-white dark:hover:bg-amber-700 px-4 py-2 h-10 shadow-sm hover:shadow-md"
                                    >
                                        <Plus className="h-4 w-4 mr-2" />
                                        Ajouter un produit
                                    </button>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Product
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Code
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Category
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Price
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Stock
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                            {products.map((product) => (
                                                <tr key={product.id} className="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center">
                                                            <div className="flex-shrink-0 h-10 w-10">
                                                                <div className="h-10 w-10 rounded-full bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                                                                    <Package className="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                                                </div>
                                                            </div>
                                                            <div className="ml-4">
                                                                <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                                    {product.name}
                                                                </div>
                                                                <div className="text-sm text-gray-500 dark:text-gray-400">
                                                                    {product.description?.substring(0, 50)}...
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {product.product_code}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {product.category?.name || 'N/A'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        {product.price_currency} {product.price_amount?.toFixed(2)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                        <span className="font-medium">{product.current_stock}</span>
                                                        <span className="text-gray-500 dark:text-gray-400"> / {product.minimum_stock}</span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm">
                                                        {getStatusBadge(product)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                        <div className="flex space-x-2">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                asChild
                                                            >
                                                                <Link href={route('pharmacy.products.show', product.id)}>
                                                                    <Eye className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleEdit(product)}
                                                                title="Modifier"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                                onClick={() => handleDelete(product)}
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Product Drawer */}
            <ProductDrawer
                isOpen={drawerOpen}
                onClose={() => {
                    setDrawerOpen(false);
                    setEditingProduct(null);
                }}
                product={editingProduct}
                categories={categories}
            />
        </AppLayout>
    );
}