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
                            <div className="mb-4 transform group-hover:scale-110 transition-transform duration-300">
                                <feature.icon className="w-7 h-7 text-amber-600 dark:text-amber-400" />
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
