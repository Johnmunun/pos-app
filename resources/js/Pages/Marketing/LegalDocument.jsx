import { Head, Link } from '@inertiajs/react';
import AppSeoHead from '@/Components/AppSeoHead';
import MarketingPageLayout from '@/Layouts/MarketingPageLayout';
import { ArrowLeft } from 'lucide-react';

export default function LegalDocument({ document, pageSeo = null }) {
    const { title, lastUpdated, companyName, sections = [] } = document ?? {};

    return (
        <MarketingPageLayout>
            <AppSeoHead pageSeo={pageSeo} />
            <Head title={title} />

            <div className="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                <Link
                    href={route('landing')}
                    className="inline-flex items-center gap-2 text-sm font-medium text-amber-700 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 mb-8"
                >
                    <ArrowLeft className="h-4 w-4" />
                    Retour à l&apos;accueil
                </Link>

                <header className="mb-10">
                    <p className="text-sm font-medium text-amber-600 dark:text-amber-400 mb-2">{companyName}</p>
                    <h1 className="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 dark:text-white">
                        {title}
                    </h1>
                    {lastUpdated && (
                        <p className="mt-3 text-sm text-gray-500 dark:text-gray-400">
                            Dernière mise à jour : {lastUpdated}
                        </p>
                    )}
                </header>

                <article className="prose prose-gray dark:prose-invert max-w-none prose-headings:tracking-tight prose-a:text-amber-600 dark:prose-a:text-amber-400">
                    {sections.map((section, idx) => (
                        <section key={idx} className="mb-8">
                            {section.heading && (
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                                    {section.heading}
                                </h2>
                            )}
                            {Array.isArray(section.paragraphs) &&
                                section.paragraphs.map((p, pIdx) => (
                                    <p
                                        key={pIdx}
                                        className="text-gray-600 dark:text-gray-300 leading-relaxed mb-3 last:mb-0"
                                    >
                                        {p}
                                    </p>
                                ))}
                        </section>
                    ))}
                </article>
            </div>
        </MarketingPageLayout>
    );
}
