import { Check, Star } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import { Link } from '@inertiajs/react';
import LandingReveal from './LandingReveal';

/**
 * Component: Pricing
 *
 * Section de tarifs avec plans
 */
function PricingCard({ name, currencyCode, monthlyPrice, monthlyEffectivePrice, description, features, isPopular, promoLabel }) {
    const hasPromo = monthlyEffectivePrice !== null && Number(monthlyEffectivePrice) < Number(monthlyPrice);
    return (
        <div
            className={`rounded-[1.75rem] transition-all duration-300 relative h-full ${
                isPopular
                    ? 'bg-gradient-to-br from-amber-500 to-orange-600 text-white shadow-landing-soft-lg ring-1 ring-white/25 dark:ring-amber-400/20 lg:-translate-y-1'
                    : 'bg-white/95 dark:bg-gray-900/60 backdrop-blur-sm text-gray-900 dark:text-white shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-landing-soft hover:border-amber-200/60 dark:hover:border-amber-500/25'
            }`}
        >
            {isPopular && (
                <div className="absolute -top-3.5 left-1/2 transform -translate-x-1/2 z-10">
                    <span className="inline-flex items-center gap-1.5 bg-white/95 dark:bg-gray-950/90 text-amber-700 dark:text-amber-400 px-4 py-1.5 rounded-full text-xs font-bold shadow-md ring-1 ring-amber-200/50 dark:ring-amber-500/30">
                        <Star className="w-3.5 h-3.5 fill-current" aria-hidden />
                        Populaire
                    </span>
                </div>
            )}

            <div className="p-7 sm:p-8">
                <div className="mb-5">
                    <div
                        className={`w-12 h-12 rounded-2xl flex items-center justify-center ${
                            isPopular ? 'bg-white/15 ring-1 ring-white/20' : 'bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-500/15 dark:to-orange-500/10 ring-1 ring-amber-200/40 dark:ring-amber-500/15'
                        }`}
                    >
                        <svg
                            className={`w-6 h-6 ${isPopular ? 'text-white' : 'text-amber-600 dark:text-amber-400'}`}
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            aria-hidden
                        >
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>

                <h3 className="text-xl sm:text-2xl font-bold tracking-tight mb-2">{name}</h3>
                <p className={`text-sm mb-8 leading-relaxed ${isPopular ? 'text-amber-50/95' : 'text-gray-600 dark:text-gray-400'}`}>
                    {description}
                </p>

                <div className="mb-8">
                    <span className="text-4xl sm:text-5xl font-bold tabular-nums tracking-tight">
                        {currencyCode} {hasPromo ? monthlyEffectivePrice : monthlyPrice}
                    </span>
                    <span className={isPopular ? 'text-amber-100/90' : 'text-gray-500 dark:text-gray-400'}>/mois</span>
                    {hasPromo ? (
                        <div className={`text-xs mt-2 ${isPopular ? 'text-amber-100/90' : 'text-rose-600 dark:text-rose-400'}`}>
                            <span className="line-through mr-1">
                                {currencyCode} {monthlyPrice}
                            </span>
                            {promoLabel || 'Promo en cours'}
                        </div>
                    ) : null}
                </div>

                <Link
                    href={route('register')}
                    className={`block w-full text-center py-3.5 rounded-2xl font-semibold transition-all duration-200 mb-8 active:scale-[0.99] ${
                        isPopular
                            ? 'bg-white text-amber-700 hover:bg-amber-50 shadow-md'
                            : 'bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white shadow-md shadow-amber-500/20 hover:shadow-lg'
                    }`}
                >
                    Commencer maintenant
                </Link>

                <div className="space-y-3.5">
                    {features.map((feature, idx) => (
                        <div key={idx} className="flex items-start gap-3">
                            <Check className={`w-5 h-5 flex-shrink-0 mt-0.5 ${isPopular ? 'text-white' : 'text-amber-500 dark:text-amber-400'}`} aria-hidden />
                            <span className={`text-sm leading-snug ${isPopular ? 'text-white/95' : 'text-gray-600 dark:text-gray-300'}`}>
                                {feature}
                            </span>
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
            const features = featureEntries
                .map(([code, feature]) => {
                if (!feature?.enabled) return null;
                if (feature.limit !== null && feature.limit !== undefined) {
                    return `${feature.label || code}: ${feature.limit}`;
                }
                return feature.label || code;
            })
                .filter(Boolean)
                .filter((line) => {
                    if (typeof line !== 'string') return true;
                    const n = line.toLowerCase();
                    const mentionsTrial = n.includes('essai') || n.includes('trial');
                    const mentionsDuration =
                        /\b14\b/.test(n) || n.includes('jour') || n.includes('day') || n.includes('gratuit');
                    if (mentionsTrial && mentionsDuration) return false;
                    if (mentionsTrial && (n.includes('période') || n.includes('periode'))) return false;
                    return true;
                });

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
        <section id="pricing" className="py-24 sm:py-28 lg:py-32 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-gray-50/80 via-white to-white dark:from-gray-950 dark:via-gray-900 dark:to-gray-900 transition-colors duration-200">
            <div className="max-w-7xl mx-auto">
                <LandingReveal className="text-center max-w-3xl mx-auto mb-14 sm:mb-20">
                    <div className="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-500/15 dark:to-orange-500/10 ring-1 ring-amber-200/50 dark:ring-amber-500/20 mb-8 shadow-sm">
                        <svg className="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-gray-900 dark:text-white mb-5">
                        Tarifs simples et transparents
                    </h2>
                    <p className="text-lg sm:text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
                        Choisissez le plan qui correspond à vos besoins. Pas de frais cachés, annulation à tout moment.
                    </p>
                </LandingReveal>

                <div className="grid md:grid-cols-3 gap-6 lg:gap-8 mb-14 items-stretch">
                    {viewPlans.length > 0 ? (
                        viewPlans.map((plan, idx) => (
                            <LandingReveal key={plan.id} delay={idx * 70}>
                                <div className="relative h-full">
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
                            </LandingReveal>
                        ))
                    ) : (
                        <LandingReveal className="md:col-span-3">
                            <div className="rounded-3xl border border-amber-200/80 dark:border-amber-800/60 bg-amber-50/80 dark:bg-amber-950/25 backdrop-blur-sm p-8 text-center shadow-sm">
                                <p className="text-sm font-semibold text-amber-800 dark:text-amber-200">Aucun plan disponible pour le moment.</p>
                                <p className="text-xs text-amber-700/90 dark:text-amber-400/90 mt-2">Merci de réessayer un peu plus tard.</p>
                            </div>
                        </LandingReveal>
                    )}
                </div>

            </div>
        </section>
    );
}
