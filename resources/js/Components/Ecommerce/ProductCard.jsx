import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
    ShoppingCart,
    Eye,
    Image as ImageIcon,
    Star,
} from 'lucide-react';
import { useState } from 'react';

export default function ProductCard({ product, viewMode = 'grid', onAddToCart, currency = 'USD' }) {
    const [imageError, setImageError] = useState(false);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const handleAddToCart = (e) => {
        e.preventDefault();
        e.stopPropagation();
        onAddToCart(product, 1);
    };

    if (viewMode === 'list') {
        return (
            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-4 hover:shadow-lg transition-all duration-300">
                <div className="flex gap-4">
                    {/* Image */}
                    <Link href={route('ecommerce.catalog.show', product.id)} className="flex-shrink-0">
                        {product.image_url && !imageError ? (
                            <img
                                src={product.image_url}
                                alt={product.name}
                                className="w-32 h-32 object-cover rounded-lg"
                                onError={() => setImageError(true)}
                            />
                        ) : (
                            <div className="w-32 h-32 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <ImageIcon className="h-8 w-8 text-gray-400" />
                            </div>
                        )}
                    </Link>

                    {/* Content */}
                    <div className="flex-1 min-w-0">
                        <Link href={route('ecommerce.catalog.show', product.id)}>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                                {product.name}
                            </h3>
                        </Link>
                        {product.description && (
                            <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                                {product.description}
                            </p>
                        )}
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                    {formatCurrency(product.price_amount)}
                                </p>
                                {product.sku && (
                                    <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        SKU: {product.sku}
                                    </p>
                                )}
                            </div>
                            <div className="flex items-center gap-3">
                                <Badge
                                    className={
                                        product.stock > 0
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                            : 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300'
                                    }
                                >
                                    {product.stock > 0 ? `En stock (${product.stock})` : 'Rupture'}
                                </Badge>
                                <Button
                                    onClick={handleAddToCart}
                                    disabled={product.stock === 0}
                                    className="gap-2"
                                >
                                    <ShoppingCart className="h-4 w-4" />
                                    Ajouter
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        );
    }

    // Grid view (default)
    return (
        <div className="group bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1">
            {/* Image Container */}
            <Link href={route('ecommerce.catalog.show', product.id)} className="block relative overflow-hidden">
                {product.image_url && !imageError ? (
                    <img
                        src={product.image_url}
                        alt={product.name}
                        className="w-full h-64 object-cover group-hover:scale-110 transition-transform duration-500"
                        onError={() => setImageError(true)}
                    />
                ) : (
                    <div className="w-full h-64 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 flex items-center justify-center">
                        <ImageIcon className="h-16 w-16 text-gray-400" />
                    </div>
                )}
                {/* Stock Badge Overlay */}
                {product.stock > 0 && (
                    <Badge className="absolute top-2 right-2 bg-emerald-500 text-white">
                        En stock
                    </Badge>
                )}
                {product.stock === 0 && (
                    <Badge className="absolute top-2 right-2 bg-red-500 text-white">
                        Rupture
                    </Badge>
                )}
            </Link>

            {/* Content */}
            <div className="p-4">
                <Link href={route('ecommerce.catalog.show', product.id)}>
                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                        {product.name}
                    </h3>
                </Link>
                {product.description && (
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                        {product.description}
                    </p>
                )}

                {/* Price and Actions */}
                <div className="flex items-center justify-between mb-3">
                    <div>
                        <p className="text-xl font-bold text-gray-900 dark:text-white">
                            {formatCurrency(product.price_amount)}
                        </p>
                        {product.sku && (
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                SKU: {product.sku}
                            </p>
                        )}
                    </div>
                </div>

                {/* Actions */}
                <div className="flex gap-2">
                    <Link
                        href={route('ecommerce.catalog.show', product.id)}
                        className="flex-1"
                    >
                        <Button variant="outline" className="w-full gap-2">
                            <Eye className="h-4 w-4" />
                            Voir
                        </Button>
                    </Link>
                    <Button
                        onClick={handleAddToCart}
                        disabled={product.stock === 0}
                        className="flex-1 gap-2"
                    >
                        <ShoppingCart className="h-4 w-4" />
                        Panier
                    </Button>
                </div>
            </div>
        </div>
    );
}
