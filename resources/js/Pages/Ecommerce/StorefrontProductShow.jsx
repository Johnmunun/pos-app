import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Input } from '@/Components/ui/input';
import { CartProvider, useCart } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import StorefrontClientBootstrap from '@/Components/Ecommerce/StorefrontClientBootstrap';
import {
    ArrowLeft,
    ShoppingCart as ShoppingCartIcon,
    Plus,
    Minus,
    Package,
    Star,
    CheckCircle,
    Image as ImageIcon,
    Truck,
    ShieldCheck,
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';
import { formatCurrency as formatMoney } from '@/lib/currency';

function StorefrontProductHeader({ shop, cmsPages = [] }) {
    const links = useStorefrontLinks();
    const { shop: sharedShop } = usePage().props;
    const logoUrl = shop?.logo_url || sharedShop?.logo_url || null;

    return (
        <header className="sticky top-0 z-40 border-b border-slate-200/70 dark:border-slate-800 bg-white/75 dark:bg-slate-950/60 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-slate-950/50">
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.index()}
                        className="p-2 -ml-2 rounded-2xl text-slate-500 hover:text-amber-700 dark:hover:text-amber-400 hover:bg-amber-50/80 dark:hover:bg-amber-950/25 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500/30"
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Link>
                    <div className="flex items-center gap-2">
                        {logoUrl ? (
                            <span className="inline-flex justify-center h-9 w-9 rounded-2xl bg-white shadow-sm shadow-slate-900/10 ring-1 ring-slate-200 overflow-hidden">
                                <img src={logoUrl} alt={shop?.name || 'Logo'} className="w-full h-full object-contain" />
                            </span>
                        ) : (
                            <span className="inline-flex justify-center h-9 w-9 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white font-bold text-sm shadow-sm shadow-amber-500/25 ring-1 ring-white/30">
                                {shop?.name?.charAt(0) || 'S'}
                            </span>
                        )}
                        <span className="font-semibold text-sm text-slate-900 dark:text-white truncate">{shop?.name || 'Boutique'}</span>
                    </div>
                </div>
                <div className="flex items-center gap-2 sm:gap-3">
                    {cmsPages.length > 0 && (
                        <nav className="hidden md:flex items-center gap-1 rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/60 dark:bg-slate-950/30 p-1">
                            {cmsPages.slice(0, 5).map((p) => (
                                <Link
                                    key={p.id}
                                    href={links.page(p.slug)}
                                    className="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-amber-700 dark:hover:text-amber-400 hover:bg-amber-50/80 dark:hover:bg-amber-950/25 transition-colors"
                                >
                                    {p.title}
                                </Link>
                            ))}
                        </nav>
                    )}
                    <Link
                        href={links.catalog()}
                        className="hidden sm:inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white/60 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 hover:border-[var(--sf-primary,#f59e0b)] dark:hover:border-[var(--sf-primary,#f59e0b)] hover:text-[var(--sf-primary,#f59e0b)] dark:hover:text-[var(--sf-primary,#f59e0b)] transition-colors"
                    >
                        Catalogue
                    </Link>
                    <ShoppingCart buttonClassName="relative inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-[var(--sf-primary,#f59e0b)] dark:bg-white text-white dark:text-slate-900 hover:bg-[var(--sf-primary-hover,#d97706)] dark:hover:bg-[var(--sf-primary-hover,#d97706)] transition-colors shadow-sm shadow-slate-900/10 dark:shadow-none ring-1 ring-slate-900/5 dark:ring-white/10" storefrontLinks />
                </div>
            </div>
        </header>
    );
}

