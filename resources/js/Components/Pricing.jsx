import { Check, Star } from 'lucide-react';

/**
 * Component: Pricing
 *
 * Section de tarifs avec plans
 */
function PricingCard({ name, price, description, features, isPopular }) {
    return (
        <div className={`rounded-2xl transition-all duration-300 transform hover:scale-105 relative ${
            isPopular
                ? 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-2xl ring-2 ring-amber-300 dark:ring-amber-500 -mt-4'
                : 'bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-lg border border-gray-200 dark:border-gray-700 hover:border-amber-200 dark:hover:border-amber-500'
        }`}>
            {isPopular && (
                <div className="absolute -top-4 left-1/2 transform -translate-x-1/2 z-10">
                    <span className="bg-gradient-to-r from-yellow-400 to-orange-400 text-gray-900 px-4 py-1 rounded-full text-sm font-bold flex items-center gap-1 shadow-lg">
                        <Star className="w-4 h-4 fill-current" />
                        Populaire
                    </span>
                </div>
            )}

            <div className="p-8">
                {/* Icon */}
                <div className="mb-4">
                    <div className={`w-12 h-12 rounded-xl flex items-center justify-center ${
                        isPopular 
                            ? 'bg-white/20' 
                            : 'bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30'
                    }`}>
                        <svg className={`w-6 h-6 ${isPopular ? 'text-white' : 'text-amber-600 dark:text-amber-400'}`} fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <h3 className="text-2xl font-bold mb-2">{name}</h3>
                <p className={`text-sm mb-6 ${isPopular ? 'text-amber-100' : 'text-gray-600 dark:text-gray-400'}`}>
                    {description}
                </p>

                {/* Prix */}
                <div className="mb-6">
                    <span className="text-5xl font-bold">${price}</span>
                    <span className={isPopular ? 'text-amber-100' : 'text-gray-600'}>/mois</span>
                </div>

                {/* CTA */}
                <button className={`w-full py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 mb-8 shadow-lg hover:shadow-xl ${
                    isPopular
                        ? 'bg-white text-amber-600 hover:bg-gray-100'
                        : 'bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white'
                }`}>
                    Commencer maintenant
                </button>

                {/* Features */}
                <div className="space-y-4">
                    {features.map((feature, idx) => (
                        <div key={idx} className="flex items-start gap-3">
                            <Check className="w-5 h-5 flex-shrink-0 mt-0.5" />
                            <span className="text-sm">{feature}</span>
                        </div>
                    ))}
                </div>
            </div>
        </div>
    );
}

export default function Pricing() {
    const plans = [
        {
            name: 'Starter',
            price: '29',
            description: 'Parfait pour débuter',
            isPopular: false,
            features: [
                'Jusqu\'à 1 000 produits',
                'Paiements en ligne',
                'Dashboard basique',
                'Support email',
                'Rapports mensuels',
            ],
        },
        {
            name: 'Pro',
            price: '99',
            description: 'Pour les boutiques en croissance',
            isPopular: true,
            features: [
                'Produits illimités',
                'Paiements multiples',
                'Analytics avancés',
                'Support prioritaire',
                'Intégrations',
                'API access',
                'Emails personnalisés',
            ],
        },
        {
            name: 'Enterprise',
            price: '299',
            description: 'Pour les grandes équipes',
            isPopular: false,
            features: [
                'Tout de Pro +',
                'Utilisateurs illimités',
                'Domaine personnalisé',
                'Support 24/7',
                'Dédié account manager',
                'Intégrations custom',
                'SLA garanti',
            ],
        },
    ];

    return (
        <section id="pricing" className="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900 transition-colors duration-200">
            <div className="max-w-7xl mx-auto">
                {/* En-tête */}
                <div className="text-center mb-16">
                    <div className="inline-flex items-center justify-center mb-6">
                        <svg className="w-16 h-16 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                        Tarifs simples et transparents
                    </h2>
                    <p className="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Choisissez le plan qui correspond à vos besoins. Pas de frais cachés, annulation à tout moment.
                    </p>
                </div>

                {/* Cartes de tarifs */}
                <div className="grid md:grid-cols-3 gap-8 mb-12">
                    {plans.map((plan, idx) => (
                        <div key={idx} className="relative">
                            <PricingCard
                                name={plan.name}
                                price={plan.price}
                                description={plan.description}
                                features={plan.features}
                                isPopular={plan.isPopular}
                            />
                        </div>
                    ))}
                </div>

                {/* CTA final */}
                <div className="text-center">
                    <div className="inline-flex items-center gap-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg px-6 py-3">
                        <svg className="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p className="text-gray-700 dark:text-gray-300 font-medium">Toutes les facturations incluent une période d'essai gratuite de 14 jours</p>
                    </div>
                </div>
            </div>
        </section>
    );
}
