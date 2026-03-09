import { Head, Link } from '@inertiajs/react';
import { ShoppingCart, ArrowLeft } from 'lucide-react';

export default function StorefrontPage({ shop, page }) {
    return (
        <div className="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-50">
            <Head title={page?.title || 'Page'} />

            <header className="border-b border-slate-200/70 dark:border-slate-800 bg-white/90 dark:bg-slate-950/90 backdrop-blur">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={route('ecommerce.storefront.index')}
                            className="text-slate-500 hover:text-amber-600 dark:hover:text-amber-400"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div className="flex items-center gap-2">
                            <span className="inline-flex justify-center h-9 w-9 rounded-full bg-amber-500 text-white font-bold text-sm">
                                {shop?.name?.charAt(0) || 'S'}
                            </span>
                            <span className="font-semibold text-sm truncate">{shop?.name || 'Boutique'}</span>
                        </div>
                    </div>
                    <Link
                        href={route('ecommerce.cart.index')}
                        className="inline-flex justify-center h-9 w-9 rounded-full bg-slate-900 text-white hover:bg-amber-600 transition-colors"
                    >
                        <ShoppingCart className="h-4 w-4" />
                    </Link>
                </div>
            </header>

            <main className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
                <article>
                    <h1 className="text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white mb-6">
                        {page?.title}
                    </h1>
                    {page?.image_url && (
                        <div className="mb-6 rounded-xl overflow-hidden">
                            <img
                                src={page.image_url}
                                alt={page.title}
                                className="w-full h-48 sm:h-64 object-cover"
                            />
                        </div>
                    )}
                    <div
                        className="prose dark:prose-invert prose-slate max-w-none text-slate-600 dark:text-slate-300"
                        dangerouslySetInnerHTML={{ __html: page?.content || '' }}
                    />
                </article>
                <div className="mt-8">
                    <Link
                        href={route('ecommerce.storefront.index')}
                        className="inline-flex items-center gap-2 text-sm font-medium text-amber-600 dark:text-amber-400 hover:text-amber-700"
                    >
                        <ArrowLeft className="h-4 w-4" /> Retour à l&apos;accueil
                    </Link>
                </div>
            </main>
        </div>
    );
}
