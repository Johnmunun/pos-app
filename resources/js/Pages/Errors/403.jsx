import { Head, Link } from '@inertiajs/react';
import { ShieldX, Home, ArrowLeft } from 'lucide-react';

export default function Error403() {
    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-900 flex items-center justify-center px-4 sm:px-6 lg:px-8 transition-colors duration-200">
            <Head title="Accès refusé" />

            <div className="max-w-md w-full text-center">
                {/* Icon */}
                <div className="flex justify-center mb-6">
                    <div className="relative">
                        <div className="absolute inset-0 bg-red-500/20 dark:bg-red-500/10 rounded-full blur-2xl"></div>
                        <div className="relative bg-red-100 dark:bg-red-900/30 p-6 rounded-full">
                            <ShieldX className="h-16 w-16 text-red-600 dark:text-red-400" />
                        </div>
                    </div>
                </div>

                {/* Title */}
                <h1 className="text-6xl font-bold text-gray-900 dark:text-white mb-4">
                    403
                </h1>

                {/* Subtitle */}
                <h2 className="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-3">
                    Accès refusé
                </h2>

                {/* Message */}
                <p className="text-gray-600 dark:text-gray-400 mb-8 leading-relaxed">
                    Vous n'avez pas les permissions nécessaires pour accéder à cette ressource.
                    <br />
                    Veuillez contacter votre administrateur si vous pensez qu'il s'agit d'une erreur.
                </p>

                {/* Actions */}
                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                    <Link
                        href={route('dashboard')}
                        className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors shadow-sm hover:shadow-md font-medium"
                    >
                        <Home className="h-5 w-5" />
                        Retour au tableau de bord
                    </Link>
                    <button
                        onClick={() => window.history.back()}
                        className="inline-flex items-center justify-center gap-2 px-6 py-3 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors shadow-sm hover:shadow-md font-medium"
                    >
                        <ArrowLeft className="h-5 w-5" />
                        Page précédente
                    </button>
                </div>
            </div>
        </div>
    );
}

