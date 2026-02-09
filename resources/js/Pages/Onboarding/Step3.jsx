import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';

export default function Step3({ sessionData }) {
    const { data, setData, post, processing, errors } = useForm({
        company_name: sessionData?.company_name || '',
        address: sessionData?.address || '',
        phone: sessionData?.phone || '',
        company_email: sessionData?.company_email || '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.step3.process'));
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
            <Head title="Informations boutique - Étape 3/4" />
            
            {/* Header fixé */}
            <header className="fixed top-0 left-0 right-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 z-50">
                <div className="max-w-4xl mx-auto px-4 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                <span className="text-white font-bold text-sm">POS</span>
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-gray-900 dark:text-white">POS SaaS</h1>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Inscription marchand</p>
                            </div>
                        </div>
                        
                        <div className="hidden md:block">
                            <div className="flex items-center space-x-2">
                                {[1, 2, 3, 4].map((s) => (
                                    <div
                                        key={s}
                                        className={`w-3 h-3 rounded-full transition-all ${
                                            s <= 3 
                                                ? 'bg-amber-500' 
                                                : 'bg-gray-300 dark:bg-gray-600'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {/* Progress bar */}
            <div className="fixed top-16 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-40">
                <div 
                    className="h-full bg-amber-500 transition-all duration-500 ease-out"
                    style={{ width: '75%' }} // 3/4 = 75%
                />
            </div>

            {/* Contenu scrollable */}
            <main className="pt-20 pb-8">
                <div className="max-w-2xl mx-auto px-4">
                    {/* Stepper */}
                    <OnboardingStepper currentStep={3} totalSteps={4} />
                    
                    {/* Titre */}
                    <div className="text-center mb-8">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            Votre boutique
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400">
                            Ces informations apparaîtront sur vos factures et documents
                        </p>
                    </div>

                    {/* Formulaire */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                        <form onSubmit={submit} className="space-y-6">
                            
                            {/* Nom de la boutique */}
                            <div>
                                <label 
                                    htmlFor="company_name" 
                                    className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                                >
                                    Nom de votre boutique / entreprise *
                                </label>
                                <input
                                    id="company_name"
                                    name="company_name"
                                    type="text"
                                    value={data.company_name}
                                    onChange={(e) => setData('company_name', e.target.value)}
                                    required
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                    placeholder="Ma Boutique En Ligne"
                                />
                                {errors.company_name && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.company_name}</p>
                                )}
                            </div>

                            {/* Adresse */}
                            <div>
                                <label 
                                    htmlFor="address" 
                                    className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                                >
                                    Adresse complète *
                                </label>
                                <textarea
                                    id="address"
                                    name="address"
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    required
                                    rows={3}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                    placeholder="123 Rue Commerciale, Quartier X, Ville"
                                />
                                {errors.address && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.address}</p>
                                )}
                            </div>

                            {/* Téléphone */}
                            <div>
                                <label 
                                    htmlFor="phone" 
                                    className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                                >
                                    Téléphone *
                                </label>
                                <input
                                    id="phone"
                                    name="phone"
                                    type="tel"
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    required
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                    placeholder="+243 999 888 777"
                                />
                                {errors.phone && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.phone}</p>
                                )}
                            </div>

                            {/* Email entreprise (optionnel) */}
                            <div>
                                <label 
                                    htmlFor="company_email" 
                                    className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                                >
                                    Email entreprise (optionnel)
                                </label>
                                <input
                                    id="company_email"
                                    name="company_email"
                                    type="email"
                                    value={data.company_email}
                                    onChange={(e) => setData('company_email', e.target.value)}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                    placeholder="contact@votre-boutique.com"
                                />
                                {errors.company_email && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.company_email}</p>
                                )}
                            </div>

                            {/* Message d'information */}
                            <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                                <div className="flex items-start gap-3">
                                    <svg className="w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                    </svg>
                                    <div>
                                        <h4 className="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1">
                                            À savoir
                                        </h4>
                                        <p className="text-sm text-amber-700 dark:text-amber-300">
                                            Ces informations pourront être modifiées plus tard dans vos paramètres.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Boutons d'action */}
                            <OnboardingNavigationButtons
                                previousRoute={route('onboarding.step2')}
                                nextRoute={route('onboarding.step3.process')}
                                nextLabel="Continuer →"
                                processing={processing}
                            />
                        </form>
                    </div>
                </div>
            </main>
        </div>
    );
}