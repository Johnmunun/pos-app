import { ShoppingBag, CreditCard, BarChart, Smartphone, Users, Link } from 'lucide-react';

/**
 * Component: Features
 *
 * Section de présentation des fonctionnalités principales
 */
export default function Features() {
    const features = [
        {
            icon: ShoppingBag,
            title: 'Vente de produits digitaux',
            description: 'Gérez facilement vos produits, stocks et inventaires en temps réel.',
        },
        {
            icon: CreditCard,
            title: 'Paiements sécurisés',
            description: 'Acceptez tous les modes de paiement en toute sécurité.',
        },
        {
            icon: BarChart,
            title: 'Tableaux de bord intelligents',
            description: 'Analysez vos ventes et revenus avec des rapports détaillés.',
        },
        {
            icon: Smartphone,
            title: 'Accès multi-appareils',
            description: 'Gérez votre boutique depuis n\'importe quel appareil.',
        },
        {
            icon: Users,
            title: 'Gestion de clients',
            description: 'Fidélisez vos clients avec un système de profils complets.',
        },
        {
            icon: Link,
            title: 'Intégrations puissantes',
            description: 'Connectez vos outils préférés : réseaux sociaux, email, etc.',
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
                            {/* Icon with background */}
                            <div className="mb-4 transform group-hover:scale-110 transition-transform duration-300">
                                <div className="w-14 h-14 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-xl flex items-center justify-center group-hover:from-amber-200 group-hover:to-orange-200 dark:group-hover:from-amber-800/50 dark:group-hover:to-orange-800/50 transition-colors">
                                    <feature.icon className="w-7 h-7 text-amber-600 dark:text-amber-400" />
                                </div>
                            </div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">{feature.title}</h3>
                            <p className="text-gray-600 dark:text-gray-400 leading-relaxed">{feature.description}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
