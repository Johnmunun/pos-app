import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';

export default function Step5({ sessionData }) {
    const { data, setData, post, processing, errors } = useForm({
        start_mode: sessionData?.start_mode || 'empty_store',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.step5.process'));
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
            <Head title="Mode de démarrage" />

            <header className="fixed top-0 left-0 right-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 z-50">
                <div className="max-w-4xl mx-auto px-4 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                <span className="text-white font-bold text-sm">OP</span>
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-gray-900 dark:text-white">OmniPOS</h1>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Inscription marchand</p>
                            </div>
                        </div>

                        <div className="hidden md:block">
                            <div className="flex items-center space-x-2">
                                {[1, 2, 3, 4, 5].map((s) => (
                                    <div
                                        key={s}
                                        className={`w-3 h-3 rounded-full transition-all ${
                                            s <= 5 ? 'bg-amber-500' : 'bg-gray-300 dark:bg-gray-600'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div className="fixed top-16 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-40">
                <div
                    className="h-full bg-amber-500 transition-all duration-500 ease-out"
                    style={{ width: '100%' }}
                />
            </div>

            <main className="pt-20 pb-8">
                <div className="max-w-2xl mx-auto px-4">
                    <OnboardingStepper currentStep={5} totalSteps={5} />

                    <div className="text-center mb-8">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            Comment démarrer votre boutique ?
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400">
                            Dernière étape avant la création de votre compte — vous pourrez ensuite régler votre abonnement.
                        </p>
                    </div>

                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
                        <form onSubmit={submit} className="space-y-8">
                            <div>
                                <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    Mode de démarrage *
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div
                                        onClick={() => setData('start_mode', 'empty_store')}
                                        className={`
                                            p-4 rounded-xl border-2 cursor-pointer transition-all duration-200
                                            ${data.start_mode === 'empty_store'
                                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 shadow-lg'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700 hover:bg-gray-50 dark:hover:bg-gray-700'
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
                                            p-4 rounded-xl border-2 cursor-pointer transition-all duration-200
                                            ${data.start_mode === 'preconfigured_store'
                                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 shadow-lg'
                                                : 'border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700 hover:bg-gray-50 dark:hover:bg-gray-700'
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

                            <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
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
            </main>
        </div>
    );
}
