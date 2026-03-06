import React from 'react';
import { usePage } from '@inertiajs/react';
import { Badge } from '@/Components/ui/badge';
import MacWindowModal from '@/Components/Commerce/MacWindowModal';

export default function ProductDetailsModal({ isOpen, onClose, product }) {
    const { shop } = usePage().props;
    if (!product) return null;

    return (
        <MacWindowModal
            isOpen={isOpen}
            onClose={onClose}
            title={product.name}
            subtitle={`SKU ${product.sku} · ${shop?.name || 'Commerce'}`}
            size="md"
        >
            <div className="p-4 sm:p-6 space-y-4">
                <div className="flex items-start gap-4">
                    <div className="h-16 w-16 rounded-xl overflow-hidden border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-center">
                        {product.image_url ? (
                            <img
                                src={product.image_url}
                                alt={product.name}
                                className="h-full w-full object-cover"
                            />
                        ) : (
                            <span className="text-xs text-gray-400">Aucune image</span>
                        )}
                    </div>
                    <div className="min-w-0 flex-1 space-y-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant={product.is_active ? 'default' : 'secondary'}>
                                {product.is_active ? 'Actif' : 'Inactif'}
                            </Badge>
                            <span className="text-xs text-gray-500 dark:text-gray-400">
                                Catégorie: {product.category_name || '—'}
                            </span>
                        </div>
                        <div className="text-xs text-gray-500 dark:text-gray-400">
                            Dépôt / Shop: <span className="font-medium">{shop?.name || 'N/A'}</span>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div className="space-y-1">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400">
                            SKU
                        </div>
                        <div className="font-mono text-gray-900 dark:text-gray-100">
                            {product.sku || '—'}
                        </div>
                    </div>
                    <div className="space-y-1">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Code-barres
                        </div>
                        <div className="font-mono text-gray-900 dark:text-gray-100">
                            {product.barcode || '—'}
                        </div>
                    </div>
                    <div className="space-y-1">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Prix achat
                        </div>
                        <div className="text-gray-900 dark:text-gray-100">
                            {product.purchase_price} {product.purchase_price_currency}
                        </div>
                    </div>
                    <div className="space-y-1">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Prix vente
                        </div>
                        <div className="text-gray-900 dark:text-gray-100">
                            {product.sale_price_amount} {product.sale_price_currency}
                        </div>
                    </div>
                    <div className="space-y-1">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Stock actuel
                        </div>
                        <div className="text-gray-900 dark:text-gray-100">
                            {product.stock} (min. {product.minimum_stock})
                        </div>
                    </div>
                    <div className="space-y-1">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Prix gros / min.
                        </div>
                        <div className="text-gray-900 dark:text-gray-100">
                            {product.wholesale_price_amount != null
                                ? `${product.wholesale_price_amount} ${product.sale_price_currency}`
                                : '—'}
                        </div>
                    </div>
                </div>

                {product.description && (
                    <div className="space-y-1 text-sm">
                        <div className="text-xs font-medium text-gray-500 dark:text-gray-400">
                            Description
                        </div>
                        <p className="text-gray-800 dark:text-gray-200 whitespace-pre-line">
                            {product.description}
                        </p>
                    </div>
                )}
            </div>
        </MacWindowModal>
    );
}

