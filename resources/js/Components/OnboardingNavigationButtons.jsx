import { Link } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Clock, Loader2 } from 'lucide-react';

export default function OnboardingNavigationButtons({
    previousRoute = null,
    nextRoute = null,
    nextLabel = 'Continuer →',
    processing = false,
    disabled = false,
    userStatus = null, // Recevoir le statut de l'utilisateur
    nextButtonProps = {},
    prevButtonProps = {}
}) {
    // Si le statut est "pending", on désactive les actions et on montre un message
    const actualStatus = userStatus || (typeof window !== 'undefined' && window.$page?.props?.auth?.user?.status);
    if (actualStatus === 'pending') {
        return (
            <div className="flex flex-col items-center gap-4 py-6">
                <div className="w-12 h-12 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                    <Clock className="w-6 h-6 text-amber-600 dark:text-amber-400" />
                </div>
                <p className="text-center text-gray-600 dark:text-gray-400 font-medium">
                    Compte en attente de validation
                </p>
                <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 animate-pulse">
                    <div className="bg-gray-400 dark:bg-gray-600 h-2 rounded-full w-3/4 animate-pulse"></div>
                </div>
            </div>
        );
    }

    return (
        <div className="flex flex-col sm:flex-row gap-3 pt-4 w-full">
            {previousRoute && (
                <div className="w-full sm:w-auto flex-1">
                    <Link
                        href={previousRoute}
                        className="w-full sm:w-auto flex-1 px-4 py-3.5 border border-gray-200/90 dark:border-gray-700 text-gray-800 dark:text-gray-200 font-semibold rounded-2xl hover:bg-gray-50 dark:hover:bg-gray-800/80 transition-all text-center inline-flex items-center justify-center gap-2"
                        {...prevButtonProps}
                    >
                        <ArrowLeft className="w-4 h-4 shrink-0 sm:w-5 sm:h-5" aria-hidden />
                        Précédent
                    </Link>
                </div>
            )}
            
            {nextRoute && (
                <div className="w-full sm:w-auto flex-1">
                    <button
                        type="submit"
                        disabled={processing || disabled}
                        className="w-full sm:hidden flex-1 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold py-3.5 px-4 rounded-2xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-amber-500/20 hover:shadow-lg flex items-center justify-center gap-2 active:scale-[0.99]"
                        {...nextButtonProps}
                    >
                        {processing ? (
                            <>
                                <Loader2 className="w-4 h-4 animate-spin" />
                                Traitement...
                            </>
                        ) : (
                            <>
                                {nextLabel.includes('→') ? (
                                    <>
                                        <span>Continuer</span>
                                        <ArrowRight className="w-4 h-4" />
                                    </>
                                ) : (
                                    nextLabel
                                )}
                            </>
                        )}
                    </button>
                    
                    {/* Bouton suivant icône seule sur mobile */}
                    <button
                        type="submit"
                        disabled={processing || disabled}
                        className="w-full hidden sm:flex items-center justify-center px-4 py-3.5 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold rounded-2xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-amber-500/20 hover:shadow-lg active:scale-[0.99]"
                        {...nextButtonProps}
                    >
                        {processing ? (
                            <Loader2 className="w-5 h-5 animate-spin" />
                        ) : (
                            <ArrowRight className="w-5 h-5" />
                        )}
                        <span className="sr-only">Continuer</span>
                    </button>
                </div>
            )}
        </div>
    );
}