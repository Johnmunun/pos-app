import { Head } from '@inertiajs/react';
import { Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

export default function Pending({ auth }) {
    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
            <Head title="Compte en attente de validation" />
            
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h1 className="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                            Compte en attente de validation
                        </h1>
                        <p className="text-xl text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                            Votre compte est en cours de vérification par notre équipe
                        </p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                        {/* Carte principale */}
                        <div className="lg:col-span-2">
                            <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
                                <div className="flex flex-col items-center text-center">
                                    {/* Icône d'attente */}
                                    <div className="w-24 h-24 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mb-6">
                                        <svg className="w-12 h-12 text-amber-600 dark:text-amber-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>

                                    {/* Message principal */}
                                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                        Validation en cours
                                    </h2>
                                    <p className="text-gray-600 dark:text-gray-400 mb-8 max-w-md">
                                        Notre équipe examine vos informations. Vous recevrez un email de confirmation dès que votre compte sera activé.
                                    </p>

                                    {/* Statut */}
                                    <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-8 w-full max-w-sm">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <span className="text-sm font-semibold text-amber-800 dark:text-amber-200">Statut actuel</span>
                                                <p className="text-amber-700 dark:text-amber-300 font-medium">En attente de validation</p>
                                            </div>
                                            <div className="w-3 h-3 bg-amber-500 rounded-full animate-pulse"></div>
                                        </div>
                                    </div>

                                    {/* Actions disponibles */}
                                    <div className="grid grid-cols-1 gap-4 w-full max-w-md">
                                        <Link
                                            href={route('profile.edit')}
                                            className="px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-all text-center"
                                        >
                                            Compléter mon profil
                                        </Link>
                                        <Link
                                            href={route('profile.edit')}
                                            className="px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-all text-center"
                                        >
                                            Profil
                                        </Link>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Sidebar d'information */}
                        <div className="space-y-6">
                            {/* Temps estimé */}
                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                <div className="flex items-center gap-3 mb-3">
                                    <svg className="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h3 className="font-semibold text-blue-800 dark:text-blue-200">Temps d'attente</h3>
                                </div>
                                <p className="text-blue-700 dark:text-blue-300 text-sm">
                                    1-2 heures ouvrables
                                </p>
                                <p className="text-blue-600 dark:text-blue-400 text-xs mt-1">
                                    Du lundi au vendredi, 9h-17h
                                </p>
                            </div>

                            {/* Ce que vous pouvez faire */}
                            <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                <h3 className="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                                    <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Pendant ce temps
                                </h3>
                                <ul className="space-y-3">
                                    <li className="flex items-start gap-3">
                                        <svg className="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                        </svg>
                                        <span className="text-sm text-gray-600 dark:text-gray-400">Compléter votre profil</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <svg className="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                        </svg>
                                        <span className="text-sm text-gray-600 dark:text-gray-400">Explorer les paramètres</span>
                                    </li>
                                    <li className="flex items-start gap-3">
                                        <svg className="w-5 h-5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                        </svg>
                                        <span className="text-sm text-gray-600 dark:text-gray-400">Consulter la documentation</span>
                                    </li>
                                </ul>
                            </div>

                            {/* Support */}
                            <div className="bg-gray-50 dark:bg-gray-700/50 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                <h3 className="font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                                    <svg className="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192L5.636 18.364M12 2.25a9.75 9.75 0 100 19.5 9.75 9.75 0 000-19.5zM8.25 12a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0z"></path>
                                    </svg>
                                    Besoin d'aide ?
                                </h3>
                                <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Notre équipe est disponible pour répondre à vos questions.
                                </p>
                                <a 
                                    href="mailto:support@pos-saas.local" 
                                    className="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium text-sm flex items-center gap-1"
                                >
                                    support@pos-saas.local
                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}