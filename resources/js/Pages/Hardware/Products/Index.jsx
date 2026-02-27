import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import Modal from '@/Components/Modal';
import HardwareProductDrawer from '@/Components/Hardware/ProductDrawer';
import { Search, Plus, Edit, Trash2, Package, AlertTriangle, Eye, X } from 'lucide-react';
import { toast } from 'react-hot-toast';

/**
 * Page liste des produits — Module Quincaillerie.
 * Vue dédiée, aucun import Pharmacy.
 */
export default function HardwareProductsIndex({ products = [], categories = [], filters = {}, canImport = false }) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters?.category_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [detailOpen, setDetailOpen] = useState(false);
    const [detailProduct, setDetailProduct] = useState(null);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('hardware.products'), {
            search: searchTerm,
            category_id: selectedCategory,
            status: selectedStatus || undefined,
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

    const handleView = (product) => {
        setDetailProduct(product);
        setDetailOpen(true);
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
                            <p className="text-sm font-medium text-gray-900 dark:text-white">Confirmer la suppression</p>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Êtes-vous sûr de vouloir supprimer &quot;{product.name}&quot; ? Cette action est irréversible.
                            </p>
                        </div>
                    </div>
                    <div className="mt-4 flex gap-2">
                        <button
                            onClick={() => {
                                toast.dismiss(t.id);
                                router.delete(route('hardware.products.destroy', product.id), {
                                    preserveScroll: true,
                                    onSuccess: () => toast.success('Produit supprimé'),
                                    onError: (errors) => toast.error(errors?.message || 'Erreur'),
                                });
                            }}
                            className="flex-1 bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition"
                        >
                            Supprimer
                        </button>
                        <button onClick={() => toast.dismiss(t.id)} className="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition">
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        ), { duration: Infinity });
    };

    const getStatusBadge = (product) => {
        const stock = product.current_stock ?? 0;
        const minStock = product.minimum_stock ?? 0;
        if (stock <= 0) return <Badge variant="destructive">Rupture</Badge>;
        if (stock <= minStock) return <Badge variant="warning">Stock bas</Badge>;
        return <Badge variant="success">En stock</Badge>;
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Produits — Quincaillerie
                    </h2>
                    <Button onClick={handleCreate} className="bg-amber-500 hover:bg-amber-600 text-white">
                        <Plus className="h-4 w-4 mr-2" />
                        <span className="hidden sm:inline">Ajouter un produit</span>
                        <span className="sm:hidden">Ajouter</span>
                    </Button>
                </div>
            }
        >
            <Head title="Produits - Quincaillerie" />
            <div className="py-6 space-y-6">
                <Card className="mb-6 bg-white dark:bg-gray-800">
                    <CardHeader>
                        <CardTitle className="flex items-center text-gray-900 dark:text-white">
                            <Search className="h-5 w-5 mr-2" /> Recherche
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex flex-col md:flex-row gap-4">
                            <div className="flex-1">
                                <Input
                                    placeholder="Rechercher par nom ou code..."
                                    value={searchTerm}
                                    onChange={(e) => setSearchTerm(e.target.value)}
                                />
                            </div>
                            <div className="flex-1">
                                <select
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    value={selectedCategory}
                                    onChange={(e) => setSelectedCategory(e.target.value)}
                                >
                                    <option value="">Toutes les catégories</option>
                                    {categories.map((cat) => (
                                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="flex-1">
                                <select
                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                    value={selectedStatus}
                                    onChange={(e) => setSelectedStatus(e.target.value)}
                                >
                                    <option value="">Tous les statuts</option>
                                    <option value="active">Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                            </div>
                            <Button type="submit">
                                <Search className="h-4 w-4 mr-2" /> Rechercher
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-gray-800">
                    <CardHeader>
                        <CardTitle className="flex items-center text-gray-900 dark:text-white">
                            <Package className="h-5 w-5 mr-2" /> Liste des produits ({products.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {products.length === 0 ? (
                            <div className="text-center py-12">
                                <Package className="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Aucun produit</h3>
                                <p className="text-gray-500 dark:text-gray-400 mb-4">Créez votre premier produit.</p>
                                <Button onClick={handleCreate} className="bg-amber-500 hover:bg-amber-600 text-white">
                                    <Plus className="h-4 w-4 mr-2" /> Ajouter un produit
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-800">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produit</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Catégorie</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                        {products.map((product) => (
                                            <tr key={product.id} className="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="flex-shrink-0 h-10 w-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                                            <Package className="h-6 w-6 text-gray-400" />
                                                        </div>
                                                        <div className="ml-4">
                                                            <div className="text-sm font-medium text-gray-900 dark:text-white">{product.name}</div>
                                                            {product.description && (
                                                                <div className="text-sm text-gray-500 dark:text-gray-400 truncate max-w-xs">{product.description}</div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{product.product_code}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">{product.category?.name || '—'}</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    {product.price_currency} {Number(product.price_amount).toFixed(2)}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                                    <span className="font-medium">{product.current_stock}</span>
                                                    <span className="text-gray-500 dark:text-gray-400"> / {product.minimum_stock}</span>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">{getStatusBadge(product)}</td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex space-x-2">
                                                        <Button variant="outline" size="sm" onClick={() => handleView(product)} title="Voir">
                                                            <Eye className="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="outline" size="sm" onClick={() => handleEdit(product)} title="Modifier">
                                                            <Edit className="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="destructive" size="sm" onClick={() => handleDelete(product)}>
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

            <Modal show={detailOpen} onClose={() => { setDetailOpen(false); setDetailProduct(null); }} maxWidth="xl">
                {detailProduct && (
                    <div className="p-6">
                        <div className="flex justify-between items-start mb-6">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Détail du produit</h3>
                            <button type="button" onClick={() => setDetailOpen(false)} className="rounded-md text-gray-400 hover:text-gray-600">
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <div className="flex gap-6">
                            <div className="flex-shrink-0 h-32 w-32 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                                <Package className="h-16 w-16 text-gray-400" />
                            </div>
                            <div className="flex-1 space-y-3 text-sm">
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Nom</span>
                                    <p className="text-gray-900 dark:text-white font-medium">{detailProduct.name}</p>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Code</span>
                                    <p className="text-gray-900 dark:text-white">{detailProduct.product_code}</p>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Catégorie</span>
                                    <p className="text-gray-900 dark:text-white">{detailProduct.category?.name || '—'}</p>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Prix</span>
                                    <p className="text-gray-900 dark:text-white">
                                        {detailProduct.price_currency} {Number(detailProduct.price_amount).toFixed(2)}
                                    </p>
                                </div>
                                <div>
                                    <span className="font-medium text-gray-500 dark:text-gray-400">Stock</span>
                                    <p className="text-gray-900 dark:text-white">
                                        {detailProduct.current_stock} / {detailProduct.minimum_stock} min
                                    </p>
                                </div>
                                {detailProduct.description && (
                                    <div>
                                        <span className="font-medium text-gray-500 dark:text-gray-400">Description</span>
                                        <p className="text-gray-900 dark:text-white">{detailProduct.description}</p>
                                    </div>
                                )}
                                <div className="pt-4">
                                    <Button variant="outline" size="sm" onClick={() => { setDetailOpen(false); handleEdit(detailProduct); }}>
                                        <Edit className="h-4 w-4 mr-2" /> Modifier
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </Modal>

            <HardwareProductDrawer
                isOpen={drawerOpen}
                onClose={() => { setDrawerOpen(false); setEditingProduct(null); }}
                product={editingProduct}
                categories={categories}
            />
        </AppLayout>
    );
}
