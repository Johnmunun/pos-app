import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';
import OnboardingPageChrome from '@/Components/OnboardingPageChrome';
import { authCardClassName } from '@/Components/AuthPageShell';

export default function Step5({ sessionData }) {
    const { data, setData, post, processing, errors } = useForm({
        start_mode: sessionData?.start_mode || 'empty_store',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.step5.process'));
    };

    return (
        <>
            <Head title="Mode de démarrage" />
            <OnboardingPageChrome currentStep={5}>
                <div className="max-w-2xl mx-auto px-4">
                    <OnboardingStepper currentStep={5} totalSteps={5} />

                    <div className="text-center mb-8">
                        <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">
                            Comment démarrer votre boutique ?
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400 text-sm sm:text-base leading-relaxed">
                            Dernière étape avant la création de votre compte — vous pourrez ensuite régler votre abonnement.
                        </p>
                    </div>

                    <div className={`${authCardClassName} p-6 sm:p-8`}>
                        <form onSubmit={submit} className="space-y-8">
                            <div>
                                <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    Mode de démarrage *
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div
                                        onClick={() => setData('start_mode', 'empty_store')}
                                        className={`
                                            p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200
                                            ${data.start_mode === 'empty_store'
                                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 shadow-md ring-1 ring-amber-500/15'
                                                : 'border-gray-100 dark:border-gray-800 hover:border-amber-300 dark:hover:border-amber-600/50 hover:bg-gray-50/80 dark:hover:bg-gray-800/50'
                                            }
                                        `}
                                    >
                                        <h3 className={`font-semibold mb-1 ${data.start_mode === 'empty_store' ? 'text-amber-700 dark:text-amber-300' : 'text-gray-900 dark:text-gray-100'}`}>
                                            Boutique vide
                                        </h3>
                                        <p className="text-xs text-gray-600 dark:text-gray-400">
                                            Devises de base et structure minimale. Vous ajoutez vos produits ensuite.
                                        </p>
                                    </div>
                                    <div
                                        onClick={() => setData('start_mode', 'preconfigured_store')}
                                        className={`
                                            p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200
                                            ${data.start_mode === 'preconfigured_store'
                                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 shadow-md ring-1 ring-amber-500/15'
                                                : 'border-gray-100 dark:border-gray-800 hover:border-amber-300 dark:hover:border-amber-600/50 hover:bg-gray-50/80 dark:hover:bg-gray-800/50'
                                            }
                                        `}
                                    >
                                        <h3 className={`font-semibold mb-1 ${data.start_mode === 'preconfigured_store' ? 'text-amber-700 dark:text-amber-300' : 'text-gray-900 dark:text-gray-100'}`}>
                                            Boutique pré-configurée
                                        </h3>
                                        <p className="text-xs text-gray-600 dark:text-gray-400">
                                            Pack de démarrage selon votre secteur (catégories, produits, stocks).
                                        </p>
                                    </div>
                                </div>
                                {errors.start_mode && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.start_mode}</p>
                                )}
                            </div>

                            <div className="bg-amber-50/90 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 rounded-2xl p-4 shadow-sm">
                                <p className="text-sm text-amber-800 dark:text-amber-200">
                                    En validant, votre compte est créé. Vous serez ensuite invité à finaliser l’abonnement si nécessaire.
                                </p>
                            </div>

                            <OnboardingNavigationButtons
                                previousRoute={route('onboarding.step4')}
                                nextRoute={route('onboarding.step5.process')}
                                nextLabel="Créer mon compte →"
                                processing={processing}
                                disabled={!data.start_mode}
                            />
                        </form>
                    </div>
                </div>
            </OnboardingPageChrome>
        </>
    );
}
