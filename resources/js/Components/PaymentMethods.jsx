import { Coins, Landmark, Smartphone } from 'lucide-react';

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
        <section id="payments" className="py-20 px-4 sm:px-6 lg:px-8 bg-white dark:bg-gray-900 transition-colors duration-200">
            <div className="max-w-7xl mx-auto">
                <div className="text-center mb-12">
                    <h2 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                        Moyens de paiement
                    </h2>
                    <p className="text-lg text-gray-600 dark:text-gray-400 max-w-3xl mx-auto">
                        Payez facilement via Mobile Money ou crypto. <span className="font-semibold text-gray-900 dark:text-white">Vous pouvez payer sans carte bancaire</span> (M-Pesa, Orange Money, MTN MoMo, Airtel Money, Moov Money ou USDT).
                    </p>
                </div>

                <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                    {methods.map((m) => (
                        <div
                            key={m.name}
                            className="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-4 shadow-sm hover:shadow-md transition-shadow"
                            title={m.name}
                        >
                            {(() => {
                                const Icon = m.icon;
                                return (
                            <div className="w-full flex flex-col items-center text-center gap-2">
                                <Icon className="h-5 w-5 text-gray-500 dark:text-gray-300" />
                                <span className={`inline-flex items-center justify-center min-w-12 h-8 px-3 rounded-full text-xs font-bold ${m.tone}`}>
                                    {m.short}
                                </span>
                                <span className="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {m.name}
                                </span>
                            </div>
                                );
                            })()}
                        </div>
                    ))}
                </div>

                <div className="mt-10 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 text-center">
                    <p className="text-sm sm:text-base text-amber-900 dark:text-amber-100">
                        Astuce: si vous n’avez pas de carte bancaire, choisissez simplement un <span className="font-semibold">paiement Mobile Money</span> ou <span className="font-semibold">USDT</span> au moment du paiement.
                    </p>
                    <div className="mt-4">
                        <span className="inline-flex items-center gap-2 rounded-full border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">
                            <span className="h-2 w-2 rounded-full bg-emerald-500" />
                            Paiements disponibles 24/7
                        </span>
                    </div>
                </div>
            </div>
        </section>
    );
}

