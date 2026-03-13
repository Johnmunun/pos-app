import { ShoppingBag, CreditCard, BarChart, Smartphone, Users, Link, Printer, ScanLine, Package } from 'lucide-react';

/**
 * Component: Features
 *
 * Section de présentation des fonctionnalités principales
 */
export default function Features() {
    const features = [
        {
            icon: ShoppingBag,
            title: 'Module Commerce & Retail',
            description: 'Caisse rapide pour supermarchés, boutiques, kiosques et restaurants avec tickets de caisse prêts à imprimer.',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                </svg>
            ),
        },
        {
            icon: Printer,
            title: 'Matériel POS compatible',
            description: 'Fonctionne avec vos imprimantes thermiques, TPE et tiroirs-caisses standards, sans matériel propriétaire.',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
            ),
        },
        {
            icon: ScanLine,
            title: 'Codes-barres & inventaires',
            description: 'Scan code-barres, inventaires guidés, réceptions de stock et transferts entre dépôts en quelques clics.',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            ),
        },
        {
            icon: CreditCard,
            title: 'Encaissement & paiements',
            description: 'Gérez espèces, mobile money, cartes bancaires, crédit client et acomptes avec suivi automatique.',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            ),
        },
        {
            icon: Package,
            title: 'Stock & alertes intelligentes',
            description: 'Suivi de stock en temps réel, multi-dépôts, lots/numéros de série et alertes de stock bas automatiques.',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            ),
        },
        {
            icon: BarChart,
            title: 'Rapports & tableaux de bord',
            description: 'Chiffre d’affaires, meilleures ventes, marges, performances par vendeur et par point de vente.',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            ),
        },
        {
            icon: Smartphone,
            title: 'Web, tablette & mobile',
            description: 'Travaillez depuis n’importe quel navigateur moderne, tablette ou mobile, avec interface responsive.',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            ),
        },
        {
            icon: Users,
            title: 'Clients & utilisateurs',
            description: 'Fiches clients, historique d’achats, rôles et permissions fines par utilisateur et par module.',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                </svg>
            ),
        },
        {
            icon: Link,
            title: 'Multi-tenant & notifications',
            description: 'Un compte ROOT pour gérer plusieurs boutiques, emails automatiques et notifications (stock bas, expirations...).',
            image: (
                <svg className="w-20 h-20 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                </svg>
            ),
        },
    ];

    return (
        <section id="features" className="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900 transition-colors duration-200">
            <div className="max-w-7xl mx-auto">
                {/* En-tête */}
                <div className="text-center mb-16">
                    <div className="inline-flex items-center justify-center mb-6">
                        <svg className="w-16 h-16 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                    </div>
                    <h2 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                        Tout ce dont vous avez besoin
                    </h2>
                    <p className="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Une plateforme complète avec tous les outils essentiels pour faire croître votre activité.
                    </p>
                </div>

                {/* Grille 3 colonnes */}
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                    {features.map((feature, idx) => (
                        <div 
                            key={idx} 
                            className="group bg-gray-50 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-700 rounded-xl p-6 transition-all duration-300 border border-gray-200 dark:border-gray-700 hover:border-amber-500 dark:hover:border-amber-500 hover:shadow-xl transform hover:-translate-y-1"
                        >
                            {/* Image SVG */}
                            <div className="mb-4 flex justify-center transform group-hover:scale-110 transition-transform duration-300">
                                <div className="w-20 h-20 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-xl flex items-center justify-center group-hover:from-amber-200 group-hover:to-orange-200 dark:group-hover:from-amber-800/50 dark:group-hover:to-orange-800/50 transition-colors">
                                    {feature.image}
                                </div>
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2 text-center">{feature.title}</h3>
                            <p className="text-gray-600 dark:text-gray-400 leading-relaxed text-center">{feature.description}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
