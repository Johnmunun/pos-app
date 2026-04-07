import { Head, Link, usePage } from '@inertiajs/react';
import { CartProvider } from '@/Contexts/CartContext';
import ShoppingCart from '@/Components/Ecommerce/ShoppingCart';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import AISupportFloatingWidget from '@/Components/Ecommerce/AISupportFloatingWidget';
import StorefrontClientBootstrap from '@/Components/Ecommerce/StorefrontClientBootstrap';
import { ArrowLeft, CalendarDays } from 'lucide-react';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';

function normalizeHtmlSpaces(value) {
    if (!value) return '';
    return String(value).replace(/&nbsp;/gi, ' ').replace(/\u00A0/g, ' ');
}

function StorefrontBlogShowHeader({ shop, cmsPages = [] }) {
    const links = useStorefrontLinks();
    const { shop: sharedShop } = usePage().props;
    const logoUrl = shop?.logo_url || sharedShop?.logo_url || null;

    return (
        <header className="sticky top-0 z-40 border-b border-slate-200/70 dark:border-slate-800 bg-white/75 dark:bg-slate-950/60 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-slate-950/50">
            <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <Link
                        href={links.blog()}
                        className="p-2 -ml-2 rounded-2xl text-slate-500 hover:text-amber-700 dark:hover:text-amber-400 hover:bg-amber-50/80 dark:hover:bg-amber-950/25 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500/30"
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Link>
                    <div className="flex items-center gap-2.5">
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
                    <ShoppingCart
                        buttonClassName="relative inline-flex items-center justify-center h-9 w-9 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:bg-amber-600 dark:hover:bg-amber-500 transition-colors shadow-sm shadow-slate-900/10 dark:shadow-none ring-1 ring-slate-900/5 dark:ring-white/10"
                        storefrontLinks
                    />
                </div>
            </div>
        </header>
    );
}

function BlogShowContent({ shop, article, cmsPages = [], whatsapp = {} }) {
    const { shop: sharedShop } = usePage().props;
    const currency = shop?.currency || sharedShop?.currency || 'CDF';
    const whatsappNumber = whatsapp.number || null;
    const whatsappSupportEnabled = !!whatsapp.enabled;
    const articleContent = normalizeHtmlSpaces(article?.content || '');

    return (
        <>
            <Head title={article?.title || 'Article'} />
            <StorefrontClientBootstrap />
            <StorefrontBlogShowHeader shop={shop} cmsPages={cmsPages} />

            <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-slate-950 dark:to-slate-900 text-slate-900 dark:text-slate-50">
                <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                    {/* Cover image */}
                    {article?.cover_url && (
                        <div className="mb-8 rounded-3xl overflow-hidden shadow-xl shadow-slate-200/60 dark:shadow-slate-900/60 ring-1 ring-slate-200/80 dark:ring-slate-700/80">
                            <img
                                src={article.cover_url}
                                alt={article.title}
                                className="w-full h-56 sm:h-72 md:h-80 object-cover"
                            />
                        </div>
                    )}

                    {/* Heading */}
                    <header className="mb-8 space-y-3">
                        <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-amber-50 dark:bg-amber-950/25 border border-amber-100 dark:border-amber-900/40 text-xs font-semibold text-amber-800 dark:text-amber-200">
                            Blog de la boutique • {currency}
                        </div>
                        <h1 className="text-3xl sm:text-4xl font-extrabold tracking-tight text-slate-900 dark:text-white">
                            {article?.title}
                        </h1>
                        <div className="flex flex-wrap items-center gap-3 text-xs text-slate-500 dark:text-slate-400">
                            <span className="inline-flex items-center gap-1.5">
                                <CalendarDays className="h-3.5 w-3.5" />
                                {article?.published_at}
                            </span>
                        </div>
                    </header>

                    {/* Content */}
                    <article
                        className="prose prose-slate dark:prose-invert prose-lg max-w-none
                            prose-headings:font-bold prose-headings:tracking-tight
                            prose-h2:mt-8 prose-h2:mb-3 prose-h2:text-xl
                            prose-p:text-slate-600 dark:prose-p:text-slate-300 prose-p:leading-relaxed
                            prose-ul:my-4 prose-li:text-slate-600 dark:prose-li:text-slate-300
                            prose-strong:text-slate-900 dark:prose-strong:text-white
                            prose-a:text-amber-600 dark:prose-a:text-amber-400 prose-a:no-underline hover:prose-a:underline"
                        dangerouslySetInnerHTML={{ __html: articleContent }}
                    />

                    {/* Back link */}
                    <div className="mt-12 pt-8 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <Link
                            href={links.blog()}
                            className="inline-flex items-center gap-2 text-sm font-semibold text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 transition-colors"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            Retour au blog
                        </Link>
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            © {new Date().getFullYear()} {shop?.name || 'Ma Boutique'}
                        </p>
                    </div>
                </main>
            </div>
            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} />
            <AISupportFloatingWidget />
        </>
    );
}

export default function StorefrontBlogShow({ shop, article, cmsPages = [], whatsapp = {} }) {
    const currency = shop?.currency || 'CDF';

    return (
        <CartProvider currency={currency} storageKey={`ecommerce_cart_${shop?.id ?? 'default'}`}>
            <BlogShowContent shop={shop} article={article} cmsPages={cmsPages} whatsapp={whatsapp} />
        </CartProvider>
    );
}

