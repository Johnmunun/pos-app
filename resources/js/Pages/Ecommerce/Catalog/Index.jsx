import { useState, useMemo } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { CartProvider, useCart } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import ProductCard from '@/Components/Ecommerce/ProductCard';
import {
    Search,
    Package,
    Filter,
    RefreshCw,
    Grid,
    List,
    SlidersHorizontal,
    X,
} from 'lucide-react';

function CatalogContent({ products = [], categories = [], filters = {} }) {
    const { auth, shop } = usePage().props;
    const { addToCart, cart } = useCart();
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

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    return (
        <>
            <Head title="Catalogue Ecommerce" />

            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                {/* Hero Section */}
                <div className="bg-gradient-to-r from-blue-600 to-purple-600 text-white py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <h1 className="text-4xl font-bold mb-2">Boutique en ligne</h1>
                        <p className="text-blue-100 text-lg">
                            Découvrez notre sélection de produits de qualité
                        </p>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    {/* Search and Filters Bar */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 mb-6">
                        <div className="flex flex-col lg:flex-row gap-4">
                            {/* Search */}
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Rechercher un produit..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={(e) => {
                                        if (e.key === 'Enter') {
                                            handleFilter();
                                        }
                                    }}
                                    className="pl-10"
                                />
                            </div>

                            {/* Category Filter */}
                            <div className="w-full lg:w-64">
                                <select
                                    className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                    value={selectedCategory}
                                    onChange={(e) => setSelectedCategory(e.target.value)}
                                >
                                    <option value="">Toutes les catégories</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>
                                            {category.name}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Sort */}
                            <div className="w-full lg:w-48">
                                <select
                                    className="w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm"
                                    value={sortBy}
                                    onChange={(e) => setSortBy(e.target.value)}
                                >
                                    <option value="name">Trier par nom</option>
                                    <option value="price_asc">Prix croissant</option>
                                    <option value="price_desc">Prix décroissant</option>
                                </select>
                            </div>

                            {/* View Mode Toggle */}
                            <div className="flex gap-2">
                                <Button
                                    variant={viewMode === 'grid' ? 'default' : 'outline'}
                                    size="icon"
                                    onClick={() => setViewMode('grid')}
                                >
                                    <Grid className="h-4 w-4" />
                                </Button>
                                <Button
                                    variant={viewMode === 'list' ? 'default' : 'outline'}
                                    size="icon"
                                    onClick={() => setViewMode('list')}
                                >
                                    <List className="h-4 w-4" />
                                </Button>
                                <Button
                                    variant={showFilters ? 'default' : 'outline'}
                                    size="icon"
                                    onClick={() => setShowFilters(!showFilters)}
                                >
                                    <SlidersHorizontal className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        {/* Advanced Filters */}
                        {showFilters && (
                            <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Prix minimum
                                        </label>
                                        <Input
                                            type="number"
                                            placeholder="0"
                                            value={priceRange.min}
                                            onChange={(e) =>
                                                setPriceRange({ ...priceRange, min: e.target.value })
                                            }
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            Prix maximum
                                        </label>
                                        <Input
                                            type="number"
                                            placeholder="1000"
                                            value={priceRange.max}
                                            onChange={(e) =>
                                                setPriceRange({ ...priceRange, max: e.target.value })
                                            }
                                        />
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <Button onClick={handleFilter} variant="outline" className="gap-2">
                                            <Filter className="h-4 w-4" />
                                            Appliquer
                                        </Button>
                                        <Button onClick={clearFilters} variant="outline" size="icon">
                                            <X className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Results Count */}
                    <div className="mb-4 flex items-center justify-between">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            {filteredAndSortedProducts.length} produit(s) trouvé(s)
                        </p>
                    </div>

                    {/* Products Grid/List */}
                    {filteredAndSortedProducts.length === 0 ? (
                        <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                            <Package className="h-16 w-16 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                            <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                Aucun produit trouvé
                            </p>
                            <p className="text-gray-500 dark:text-gray-400 mb-4">
                                Essayez de modifier vos filtres de recherche
                            </p>
                            <Button onClick={clearFilters} variant="outline">
                                Réinitialiser les filtres
                            </Button>
                        </div>
                    ) : (
                        <div
                            className={
                                viewMode === 'grid'
                                    ? 'grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6'
                                    : 'space-y-4'
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
                                    Boutique
                                </h2>
                            </div>
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
