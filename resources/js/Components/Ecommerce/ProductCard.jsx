import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { ShoppingCart, Eye, Image as ImageIcon } from 'lucide-react';
import { useState } from 'react';

export default function ProductCard({ product, viewMode = 'grid', onAddToCart, currency = 'USD' }) {
    const [imageError, setImageError] = useState(false);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        }).format(amount);
    };

    const handleAddToCart = (e) => {
        e.preventDefault();
        e.stopPropagation();
        onAddToCart(product, 1);
    };

    const imagePlaceholder = (
        <div className="w-full bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center">
            <ImageIcon className="h-12 w-12 sm:h-16 sm:w-16 text-slate-400" />
        </div>
    );

    const stockBadge = product.stock > 0 ? (
        <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
            En stock
        </span>
    ) : (
        <span className="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
            Rupture
        </span>
    );

    // List view
    if (viewMode === 'list') {
        return (
            <article className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden hover:shadow-lg hover:border-amber-200 dark:hover:border-amber-800/50 transition-all duration-300">
                <div className="flex flex-col sm:flex-row">
                    <Link
                        href={route('ecommerce.catalog.show', product.id)}
                        className="flex-shrink-0 block sm:w-40 sm:min-h-[140px] aspect-square sm:aspect-auto"
                    >
                        {product.image_url && !imageError ? (
                            <img
                                src={product.image_url}
                                alt={product.name}
                                className="w-full h-full min-h-[140px] object-cover"
                                onError={() => setImageError(true)}
                            />
                        ) : (
                            imagePlaceholder
                        )}
                    </Link>
                    <div className="flex-1 p-4 sm:p-5 flex flex-col sm:justify-between">
                        <div>
                            <Link href={route('ecommerce.catalog.show', product.id)}>
                                <h3 className="font-semibold text-slate-900 dark:text-white text-base sm:text-lg mb-2 line-clamp-2 hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                                    {product.name}
                                </h3>
                            </Link>
                            {product.description && (
                                <p className="text-sm text-slate-500 dark:text-slate-400 mb-3 line-clamp-2">
                                    {product.description}
                                </p>
                            )}
                        </div>
                        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div className="flex items-center gap-3">
                                <p className="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                                    {formatCurrency(product.price_amount)}
                                </p>
                                {stockBadge}
                            </div>
                            <div className="flex gap-2">
                                <Link href={route('ecommerce.catalog.show', product.id)} className="flex-1 sm:flex-initial">
                                    <Button variant="outline" size="sm" className="w-full sm:w-auto gap-2 rounded-xl h-11">
                                        <Eye className="h-4 w-4" />
                                        Voir
                                    </Button>
                                </Link>
                                <Button
                                    onClick={handleAddToCart}
                                    disabled={product.stock === 0}
                                    size="sm"
                                    className="flex-1 sm:flex-initial gap-2 rounded-xl h-11 min-w-[44px]"
                                >
                                    <ShoppingCart className="h-4 w-4" />
                                    Panier
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </article>
        );
    }

    // Grid view (default)
    return (
        <article className="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-slate-900/50 hover:border-amber-200 dark:hover:border-amber-800/50 transition-all duration-300 flex flex-col h-full">
            <Link
                href={route('ecommerce.catalog.show', product.id)}
                className="block relative overflow-hidden aspect-square sm:aspect-[4/3]"
            >
                {product.image_url && !imageError ? (
                    <img
                        src={product.image_url}
                        alt={product.name}
                        className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                        onError={() => setImageError(true)}
                    />
                ) : (
                    imagePlaceholder
                )}
                <div className="absolute top-3 right-3">
                    {stockBadge}
                </div>
            </Link>

            <div className="p-4 sm:p-5 flex flex-col flex-1">
                <Link href={route('ecommerce.catalog.show', product.id)}>
                    <h3 className="font-semibold text-slate-900 dark:text-white text-base sm:text-lg mb-2 line-clamp-2 group-hover:text-amber-600 dark:hover:text-amber-400 transition-colors">
                        {product.name}
                    </h3>
                </Link>
                {product.description && (
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2 flex-1">
                        {product.description}
                    </p>
                )}

                <div className="mt-auto space-y-4">
                    <p className="text-xl font-bold text-slate-900 dark:text-white">
                        {formatCurrency(product.price_amount)}
                    </p>

                    <div className="flex gap-2">
                        <Link href={route('ecommerce.catalog.show', product.id)} className="flex-1">
                            <Button
                                variant="outline"
                                size="sm"
                                className="w-full gap-2 rounded-xl h-11 min-h-[44px] sm:min-h-0"
                            >
                                <Eye className="h-4 w-4 shrink-0" />
                                Voir
                            </Button>
                        </Link>
                        <Button
                            onClick={handleAddToCart}
                            disabled={product.stock === 0}
                            size="sm"
                            className="flex-1 gap-2 rounded-xl h-11 min-h-[44px] sm:min-h-0"
                        >
                            <ShoppingCart className="h-4 w-4 shrink-0" />
                            Panier
                        </Button>
                    </div>
                </div>
            </div>
        </article>
    );
}
