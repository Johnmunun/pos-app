export default function PaymentMethods() {
    const methods = [
        {
            name: 'M-Pesa',
            logo: '/images/payments/m-pesa.svg',
        },
        {
            name: 'Orange Money',
            logo: '/images/payments/orange-money.svg',
        },
        {
            name: 'MTN MoMo',
            logo: '/images/payments/mtn-momo.svg',
        },
        {
            name: 'Airtel Money',
            logo: '/images/payments/airtel-money.svg',
        },
        {
            name: 'Moov Money',
            logo: '/images/payments/moov-money.svg',
        },
        {
            name: 'USDT',
            logo: '/images/payments/usdt.svg',
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
                            className="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl p-5 flex items-center justify-center shadow-sm hover:shadow-md transition-shadow"
                            title={m.name}
                        >
                            <img
                                src={m.logo}
                                alt={m.name}
                                className="h-10 w-auto max-w-[160px] opacity-95"
                                loading="lazy"
                            />
                        </div>
                    ))}
                </div>

                <div className="mt-10 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-6 text-center">
                    <p className="text-sm sm:text-base text-amber-900 dark:text-amber-100">
                        Astuce: si vous n’avez pas de carte bancaire, choisissez simplement un <span className="font-semibold">paiement Mobile Money</span> ou <span className="font-semibold">USDT</span> au moment du paiement.
                    </p>
                </div>
            </div>
        </section>
    );
}

