import { Head, Link } from '@inertiajs/react';
import { ShoppingCart, ArrowLeft, MapPin, Phone, Mail, Clock } from 'lucide-react';
import WhatsAppFloatingButton from '@/Components/Ecommerce/WhatsAppFloatingButton';
import useStorefrontLinks from '@/hooks/useStorefrontLinks';

export default function StorefrontPage({ shop, page, cmsPages = [], whatsapp = {} }) {
    const links = useStorefrontLinks();
    const isContact = page?.template === 'contact';
    const meta = page?.metadata && typeof page.metadata === 'object' ? page.metadata : {};
    const hasContactInfo =
        (meta.address && meta.address.trim()) ||
        (meta.phone && meta.phone.trim()) ||
        (meta.email && meta.email.trim()) ||
        (meta.hours && meta.hours.trim());

    const whatsappNumber = whatsapp.number || null;
    const whatsappSupportEnabled = !!whatsapp.enabled;

    return (
        <div className="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-slate-950 dark:to-slate-900 text-slate-900 dark:text-slate-50">
            <Head title={page?.title || 'Page'} />

            {/* Header */}
            <header className="sticky top-0 z-40 border-b border-slate-200/70 dark:border-slate-800 bg-white/75 dark:bg-slate-950/60 backdrop-blur-xl supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-slate-950/50">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Link
                            href={links.index()}
                            className="p-2 -ml-2 rounded-2xl text-slate-500 hover:text-amber-700 dark:hover:text-amber-400 hover:bg-amber-50/80 dark:hover:bg-amber-950/25 transition-colors focus:outline-none focus:ring-2 focus:ring-amber-500/30"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div className="flex items-center gap-2.5">
                            {shop?.logo_url ? (
                                <span className="inline-flex justify-center h-9 w-9 rounded-2xl bg-white shadow-sm shadow-slate-900/10 ring-1 ring-slate-200 overflow-hidden">
                                    <img src={shop.logo_url} alt={shop?.name || 'Logo'} className="w-full h-full object-contain" />
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
                        {cmsPages.length > 0 && (
                            <nav className="hidden sm:flex items-center gap-1 rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/60 dark:bg-slate-950/30 p-1">
                                {cmsPages.slice(0, 4).map((p) => (
                                    <Link
                                        key={p.id}
                                        href={links.page(p.slug)}
                                        className={`px-3 py-2 rounded-xl text-xs font-semibold transition-colors ${
                                            page?.slug === p.slug
                                                ? 'text-amber-700 dark:text-amber-400 bg-amber-50/80 dark:bg-amber-950/25'
                                                : 'text-slate-600 dark:text-slate-300 hover:text-amber-700 dark:hover:text-amber-400 hover:bg-slate-100/80 dark:hover:bg-slate-800/60'
                                        }`}
                                    >
                                        {p.title}
                                    </Link>
                                ))}
                            </nav>
                        )}
                        <Link
                            href={links.cart()}
                            className="relative inline-flex justify-center h-9 w-9 rounded-2xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 hover:bg-amber-600 dark:hover:bg-amber-500 transition-colors shadow-sm shadow-slate-900/10 dark:shadow-none ring-1 ring-slate-900/5 dark:ring-white/10"
                        >
                            <ShoppingCart className="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </header>

            <main className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
                {/* Hero section with image */}
                <div className="mb-10">
                    {page?.image_url && (
                        <div className="rounded-2xl overflow-hidden shadow-xl shadow-slate-200/50 dark:shadow-slate-900/50 mb-8 ring-1 ring-slate-200/80 dark:ring-slate-700/80">
                            <img
                                src={page.image_url}
                                alt={page.title}
                                className="w-full h-56 sm:h-72 md:h-80 object-cover"
                            />
                        </div>
                    )}
                    <h1 className="text-3xl sm:text-4xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                        {page?.title}
                    </h1>
                </div>

                {/* Contact template: info blocks */}
                {isContact && hasContactInfo && (
                    <div className="mb-10 grid sm:grid-cols-2 gap-4">
                        {meta.address && (
                            <a
                                href={`https://maps.google.com/?q=${encodeURIComponent(meta.address)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-amber-300 dark:hover:border-amber-700 transition-colors"
                            >
                                <div className="flex-shrink-0 w-11 h-11 rounded-xl bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center text-amber-600 dark:text-amber-400 group-hover:bg-amber-200 dark:group-hover:bg-amber-900/50">
                                    <MapPin className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Adresse</p>
                                    <p className="text-slate-800 dark:text-slate-100 font-medium mt-0.5">{meta.address}</p>
                                </div>
                            </a>
                        )}
                        {meta.phone && (
                            <a
                                href={`tel:${meta.phone.replace(/\s/g, '')}`}
                                className="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-amber-300 dark:hover:border-amber-700 transition-colors"
                            >
                                <div className="flex-shrink-0 w-11 h-11 rounded-xl bg-emerald-100 dark:bg-emerald-900/30 flex items-center justify-center text-emerald-600 dark:text-emerald-400 group-hover:bg-emerald-200 dark:group-hover:bg-emerald-900/50">
                                    <Phone className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Téléphone</p>
                                    <p className="text-slate-800 dark:text-slate-100 font-medium mt-0.5">{meta.phone}</p>
                                </div>
                            </a>
                        )}
                        {meta.email && (
                            <a
                                href={`mailto:${meta.email}`}
                                className="group flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 hover:border-amber-300 dark:hover:border-amber-700 transition-colors sm:col-span-2"
                            >
                                <div className="flex-shrink-0 w-11 h-11 rounded-xl bg-sky-100 dark:bg-sky-900/30 flex items-center justify-center text-sky-600 dark:text-sky-400 group-hover:bg-sky-200 dark:group-hover:bg-sky-900/50">
                                    <Mail className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Email</p>
                                    <p className="text-slate-800 dark:text-slate-100 font-medium mt-0.5">{meta.email}</p>
                                </div>
                            </a>
                        )}
                        {meta.hours && (
                            <div className="flex items-start gap-4 p-5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/50 sm:col-span-2">
                                <div className="flex-shrink-0 w-11 h-11 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center text-violet-600 dark:text-violet-400">
                                    <Clock className="h-5 w-5" />
                                </div>
                                <div>
                                    <p className="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Horaires</p>
                                    <p className="text-slate-800 dark:text-slate-100 font-medium mt-0.5">{meta.hours}</p>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Main content */}
                <article
                    className="prose prose-slate dark:prose-invert prose-lg max-w-none
                        prose-headings:font-bold prose-headings:tracking-tight
                        prose-h2:mt-10 prose-h2:mb-4 prose-h2:text-xl
                        prose-p:text-slate-600 dark:prose-p:text-slate-300 prose-p:leading-relaxed
                        prose-ul:my-4 prose-li:text-slate-600 dark:prose-li:text-slate-300
                        prose-strong:text-slate-900 dark:prose-strong:text-white
                        prose-a:text-amber-600 dark:prose-a:text-amber-400 prose-a:no-underline hover:prose-a:underline"
                    dangerouslySetInnerHTML={{ __html: page?.content || '' }}
                />

                {/* Back link */}
                <div className="mt-12 pt-8 border-t border-slate-200 dark:border-slate-800">
                    <Link
                        href={links.index()}
                        className="inline-flex items-center gap-2 text-sm font-semibold text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 transition-colors"
                    >
                        <ArrowLeft className="h-4 w-4" /> Retour à l&apos;accueil
                    </Link>
                </div>
            </main>

            {/* Footer */}
            <footer className="mt-16 border-t border-slate-200/80 dark:border-slate-800 bg-white/70 dark:bg-slate-950/40 backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-slate-950/30">
                <div className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div className="grid gap-10 lg:grid-cols-12">
                        {/* Brand */}
                        <div className="lg:col-span-4">
                            <Link href={links.index()} className="inline-flex items-center gap-3">
                                {shop?.logo_url ? (
                                    <span className="inline-flex items-center justify-center h-11 w-11 rounded-2xl bg-white shadow-lg shadow-slate-900/10 ring-1 ring-slate-200 overflow-hidden">
                                        <img src={shop.logo_url} alt={shop?.name || 'Logo'} className="w-full h-full object-contain" />
                                    </span>
                                ) : (
                                    <span className="inline-flex items-center justify-center h-11 w-11 rounded-2xl bg-gradient-to-br from-amber-500 to-amber-600 text-white font-bold text-sm shadow-lg shadow-amber-500/20">
                                        {shop?.name?.charAt(0) || 'S'}
                                    </span>
                                )}
                                <div className="leading-tight">
                                    <div className="text-sm font-bold text-slate-900 dark:text-white">{shop?.name || 'Ma Boutique'}</div>
                                    <div className="text-xs text-slate-500 dark:text-slate-400">Boutique en ligne</div>
                                </div>
                            </Link>
                            <p className="mt-4 text-sm text-slate-600 dark:text-slate-300 max-w-sm">
                                Besoin d’informations ? Retrouvez ici nos pages utiles et accédez rapidement au catalogue.
                            </p>
                            <div className="mt-5 flex flex-wrap gap-2">
                                <Link
                                    href={links.catalog()}
                                    className="inline-flex items-center justify-center px-4 py-2 rounded-xl bg-slate-900 dark:bg-slate-100 text-white dark:text-slate-900 text-sm font-semibold hover:bg-amber-600 dark:hover:bg-amber-500 transition-colors"
                                >
                                    Catalogue
                                </Link>
                                <Link
                                    href={links.cart()}
                                    className="inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:border-amber-400 hover:text-amber-700 dark:hover:text-amber-400 transition-colors"
                                >
                                    Panier
                                </Link>
                            </div>
                        </div>

                        {/* Links */}
                        <div className="lg:col-span-8">
                            <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Boutique
                                    </p>
                                    <ul className="mt-4 space-y-2">
                                        <li>
                                            <Link
                                                href={links.catalog()}
                                                className="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                            >
                                                Catalogue
                                            </Link>
                                        </li>
                                        <li>
                                            <Link
                                                href={links.cart()}
                                                className="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                            >
                                                Panier
                                            </Link>
                                        </li>
                                    </ul>
                                </div>

                                <div className={cmsPages.length > 0 ? '' : 'sm:col-span-2 lg:col-span-2'}>
                                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Informations
                                    </p>
                                    {cmsPages.length > 0 ? (
                                        <ul className="mt-4 space-y-2">
                                            {cmsPages.map((p) => (
                                                <li key={p.id}>
                                                    <Link
                                                        href={links.page(p.slug)}
                                                        className="text-sm font-medium text-slate-700 dark:text-slate-200 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                                    >
                                                        {p.title}
                                                    </Link>
                                                </li>
                                            ))}
                                        </ul>
                                    ) : (
                                        <p className="mt-4 text-sm text-slate-600 dark:text-slate-300">
                                            Des pages d’informations seront bientôt disponibles.
                                        </p>
                                    )}
                                </div>

                                <div className="hidden lg:block">
                                    <p className="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
                                        Contact
                                    </p>
                                    <p className="mt-4 text-sm text-slate-600 dark:text-slate-300">
                                        Consultez la page “Contact” si elle est disponible dans la section informations.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="mt-10 pt-8 border-t border-slate-200/80 dark:border-slate-800 flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            © {new Date().getFullYear()} {shop?.name || 'Ma Boutique'}. Tous droits réservés.
                        </p>
                        <p className="text-xs text-slate-500 dark:text-slate-400">Mentions & pages d’aide via le CMS</p>
                    </div>
                </div>
            </footer>

            <WhatsAppFloatingButton phone={whatsappNumber} enabled={whatsappSupportEnabled} />
        </div>
    );
}
