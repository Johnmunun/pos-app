import { useState, useMemo } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { CartProvider, useCart } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import ProductCard from '@/Components/Ecommerce/ProductCard';
import { Search, Package, Filter, Grid, List, SlidersHorizontal, X, ArrowLeft, Sparkles, ArrowRight } from 'lucide-react';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';

function shouldShowPageInNav(page) {
    if (!page) return false;
    const title = (page.title || '').toLowerCase();
    const slug = (page.slug || '').toLowerCase();

    const isCgv =
        (slug && (slug.includes('cgv') || slug.includes('conditions-generales-de-vente'))) ||
        (title && title.includes('condition') && title.includes('vente'));

    const isPrivacy =
        (slug && slug.includes('politique-de-confidentialite')) ||
        (title && title.includes('politique') && title.includes('confidentialit'));

    return !isCgv && !isPrivacy;
}

function StorefrontHeader({ shop, cmsPages = [] }) {
    const links = useStorefrontLinks();
    const { shop: sharedShop } = usePage().props;
    const logoUrl = shop?.logo_url || sharedShop?.logo_url || null;

    const navPages = (cmsPages || []).filter(shouldShowPageInNav).slice(0, 4);

    return (
        <header className="sticky top-0 z-40 border-b border-slate-200/70 dark:border-slate-800 bg-white/75 dark:bg-slate-950/60 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-slate-950/50">
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.index()}
                        className="p-2 -ml-2 rounded-2xl text-slate-500 hover:text-[var(--sf-primary)] hover:bg-[var(--sf-primary)]/10 transition-colors focus:outline-none focus:ring-2 focus:ring-[var(--sf-primary)]/30"
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Link>
                    <div className="flex items-center gap-2">
                        {logoUrl ? (
                            <span className="inline-flex justify-center h-9 w-9 rounded-2xl bg-white shadow-sm shadow-slate-900/10 ring-1 ring-slate-200 overflow-hidden">
                                <img src={logoUrl} alt={shop?.name || 'Logo'} className="w-full h-full object-contain" />
                            </span>
                        ) : (
                            <span className="inline-flex justify-center h-9 w-9 rounded-2xl bg-gradient-to-br from-[var(--sf-primary)] to-[var(--sf-secondary)] text-white font-bold text-sm shadow-sm shadow-[var(--sf-primary)]/25 ring-1 ring-white/30">
                                {shop?.name?.charAt(0) || 'S'}
                            </span>
                        )}
                        <span className="font-semibold text-sm truncate">{shop?.name || 'Boutique'}</span>
                    </div>
                </div>
                <div className="flex items-center gap-2 sm:gap-3">
                    <nav className="hidden md:flex items-center gap-1 rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/60 dark:bg-slate-950/30 p-1">
                        {navPages.map((p) => (
                            <Link
                                key={p.id}
                                href={links.page(p.slug)}
                                className="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-[var(--sf-primary)] hover:bg-[var(--sf-primary)]/10 transition-colors"
                            >
                                {p.title}
                            </Link>
                        ))}
                        <Link
                            href={links.blog()}
                            className="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-[var(--sf-primary)] hover:bg-[var(--sf-primary)]/10 transition-colors"
                        >
                            Blog
                        </Link>
                    </nav>
                    <Link
                        href={links.index()}
                        className="hidden sm:inline-flex items-center px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white/60 dark:bg-slate-950/30 border border-slate-200/70 dark:border-slate-800 hover:border-[var(--sf-primary)] hover:text-[var(--sf-primary)] transition-colors"
                    >
                        Accueil
                    </Link>
                    <ShoppingCart buttonClassName="relative inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-[var(--sf-primary-hover)] transition-colors shadow-sm shadow-slate-900/10 dark:shadow-none ring-1 ring-slate-900/5 dark:ring-white/10" storefrontLinks />
                </div>
            </div>
        </header>
    );
}

