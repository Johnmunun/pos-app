import { CheckCircle, Check } from 'lucide-react';

/**
 * Component: Hero
 *
 * Section héro principale avec titre, sous-titre et CTA centrés
 */
export default function Hero() {
    return (
        <section className="pt-32 pb-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900 transition-colors duration-200">
            <div className="max-w-4xl mx-auto text-center">
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
                <p className="text-xl md:text-2xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto mb-10 leading-relaxed">
                    La solution complète pour gérer vos ventes digitales, paiements et clients. Tout ce qu'il vous faut en une seule plateforme.
                </p>

                {/* CTA Buttons côte à côte */}
                <div className="flex flex-col sm:flex-row gap-4 justify-center mb-16">
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

                {/* Avantages rapides */}
                <div className="flex flex-col sm:flex-row justify-center gap-8 text-sm mb-16">
                    {[
                        { text: 'Installation en 5 minutes' },
                        { text: 'Paiements sécurisés' },
                        { text: 'Support 24/7' },
                    ].map((item, idx) => (
                        <div key={idx} className="flex items-center gap-2">
                            <Check className="w-5 h-5 text-emerald-500 dark:text-emerald-400 flex-shrink-0" />
                            <span className="text-gray-700 dark:text-gray-300 font-medium">{item.text}</span>
                        </div>
                    ))}
                </div>

                {/* Image/mockup - optionnel, léger */}
                <div className="mt-20">
                    <div className="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-1">
                        <div className="bg-white rounded-xl p-8">
                            <div className="space-y-4">
                                <div className="flex gap-2">
                                    <div className="w-3 h-3 bg-red-400 rounded-full"></div>
                                    <div className="w-3 h-3 bg-yellow-400 rounded-full"></div>
                                    <div className="w-3 h-3 bg-emerald-400 rounded-full"></div>
                                </div>
                                <div className="h-4 bg-gray-200 rounded w-2/3"></div>
                                <div className="grid grid-cols-4 gap-3 mt-6">
                                    {[1, 2, 3, 4].map((i) => (
                                        <div key={i} className="h-16 bg-gray-100 rounded-lg"></div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}
