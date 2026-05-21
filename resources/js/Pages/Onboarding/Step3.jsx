import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';
import OnboardingPageChrome from '@/Components/OnboardingPageChrome';
import { authCardClassName, authInputClassName } from '@/Components/AuthPageShell';
import { useEffect } from 'react';

export default function Step3({ sessionData }) {
    const { data, setData, post, processing, errors } = useForm({
        company_name: sessionData?.company_name || '',
        address: sessionData?.address || '',
        phone: sessionData?.phone || '',
        company_email: sessionData?.company_email || '',
        referral_code: sessionData?.referral_code || '',
    });

    // Pré-remplir le code de parrainage depuis l'URL (?ref=CODE) si présent
    useEffect(() => {
        if (typeof window === 'undefined') return;
        try {
            const params = new URLSearchParams(window.location.search);
            const ref = params.get('ref');
            if (ref && !data.referral_code) {
                setData('referral_code', ref);
            }
        } catch {
            // Ignorer les erreurs de parsing URL
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.step3.process'));
    };

    return (
        <>
            <Head title="Informations boutique" />
            <OnboardingPageChrome currentStep={3}>
                <div className="max-w-2xl mx-auto px-4">
                    {/* Stepper */}
                    <OnboardingStepper currentStep={3} totalSteps={5} />
                    
                    {/* Titre */}
                    <div className="text-center mb-8">
                        <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">
                            Votre boutique
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400 text-sm sm:text-base leading-relaxed">
                            Ces informations apparaîtront sur vos factures et documents
                        </p>
                    </div>

                    <div className={`${authCardClassName} p-6 sm:p-8`}>
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
                                    className={authInputClassName}
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
                                    className={authInputClassName}
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
                                    className={authInputClassName}
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
                                    className={authInputClassName}
                                    placeholder="contact@votre-boutique.com"
                                />
                                {errors.company_email && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.company_email}</p>
                                )}
                            </div>

                            {/* Code de parrainage / invitation (optionnel) */}
                            <div>
                                <label
                                    htmlFor="referral_code"
                                    className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                                >
                                    Code de parrainage (optionnel)
                                </label>
                                <input
                                    id="referral_code"
                                    name="referral_code"
                                    type="text"
                                    value={data.referral_code}
                                    onChange={(e) => setData('referral_code', e.target.value)}
                                    className={authInputClassName}
                                    placeholder="Ex: ABCD1234"
                                />
                                {errors.referral_code && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.referral_code}</p>
                                )}
                                <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                    Si vous avez été invité, entrez ici le code de votre parrain. Laissez vide si vous n&apos;en avez pas.
                                </p>
                            </div>

                            {/* Message d'information */}
                            <div className="bg-amber-50/90 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 rounded-2xl p-4 shadow-sm">
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
            </OnboardingPageChrome>
        </>
    );
}