function CatalogContent({ products = [], categories = [], filters = {}, shop, cmsPages, banners = [], whatsapp = {} }) {
    const links = useStorefrontLinks();
    const { addToCart } = useCart();
    const currency = shop?.currency || 'USD';
    const productDetailUrl = (id) => links.product(id);

    const [search, setSearch] = useState(filters.search || '');
    const [selectedCategory, setSelectedCategory] = useState(filters.category_id || '');
    const [viewMode, setViewMode] = useState('grid');
    const [showFilters, setShowFilters] = useState(false);
    const [priceRange, setPriceRange] = useState({ min: '', max: '' });
    const [sortBy, setSortBy] = useState('name');

    const sliderBanners = (banners || []).filter((b) => b.position === 'slider' && (b.image_url || b.title));
    const promotionBanner = (banners || []).find((b) => b.position === 'promotion' && (b.image_url || b.title));
    const [activeSlide, setActiveSlide] = useState(0);

    const whatsappNumber = whatsapp.number || null;
    const whatsappSupportEnabled = !!whatsapp.enabled;

    const filteredAndSortedProducts = useMemo(() => {
        let filtered = products.filter((product) => {
            if (selectedCategory && product.category_id !== selectedCategory) return false;
            if (search) {
                const q = search.toLowerCase();
                if (
                    !product.name.toLowerCase().includes(q) &&
                    !(product.description && product.description.toLowerCase().includes(q)) &&
                    !(product.sku && product.sku.toLowerCase().includes(q))
                ) {
                    return false;
                }
            }
            if (priceRange.min && product.price_amount < parseFloat(priceRange.min)) return false;
            if (priceRange.max && product.price_amount > parseFloat(priceRange.max)) return false;
            return product.stock > 0;
        });

        filtered.sort((a, b) => {
            switch (sortBy) {
                case 'price_asc':
                    return a.price_amount - b.price_amount;
                case 'price_desc':
                    return b.price_amount - a.price_amount;
                default:
                    return a.name.localeCompare(b.name);
            }
        });

        return filtered;
    }, [products, selectedCategory, search, priceRange, sortBy]);

    const handleFilter = () => {
        router.get(links.catalog(), { search, category_id: selectedCategory }, { preserveState: true, preserveScroll: true });
    };

    const clearFilters = () => {
        setSearch('');
        setSelectedCategory('');
        setPriceRange({ min: '', max: '' });
        router.get(links.catalog(), {}, { preserveState: true });
    };

    const selectClass =
        'w-full rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-800 px-4 py-3 text-sm text-slate-900 dark:text-white focus:ring-2 focus:ring-[var(--sf-primary)]/30 focus:border-[var(--sf-primary)] transition-colors appearance-none cursor-pointer';

    return (
        <>
            <Head title="Catalogue - Boutique" />

            <StorefrontHeader shop={shop} cmsPages={cmsPages} />

            <div className="min-h-screen bg-slate-50 dark:bg-slate-950">
                <div className="relative overflow-hidden bg-gradient-to-br from-[var(--sf-primary)] via-[var(--sf-secondary)] to-[var(--sf-primary)]">
                    <div className="absolute inset-0 opacity-10 bg-[length:24px_24px] [background-image:radial-gradient(circle_at_1px_1px,rgba(255,255,255,0.4)_1px,transparent_0)]" />
                    <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-10 lg:py-12">
                        <div className="grid gap-6 lg:grid-cols-12 items-stretch">
                            <div className="lg:col-span-6 flex flex-col justify-center space-y-2 sm:space-y-3">
                                <span className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/15 text-xs font-semibold text-amber-50/90">
                                    <Sparkles className="h-3.5 w-3.5" />
                                    Catalogue en ligne
                                </span>
                                <h1 className="text-2xl sm:text-3xl lg:text-4xl font-bold text-white tracking-tight drop-shadow-sm">
                                    Catalogue
                                </h1>
                                <p className="mt-1 sm:mt-2 text-amber-50 text-sm sm:text-base lg:text-lg max-w-xl">
                                    Recherchez et filtrez nos produits de manière claire et responsive.
                                </p>
                            </div>

                            {(sliderBanners.length > 0 || promotionBanner) && (
                                <div className="lg:col-span-6">
                                    <div className="bg-white/10 backdrop-blur-sm rounded-2xl border border-white/30 shadow-lg overflow-hidden">
                                        {sliderBanners.length > 0 && (
                                            <div className="relative h-32 sm:h-40">
                                                <div className="w-full h-full overflow-hidden">
                                                    <div
                                                        className="flex w-full h-full transition-transform duration-500 ease-out"
                                                        style={{ transform: `translateX(-${activeSlide * 100}%)` }}
                                                    >
                                                        {sliderBanners.map((b) => (
                                                            <Link
                                                                key={b.id}
                                                                href={b.link || '#'}
                                                                className="min-w-full flex-shrink-0 h-full relative"
                                                            >
                                                                {b.image_url ? (
                                                                    <img
                                                                        src={b.image_url}
                                                                        alt={b.title}
                                                                        className="w-full h-full object-cover"
                                                                    />
                                                                ) : (
                                                                    <div className="w-full h-full flex items-center justify-center text-xs sm:text-sm text-amber-50/90 px-4">
                                                                        {b.title}
                                                                    </div>
                                                                )}
                                                                <div className="absolute inset-0 bg-gradient-to-r from-black/60 via-black/30 to-transparent" />
                                                                <div className="absolute inset-y-0 left-0 px-4 sm:px-5 flex flex-col justify-center">
                                                                    <p className="text-[11px] font-semibold tracking-[0.18em] uppercase text-amber-200">
                                                                        Offre catalogue
                                                                    </p>
                                                                    <p className="mt-1 text-sm sm:text-base font-semibold text-white line-clamp-2 max-w-xs">
                                                                        {b.title}
                                                                    </p>
                                                                </div>
                                                            </Link>
                                                        ))}
                                                    </div>
                                                </div>
                                                {sliderBanners.length > 1 && (
                                                    <div className="absolute bottom-2 right-3 flex items-center gap-1.5">
                                                        {sliderBanners.map((b, idx) => (
                                                            <button
                                                                key={b.id}
                                                                type="button"
                                                                onClick={() => setActiveSlide(idx)}
                                                                className={`h-1.5 rounded-full transition-all ${
                                                                    activeSlide === idx
                                                                        ? 'w-5 bg-[var(--sf-primary)]'
                                                                        : 'w-2 bg-white/60 hover:bg-[var(--sf-primary)]/70'
                                                                }`}
                                                            />
                                                        ))}
                                                    </div>
                                                )}
                                            </div>
                                        )}

                                        {promotionBanner && (
                                            <div className="px-4 sm:px-5 py-3 sm:py-3.5 flex items-center justify-between gap-3 border-t border-white/20 bg-black/15">
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-[11px] font-semibold uppercase tracking-wide text-amber-200 flex items-center gap-1.5">
                                                        <Sparkles className="h-3.5 w-3.5" />
                                                        Promotion
                                                    </p>
                                                    <p className="text-xs sm:text-sm text-amber-50 line-clamp-1">
                                                        {promotionBanner.title || 'Promotion en cours sur le catalogue'}
                                                    </p>
                                                </div>
                                                <Link
                                                    href={promotionBanner.link || links.catalog()}
                                                    className="inline-flex items-center gap-1.5 rounded-xl bg-white/90 text-[var(--sf-primary)] text-[11px] sm:text-xs font-semibold px-3 py-1.5 hover:bg-white transition-colors shrink-0"
                                                >
                                                    Voir
                                                    <ArrowRight className="h-3 w-3" />
                                                </Link>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-4 sm:-mt-6 lg:-mt-8 relative z-10">
                    <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-lg shadow-slate-200/50 dark:shadow-slate-900/50 border border-slate-200/60 dark:border-slate-700/60 p-4 sm:p-5 mb-6">
                        <div className="flex flex-col sm:flex-row gap-3 sm:gap-4">
                            <div className="flex-1 flex gap-2">
                                <div className="flex-1 relative">
                                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400 pointer-events-none" />
                                    <Input
                                        type="text"
                                        placeholder="Rechercher un produit..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        onKeyPress={(e) => e.key === 'Enter' && handleFilter()}
                                        className="pl-11 h-12 rounded-xl border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/50 focus:ring-2 focus:ring-[var(--sf-primary)]/30 text-base"
                                    />
                                </div>
                                <Button onClick={handleFilter} className="h-12 px-5 rounded-xl gap-2 shrink-0">
                                    <Search className="h-5 w-5" />
                                    Rechercher
                                </Button>
                            </div>
                            <div className="flex flex-col sm:flex-row gap-3 sm:gap-3 sm:items-center">
                                <select
                                    className={selectClass + ' sm:w-56'}
                                    value={selectedCategory}
                                    onChange={(e) => setSelectedCategory(e.target.value)}
                                >
                                    <option value="">Toutes les catégories</option>
                                    {categories.map((c) => (
                                        <option key={c.id} value={c.id}>
                                            {c.name}
                                        </option>
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

                        {showFilters && (
                            <div className="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700">
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Prix minimum</label>
                                        <Input
                                            type="number"
                                            placeholder="0"
                                            value={priceRange.min}
                                            onChange={(e) => setPriceRange({ ...priceRange, min: e.target.value })}
                                            className="rounded-xl"
                                        />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">Prix maximum</label>
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

                    <div className="flex items-center justify-between mb-4 sm:mb-5">
                        <p className="text-sm text-slate-600 dark:text-slate-400">
                            {filteredAndSortedProducts.length} produit{filteredAndSortedProducts.length !== 1 ? 's' : ''} trouvé
                            {filteredAndSortedProducts.length !== 1 ? 's' : ''}
                        </p>
                        <div className="sm:hidden flex gap-1">
                            <button
                                type="button"
                                onClick={() => setViewMode('grid')}
                                className={`p-2 rounded-lg ${viewMode === 'grid' ? 'bg-[var(--sf-primary)]/20 text-[var(--sf-primary)]' : 'text-slate-400'}`}
                            >
                                <Grid className="h-4 w-4" />
                            </button>
                            <button
                                type="button"
                                onClick={() => setViewMode('list')}
                                className={`p-2 rounded-lg ${viewMode === 'list' ? 'bg-[var(--sf-primary)]/20 text-[var(--sf-primary)]' : 'text-slate-400'}`}
                            >
                                <List className="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    {filteredAndSortedProducts.length === 0 ? (
                        <div className="bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-700 p-8 sm:p-12 lg:p-16 text-center">
                            <div className="w-20 h-20 mx-auto rounded-2xl bg-slate-100 dark:bg-slate-700 flex items-center justify-center mb-6">
                                <Package className="h-10 w-10 text-slate-400" />
                            </div>
                            <h3 className="text-lg font-semibold text-slate-800 dark:text-slate-200 mb-2">Aucun produit trouvé</h3>
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
                                    detailUrl={productDetailUrl(product.id)}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </div>

            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} />
        </>
    );
}

export default function StorefrontCatalog({ shop, products = [], categories = [], filters = {}, cmsPages = [], banners = [], whatsapp = {} }) {
    const currency = shop?.currency || 'CDF';

    return (
        <CartProvider currency={currency}>
            <CatalogContent
                products={products}
                categories={categories}
                filters={filters}
                shop={shop}
                cmsPages={cmsPages}
                banners={banners}
                whatsapp={whatsapp}
            />
        </CartProvider>
    );
}
