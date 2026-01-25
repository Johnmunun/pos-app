import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Drawer from '@/Components/Drawer';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { Plus, Edit, Trash2, Package, AlertTriangle, Image as ImageIcon, Calendar, Hash, X } from 'lucide-react';
import Swal from 'sweetalert2';

export default function Products({ products, currencies }) {
    const [batchDrawerOpen, setBatchDrawerOpen] = useState(false);
    const [selectedProduct, setSelectedProduct] = useState(null);
    const [editingBatch, setEditingBatch] = useState(null);
    const [showBatchForm, setShowBatchForm] = useState(false);

    const { data: batchData, setData: setBatchData, post: postBatch, put: putBatch, processing: batchProcessing, errors: batchErrors, reset: resetBatch } = useForm({
        batch_number: '',
        manufacturing_date: '',
        expiration_date: '',
        quantity: '',
        purchase_price: '',
    });

    const openBatchDrawer = (product) => {
        setSelectedProduct(product);
        setBatchDrawerOpen(true);
        setShowBatchForm(false);
        setEditingBatch(null);
        resetBatch();
    };

    const closeBatchDrawer = () => {
        setBatchDrawerOpen(false);
        setSelectedProduct(null);
        setShowBatchForm(false);
        setEditingBatch(null);
        resetBatch();
    };

    const openBatchForm = (batch = null) => {
        if (batch) {
            setEditingBatch(batch);
            setBatchData({
                batch_number: batch.batch_number || '',
                manufacturing_date: batch.manufacturing_date || '',
                expiration_date: batch.expiration_date || '',
                quantity: batch.quantity || '',
                purchase_price: batch.purchase_price || '',
            });
        } else {
            setEditingBatch(null);
            resetBatch();
        }
        setShowBatchForm(true);
    };

    const handleBatchSubmit = (e) => {
        e.preventDefault();
        
        if (editingBatch) {
            putBatch(route('pharmacy.products.batches.update', [selectedProduct.id, editingBatch.id]), {
                preserveScroll: true,
                onSuccess: () => {
                    setShowBatchForm(false);
                    setEditingBatch(null);
                    resetBatch();
                },
            });
        } else {
            postBatch(route('pharmacy.products.batches.store', selectedProduct.id), {
                preserveScroll: true,
                onSuccess: () => {
                    setShowBatchForm(false);
                    resetBatch();
                },
            });
        }
    };

    const handleDeleteBatch = (batchId) => {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: 'Vous ne pourrez pas revenir en arrière !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Oui, supprimer !',
            cancelButtonText: 'Annuler',
            customClass: {
                popup: 'dark:bg-gray-800 dark:text-gray-100 dark:border dark:border-gray-700',
                confirmButton: 'dark:bg-red-600 dark:hover:bg-red-700',
                cancelButton: 'dark:bg-gray-600 dark:hover:bg-gray-700',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('pharmacy.products.batches.destroy', [selectedProduct.id, batchId]), {
                    preserveScroll: true,
                    onSuccess: () => {
                        Swal.fire('Supprimé !', 'Le lot a été supprimé.', 'success');
                    },
                });
            }
        });
    };

    const handleDelete = (productId) => {
        if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
            router.delete(route('pharmacy.products.destroy', productId));
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-row justify-between items-center gap-4">
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Produits
                    </h2>
                    <Link
                        href={route('pharmacy.products.create')}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors shadow-sm hover:shadow-md whitespace-nowrap"
                    >
                        <Plus className="h-4 w-4 sm:h-5 sm:w-5" />
                        <span className="hidden sm:inline">Créer un nouveau produit</span>
                        <span className="sm:hidden">Ajouter</span>
                    </Link>
                </div>
            }
        >
            <Head title="Produits - Pharmacy" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        {products.map((product) => (
                            <div
                                key={product.id}
                                className="bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border border-gray-200 dark:border-gray-700"
                            >
                                <div className="relative h-48 bg-gray-100 dark:bg-gray-700 flex items-center justify-center overflow-hidden">
                                    {product.image_url && product.image_url !== '/images/default-product.png' ? (
                                        <img
                                            src={product.image_url}
                                            alt={product.name}
                                            className="w-full h-full object-cover"
                                            onError={(e) => {
                                                e.target.style.display = 'none';
                                                const fallback = e.target.parentElement.querySelector('.image-fallback');
                                                if (fallback) fallback.style.display = 'flex';
                                            }}
                                        />
                                    ) : null}
                                    <div 
                                        className={`image-fallback w-full h-full ${product.image_url && product.image_url !== '/images/default-product.png' ? 'hidden' : 'flex'} items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800`}
                                    >
                                        <ImageIcon className="w-16 h-16 text-gray-400 dark:text-gray-500" />
                                    </div>
                                    {product.is_low_stock && (
                                        <div className="absolute top-2 right-2 bg-red-500 dark:bg-red-600 text-white px-2 py-1 rounded-md text-xs flex items-center gap-1 shadow-md">
                                            <AlertTriangle className="h-3 w-3" />
                                            Stock bas
                                        </div>
                                    )}
                                </div>
                                <div className="p-5">
                                    <h3 className="font-semibold text-gray-900 dark:text-white truncate text-lg mb-1">
                                        {product.name}
                                    </h3>
                                    <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                        SKU: {product.sku || 'N/A'}
                                    </p>
                                    <div className="flex items-baseline justify-between mb-3">
                                        <p className="text-xl font-bold text-amber-600 dark:text-amber-400">
                                            {product.currency_symbol} {product.selling_price}
                                        </p>
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            Stock: <span className="font-semibold">{product.total_stock}</span>
                                        </p>
                                    </div>
                                    <div className="flex gap-2 mt-4">
                                        <Link
                                            href={route('pharmacy.products.edit', product.id)}
                                            className="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors text-sm font-medium"
                                        >
                                            <Edit className="h-4 w-4" />
                                            Modifier
                                        </Link>
                                        <button
                                            onClick={() => openBatchDrawer(product)}
                                            className="flex-1 inline-flex items-center justify-center gap-1 px-3 py-2 bg-amber-100 dark:bg-amber-900/30 hover:bg-amber-200 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-400 rounded-lg transition-colors text-sm font-medium"
                                        >
                                            <Package className="h-4 w-4" />
                                            Lots
                                        </button>
                                        <button
                                            onClick={() => handleDelete(product.id)}
                                            className="px-3 py-2 bg-red-100 dark:bg-red-900/30 hover:bg-red-200 dark:hover:bg-red-900/50 text-red-700 dark:text-red-400 rounded-lg transition-colors"
                                            title="Supprimer"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {products.length === 0 && (
                        <div className="text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700">
                            <Package className="mx-auto h-16 w-16 text-gray-400 dark:text-gray-500 mb-4" />
                            <p className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                                Aucun produit trouvé
                            </p>
                            <p className="text-gray-600 dark:text-gray-400 mb-6">
                                Créez votre premier produit pour commencer.
                            </p>
                            <Link
                                href={route('pharmacy.products.create')}
                                className="inline-flex items-center gap-2 px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors shadow-sm hover:shadow-md"
                            >
                                <Plus className="h-5 w-5" />
                                Créer un produit
                            </Link>
                        </div>
                    )}
                </div>
            </div>

            {/* Batch Drawer */}
            <Drawer
                isOpen={batchDrawerOpen}
                onClose={closeBatchDrawer}
                title={`Lots - ${selectedProduct?.name || ''}`}
                size="lg"
            >
                {selectedProduct && (
                    <div className="space-y-6">
                        {/* Header avec bouton ajouter */}
                        {!showBatchForm && (
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Gestion des lots pour ce produit
                                </p>
                                <button
                                    onClick={() => openBatchForm()}
                                    className="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors text-sm font-medium"
                                >
                                    <Plus className="h-4 w-4" />
                                    Ajouter un lot
                                </button>
                            </div>
                        )}

                        {/* Formulaire d'ajout/édition */}
                        {showBatchForm && (
                            <div className="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                                <div className="flex items-center justify-between mb-4">
                                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                        {editingBatch ? 'Modifier le lot' : 'Nouveau lot'}
                                    </h3>
                                    <button
                                        onClick={() => {
                                            setShowBatchForm(false);
                                            setEditingBatch(null);
                                            resetBatch();
                                        }}
                                        className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                    >
                                        <X className="h-5 w-5" />
                                    </button>
                                </div>

                                <form onSubmit={handleBatchSubmit} className="space-y-4">
                                    <div>
                                        <InputLabel htmlFor="batch_number" value="Numéro de lot *" />
                                        <TextInput
                                            id="batch_number"
                                            type="text"
                                            value={batchData.batch_number}
                                            onChange={(e) => setBatchData('batch_number', e.target.value)}
                                            className="mt-1 block w-full"
                                            required
                                        />
                                        <InputError message={batchErrors.batch_number} className="mt-2" />
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="manufacturing_date" value="Date de fabrication" />
                                            <TextInput
                                                id="manufacturing_date"
                                                type="date"
                                                value={batchData.manufacturing_date}
                                                onChange={(e) => setBatchData('manufacturing_date', e.target.value)}
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={batchErrors.manufacturing_date} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="expiration_date" value="Date d'expiration *" />
                                            <TextInput
                                                id="expiration_date"
                                                type="date"
                                                value={batchData.expiration_date}
                                                onChange={(e) => setBatchData('expiration_date', e.target.value)}
                                                className="mt-1 block w-full"
                                                required
                                            />
                                            <InputError message={batchErrors.expiration_date} className="mt-2" />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <InputLabel htmlFor="quantity" value="Quantité *" />
                                            <TextInput
                                                id="quantity"
                                                type="number"
                                                min="1"
                                                value={batchData.quantity}
                                                onChange={(e) => setBatchData('quantity', e.target.value)}
                                                className="mt-1 block w-full"
                                                required
                                            />
                                            <InputError message={batchErrors.quantity} className="mt-2" />
                                        </div>

                                        <div>
                                            <InputLabel htmlFor="purchase_price" value="Prix d'achat" />
                                            <TextInput
                                                id="purchase_price"
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                value={batchData.purchase_price}
                                                onChange={(e) => setBatchData('purchase_price', e.target.value)}
                                                className="mt-1 block w-full"
                                            />
                                            <InputError message={batchErrors.purchase_price} className="mt-2" />
                                        </div>
                                    </div>

                                    <div className="flex gap-3 pt-4">
                                        <PrimaryButton
                                            type="submit"
                                            disabled={batchProcessing}
                                            className="flex-1"
                                        >
                                            {batchProcessing ? 'Enregistrement...' : (editingBatch ? 'Modifier' : 'Ajouter')}
                                        </PrimaryButton>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setShowBatchForm(false);
                                                setEditingBatch(null);
                                                resetBatch();
                                            }}
                                            className="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 rounded-lg transition-colors"
                                        >
                                            Annuler
                                        </button>
                                    </div>
                                </form>
                            </div>
                        )}

                        {/* Liste des lots existants */}
                        {!showBatchForm && (
                            <div>
                                <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                                    Lots existants ({selectedProduct.batches?.length || 0})
                                </h3>
                                
                                {selectedProduct.batches && selectedProduct.batches.length > 0 ? (
                                    <div className="space-y-2">
                                        {selectedProduct.batches.map((batch) => (
                                            <div
                                                key={batch.id}
                                                className={`flex items-center justify-between p-4 rounded-lg border ${
                                                    batch.is_expired
                                                        ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800'
                                                        : batch.is_expiring_soon
                                                        ? 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800'
                                                        : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700'
                                                }`}
                                            >
                                                <div className="flex-1">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <Hash className="h-4 w-4 text-gray-400 dark:text-gray-500" />
                                                        <span className="font-medium text-gray-900 dark:text-white">
                                                            {batch.batch_number}
                                                        </span>
                                                        {batch.is_expired && (
                                                            <span className="px-2 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs rounded">
                                                                Expiré
                                                            </span>
                                                        )}
                                                        {batch.is_expiring_soon && !batch.is_expired && (
                                                            <span className="px-2 py-0.5 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-xs rounded">
                                                                Expire bientôt
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="grid grid-cols-2 gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <div className="flex items-center gap-1">
                                                            <Calendar className="h-3 w-3" />
                                                            <span>Exp: {batch.expiration_date || 'N/A'}</span>
                                                        </div>
                                                        <div>
                                                            Quantité: <span className="font-semibold">{batch.quantity}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div className="flex gap-2">
                                                    <button
                                                        onClick={() => openBatchForm(batch)}
                                                        className="p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition-colors"
                                                        title="Modifier"
                                                    >
                                                        <Edit className="h-4 w-4" />
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeleteBatch(batch.id)}
                                                        className="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors"
                                                        title="Supprimer"
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                                        <Package className="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-3" />
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            Aucun lot enregistré pour ce produit
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                )}
            </Drawer>
        </AppLayout>
    );
}


