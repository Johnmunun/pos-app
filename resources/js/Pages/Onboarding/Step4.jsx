import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';
import { useState } from 'react';

export default function Step4() {
    const { data, setData, post, processing } = useForm({
        idnat: '',
        rccm: '',
    });

    const [skipLegal, setSkipLegal] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.step4'));
    };

    const skipStep = () => {
        setSkipLegal(true);
        // Attendre un peu pour l'animation puis continuer
        setTimeout(() => {
            post(route('onboarding.step4'));
        }, 300);
    };

    if (skipLegal) {
        return (
            <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center px-4">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-amber-500 mx-auto mb-4"></div>
                    <p className="text-gray-600 dark:text-gray-400">Continuons vers la dernière étape...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <Head title="Informations légales - Étape 4/5" />
            
            <div className="w-full max-w-md">
                {/* Header */}
                <div className="text-center mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Informations légales
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Ces documents pourront être ajoutés plus tard
                    </p>
                </div>

                {/* Stepper */}
                <OnboardingStepper currentStep={4} totalSteps={4} />

                {/* Formulaire */}
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
                    <form onSubmit={submit} className="space-y-6">
                        {/* IDNAT */}
                        <div>
                            <label 
                                htmlFor="idnat" 
                                className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                            >
                                Numéro IDNAT (optionnel)
                            </label>
                            <input
                                id="idnat"
                                name="idnat"
                                type="text"
                                value={data.idnat}
                                onChange={(e) => setData('idnat', e.target.value)}
                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                placeholder="IDNAT1234567890"
                            />
                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Identifiant National pour les Traders
                            </p>
                        </div>

                        {/* RCCM */}
                        <div>
                            <label 
                                htmlFor="rccm" 
                                className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                            >
                                Numéro RCCM (optionnel)
                            </label>
                            <input
                                id="rccm"
                                name="rccm"
                                type="text"
                                value={data.rccm}
                                onChange={(e) => setData('rccm', e.target.value)}
                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                placeholder="RCCM/BUN/21/12345"
                            />
                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Registre de Commerce et de Crédit Mobilier
                            </p>
                        </div>

                        {/* Message d'information */}
                        <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4">
                            <div className="flex items-start gap-3">
                                <svg className="w-5 h-5 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                                <div>
                                    <h4 className="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1">
                                        Vous pourrez les ajouter plus tard
                                    </h4>
                                    <p className="text-sm text-amber-700 dark:text-amber-300">
                                        Ces informations ne sont pas obligatoires pour commencer. Vous pouvez les renseigner dans vos paramètres plus tard.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Boutons d'action */}
                        <div className="flex flex-col gap-3 pt-4">
                            <OnboardingNavigationButtons
                                previousRoute={route('onboarding.step3')}
                                nextRoute={route('onboarding.step4')}
                                nextLabel="Continuer →"
                                processing={processing}
                            />
                            
                            <button
                                type="button"
                                onClick={skipStep}
                                disabled={processing}
                                className="w-full px-4 py-3 text-gray-600 dark:text-gray-400 font-semibold rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700 transition-all"
                            >
                                Passer cette étape pour le moment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}