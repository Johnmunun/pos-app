import { Head, Link, usePage } from '@inertiajs/react';
import { CartProvider } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import { ArrowLeft, CalendarDays, ArrowRight, Sparkles } from 'lucide-react';

function StorefrontBlogHeader({ shop, cmsPages = [] }) {
    const { shop: sharedShop } = usePage().props;
    const logoUrl = shop?.logo_url || sharedShop?.logo_url || null;

    return (
        <header className="sticky top-0 z-40 border-b border-slate-200/70 dark:border-slate-800 bg-white/75 dark:bg-slate-950/60 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-slate-950/50">
            <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link
                        href={route('ecommerce.storefront.index')}
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
                        <span className="font-semibold text-sm truncate">{shop?.name || 'Boutique'}</span>
                    </div>
                </div>
                <div className="flex items-center gap-2 sm:gap-3">
                    <nav className="hidden md:flex items-center gap-1 rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/60 dark:bg-slate-950/30 p-1">
                        {cmsPages.slice(0, 3).map((p) => (
                            <Link
                                key={p.id}
                                href={route('ecommerce.storefront.page', p.slug)}
                                className="px-3 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:text-amber-700 dark:hover:text-amber-400 hover:bg-amber-50/80 dark:hover:bg-amber-950/25 transition-colors"
                            >
                                {p.title}
                            </Link>
                        ))}
                        <Link
                            href={route('ecommerce.storefront.blog')}
                            className="px-3 py-2 rounded-xl text-xs font-semibold text-amber-700 dark:text-amber-400 bg-amber-50/80 dark:bg-amber-950/25"
                        >
                            Blog
                        </Link>
                    </nav>
                    <ShoppingCart
                        buttonClassName="relative inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-amber-600 dark:hover:bg-amber-500 transition-colors shadow-sm shadow-slate-900/10 dark:shadow-none ring-1 ring-slate-900/5 dark:ring-white/10"
                        storefrontLinks
                    />
                </div>
            </div>
        </header>
    );
}

function BlogContent({ shop, articles = [], cmsPages = [], whatsapp = {} }) {
    const { shop: sharedShop } = usePage().props;
    const currency = shop?.currency || sharedShop?.currency || 'CDF';
    const whatsappNumber = whatsapp.number || null;
    const whatsappSupportEnabled = !!whatsapp.enabled;

    return (
        <>
            <Head title="Blog de la boutique" />
            <StorefrontBlogHeader shop={shop} cmsPages={cmsPages} />

            <div className="min-h-screen bg-gradient-to-b from-slate-50 via-white to-slate-50 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">
                {/* Hero */}
                <section className="relative overflow-hidden border-b border-slate-200/70 dark:border-slate-800">
                    <div className="absolute inset-0 bg-gradient-to-br from-amber-500 via-amber-600 to-rose-500 dark:from-amber-600 dark:via-amber-700 dark:to-rose-700" />
                    <div className="absolute inset-0 opacity-20 bg-[radial-gradient(circle_at_top,_#fff_0,_transparent_55%)]" />
                    <div className="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14 lg:py-16 text-white">
                        <div className="max-w-2xl space-y-4">
                            <span className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-white/15 text-xs font-semibold tracking-wide uppercase">
                                <Sparkles className="h-3.5 w-3.5 text-amber-200" />
                                Blog de la boutique
                            </span>
                            <h1 className="text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight">
                                Conseils, nouveautés et actualités de votre boutique
                            </h1>
                            <p className="text-sm sm:text-base text-amber-50/90 max-w-xl">
                                Inspirez vos clients avec des articles professionnels : promotions, nouveautés produits, guides d&apos;achat
                                et plus encore.
                            </p>
                            <p className="text-xs sm:text-sm text-amber-50/80">
                                Devise de la boutique : <span className="font-semibold">{currency}</span>
                            </p>
                        </div>
                    </div>
                </section>

                {/* Liste des articles */}
                <section className="py-10 sm:py-14">
                    <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                        {articles.length === 0 ? (
                            <div className="rounded-3xl border border-dashed border-slate-300 dark:border-slate-700 bg-white/70 dark:bg-slate-900/60 p-10 text-center">
                                <p className="text-sm text-slate-600 dark:text-slate-300 max-w-md mx-auto">
                                    Aucun article publié pour le moment. Publiez un article dans le CMS pour le voir apparaître ici.
                                </p>
                            </div>
                        ) : (
                            <div className="grid gap-6 sm:gap-8 md:grid-cols-2">
                                {articles.map((article) => (
                                    <Link
                                        key={article.id}
                                        href={route('ecommerce.storefront.blog.show', article.slug)}
                                        className="group rounded-3xl border border-slate-200/80 dark:border-slate-800 bg-white/80 dark:bg-slate-900/80 overflow-hidden shadow-sm hover:shadow-xl hover:border-amber-200/80 dark:hover:border-amber-800/60 transition-all duration-300 flex flex-col"
                                    >
                                        {article.cover_url && (
                                            <div className="relative h-44 sm:h-52 overflow-hidden">
                                                <img
                                                    src={article.cover_url}
                                                    alt={article.title}
                                                    className="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                />
                                                <div className="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent" />
                                            </div>
                                        )}
                                        <div className="flex-1 p-5 sm:p-6 flex flex-col gap-3">
                                            <div className="flex items-center justify-between gap-3 text-xs text-slate-500 dark:text-slate-400">
                                                <span className="inline-flex items-center gap-1.5">
                                                    <CalendarDays className="h-3.5 w-3.5" />
                                                    {article.published_at}
                                                </span>
                                            </div>
                                            <h2 className="text-base sm:text-lg font-semibold text-slate-900 dark:text-white line-clamp-2 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                                                {article.title}
                                            </h2>
                                            {article.excerpt && (
                                                <p className="text-sm text-slate-600 dark:text-slate-300 line-clamp-3">{article.excerpt}</p>
                                            )}
                                            <div className="mt-2 pt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 border-t border-slate-100 dark:border-slate-800">
                                                <span>Lire l&apos;article complet</span>
                                                <span className="inline-flex items-center gap-1 text-amber-600 dark:text-amber-400 group-hover:translate-x-0.5 transition-transform">
                                                    Voir
                                                    <ArrowRight className="h-3 w-3" />
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </div>
                </section>
            </div>

            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} />
        </>
    );
}

export default function StorefrontBlog({ shop, articles = [], cmsPages = [], whatsapp = {} }) {
    const currency = shop?.currency || 'CDF';

    return (
        <CartProvider currency={currency}>
            <BlogContent shop={shop} articles={articles} cmsPages={cmsPages} whatsapp={whatsapp} />
        </CartProvider>
    );
}

