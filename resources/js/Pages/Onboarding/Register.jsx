import { Head, useForm } from '@inertiajs/react';
import { useState, useEffect } from 'react';

export default function Register({ currentStep, sectors, businessTypes, sessionData }) {
    const [step, setStep] = useState(currentStep || 1);
    const [showPassword, setShowPassword] = useState(false);

    // Initialiser les données avec sessionData si disponible
    const initialData = {
        // Step 1
        name: sessionData?.name || '',
        email: sessionData?.email || '',
        password: '',
        password_confirmation: '',
        // Step 2
        sector: sessionData?.sector || '',
        business_type: sessionData?.business_type || '',
        // Step 3
        company_name: sessionData?.company_name || '',
        address: sessionData?.address || '',
        phone: sessionData?.phone || '',
        company_email: sessionData?.email || '',
        // Step 4
        idnat: sessionData?.idnat || '',
        rccm: sessionData?.rccm || '',
        tax_id: sessionData?.tax_id || '',
    };

    // Formulaire multi-étape
    const { data, setData, post, processing, errors, reset } = useForm(initialData);

    // Navigation entre étapes
    const nextStep = () => {
        if (step < 4) setStep(step + 1);
    };

    const prevStep = () => {
        if (step > 1) setStep(step - 1);
    };

    // Soumission selon l'étape
    const submitStep = (e) => {
        e.preventDefault();
        
        const routes = {
            1: 'onboarding.step1.process',
            2: 'onboarding.step2.process',
            3: 'onboarding.step3.process',
            4: 'onboarding.step4.process'
        };

        post(route(routes[step]), {
            onSuccess: () => {
                if (step < 4) nextStep();
            }
        });
    };

    // Progression
    const progress = (step / 4) * 100;

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
            <Head title={`Inscription - Étape ${step}/4`} />

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
                                            s <= step 
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
                    style={{ width: `${progress}%` }}
                />
            </div>

            {/* Contenu scrollable */}
            <main className="pt-20 pb-8">
                <div className="max-w-md mx-auto px-4">
                    {/* Titre de l'étape */}
                    <div className="text-center mb-8">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            {step === 1 && 'Création du compte'}
                            {step === 2 && 'Votre activité'}
                            {step === 3 && 'Informations boutique'}
                            {step === 4 && 'Documents (optionnel)'}
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400">
                            Étape {step} sur 4
                        </p>
                    </div>

                    {/* Formulaire */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                        <form onSubmit={submitStep} className="space-y-6">
                            
                            {/* Étape 1 : Compte */}
                            {step === 1 && (
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Nom complet *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            required
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="John Doe"
                                        />
                                        {errors.name && <p className="mt-2 text-sm text-red-600">{errors.name}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Email *
                                        </label>
                                        <input
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            required
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="vous@exemple.com"
                                        />
                                        {errors.email && <p className="mt-2 text-sm text-red-600">{errors.email}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Mot de passe *
                                        </label>
                                        <div className="relative">
                                            <input
                                                type={showPassword ? "text" : "password"}
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                required
                                                className="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                                placeholder="••••••••"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => setShowPassword(!showPassword)}
                                                className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                            >
                                                <svg className="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    {showPassword ? (
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                                    ) : (
                                                        <>
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                        </>
                                                    )}
                                                </svg>
                                            </button>
                                        </div>
                                        {errors.password && <p className="mt-2 text-sm text-red-600">{errors.password}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Confirmer le mot de passe *
                                        </label>
                                        <input
                                            type="password"
                                            value={data.password_confirmation}
                                            onChange={(e) => setData('password_confirmation', e.target.value)}
                                            required
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="••••••••"
                                        />
                                        {errors.password_confirmation && <p className="mt-2 text-sm text-red-600">{errors.password_confirmation}</p>}
                                    </div>
                                </div>
                            )}

                            {/* Étape 2 : Secteur */}
                            {step === 2 && (
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                            Secteur d'activité *
                                        </label>
                                        <div className="grid grid-cols-2 gap-3">
                                            {Object.entries(sectors).map(([key, label]) => (
                                                <div
                                                    key={key}
                                                    onClick={() => setData('sector', key)}
                                                    className={`
                                                        p-4 rounded-xl border-2 cursor-pointer transition-all
                                                        ${data.sector === key
                                                            ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                                            : 'border-gray-200 dark:border-gray-700 hover:border-amber-300'
                                                        }
                                                    `}
                                                >
                                                    <div className="text-center">
                                                        <div className="text-2xl mb-2">
                                                            {key === 'pharmacy' && '💊'}
                                                            {key === 'kiosk' && '🏪'}
                                                            {key === 'supermarket' && '🛒'}
                                                            {key === 'butchery' && '🥩'}
                                                            {key === 'hardware' && '🔧'}
                                                            {key === 'other' && '🏢'}
                                                        </div>
                                                        <span className="font-medium text-gray-900 dark:text-gray-100">
                                                            {label}
                                                        </span>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                        {errors.sector && <p className="mt-2 text-sm text-red-600">{errors.sector}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                            Type de commerce *
                                        </label>
                                        <div className="space-y-2">
                                            {Object.entries(businessTypes).map(([key, label]) => (
                                                <div
                                                    key={key}
                                                    onClick={() => setData('business_type', key)}
                                                    className={`
                                                        p-3 rounded-lg border cursor-pointer transition-all
                                                        ${data.business_type === key
                                                            ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                                            : 'border-gray-200 dark:border-gray-700 hover:border-amber-300'
                                                        }
                                                    `}
                                                >
                                                    <span className="font-medium text-gray-900 dark:text-gray-100">
                                                        {label}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                        {errors.business_type && <p className="mt-2 text-sm text-red-600">{errors.business_type}</p>}
                                    </div>
                                </div>
                            )}

                            {/* Étape 3 : Boutique */}
                            {step === 3 && (
                                <div className="space-y-6">
                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Nom de votre boutique *
                                        </label>
                                        <input
                                            type="text"
                                            value={data.company_name}
                                            onChange={(e) => setData('company_name', e.target.value)}
                                            required
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="Ma Boutique"
                                        />
                                        {errors.company_name && <p className="mt-2 text-sm text-red-600">{errors.company_name}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Adresse complète *
                                        </label>
                                        <textarea
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                            required
                                            rows={3}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="123 Rue Commerciale, Ville"
                                        />
                                        {errors.address && <p className="mt-2 text-sm text-red-600">{errors.address}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Téléphone *
                                        </label>
                                        <input
                                            type="tel"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            required
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="+243 999 888 777"
                                        />
                                        {errors.phone && <p className="mt-2 text-sm text-red-600">{errors.phone}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Email de la boutique (optionnel)
                                        </label>
                                        <input
                                            type="email"
                                            value={data.company_email}
                                            onChange={(e) => setData('company_email', e.target.value)}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="contact@boutique.com"
                                        />
                                        {errors.company_email && <p className="mt-2 text-sm text-red-600">{errors.company_email}</p>}
                                    </div>
                                </div>
                            )}

                            {/* Étape 4 : Légal */}
                            {step === 4 && (
                                <div className="space-y-6">
                                    <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                                        <p className="text-amber-800 dark:text-amber-200 text-sm">
                                            Ces documents peuvent être ajoutés plus tard dans vos paramètres.
                                        </p>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            IDNAT (optionnel)
                                        </label>
                                        <input
                                            type="text"
                                            value={data.idnat}
                                            onChange={(e) => setData('idnat', e.target.value)}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="IDNAT123456789"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            RCCM (optionnel)
                                        </label>
                                        <input
                                            type="text"
                                            value={data.rccm}
                                            onChange={(e) => setData('rccm', e.target.value)}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="RCCM/BUN/21/12345"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                            Numéro fiscal (optionnel)
                                        </label>
                                        <input
                                            type="text"
                                            value={data.tax_id}
                                            onChange={(e) => setData('tax_id', e.target.value)}
                                            className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                            placeholder="TAX123456789"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Boutons de navigation */}
                            <div className="flex gap-3 pt-4">
                                {step > 1 && (
                                    <button
                                        type="button"
                                        onClick={prevStep}
                                        className="flex-1 px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-700 transition-all"
                                    >
                                        ← Précédent
                                    </button>
                                )}
                                
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex-1 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl"
                                >
                                    {processing ? (
                                        <span className="flex items-center justify-center">
                                            <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            {step === 4 ? 'Finalisation...' : 'Suivant →'}
                                        </span>
                                    ) : (
                                        step === 4 ? 'Finaliser l\'inscription' : 'Suivant →'
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>

                    {/* Déjà inscrit */}
                    {step === 1 && (
                        <div className="mt-6 text-center">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Déjà inscrit ?{' '}
                                <a 
                                    href={route('login')} 
                                    className="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-semibold"
                                >
                                    Se connecter
                                </a>
                            </p>
                        </div>
                    )}
                </div>
            </main>
        </div>
    );
}