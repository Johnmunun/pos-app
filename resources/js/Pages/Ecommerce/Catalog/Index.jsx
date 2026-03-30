import { useState, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { CartProvider, useCart } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import ProductCard from '@/Components/Ecommerce/ProductCard';
import {
    Search,
    Package,
    Filter,
    Grid,
    List,
    SlidersHorizontal,
    X,
    Eye,
} from 'lucide-react';

function CatalogContent({ products = [], categories = [], filters = {} }) {
    const { shop } = usePage().props;
    const { addToCart } = useCart();
    const currency = shop?.currency || 'USD';

    const [search, setSearch] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category_id || '');
    const [viewMode, setViewMode] = useState('grid'); // 'grid' or 'list'
    const [showFilters, setShowFilters] = useState(false);
    const [priceRange, setPriceRange] = useState({ min: '', max: '' });
    const [sortBy, setSortBy] = useState('name'); // 'name', 'price_asc', 'price_desc'

    const filteredAndSortedProducts = useMemo(() => {
        let filtered = products.filter((product) => {
            // Category filter
            if (selectedCategory && product.category_id !== selectedCategory) {
                return false;
            }

            // Search filter
            if (search) {
                const searchLower = search.toLowerCase();
                if (
                    !product.name.toLowerCase().includes(searchLower) &&
                    !(product.description && product.description.toLowerCase().includes(searchLower)) &&
                    !(product.sku && product.sku.toLowerCase().includes(searchLower))
                ) {
                    return false;
                }
            }

            // Price range filter
            if (priceRange.min && product.price_amount < parseFloat(priceRange.min)) {
                return false;
            }
            if (priceRange.max && product.price_amount > parseFloat(priceRange.max)) {
                return false;
            }

            // Only active products with stock
            return product.stock > 0;
        });

        // Sort
        filtered.sort((a, b) => {
            switch (sortBy) {
                case 'price_asc':
                    return a.price_amount - b.price_amount;
                case 'price_desc':
                    return b.price_amount - a.price_amount;
                case 'name':
                default:
                    return a.name.localeCompare(b.name);
            }
        });

        return filtered;
    }, [products, selectedCategory, search, priceRange, sortBy]);

    const handleFilter = () => {
        router.get(
            route('ecommerce.catalog.index'),
            {
                search,
                category_id: selectedCategory,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedCategory('');
        setPriceRange({ min: '', max: '' });
        router.get(route('ecommerce.catalog.index'), {}, { preserveState: true });
    };

    const selectClass =
        'w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition-colors appearance-none cursor-pointer';

    return (
        <>
            <Head title="Catalogue - Boutique" />

            <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
                {/* Hero - Desktop: large, Mobile: compact */}
                <div className="relative overflow-hidden bg-gradient-to-br from-amber-500 via-amber-600 to-amber-700 dark:from-amber-600 dark:via-amber-700 dark:to-amber-800">
                    <div className="absolute inset-0 opacity-10 bg-[length:24px_24px] [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.4)_1px,transparent_0)]" />
                    <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-14">
                        <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight drop-shadow-sm">
                            Boutique en ligne
                        </h1>
                        <p className="mt-1 sm:mt-2 text-amber-100 text-sm sm:text-base lg:text-lg max-w-xl">
                            Découvrez notre sélection de produits de qualité
                        </p>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-4 sm:-mt-6 lg:-mt-8 relative z-10">
                    {/* Search + Filters bar */}
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-lg shadow-slate-200/50 dark:shadow-slate-900/50 border border-slate-200/60 dark:border-slate-700/60 p-4 sm:p-5 mb-6">
                        {/* Row 1: Search + Category (mobile: stacked, desktop: inline) */}
                        <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                            <div className="flex-1 relative">
                                <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 pointer-events-none" />
                                <Input
                                    type="text"
                                    placeholder="Rechercher un produit..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                    className="pl-11 h-12 rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/50 focus:ring-2 focus:ring-amber-500/30 text-base"
                                />
                            </div>
                            <div className="flex flex-col sm:flex-row gap-3 sm:gap-3 sm:items-center">
                                <select
                                    className={selectClass + ' sm:w-56'}
                                    value={selectedCategory}
                                    onChange={(e) => setSelectedCategory(e.target.value)}
                                >
                                    <option value="">Toutes les catégories</option>
                                    {categories.map((c) => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                                <select
                                    className={selectClass + ' sm:w-48'}
                                    value={sortBy}
                                    onChange={(e) => setSortBy(e.target.value)}
                                >
                                    <option value="name">Trier par nom</option>
                                    <option value="price_asc">Prix croissant</option>
                                    <option value="price_desc">Prix décroissant</option>
                                </select>
                                {/* View mode - hidden on very small screens */}
                                <div className="hidden sm:flex gap-1.5">
                                    <Button
                                        variant={viewMode === 'grid' ? 'default' : 'ghost'}
                                        size="sm"
                                        className="h-10 w-10 rounded-xl"
                                        onClick={() => setViewMode('grid')}
                                    >
                                        <Grid className="h-4 w-4" />
                                    </Button>
                                    <Button
                                        variant={viewMode === 'list' ? 'default' : 'ghost'}
                                        size="sm"
                                        className="h-10 w-10 rounded-xl"
                                        onClick={() => setViewMode('list')}
                                    >
                                        <List className="h-4 w-4" />
                                    </Button>
                                </div>
                                {/* Mobile: filters toggle */}
                                <Button
                                    variant={showFilters ? 'default' : 'outline'}
                                    size="sm"
                                    className="sm:ml-auto gap-2 rounded-xl h-12 sm:h-10"
                                    onClick={() => setShowFilters(!showFilters)}
                                >
                                    <SlidersHorizontal className="h-4 w-4" />
                                    <span className="sm:hidden">Filtres avancés</span>
                                </Button>
                            </div>
                        </div>

                        {/* Advanced Filters (collapsible) */}
                        {showFilters && (
                            <div className="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                            Prix minimum
                                        </label>
                                        <Input
                                            type="number"
                                            placeholder="0"
                                            value={priceRange.min}
                                            onChange={(e) => setPriceRange({ ...priceRange, min: e.target.value })}
                                            className="rounded-xl"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                            Prix maximum
                                        </label>
                                        <Input
                                            type="number"
                                            placeholder="1000"
                                            value={priceRange.max}
                                            onChange={(e) => setPriceRange({ ...priceRange, max: e.target.value })}
                                            className="rounded-xl"
                                        />
                                    </div>
                                    <div className="flex items-end gap-2 sm:col-span-2">
                                        <Button onClick={handleFilter} variant="default" className="gap-2 rounded-xl">
                                            <Filter className="h-4 w-4" />
                                            Appliquer
                                        </Button>
                                        <Button onClick={clearFilters} variant="outline" size="icon" className="rounded-xl">
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Results count + optional mobile view toggle */}
                    <div className="flex items-center justify-between mb-4 sm:mb-5">
                        <p className="text-sm text-slate-600 dark:text-slate-400">
                            {filteredAndSortedProducts.length} produit{filteredAndSortedProducts.length !== 1 ? 's' : ''} trouvé{filteredAndSortedProducts.length !== 1 ? 's' : ''}
                        </p>
                        <div className="sm:hidden flex gap-1">
                            <button
                                type="button"
                                onClick={() => setViewMode('grid')}
                                className={`p-2 rounded-lg ${viewMode === 'grid' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' : 'text-slate-400'}`}
                            >
                                <Grid className="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode('list')}
                                className={`p-2 rounded-lg ${viewMode === 'list' ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-300' : 'text-slate-400'}`}
                            >
                                <List className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    {/* Products */}
                    {filteredAndSortedProducts.length === 0 ? (
                        <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 sm:p-12 lg:p-16 text-center">
                            <div className="w-20 h-20 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-6">
                                <Package className="h-10 w-10 text-slate-400" />
                            </div>
                            <h3 className="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-2">
                                Aucun produit trouvé
                            </h3>
                            <p className="text-slate-500 dark:text-slate-400 mb-6 max-w-sm mx-auto">
                                Essayez de modifier vos filtres ou votre recherche
                            </p>
                            <Button onClick={clearFilters} variant="outline" className="rounded-xl">
                                Réinitialiser les filtres
                            </Button>
                        </div>
                    ) : (
                        <div
                            className={
                                viewMode === 'grid'
                                    ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 sm:gap-5 lg:gap-6'
                                    : 'space-y-4 sm:space-y-5'
                            }
                        >
                            {filteredAndSortedProducts.map((product) => (
                                <ProductCard
                                    key={product.id}
                                    product={product}
                                    viewMode={viewMode}
                                    onAddToCart={addToCart}
                                    currency={currency}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

export default function CatalogIndex({ products = [], categories = [], filters = {} }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'USD';

    return (
        <CartProvider currency={currency}>
            <AppLayout
                header={
                    <div className="flex items-center justify-between w-full">
                        <div className="flex items-center gap-4">
                            <div className="flex items-center gap-2">
                                <Package className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                    Catalogue
                                </h2>
                            </div>
                            <Link
                                href={route('ecommerce.storefront.index')}
                                className="inline-flex items-center justify-center rounded-lg border border-amber-500/60 p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 transition-colors"
                                title="Prévisualiser la boutique"
                                aria-label="Prévisualiser la boutique"
                            >
                                <Eye className="h-4 w-4" />
                            </Link>
                        </div>
                        <div className="flex items-center gap-4">
                            <ShoppingCart />
                        </div>
                    </div>
                }
            >
                <CatalogContent products={products} categories={categories} filters={filters} />
            </AppLayout>
        </CartProvider>
    );
}
