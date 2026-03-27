import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Pending() {
    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Compte en attente
                </h2>
            }
        >
            <div className="py-6">
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
                            <div className="lg:col-span-2">
                                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
                                    <div className="flex flex-col items-center text-center">
                                        <div className="w-24 h-24 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center mb-6">
                                            <svg className="w-12 h-12 text-amber-600 dark:text-amber-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>

                                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-4">
                                            Validation en cours
                                        </h2>
                                        <p className="text-gray-600 dark:text-gray-400 mb-8 max-w-md">
                                            Paiement recu. Notre equipe examine maintenant vos informations.
                                        </p>

                                        <div className="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-xl p-4 mb-6 w-full max-w-xl text-left">
                                            <p className="text-sm text-emerald-800 dark:text-emerald-200 font-semibold mb-1">
                                                Confirmation par email
                                            </p>
                                            <p className="text-sm text-emerald-700 dark:text-emerald-300">
                                                Un email de confirmation sera envoye a votre adresse apres creation du compte, puis un autre email apres validation du paiement.
                                            </p>
                                        </div>

                                        <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 mb-8 w-full max-w-sm">
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <span className="text-sm font-semibold text-amber-800 dark:text-amber-200">Statut actuel</span>
                                                    <p className="text-amber-700 dark:text-amber-300 font-medium">En attente de validation</p>
                                                </div>
                                                <div className="w-3 h-3 bg-amber-500 rounded-full animate-pulse"></div>
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-1 gap-4 w-full max-w-md">
                                            <Link
                                                href={route('profile.edit')}
                                                className="px-4 py-3 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-gray-600 transition-all text-center"
                                            >
                                                Completer mon profil
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-6">
                                <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-6">
                                    <div className="flex items-center gap-3 mb-3">
                                        <svg className="w-6 h-6 text-blue-500 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <h3 className="font-semibold text-blue-800 dark:text-blue-200">Temps d'attente</h3>
                                    </div>
                                    <p className="text-blue-700 dark:text-blue-300 text-sm">1-2 heures ouvrables</p>
                                </div>

                                <div className="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-4">Besoin d'aide ?</h3>
                                    <a
                                        href="mailto:support@pos-saas.local"
                                        className="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium text-sm"
                                    >
                                        support@pos-saas.local
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}