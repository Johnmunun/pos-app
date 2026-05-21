import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';
import { AuthMarkLink, AuthVisualBackdrop, authCardClassName, authInputClassName } from '@/Components/AuthPageShell';
import { useState } from 'react';

export default function Step4() {
    const { data, setData, post, processing } = useForm({
        idnat: '',
        rccm: '',
    });

    const [skipLegal, setSkipLegal] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.step4.process'));
    };

    const skipStep = () => {
        setSkipLegal(true);
        setTimeout(() => {
            post(route('onboarding.step4.process'));
        }, 300);
    };

    if (skipLegal) {
        return (
            <>
                <Head title="Informations légales" />
                <AuthVisualBackdrop>
                    <div className="text-center max-w-sm">
                        <div className="mx-auto mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-100 dark:bg-amber-900/30">
                            <div className="h-6 w-6 border-2 border-amber-500 border-t-transparent rounded-full animate-spin" />
                        </div>
                        <p className="text-gray-700 dark:text-gray-300 font-medium">Continuons vers la dernière étape…</p>
                    </div>
                </AuthVisualBackdrop>
            </>
        );
    }

    return (
        <>
            <Head title="Informations légales - Étape 4/5" />
            <AuthVisualBackdrop>
                <div className="w-full max-w-md">
                    <AuthMarkLink />

                    <div className="text-center mb-8">
                        <h1 className="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">
                            Informations légales
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400 text-sm sm:text-base leading-relaxed">
                            Ces documents pourront être ajoutés plus tard
                        </p>
                    </div>

                    <OnboardingStepper currentStep={4} totalSteps={5} />

                    <div className={`${authCardClassName} p-8 mt-2`}>
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <label htmlFor="idnat" className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Numéro IDNAT (optionnel)
                                </label>
                                <input
                                    id="idnat"
                                    name="idnat"
                                    type="text"
                                    value={data.idnat}
                                    onChange={(e) => setData('idnat', e.target.value)}
                                    className={authInputClassName}
                                    placeholder="IDNAT1234567890"
                                />
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">Identifiant National pour les Traders</p>
                            </div>

                            <div>
                                <label htmlFor="rccm" className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Numéro RCCM (optionnel)
                                </label>
                                <input
                                    id="rccm"
                                    name="rccm"
                                    type="text"
                                    value={data.rccm}
                                    onChange={(e) => setData('rccm', e.target.value)}
                                    className={authInputClassName}
                                    placeholder="RCCM/BUN/21/12345"
                                />
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">Registre de Commerce et de Crédit Mobilier</p>
                            </div>

                            <div className="bg-amber-50/90 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/60 rounded-2xl p-4 shadow-sm">
                                <div className="flex items-start gap-3">
                                    <svg className="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20" aria-hidden>
                                        <path
                                            fillRule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                            clipRule="evenodd"
                                        />
                                    </svg>
                                    <div>
                                        <h4 className="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-1">Vous pourrez les ajouter plus tard</h4>
                                        <p className="text-sm text-amber-800/95 dark:text-amber-200/90 leading-relaxed">
                                            Ces informations ne sont pas obligatoires pour commencer. Vous pouvez les renseigner dans vos paramètres plus tard.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div className="flex flex-col gap-3 pt-2">
                                <OnboardingNavigationButtons
                                    previousRoute={route('onboarding.step3')}
                                    nextRoute={route('onboarding.step4.process')}
                                    nextLabel="Continuer →"
                                    processing={processing}
                                />

                                <button
                                    type="button"
                                    onClick={skipStep}
                                    disabled={processing}
                                    className="w-full px-4 py-3.5 text-gray-700 dark:text-gray-300 font-semibold rounded-2xl border border-gray-200/90 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all disabled:opacity-50"
                                >
                                    Passer cette étape pour le moment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </AuthVisualBackdrop>
        </>
    );
}
