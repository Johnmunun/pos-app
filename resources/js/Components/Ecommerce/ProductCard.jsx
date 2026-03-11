import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { ShoppingCart, Eye, Image as ImageIcon, Percent } from 'lucide-react';
import { useState } from 'react';

export default function ProductCard({ product, viewMode = 'grid', onAddToCart, currency = 'USD', detailUrl }) {
    const [imageError, setImageError] = useState(false);
    const productUrl = detailUrl ?? route('ecommerce.storefront.product', product.id);

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
        <div className="w-full h-full bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-700 dark:to-slate-800 flex items-center justify-center">
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

    const hasPromotion = !!product.has_promotion || (product.discount_percent && Number(product.discount_percent) > 0);

    // List view
    if (viewMode === 'list') {
        return (
            <article className="bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden hover:shadow-lg hover:border-[var(--sf-primary)]/50 transition-all duration-300">
                <div className="flex flex-col sm:flex-row">
                    <Link
                        href={productUrl}
                        className="flex-shrink-0 block w-20 h-20 sm:w-40 sm:h-auto aspect-square sm:aspect-auto"
                    >
                        {product.image_url && !imageError ? (
                            <img
                                src={product.image_url}
                                alt={product.name}
                                className="w-full h-full object-cover"
                                onError={() => setImageError(true)}
                            />
                        ) : (
                            imagePlaceholder
                        )}
                    </Link>
                    <div className="flex-1 p-4 sm:p-5 flex flex-col sm:justify-between">
                        <div>
                            <Link href={productUrl}>
                                <h3 className="font-semibold text-slate-900 dark:text-white text-base sm:text-lg mb-2 line-clamp-2 hover:text-[var(--sf-primary)] transition-colors">
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
                                <div className="flex flex-col">
                                    <p className="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">
                                        {formatCurrency(product.price_amount)}
                                    </p>
                                    {hasPromotion && (
                                        <div className="mt-0.5 inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 px-2 py-0.5 text-[11px] font-semibold">
                                            <Percent className="h-3 w-3" />
                                            Promo
                                            {product.discount_percent ? ` -${Number(product.discount_percent)}%` : ''}
                                        </div>
                                    )}
                                </div>
                                {stockBadge}
                            </div>
                            <div className="flex gap-2">
                                <Link href={productUrl} className="flex-1 sm:flex-initial">
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
            <article className="group bg-white dark:bg-slate-800 rounded-2xl border border-slate-200/80 dark:border-slate-700/80 overflow-hidden hover:shadow-xl hover:shadow-slate-200/50 dark:hover:shadow-slate-900/50 hover:border-[var(--sf-primary)]/50 transition-all duration-300 flex flex-col h-full">
            <Link
                href={productUrl}
                className="block relative overflow-hidden aspect-[4/5] sm:aspect-[4/3]"
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
                <Link href={productUrl}>
                    <h3 className="font-semibold text-slate-900 dark:text-white text-base sm:text-lg mb-2 line-clamp-2 group-hover:text-[var(--sf-primary)] transition-colors">
                        {product.name}
                    </h3>
                </Link>
                {product.description && (
                    <p
                        className="text-sm text-slate-500 dark:text-slate-400 mb-4 line-clamp-2 flex-1"
                        dangerouslySetInnerHTML={{ __html: product.description }}
                    />
                )}

                <div className="mt-auto space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="flex flex-col">
                            <p className="text-xl font-bold text-slate-900 dark:text-white">
                                {formatCurrency(product.price_amount)}
                            </p>
                            {hasPromotion && (
                                <div className="mt-0.5 inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 px-2 py-0.5 text-[11px] font-semibold">
                                    <Percent className="h-3 w-3" />
                                    Promo
                                    {product.discount_percent ? ` -${Number(product.discount_percent)}%` : ''}
                                </div>
                            )}
                        </div>
                    </div>

                    <div className="flex gap-2">
                        <Link href={productUrl} className="flex-1">
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
