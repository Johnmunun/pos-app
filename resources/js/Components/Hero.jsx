import { CheckCircle, Check, ShoppingBag, CreditCard, BarChart } from 'lucide-react';
import { usePage } from '@inertiajs/react';

/**
 * Component: Hero
 *
 * Section héro principale avec titre, sous-titre et CTA centrés
 */
export default function Hero() {
    const { props } = usePage();
    const heroImages = props.heroImages || {};
    const heroMainUrl = heroImages.main || '/images/pos-hero-main.png';
    const heroDevicesUrl = heroImages.devices || '/images/pos-hero-devices.png';

    return (
        <section className="relative overflow-hidden pt-32 pb-24 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-950 transition-colors duration-200">
            {/* Fond hero avec images POS */}
            <div className="pointer-events-none select-none absolute inset-0">
                <div className="absolute inset-0 bg-gradient-to-br from-amber-50 via-white to-gray-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-900" />

                {/* Image principale POS (droite) */}
                <div className="hidden md:block absolute inset-y-0 right-0 w-1/2 lg:w-[52%] opacity-80">
                    <img
                        src={heroMainUrl}
                        alt=""
                        className="h-full w-full object-cover object-center"
                        loading="lazy"
                    />
                </div>

                {/* Image terminaux en bas à droite */}
                <div className="hidden sm:block absolute bottom-0 right-4 md:right-10 lg:right-24 w-40 sm:w-56 md:w-64 lg:w-72 translate-y-1/4 md:translate-y-1/3 opacity-90 drop-shadow-2xl">
                    <img
                        src={heroDevicesUrl}
                        alt=""
                        className="w-full h-auto object-contain"
                        loading="lazy"
                    />
                </div>

                {/* Overlay dégradé pour lisibilité du texte */}
                <div className="absolute inset-0 bg-gradient-to-r from-white/95 via-white/85 to-white/10 dark:from-gray-950/98 dark:via-gray-950/95 dark:to-gray-950/40" />
            </div>

            <div className="relative max-w-4xl mx-auto text-center">
                {/* Colonne texte */}
                <div className="w-full">
                {/* Badge */}
                <div className="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-full text-sm font-medium text-amber-700 dark:text-amber-400 mb-8">
                    <CheckCircle className="w-4 h-4" />
                    Essai gratuit de 14 jours • Aucune carte bancaire requise
                </div>

                {/* Titre principal */}
                <h1 className="text-5xl md:text-6xl lg:text-7xl font-bold text-gray-900 dark:text-white leading-tight mb-6">
                    Votre point de vente
                    <span className="bg-gradient-to-r from-amber-500 to-orange-600 bg-clip-text text-transparent"> en ligne</span>
                </h1>

                {/* Sous-titre */}
                <p className="text-xl md:text-2xl text-gray-600 dark:text-gray-300 max-w-xl lg:max-w-none mx-auto lg:mx-0 mb-10 leading-relaxed">
                    La solution complète pour gérer vos ventes digitales, paiements et clients. Tout ce qu'il vous faut en une seule plateforme.
                </p>

                {/* CTA Buttons côte à côte */}
                <div className="flex flex-col sm:flex-row gap-4 justify-center items-center mb-16">
                    <a 
                        href={route('register')}
                        className="bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white px-8 py-4 rounded-xl font-semibold transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105 active:scale-95"
                    >
                        Démarrer gratuitement
                    </a>
                    <a 
                        href="#features"
                        className="border-2 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-amber-500 dark:hover:border-amber-500 hover:text-amber-600 dark:hover:text-amber-400 bg-white dark:bg-gray-800 px-8 py-4 rounded-xl font-semibold transition-all duration-200"
                    >
                        Découvrir les fonctionnalités
                    </a>
                </div>

                {/* Preuve sociale */}
                <div className="mb-12 flex flex-col items-center gap-3">
                    <div className="flex -space-x-3">
                        {[
                            { label: 'A', tone: 'bg-stone-900 text-white' },
                            { label: 'B', tone: 'bg-stone-700 text-white' },
                            { label: 'C', tone: 'bg-amber-900 text-white' },
                            { label: 'D', tone: 'bg-amber-700 text-white' },
                            { label: 'E', tone: 'bg-orange-700 text-white' },
                            { label: 'F', tone: 'bg-orange-500 text-white' },
                        ].map((avatar, idx) => (
                            <div
                                key={idx}
                                className={`h-10 w-10 rounded-full border-2 border-white dark:border-gray-900 flex items-center justify-center text-xs font-bold shadow-sm ${avatar.tone}`}
                                title="Utilisateur"
                            >
                                {avatar.label}
                            </div>
                        ))}
                    </div>
                    <p className="text-sm sm:text-base text-gray-700 dark:text-gray-300 font-medium">
                        <span className="font-bold text-gray-900 dark:text-white">+ de 150 personnes</span> utilisent deja la plateforme
                    </p>
                </div>

                {/* Avantages rapides */}
                <div className="flex flex-col sm:flex-row justify-center items-center gap-8 text-sm mb-16">
                    {[
                        {
                            icon: ShoppingBag,
                            text: 'Caisse rapide pour commerce & retail',
                        },
                        {
                            icon: CreditCard,
                            text: 'Tous vos paiements au même endroit',
                        },
                        {
                            icon: BarChart,
                            text: 'Stats de ventes en temps réel',
                        },
                    ].map((item, idx) => (
                        <div key={idx} className="flex items-center gap-2">
                            <div className="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 dark:bg-emerald-900/20">
                                <item.icon className="w-4 h-4 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <span className="text-gray-700 dark:text-gray-300 font-medium text-sm text-left">
                                {item.text}
                            </span>
                        </div>
                    ))}
                </div>

                {/* Équipements compatibles */}
                <div className="mb-8 sm:mb-16">
                    <p className="text-sm text-gray-600 dark:text-gray-400 text-center mb-6">
                        Compatible avec vos équipements existants
                    </p>
                    <div className="flex flex-wrap items-center justify-center gap-6">
                        {[
                            { 
                                icon: (
                                    <svg className="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                ),
                                label: 'Imprimante thermique',
                                desc: 'Reçus instantanés'
                            },
                            { 
                                icon: (
                                    <svg className="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                ),
                                label: 'Scanner code-barres',
                                desc: 'Ajout rapide'
                            },
                            { 
                                icon: (
                                    <svg className="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                ),
                                label: 'Terminal de paiement',
                                desc: 'Tous modes'
                            },
                            { 
                                icon: (
                                    <svg className="w-10 h-10 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                    </svg>
                                ),
                                label: 'Tablette & Mobile',
                                desc: 'Multi-appareils'
                            },
                        ].map((item, idx) => (
                            <div key={idx} className="flex flex-col items-center gap-2 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-amber-500 dark:hover:border-amber-500 transition-colors">
                                <div className="w-14 h-14 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-lg flex items-center justify-center">
                                    {item.icon}
                                </div>
                                <p className="text-xs font-semibold text-gray-900 dark:text-white text-center">{item.label}</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400 text-center">{item.desc}</p>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Fin colonne texte */}
                </div>
            </div>
        </section>
    );
}
