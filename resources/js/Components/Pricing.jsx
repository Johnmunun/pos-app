import { Check, Star } from 'lucide-react';

/**
 * Component: Pricing
 *
 * Section de tarifs avec plans
 */
function PricingCard({ name, price, description, features, isPopular }) {
    return (
        <div className={`rounded-2xl transition-all duration-300 transform hover:scale-105 ${
            isPopular
                ? 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-2xl ring-2 ring-amber-300 -mt-4'
                : 'bg-white text-gray-900 shadow-lg border border-gray-200 hover:border-amber-200'
        }`}>
            {isPopular && (
                <div className="absolute -top-4 left-1/2 transform -translate-x-1/2">
                    <span className="bg-gradient-to-r from-yellow-400 to-orange-400 text-gray-900 px-4 py-1 rounded-full text-sm font-bold flex items-center gap-1">
                        <Star className="w-4 h-4 fill-current" />
                        Populaire
                    </span>
                </div>
            )}

            <div className="p-8">
                <h3 className="text-2xl font-bold mb-2">{name}</h3>
                <p className={`text-sm mb-6 ${isPopular ? 'text-amber-100' : 'text-gray-600'}`}>
                    {description}
                </p>

                {/* Prix */}
                <div className="mb-6">
                    <span className="text-5xl font-bold">${price}</span>
                    <span className={isPopular ? 'text-amber-100' : 'text-gray-600'}>/mois</span>
                </div>

                {/* CTA */}
                <button className={`w-full py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 mb-8 ${
                    isPopular
                        ? 'bg-white text-amber-600 hover:bg-gray-100'
                        : 'bg-amber-500 hover:bg-amber-600 text-white'
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
        <section id="pricing" className="py-20 px-4 sm:px-6 lg:px-8 bg-white">
            <div className="max-w-7xl mx-auto">
                {/* En-tête */}
                <div className="text-center mb-16">
                    <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        Tarifs simples et transparents
                    </h2>
                    <p className="text-lg text-gray-600 max-w-2xl mx-auto">
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
                    <p className="text-gray-600 mb-4">Toutes les facturations incluent une période d'essai gratuite de 14 jours</p>
                </div>
            </div>
        </section>
    );
}
