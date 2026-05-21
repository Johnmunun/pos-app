import LandingReveal from './LandingReveal';

/**
 * Component: Features
 *
 * Section fonctionnalités — cartes premium, hiérarchie claire, animations au scroll
 */
export default function Features() {
    const features = [
        {
            title: 'Module Commerce & Retail',
            description:
                'Caisse rapide pour supermarchés, boutiques, kiosques et restaurants avec tickets de caisse prêts à imprimer.',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            ),
        },
        {
            title: 'Matériel POS compatible',
            description:
                'Fonctionne avec vos imprimantes thermiques, TPE et tiroirs-caisses standards, sans matériel propriétaire.',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
            ),
        },
        {
            title: 'Codes-barres & inventaires',
            description:
                'Scan code-barres, inventaires guidés, réceptions de stock et transferts entre dépôts en quelques clics.',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            ),
        },
        {
            title: 'Encaissement & paiements',
            description:
                'Gérez espèces, mobile money, cartes bancaires, crédit client et acomptes avec suivi automatique.',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            ),
        },
        {
            title: 'Stock & alertes intelligentes',
            description:
                'Suivi de stock en temps réel, multi-dépôts, lots/numéros de série et alertes de stock bas automatiques.',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            ),
        },
        {
            title: 'Rapports & tableaux de bord',
            description:
                'Chiffre d’affaires, meilleures ventes, marges, performances par vendeur et par point de vente.',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            ),
        },
        {
            title: 'Web, tablette & mobile',
            description:
                'Travaillez depuis n’importe quel navigateur moderne, tablette ou mobile, avec interface responsive.',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            ),
        },
        {
            title: 'Clients & utilisateurs',
            description:
                'Fiches clients, historique d’achats, rôles et permissions fines par utilisateur et par module.',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            ),
        },
        {
            title: 'Multi-tenant & notifications',
            description:
                'Un compte ROOT pour gérer plusieurs boutiques, emails automatiques et notifications (stock bas, expirations...).',
            image: (
                <svg className="w-9 h-9 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            ),
        },
    ];

    return (
        <section
            id="features"
            className="relative py-24 sm:py-28 lg:py-32 lg:pb-40 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-white via-gray-50/40 to-white dark:from-gray-900 dark:via-gray-900/95 dark:to-gray-900 transition-colors duration-200 overflow-hidden"
        >
            <div className="pointer-events-none absolute left-1/2 top-24 h-64 w-[min(90vw,42rem)] -translate-x-1/2 rounded-full bg-amber-400/10 blur-3xl dark:bg-amber-500/5" />

            <div className="relative max-w-7xl mx-auto">
                <LandingReveal className="text-center max-w-3xl mx-auto mb-16 sm:mb-20 lg:mb-24">
                    <div className="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-500/20 dark:to-orange-500/10 ring-1 ring-amber-200/60 dark:ring-amber-500/20 mb-8 shadow-sm">
                        <svg className="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-gray-900 dark:text-white mb-5">
                        Tout ce dont vous avez besoin
                    </h2>
                    <p className="text-lg sm:text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
                        Une plateforme complète avec tous les outils essentiels pour faire croître votre activité.
                    </p>
                </LandingReveal>

                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 lg:gap-8">
                    {features.map((feature, idx) => (
                        <LandingReveal key={idx} delay={Math.min(idx * 50, 400)}>
                            <article
                                className={`group relative h-full flex flex-col rounded-3xl border border-gray-100/90 dark:border-gray-800/90 bg-white/80 dark:bg-gray-900/50 backdrop-blur-sm p-6 sm:p-7 shadow-sm hover:shadow-landing-soft transition-all duration-300 hover:-translate-y-1 hover:border-amber-200/70 dark:hover:border-amber-500/25 ${
                                    idx % 3 === 1 ? 'lg:translate-y-4' : ''
                                }`}
                            >
                                <div className="mb-5 flex items-start justify-between gap-4">
                                    <div className="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-500/15 dark:to-orange-500/10 ring-1 ring-amber-200/50 dark:ring-amber-500/15 transition-transform duration-300 group-hover:scale-105">
                                        {feature.image}
                                    </div>
                                    <span className="text-[10px] font-bold uppercase tracking-widest text-gray-400 dark:text-gray-500 tabular-nums">
                                        {String(idx + 1).padStart(2, '0')}
                                    </span>
                                </div>
                                <h3 className="text-lg sm:text-xl font-semibold tracking-tight text-gray-900 dark:text-white mb-3">
                                    {feature.title}
                                </h3>
                                <p className="text-sm sm:text-base text-gray-600 dark:text-gray-400 leading-relaxed grow">
                                    {feature.description}
                                </p>
                                <div className="mt-6 h-px w-full bg-gradient-to-r from-transparent via-amber-200/50 to-transparent dark:via-amber-500/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300" />
                            </article>
                        </LandingReveal>
                    ))}
                </div>
            </div>
        </section>
    );
}
