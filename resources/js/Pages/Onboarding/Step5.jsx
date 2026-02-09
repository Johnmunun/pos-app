import { Head } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';

export default function Step5() {
    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <Head title="Confirmation - Étape 5/5" />
            
            <div className="w-full max-w-md">
                {/* Header */}
                <div className="text-center mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Merci !
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Votre inscription est presque terminée
                    </p>
                </div>

                {/* Stepper */}
                <OnboardingStepper currentStep={5} />

                {/* Carte de confirmation */}
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
                    <div className="text-center">
                        {/* Icône de succès */}
                        <div className="w-20 h-20 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-6">
                            <svg className="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>

                        {/* Titre */}
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                            Compte créé avec succès !
                        </h2>

                        {/* Message principal */}
                        <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 mb-6">
                            <div className="flex items-start gap-3">
                                <svg className="w-6 h-6 text-amber-500 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                </svg>
                                <div>
                                    <h3 className="font-semibold text-amber-800 dark:text-amber-200 mb-2">
                                        Votre compte est en attente de validation
                                    </h3>
                                    <p className="text-amber-700 dark:text-amber-300 text-sm">
                                        Notre équipe vérifie vos informations. Vous recevrez un email dès que votre compte sera activé.
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Ce qui suit */}
                        <div className="bg-gray-50 dark:bg-gray-700/50 rounded-xl p-6 mb-6 text-left">
                            <h3 className="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Pendant ce temps, vous pouvez :
                            </h3>
                            <ul className="space-y-2 text-sm text-gray-600 dark:text-gray-400">
                                <li className="flex items-start gap-2">
                                    <svg className="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                    </svg>
                                    <span>Compléter votre profil</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <svg className="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                    </svg>
                                    <span>Explorer vos paramètres</span>
                                </li>
                                <li className="flex items-start gap-2">
                                    <svg className="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                    </svg>
                                    <span>Consulter nos guides d'utilisation</span>
                                </li>
                            </ul>
                        </div>

                        {/* Bouton d'accès */}
                        <a
                            href={route('pending')}
                            className="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98] inline-block"
                        >
                            Accéder à mon compte →
                        </a>

                        {/* Temps d'attente estimé */}
                        <div className="mt-6 text-xs text-gray-500 dark:text-gray-400">
                            Temps d'activation habituel : 1-2 heures ouvrables
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}