import { Check, Star } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { Link } from '@inertiajs/react';

/**
 * Component: Pricing
 *
 * Section de tarifs avec plans
 */
function PricingCard({ name, currencyCode, monthlyPrice, monthlyEffectivePrice, description, features, isPopular, promoLabel }) {
    const hasPromo = monthlyEffectivePrice !== null && Number(monthlyEffectivePrice) < Number(monthlyPrice);
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
                    <span className="text-5xl font-bold">{currencyCode} {hasPromo ? monthlyEffectivePrice : monthlyPrice}</span>
                    <span className={isPopular ? 'text-amber-100' : 'text-gray-600'}>/mois</span>
                    {hasPromo ? (
                        <div className={`text-xs mt-1 ${isPopular ? 'text-amber-100' : 'text-rose-600 dark:text-rose-400'}`}>
                            <span className="line-through mr-1">{currencyCode} {monthlyPrice}</span>
                            {promoLabel || 'Promo en cours'}
                        </div>
                    ) : null}
                </div>

                {/* CTA */}
                <Link
                    href={route('register')}
                    className={`block w-full text-center py-3 rounded-lg font-semibold transition-all duration-200 transform hover:scale-105 mb-8 shadow-lg hover:shadow-xl ${
                    isPopular
                        ? 'bg-white text-amber-600 hover:bg-gray-100'
                        : 'bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white'
                }`}
                >
                    Commencer maintenant
                </Link>

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
    const [plans, setPlans] = useState([]);

    useEffect(() => {
        let mounted = true;
        axios
            .get(route('api.billing.plans.public'))
            .then(({ data }) => {
                if (!mounted) return;
                setPlans(Array.isArray(data?.data) ? data.data : []);
            })
            .catch(() => {
                if (!mounted) return;
                setPlans([]);
            });
        return () => {
            mounted = false;
        };
    }, []);

    const viewPlans = useMemo(() => {
        const mapped = plans.map((plan) => {
            const featureEntries = Object.entries(plan.features || {});
            const features = featureEntries.map(([code, feature]) => {
                if (!feature?.enabled) return null;
                if (feature.limit !== null && feature.limit !== undefined) {
                    return `${feature.label || code}: ${feature.limit}`;
                }
                return feature.label || code;
            }).filter(Boolean);

            return {
                id: plan.id,
                name: plan.name,
                description: plan.description || '',
                isPopular: String(plan.code || '').toLowerCase() === 'pro',
                currencyCode: plan.pricing?.currency_code || 'USD',
                monthlyPrice: plan.pricing?.monthly ?? 0,
                monthlyEffectivePrice: plan.pricing?.monthly_effective ?? plan.pricing?.monthly ?? 0,
                promoLabel: plan.promotion?.is_active ? plan.promotion?.label : null,
                features,
            };
        });

        return mapped.sort((a, b) => (a.isPopular === b.isPopular ? 0 : a.isPopular ? -1 : 1));
    }, [plans]);

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
                    {viewPlans.length > 0 ? (
                        viewPlans.map((plan) => (
                            <div key={plan.id} className="relative">
                                <PricingCard
                                    name={plan.name}
                                    currencyCode={plan.currencyCode}
                                    monthlyPrice={plan.monthlyPrice}
                                    monthlyEffectivePrice={plan.monthlyEffectivePrice}
                                    description={plan.description}
                                    features={plan.features}
                                    isPopular={plan.isPopular}
                                    promoLabel={plan.promoLabel}
                                />
                            </div>
                        ))
                    ) : (
                        <div className="md:col-span-3 rounded-2xl border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-6 text-center">
                            <p className="text-sm font-medium text-amber-700 dark:text-amber-300">
                                Aucun plan disponible pour le moment.
                            </p>
                            <p className="text-xs text-amber-600 dark:text-amber-400 mt-1">
                                Merci de reessayer un peu plus tard.
                            </p>
                        </div>
                    )}
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
