import AppSeoHead from '@/Components/AppSeoHead';
import { Link } from '@inertiajs/react';

/**
 * En-tête fixe + barre de progression + fond — étapes onboarding 2–5
 */
export default function OnboardingPageChrome({ currentStep = 1, totalSteps = 5, children }) {
    const pct = Math.min(100, Math.max(0, (currentStep / totalSteps) * 100));

    return (
        <div className="min-h-screen relative overflow-x-hidden bg-gradient-to-b from-amber-50/85 via-white to-white dark:from-gray-950 dark:via-gray-950 dark:to-gray-900 transition-colors">
            <AppSeoHead pageSeo={{ title: 'Inscription', noindex: true }} />
            <div className="pointer-events-none absolute inset-0">
                <div className="absolute -top-16 right-1/3 h-80 w-80 rounded-full bg-amber-400/15 blur-3xl dark:bg-amber-500/8" />
                <div className="absolute bottom-20 left-10 h-64 w-64 rounded-full bg-orange-200/20 blur-3xl dark:bg-orange-600/8" />
            </div>

            <header className="fixed top-0 left-0 right-0 z-50 border-b border-gray-200/80 dark:border-gray-800/80 bg-white/75 dark:bg-gray-950/80 backdrop-blur-xl shadow-sm">
                <div className="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between">
                    <Link
                        href="/"
                        className="flex items-center gap-3 rounded-xl -ml-1 pr-2 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/60 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950"
                    >
                        <div className="w-9 h-9 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-md shadow-amber-500/20">
                            <span className="text-white font-bold text-xs">OP</span>
                        </div>
                        <div className="text-left">
                            <p className="text-base font-bold tracking-tight text-gray-900 dark:text-white leading-tight">OmniPOS</p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">Inscription marchand</p>
                        </div>
                    </Link>

                    <div className="hidden md:flex items-center gap-1.5" aria-hidden>
                        {Array.from({ length: totalSteps }, (_, i) => i + 1).map((s) => (
                            <div
                                key={s}
                                className={`h-2 w-2 rounded-full transition-colors duration-300 ${
                                    s <= currentStep ? 'bg-gradient-to-r from-amber-500 to-orange-500' : 'bg-gray-200 dark:bg-gray-700'
                                }`}
                            />
                        ))}
                    </div>
                </div>
            </header>

            <div className="fixed top-16 left-0 right-0 h-1 bg-gray-200/90 dark:bg-gray-800 z-40">
                <div
                    className="h-full bg-gradient-to-r from-amber-500 to-orange-500 transition-all duration-500 ease-out shadow-sm shadow-amber-500/20"
                    style={{ width: `${pct}%` }}
                />
            </div>

            <main className="relative z-0 pt-20 pb-12">{children}</main>
        </div>
    );
}
