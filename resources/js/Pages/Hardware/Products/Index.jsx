import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import Modal from '@/Components/Modal';
import HardwareProductDrawer from '@/Components/Hardware/ProductDrawer';
import { Search, Plus, Edit, Trash2, Package, AlertTriangle, Eye, X, Copy, CheckSquare, Square } from 'lucide-react';
import { toast } from 'react-hot-toast';

/**
 * Page liste des produits — Module Quincaillerie.
 * Vue dédiée, aucun import Pharmacy.
 */
export default function HardwareProductsIndex({ products = [], categories = [], filters = {}, canImport = false, depots = [] }) {
    const { props } = usePage();
    const depotsList = props.depots || depots || [];
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters?.category_id || '');
    const [selectedStatus, setSelectedStatus] = useState(filters?.status || '');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingProduct, setEditingProduct] = useState(null);
    const [detailOpen, setDetailOpen] = useState(false);
    const [detailProduct, setDetailProduct] = useState(null);
    const [duplicateModalOpen, setDuplicateModalOpen] = useState(false);
    const [productToDuplicate, setProductToDuplicate] = useState(null);
    const [selectedDepotId, setSelectedDepotId] = useState('');
    const [selectedProducts, setSelectedProducts] = useState([]);
    const [isSelectingMultiple, setIsSelectingMultiple] = useState(false);

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

    const toggleProductSelection = (product) => {
        setSelectedProducts(prev => {
            const isSelected = prev.some(p => p.id === product.id);
            if (isSelected) {
                return prev.filter(p => p.id !== product.id);
            } else {
                return [...prev, product];
            }
        });
    };

    const toggleSelectAll = () => {
        if (selectedProducts.length === products.length) {
            setSelectedProducts([]);
        } else {
            setSelectedProducts([...products]);
        }
    };

    const handleDuplicate = (product) => {
        if (product) {
            setProductToDuplicate([product]);
        } else {
            // Duplication multiple depuis la sélection
            if (selectedProducts.length === 0) {
                toast.error('Veuillez sélectionner au moins un produit');
                return;
            }
            setProductToDuplicate(selectedProducts);
        }
        setSelectedDepotId('');
        setDuplicateModalOpen(true);
    };

    const handleConfirmDuplicate = async () => {
        if (!selectedDepotId) {
            toast.error('Veuillez sélectionner un dépôt de destination');
            return;
        }
        
        if (!productToDuplicate || productToDuplicate.length === 0) {
            toast.error('Aucun produit sélectionné');
            return;
        }

        // Si un seul produit, utiliser l'ancienne méthode
        if (productToDuplicate.length === 1) {
            router.post(route('hardware.products.duplicate-to-depot', productToDuplicate[0].id), {
                target_depot_id: selectedDepotId,
            }, {
                preserveScroll: false,
                onSuccess: () => {
                    toast.success('Produit dupliqué avec succès');
                    setDuplicateModalOpen(false);
                    setProductToDuplicate(null);
                    setSelectedDepotId('');
                    setSelectedProducts([]);
                    setIsSelectingMultiple(false);
                },
                onError: (errors) => {
                    const errorMessage = errors?.message || (typeof errors === 'string' ? errors : 'Erreur lors de la duplication');
                    toast.error(errorMessage);
                },
            });
        } else {
            // Duplication multiple - traiter séquentiellement pour éviter les problèmes
            let successCount = 0;
            let errorCount = 0;
            
            for (const product of productToDuplicate) {
                try {
                    await new Promise((resolve, reject) => {
                        router.post(route('hardware.products.duplicate-to-depot', product.id), {
                            target_depot_id: selectedDepotId,
                        }, {
                            preserveScroll: false,
                            only: [],
                            onSuccess: () => {
                                successCount++;
                                resolve();
                            },
                            onError: (errors) => {
                                errorCount++;
                                console.error('Error duplicating product:', product.id, errors);
                                resolve(); // Continue même en cas d'erreur
                            },
                        });
                    });
                } catch (error) {
                    errorCount++;
                    console.error('Error duplicating product:', product.id, error);
                }
            }

            if (successCount > 0) {
                toast.success(`${successCount} produit${successCount > 1 ? 's' : ''} dupliqué${successCount > 1 ? 's' : ''} avec succès`);
            }
            if (errorCount > 0) {
                toast.error(`${errorCount} produit${errorCount > 1 ? 's' : ''} n'a${errorCount > 1 ? 'ont' : ''} pas pu être dupliqué${errorCount > 1 ? 's' : ''}`);
            }

            setDuplicateModalOpen(false);
            setProductToDuplicate(null);
            setSelectedDepotId('');
            setSelectedProducts([]);
            setIsSelectingMultiple(false);
            router.reload({ only: ['products'] });
        }
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
                    <div className="flex gap-2">
                        {isSelectingMultiple && (
                            <>
                                <Button 
                                    variant="outline" 
                                    onClick={() => {
                                        if (selectedProducts.length > 0) {
                                            handleDuplicate(null);
                                        } else {
                                            toast.error('Veuillez sélectionner au moins un produit');
                                        }
                                    }}
                                    disabled={selectedProducts.length === 0}
                                    className="border-amber-500 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20 dark:border-amber-600 dark:text-amber-400"
                                >
                                    <Copy className="h-4 w-4 mr-2" />
                                    Dupliquer ({selectedProducts.length})
                                </Button>
                                <Button 
                                    variant="outline" 
                                    onClick={() => {
                                        setIsSelectingMultiple(false);
                                        setSelectedProducts([]);
                                    }}
                                    className="border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300"
                                >
                                    Annuler
                                </Button>
                            </>
                        )}
                        {!isSelectingMultiple && (
                            <>
                                <Button 
                                    variant="outline" 
                                    onClick={() => setIsSelectingMultiple(true)}
                                    className="border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    <CheckSquare className="h-4 w-4 mr-2" />
                                    Sélectionner
                                </Button>
                                <Button onClick={handleCreate} className="bg-amber-500 hover:bg-amber-600 text-white">
                                    <Plus className="h-4 w-4 mr-2" />
                                    <span className="hidden sm:inline">Ajouter un produit</span>
                                    <span className="sm:hidden">Ajouter</span>
                                </Button>
                            </>
                        )}
                    </div>
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
                                            {isSelectingMultiple && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                    <button
                                                        onClick={toggleSelectAll}
                                                        className="flex items-center hover:opacity-80 transition-opacity"
                                                        title={selectedProducts.length === products.length ? 'Désélectionner tout' : 'Sélectionner tout'}
                                                    >
                                                        {selectedProducts.length === products.length ? (
                                                            <CheckSquare className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                                        ) : (
                                                            <Square className="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                                        )}
                                                    </button>
                                                </th>
                                            )}
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Produit</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Code</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Catégorie</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Prix</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Statut</th>
                                            {!isSelectingMultiple && (
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                            )}
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                        {products.map((product) => {
                                            const isSelected = selectedProducts.some(p => p.id === product.id);
                                            return (
                                            <tr key={product.id} className={`hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors ${isSelected ? 'bg-amber-50 dark:bg-amber-900/20' : ''}`}>
                                                {isSelectingMultiple && (
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <button
                                                            onClick={() => toggleProductSelection(product)}
                                                            className="flex items-center"
                                                        >
                                                            {isSelected ? (
                                                                <CheckSquare className="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                                            ) : (
                                                                <Square className="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                                            )}
                                                        </button>
                                                    </td>
                                                )}
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="flex items-center">
                                                        <div className="flex-shrink-0 h-10 w-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden relative">
                                                            {product.image_url ? (
                                                                <>
                                                                    <img 
                                                                        src={product.image_url} 
                                                                        alt={product.name}
                                                                        className="h-full w-full object-cover"
                                                                        onError={(e) => {
                                                                            e.target.style.display = 'none';
                                                                        }}
                                                                    />
                                                                    <Package className="h-6 w-6 text-gray-400 absolute inset-0 m-auto" style={{ display: 'none' }} />
                                                                </>
                                                            ) : (
                                                                <Package className="h-6 w-6 text-gray-400" />
                                                            )}
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
                                                {!isSelectingMultiple && (
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex space-x-2">
                                                            <Button variant="outline" size="sm" onClick={() => handleView(product)} title="Voir">
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                            <Button variant="outline" size="sm" onClick={() => handleEdit(product)} title="Modifier">
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                            <Button variant="outline" size="sm" onClick={() => handleDuplicate(product)} title="Dupliquer vers un autre dépôt">
                                                                <Copy className="h-4 w-4" />
                                                            </Button>
                                                            <Button variant="destructive" size="sm" onClick={() => handleDelete(product)}>
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Modal de duplication */}
            <Modal show={duplicateModalOpen} onClose={() => { setDuplicateModalOpen(false); setProductToDuplicate(null); setSelectedDepotId(''); }} maxWidth="md" className="bg-white dark:bg-gray-800">
                <div className="p-6 bg-white dark:bg-gray-800">
                    <div className="flex justify-between items-start mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <div>
                            <h3 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <Copy className="h-5 w-5 text-amber-500" />
                                {productToDuplicate && productToDuplicate.length > 1 ? 'Dupliquer les produits' : 'Dupliquer le produit'}
                            </h3>
                            {productToDuplicate && productToDuplicate.length > 1 && (
                                <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                    {productToDuplicate.length} produit{productToDuplicate.length > 1 ? 's' : ''} sélectionné{productToDuplicate.length > 1 ? 's' : ''}
                                </p>
                            )}
                        </div>
                        <button 
                            type="button" 
                            onClick={() => { setDuplicateModalOpen(false); setProductToDuplicate(null); setSelectedDepotId(''); }} 
                            className="rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>
                    {productToDuplicate && productToDuplicate.length > 0 && (
                        <div className="space-y-5">
                            <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                                <p className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Produit{productToDuplicate.length > 1 ? 's' : ''} à dupliquer :</p>
                                <div className="space-y-2 max-h-48 overflow-y-auto">
                                    {productToDuplicate.map((product) => (
                                        <div key={product.id} className="flex items-center justify-between p-2 bg-white dark:bg-gray-800 rounded border border-gray-200 dark:border-gray-600">
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-white text-sm">{product.name}</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">Code : {product.product_code}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div>
                                <label htmlFor="target_depot" className="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                    Dépôt de destination <span className="text-red-500">*</span>
                                </label>
                                <select
                                    id="target_depot"
                                    value={selectedDepotId}
                                    onChange={(e) => setSelectedDepotId(e.target.value)}
                                    className="w-full h-10 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white px-3 shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                                >
                                    <option value="" className="bg-white dark:bg-gray-700">Sélectionner un dépôt</option>
                                    {depotsList
                                        .filter(depot => depot.id !== (props?.currentDepot?.id))
                                        .map((depot) => (
                                            <option key={depot.id} value={depot.id} className="bg-white dark:bg-gray-700">
                                                {depot.name} {depot.code ? `(${depot.code})` : ''}
                                            </option>
                                        ))}
                                </select>
                            </div>
                            <div className="bg-amber-50 dark:bg-amber-900/30 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                                <p className="text-sm text-amber-800 dark:text-amber-200">
                                    <strong className="font-semibold">Note :</strong> Le{productToDuplicate.length > 1 ? 's' : ''} produit{productToDuplicate.length > 1 ? 's' : ''} sera{productToDuplicate.length > 1 ? 'ont' : ''} dupliqué{productToDuplicate.length > 1 ? 's' : ''} avec tous {productToDuplicate.length > 1 ? 'leurs' : 'ses'} prix, mais le stock sera initialisé à 0.
                                </p>
                            </div>
                            <div className="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <Button 
                                    variant="outline" 
                                    onClick={() => { setDuplicateModalOpen(false); setProductToDuplicate(null); setSelectedDepotId(''); }}
                                    className="border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
                                >
                                    Annuler
                                </Button>
                                <Button 
                                    onClick={handleConfirmDuplicate}
                                    disabled={!selectedDepotId}
                                    className="bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 text-white disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    <Copy className="h-4 w-4 mr-2" />
                                    Dupliquer {productToDuplicate.length > 1 ? `(${productToDuplicate.length})` : ''}
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </Modal>

            <Modal show={detailOpen} onClose={() => { setDetailOpen(false); setDetailProduct(null); }} maxWidth="xl" className="bg-white dark:bg-gray-800">
                {detailProduct && (
                    <div className="p-6 bg-white dark:bg-gray-800">
                        <div className="flex justify-between items-start mb-6 border-b border-gray-200 dark:border-gray-700 pb-4">
                            <h3 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                <Package className="h-5 w-5 text-amber-500" />
                                Détail du produit
                            </h3>
                            <button 
                                type="button" 
                                onClick={() => setDetailOpen(false)} 
                                className="rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>
                        <div className="flex gap-6">
                            <div className="flex-shrink-0 h-32 w-32 rounded-lg bg-gray-100 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-600 flex items-center justify-center overflow-hidden">
                                {detailProduct.image_url ? (
                                    <img 
                                        src={detailProduct.image_url} 
                                        alt={detailProduct.name}
                                        className="h-full w-full object-cover"
                                        onError={(e) => {
                                            e.target.style.display = 'none';
                                            e.target.nextSibling.style.display = 'flex';
                                        }}
                                    />
                                ) : null}
                                <Package className="h-16 w-16 text-gray-400 dark:text-gray-500" style={{ display: detailProduct.image_url ? 'none' : 'flex' }} />
                            </div>
                            <div className="flex-1 space-y-4">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                        <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Nom</span>
                                        <p className="text-gray-900 dark:text-white font-semibold mt-1">{detailProduct.name}</p>
                                    </div>
                                    <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                        <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Code</span>
                                        <p className="text-gray-900 dark:text-white font-medium mt-1">{detailProduct.product_code}</p>
                                    </div>
                                    <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                        <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Catégorie</span>
                                        <p className="text-gray-900 dark:text-white font-medium mt-1">{detailProduct.category?.name || '—'}</p>
                                    </div>
                                    <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                        <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Prix</span>
                                        <p className="text-gray-900 dark:text-white font-semibold mt-1">
                                            {detailProduct.price_currency} {Number(detailProduct.price_amount).toFixed(2)}
                                        </p>
                                    </div>
                                    <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                        <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stock actuel</span>
                                        <p className="text-gray-900 dark:text-white font-semibold mt-1">{detailProduct.current_stock}</p>
                                    </div>
                                    <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                        <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stock minimum</span>
                                        <p className="text-gray-900 dark:text-white font-medium mt-1">{detailProduct.minimum_stock}</p>
                                    </div>
                                </div>
                                {detailProduct.description && (
                                    <div className="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-3 border border-gray-200 dark:border-gray-600">
                                        <span className="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide">Description</span>
                                        <p className="text-gray-900 dark:text-white mt-1">{detailProduct.description}</p>
                                    </div>
                                )}
                                <div className="pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        onClick={() => { setDetailOpen(false); handleEdit(detailProduct); }}
                                        className="border-amber-500 text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-900/20"
                                    >
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
