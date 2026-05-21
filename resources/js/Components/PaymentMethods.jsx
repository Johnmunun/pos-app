import { Coins, Landmark, Smartphone } from 'lucide-react';
import LandingReveal from './LandingReveal';

export default function PaymentMethods() {
    const methods = [
        {
            name: 'M-Pesa',
            short: 'MPS',
            icon: Smartphone,
            tone: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300',
        },
        {
            name: 'Orange Money',
            short: 'ORG',
            icon: Smartphone,
            tone: 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-300',
        },
        {
            name: 'MTN MoMo',
            short: 'MTN',
            icon: Smartphone,
            tone: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
        },
        {
            name: 'Airtel Money',
            short: 'AIR',
            icon: Smartphone,
            tone: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
        },
        {
            name: 'Moov Money',
            short: 'MOV',
            icon: Landmark,
            tone: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
        },
        {
            name: 'USDT',
            short: 'USDT',
            icon: Coins,
            tone: 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-300',
        },
    ];

    return (
        <section
            id="payments"
            className="py-24 sm:py-28 lg:py-32 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900 transition-colors duration-200"
        >
            <div className="max-w-7xl mx-auto">
                <LandingReveal className="text-center max-w-3xl mx-auto mb-14 sm:mb-16">
                    <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-gray-900 dark:text-white mb-5">
                        Moyens de paiement
                    </h2>
                    <p className="text-lg sm:text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
                        Payez facilement via Mobile Money ou crypto.{' '}
                        <span className="font-semibold text-gray-900 dark:text-white">
                            Vous pouvez payer sans carte bancaire
                        </span>{' '}
                        (M-Pesa, Orange Money, MTN MoMo, Airtel Money, Moov Money ou USDT).
                    </p>
                </LandingReveal>

                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
                    {methods.map((m, idx) => (
                        <LandingReveal key={m.name} delay={idx * 40}>
                            <div
                                className="group h-full rounded-2xl sm:rounded-3xl border border-gray-100 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-800/40 backdrop-blur-sm p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-amber-200/60 dark:hover:border-amber-500/20 transition-all duration-300 hover:-translate-y-0.5"
                                title={m.name}
                            >
                                {(() => {
                                    const Icon = m.icon;
                                    return (
                                        <div className="w-full flex flex-col items-center text-center gap-2.5">
                                            <Icon className="h-5 w-5 text-gray-500 dark:text-gray-400 group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors" />
                                            <span
                                                className={`inline-flex items-center justify-center min-w-12 h-8 px-3 rounded-full text-xs font-bold ${m.tone}`}
                                            >
                                                {m.short}
                                            </span>
                                            <span className="text-xs sm:text-sm font-semibold text-gray-800 dark:text-gray-100 leading-tight">
                                                {m.name}
                                            </span>
                                        </div>
                                    );
                                })()}
                            </div>
                        </LandingReveal>
                    ))}
                </div>

                <LandingReveal className="mt-10 sm:mt-12" delay={120}>
                    <div className="rounded-3xl border border-amber-200/70 dark:border-amber-500/20 bg-gradient-to-br from-amber-50/90 to-orange-50/40 dark:from-amber-950/40 dark:to-gray-900/40 backdrop-blur-sm p-6 sm:p-8 text-center shadow-sm">
                        <p className="text-sm sm:text-base text-amber-950/90 dark:text-amber-100/95 leading-relaxed max-w-2xl mx-auto">
                            Astuce : si vous n’avez pas de carte bancaire, choisissez simplement un{' '}
                            <span className="font-semibold">paiement Mobile Money</span> ou{' '}
                            <span className="font-semibold">USDT</span> au moment du paiement.
                        </p>
                        <div className="mt-5 flex justify-center">
                            <span className="inline-flex items-center gap-2 rounded-full border border-emerald-200/80 dark:border-emerald-800/80 bg-emerald-50/90 dark:bg-emerald-950/30 px-4 py-2 text-xs font-semibold text-emerald-800 dark:text-emerald-300">
                                <span className="h-2 w-2 rounded-full bg-emerald-500 motion-safe:animate-pulse" />
                                Paiements disponibles 24/7
                            </span>
                        </div>
                    </div>
                </LandingReveal>
            </div>
        </section>
    );
}
