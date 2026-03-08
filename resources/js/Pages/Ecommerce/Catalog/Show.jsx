import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { CartProvider, useCart } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import {
    ArrowLeft,
    ShoppingCart as ShoppingCartIcon,
    Plus,
    Minus,
    Package,
    Star,
    CheckCircle,
    Image as ImageIcon,
    Share2,
    Heart,
} from 'lucide-react';
import { toast } from 'react-hot-toast';

function ProductContent({ product }) {
    const { shop } = usePage().props;
    const { addToCart } = useCart();
    const currency = shop?.currency || 'USD';

    const [quantity, setQuantity] = useState(1);
    const [selectedImage, setSelectedImage] = useState(product.image_url || null);
    const [imageError, setImageError] = useState(false);

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const handleAddToCart = () => {
        if (product.stock < quantity) {
            toast.error('Stock insuffisant');
            return;
        }
        addToCart(product, quantity);
        toast.success('Produit ajouté au panier');
    };

    const increaseQuantity = () => {
        if (quantity < product.stock) {
            setQuantity(quantity + 1);
        } else {
            toast.error('Stock insuffisant');
        }
    };

    const decreaseQuantity = () => {
        if (quantity > 1) {
            setQuantity(quantity - 1);
        }
    };

    return (
        <>
            <Head title={product.name} />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Breadcrumb */}
                    <div className="mb-6">
                        <Link
                            href={route('ecommerce.catalog.index')}
                            className="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-2"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Retour au catalogue
                        </Link>
                    </div>

                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-8 p-6 lg:p-8">
                            {/* Images */}
                            <div className="space-y-4">
                                {/* Main Image */}
                                <div className="relative aspect-square bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden">
                                    {selectedImage && !imageError ? (
                                        <img
                                            src={selectedImage}
                                            alt={product.name}
                                            className="w-full h-full object-cover"
                                            onError={() => setImageError(true)}
                                        />
                                    ) : (
                                        <div className="w-full h-full flex items-center justify-center">
                                            <ImageIcon className="h-24 w-24 text-gray-400" />
                                        </div>
                                    )}
                                    {product.stock > 0 && (
                                        <Badge className="absolute top-4 right-4 bg-emerald-500 text-white">
                                            En stock
                                        </Badge>
                                    )}
                                    {product.stock === 0 && (
                                        <Badge className="absolute top-4 right-4 bg-red-500 text-white">
                                            Rupture de stock
                                        </Badge>
                                    )}
                                </div>

                                {/* Thumbnails (if multiple images) */}
                                {product.image_url && (
                                    <div className="flex gap-2">
                                        <button
                                            onClick={() => setSelectedImage(product.image_url)}
                                            className={`w-20 h-20 rounded-lg overflow-hidden border-2 ${
                                                selectedImage === product.image_url
                                                    ? 'border-blue-500'
                                                    : 'border-gray-200 dark:border-gray-700'
                                            }`}
                                        >
                                            <img
                                                src={product.image_url}
                                                alt={product.name}
                                                className="w-full h-full object-cover"
                                            />
                                        </button>
                                    </div>
                                )}
                            </div>

                            {/* Product Info */}
                            <div className="space-y-6">
                                <div>
                                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                                        {product.name}
                                    </h1>
                                    {product.sku && (
                                        <p className="text-sm text-gray-500 dark:text-gray-400">
                                            SKU: {product.sku}
                                        </p>
                                    )}
                                </div>

                                {/* Price */}
                                <div className="flex items-center gap-4">
                                    <div>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-1">Prix</p>
                                        <p className="text-4xl font-bold text-gray-900 dark:text-white">
                                            {formatCurrency(product.price_amount)}
                                        </p>
                                    </div>
                                </div>

                                {/* Stock Status */}
                                <div className="flex items-center gap-2">
                                    {product.stock > 0 ? (
                                        <>
                                            <CheckCircle className="h-5 w-5 text-emerald-500" />
                                            <span className="text-emerald-600 dark:text-emerald-400 font-medium">
                                                {product.stock} disponible(s)
                                            </span>
                                        </>
                                    ) : (
                                        <>
                                            <Package className="h-5 w-5 text-red-500" />
                                            <span className="text-red-600 dark:text-red-400 font-medium">
                                                Rupture de stock
                                            </span>
                                        </>
                                    )}
                                </div>

                                {/* Description */}
                                {product.description && (
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                            Description
                                        </h3>
                                        <p className="text-gray-600 dark:text-gray-400 leading-relaxed">
                                            {product.description}
                                        </p>
                                    </div>
                                )}

                                {/* Quantity and Add to Cart */}
                                <div className="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div className="flex items-center gap-4">
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Quantité:
                                        </label>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                onClick={decreaseQuantity}
                                                disabled={quantity <= 1}
                                            >
                                                <Minus className="h-4 w-4" />
                                            </Button>
                                            <Input
                                                type="number"
                                                min="1"
                                                max={product.stock}
                                                value={quantity}
                                                onChange={(e) => {
                                                    const val = parseInt(e.target.value) || 1;
                                                    if (val >= 1 && val <= product.stock) {
                                                        setQuantity(val);
                                                    }
                                                }}
                                                className="w-20 text-center"
                                            />
                                            <Button
                                                variant="outline"
                                                size="icon"
                                                onClick={increaseQuantity}
                                                disabled={quantity >= product.stock}
                                            >
                                                <Plus className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>

                                    <Button
                                        onClick={handleAddToCart}
                                        disabled={product.stock === 0}
                                        size="lg"
                                        className="w-full gap-2"
                                    >
                                        <ShoppingCartIcon className="h-5 w-5" />
                                        Ajouter au panier
                                    </Button>
                                </div>

                                {/* Additional Info */}
                                <div className="pt-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                                    <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                        <Package className="h-4 w-4" />
                                        <span>Stock disponible: {product.stock}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

export default function ProductShow({ product }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'USD';

    return (
        <CartProvider currency={currency}>
            <AppLayout
                header={
                    <div className="flex items-center justify-between w-full">
                        <div className="flex items-center gap-4">
                            <Link href={route('ecommerce.catalog.index')}>
                                <Button variant="ghost" size="sm">
                                    <ArrowLeft className="h-4 w-4 mr-2" />
                                    Retour
                                </Button>
                            </Link>
                        </div>
                        <div className="flex items-center gap-4">
                            <ShoppingCart />
                        </div>
                    </div>
                }
            >
                <ProductContent product={product} />
            </AppLayout>
        </CartProvider>
    );
}