function ProductContent({ product, reviews = [], shop, cmsPages, whatsapp = {}, links }) {
    const { addToCart } = useCart();
    const currency = shop?.currency || 'USD';

    const thumbnails = [product.image_url, ...(product.gallery_urls || [])].filter(Boolean);
    const initialImage = thumbnails[0] || null;

    const [quantity, setQuantity] = useState(1);
    const [selectedImage, setSelectedImage] = useState(initialImage);
    const [imageError, setImageError] = useState(false);

    const reviewCount = Array.isArray(reviews) ? reviews.length : 0;
    const avgRating =
        reviewCount > 0 ? Math.round((reviews.reduce((sum, r) => sum + (Number(r.rating) || 0), 0) / reviewCount) * 10) / 10 : 0;
    const filledStars = Math.round(avgRating);

    const formatCurrency = (amount) => formatMoney(amount, product?.price_currency || currency || 'CDF');

    const handleAddToCart = () => {
        if (product.stock < quantity) {
            toast.error('Stock insuffisant');
            return;
        }
        addToCart(product, quantity);
        toast.success('Produit ajouté au panier');
    };

    const increaseQuantity = () => {
        if (quantity < product.stock) setQuantity(quantity + 1);
        else toast.error('Stock insuffisant');
    };

    const decreaseQuantity = () => {
        if (quantity > 1) setQuantity(quantity - 1);
    };

    const whatsappNumber = whatsapp.number || null;
    const whatsappSupportEnabled = !!whatsapp.enabled;

    return (
        <>
            <Head title={product.name} />
            <StorefrontClientBootstrap />

            <StorefrontProductHeader shop={shop} cmsPages={cmsPages} />

            <div className="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-50 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 lg:py-10">
                    {/* Breadcrumb */}
                    <nav className="mb-6 flex items-center gap-2 text-sm">
                        <Link
                            href={links.index()}
                            className="text-slate-500 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400"
                        >
                            Accueil
                        </Link>
                        <span className="text-slate-400">/</span>
                        <Link
                            href={links.catalog()}
                            className="text-slate-500 dark:text-slate-400 hover:text-amber-600 dark:hover:text-amber-400"
                        >
                            Catalogue
                        </Link>
                        <span className="text-slate-400">/</span>
                        <span className="text-slate-900 dark:text-white font-medium truncate">{product.name}</span>
                    </nav>

                    <div className="bg-white/90 dark:bg-slate-900/80 rounded-3xl shadow-2xl shadow-slate-200/60 dark:shadow-slate-950/50 border border-slate-200/70 dark:border-slate-800 overflow-hidden">
                        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 p-5 sm:p-6 lg:p-10">
                            {/* Media gallery */}
                            <div className="lg:col-span-7">
                                <div className="flex gap-4">
                                    {/* Thumbnails (desktop) */}
                                    {thumbnails.length > 1 && (
                                        <div className="hidden sm:flex w-20 flex-col gap-2">
                                            {thumbnails.map((url, idx) => (
                                                <button
                                                    key={idx}
                                                    type="button"
                                                    onClick={() => {
                                                        setImageError(false);
                                                        setSelectedImage(url);
                                                    }}
                                                    className={`relative h-20 w-20 rounded-2xl overflow-hidden border transition-colors ${
                                                        selectedImage === url
                                                            ? 'border-amber-500 ring-2 ring-amber-500/20'
                                                            : 'border-slate-200/80 dark:border-slate-800 hover:border-amber-300 dark:hover:border-amber-700'
                                                    }`}
                                                >
                                                    <img src={url} alt={product.name} className="h-full w-full object-cover" />
                                                    <span className="absolute inset-0 ring-1 ring-black/5 dark:ring-white/5" />
                                                </button>
                                            ))}
                                        </div>
                                    )}

                                    {/* Main image */}
                                    <div className="relative flex-1">
                                        <div className="relative aspect-square rounded-3xl overflow-hidden bg-slate-100 dark:bg-slate-800 ring-1 ring-slate-900/5 dark:ring-white/10">
                                            {selectedImage && !imageError ? (
                                                <img
                                                    src={selectedImage}
                                                    alt={product.name}
                                                    className="w-full h-full object-cover"
                                                    onError={() => setImageError(true)}
                                                />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center">
                                                    <ImageIcon className="h-24 w-24 text-slate-400" />
                                                </div>
                                            )}
                                            <div className="absolute left-4 top-4">
                                                {product.stock > 0 ? (
                                                    <Badge className="bg-emerald-500 text-white border-0">En stock</Badge>
                                                ) : (
                                                    <Badge className="bg-red-500 text-white border-0">Rupture</Badge>
                                                )}
                                            </div>
                                        </div>

                                        {/* Thumbnails (mobile) */}
                                        {thumbnails.length > 1 && (
                                            <div className="sm:hidden mt-4 flex gap-2 overflow-x-auto pb-1">
                                                {thumbnails.map((url, idx) => (
                                                    <button
                                                        key={idx}
                                                        type="button"
                                                        onClick={() => {
                                                            setImageError(false);
                                                            setSelectedImage(url);
                                                        }}
                                                        className={`flex-shrink-0 w-20 h-20 rounded-2xl overflow-hidden border transition-colors ${
                                                            selectedImage === url
                                                                ? 'border-amber-500 ring-2 ring-amber-500/20'
                                                                : 'border-slate-200/80 dark:border-slate-800 hover:border-amber-300 dark:hover:border-amber-700'
                                                        }`}
                                                    >
                                                        <img src={url} alt={product.name} className="w-full h-full object-cover" />
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {/* Product info */}
                            <div className="lg:col-span-5">
                                <div className="lg:sticky lg:top-24 space-y-6">
                                    <div className="space-y-2">
                                        <h1 className="text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                                            {product.name}
                                        </h1>
                                        <div className="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
                                            {reviewCount > 0 ? (
                                                <div className="inline-flex items-center gap-2">
                                                    <div className="flex gap-0.5">
                                                        {[1, 2, 3, 4, 5].map((s) => (
                                                            <Star
                                                                key={s}
                                                                className={`h-4 w-4 ${
                                                                    s <= filledStars
                                                                        ? 'text-amber-500 fill-amber-500'
                                                                        : 'text-slate-300 dark:text-slate-700'
                                                                }`}
                                                            />
                                                        ))}
                                                    </div>
                                                    <span className="text-slate-700 dark:text-slate-300 font-semibold">{avgRating}</span>
                                                    <span className="text-slate-500 dark:text-slate-400">
                                                        ({reviewCount} avis)
                                                    </span>
                                                </div>
                                            ) : (
                                                <span className="text-slate-500 dark:text-slate-400">Aucun avis</span>
                                            )}
                                            {product.sku ? (
                                                <span className="text-slate-500 dark:text-slate-400">Réf: {product.sku}</span>
                                            ) : null}
                                        </div>
                                    </div>

                                    <div className="flex items-end justify-between gap-4">
                                        <div className="flex flex-col">
                                            <div className="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white">
                                                {formatCurrency(product.price_amount)}
                                            </div>
                                            {(product.has_promotion || (product.discount_percent && Number(product.discount_percent) > 0)) && (
                                                <div className="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200 px-2 py-0.5 text-[11px] font-semibold">
                                                    Promo
                                                    {product.discount_percent ? ` -${Number(product.discount_percent)}%` : ''}
                                                </div>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {product.stock > 0 ? (
                                                <>
                                                    <CheckCircle className="h-5 w-5 text-emerald-500" />
                                                    <span className="text-emerald-700 dark:text-emerald-400 font-semibold">
                                                        {product.stock} dispo
                                                    </span>
                                                </>
                                            ) : (
                                                <>
                                                    <Package className="h-5 w-5 text-red-500" />
                                                    <span className="text-red-600 dark:text-red-400 font-semibold">Rupture</span>
                                                </>
                                            )}
                                        </div>
                                    </div>

                                    {product.description ? (
                                        <div
                                            className="prose prose-sm sm:prose dark:prose-invert max-w-none text-slate-600 dark:text-slate-300 leading-relaxed"
                                            dangerouslySetInnerHTML={{ __html: product.description }}
                                        />
                                    ) : null}

                                    {product.mode_paiement === 'paiement_livraison' && (
                                        <div className="rounded-2xl bg-amber-50/80 dark:bg-amber-950/25 border border-amber-200/70 dark:border-amber-900/40 p-4 flex items-center gap-3">
                                            <Truck className="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0" />
                                            <p className="text-sm font-semibold text-amber-900 dark:text-amber-200">
                                                Paiement à la livraison disponible
                                            </p>
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="rounded-3xl border border-slate-200/70 dark:border-slate-800 bg-white/70 dark:bg-slate-950/20 p-4 sm:p-5 space-y-4">
                                        <div className="flex items-center justify-between gap-3">
                                            <div className="text-sm font-semibold text-slate-700 dark:text-slate-200">Quantité</div>
                                            <div className="flex items-center gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={decreaseQuantity}
                                                    disabled={quantity <= 1}
                                                    className="rounded-2xl h-11 w-11"
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
                                                        if (val >= 1 && val <= product.stock) setQuantity(val);
                                                    }}
                                                    className="w-20 text-center rounded-2xl h-11"
                                                />
                                                <Button
                                                    variant="outline"
                                                    size="icon"
                                                    onClick={increaseQuantity}
                                                    disabled={quantity >= product.stock}
                                                    className="rounded-2xl h-11 w-11"
                                                >
                                                    <Plus className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                            <Button
                                                onClick={handleAddToCart}
                                                disabled={product.stock === 0}
                                                size="lg"
                                                className="w-full gap-2 rounded-2xl h-12 text-base font-semibold"
                                            >
                                                <ShoppingCartIcon className="h-5 w-5" />
                                                Ajouter
                                            </Button>
                                            <Link
                                                href={links.cart()}
                                                className={`inline-flex items-center justify-center rounded-2xl h-12 text-base font-semibold border transition-colors ${
                                                    product.stock === 0
                                                        ? 'pointer-events-none opacity-50 border-slate-200 dark:border-slate-800 text-slate-400'
                                                        : 'border-slate-200/80 dark:border-slate-800 text-slate-800 dark:text-slate-100 hover:border-amber-300 dark:hover:border-amber-700 hover:text-amber-700 dark:hover:text-amber-400 bg-white/70 dark:bg-slate-950/20'
                                                }`}
                                            >
                                                Voir le panier
                                            </Link>
                                        </div>

                                        <div className="pt-3 border-t border-slate-200/70 dark:border-slate-800 grid grid-cols-2 gap-3 text-sm text-slate-600 dark:text-slate-400">
                                            <span className="inline-flex items-center gap-2">
                                                <ShieldCheck className="h-4 w-4 text-emerald-500" />
                                                Paiement sécurisé
                                            </span>
                                            <span className="inline-flex items-center gap-2">
                                                <Truck className="h-4 w-4 text-amber-500" />
                                                Livraison rapide
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Avis clients */}
                        {reviews?.length > 0 && (
                            <div className="border-t border-slate-200 dark:border-slate-700 p-6 lg:p-10">
                                <h3 className="text-lg font-semibold text-slate-900 dark:text-white mb-4 flex items-center gap-2">
                                    <Star className="h-5 w-5 text-amber-500 fill-amber-500" />
                                    Avis clients ({reviews.length})
                                </h3>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    {reviews.map((r) => (
                                        <div
                                            key={r.id}
                                            className="rounded-xl border border-slate-200 dark:border-slate-700 p-4 bg-slate-50/50 dark:bg-slate-800/50"
                                        >
                                            <div className="flex items-center justify-between mb-2">
                                                <span className="font-medium text-slate-900 dark:text-white">{r.customer_name}</span>
                                                <div className="flex gap-0.5">
                                                    {[1, 2, 3, 4, 5].map((s) => (
                                                        <Star
                                                            key={s}
                                                            className={`h-4 w-4 ${
                                                                s <= r.rating
                                                                    ? 'text-amber-500 fill-amber-500'
                                                                    : 'text-slate-300 dark:text-slate-600'
                                                            }`}
                                                        />
                                                    ))}
                                                </div>
                                            </div>
                                            {r.title && (
                                                <p className="font-medium text-slate-800 dark:text-slate-200 mb-1">{r.title}</p>
                                            )}
                                            {r.comment && (
                                                <p className="text-sm text-slate-600 dark:text-slate-400">{r.comment}</p>
                                            )}
                                            <p className="text-xs text-slate-500 mt-2">{r.created_at}</p>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} />
        </>
    );
}

export default function StorefrontProductShow({ shop, product, reviews = [], cmsPages = [], whatsapp = {} }) {
    const currency = shop?.currency || 'CDF';
    const links = useStorefrontLinks();

    return (
        <CartProvider currency={currency} storageKey={`ecommerce_cart_${shop?.id ?? 'default'}`}>
            <ProductContent
                product={product}
                reviews={reviews}
                shop={shop}
                cmsPages={cmsPages}
                whatsapp={whatsapp}
                links={links}
            />
        </CartProvider>
    );
}
