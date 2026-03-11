import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';

export default function SystemStatus({ services, modules }) {
    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Statut système
                    </h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Vue d&apos;ensemble de l&apos;état des services OmniPOS.
                    </p>
                </div>
            }
        >
            <Head title="Statut système" />

            <div className="py-6">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* KPIs */}
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                            <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Services opérationnels
                            </p>
                            <p className="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {services.filter((s) => s.status === 'operational').length}/{services.length}
                            </p>
                        </div>
                        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                            <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Modules actifs
                            </p>
                            <p className="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-400">
                                {modules.filter((m) => m.active).length}/{modules.length}
                            </p>
                        </div>
                        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                            <p className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Incident critique en cours
                            </p>
                            <p className="mt-2 text-2xl font-bold text-gray-900 dark:text-gray-100">
                                0
                            </p>
                        </div>
                    </div>

                    {/* Services */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                            Statut des services
                        </h3>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {services.map((service, idx) => (
                                <div
                                    key={idx}
                                    className="flex items-center justify-between rounded-xl border border-gray-100 dark:border-gray-700 px-3 py-2 bg-gray-50 dark:bg-gray-900/40"
                                >
                                    <div>
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {service.name}
                                        </p>
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Uptime {service.uptime}
                                        </p>
                                    </div>
                                    <span
                                        className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${
                                            service.status === 'operational'
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                : 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-300'
                                        }`}
                                    >
                                        {service.status === 'operational' ? 'Opérationnel' : 'Dégradé'}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Modules */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                            Modules actifs
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            {modules.map((module, idx) => (
                                <span
                                    key={idx}
                                    className={`inline-flex items-center px-3 py-1 rounded-full text-xs font-medium ${
                                        module.active
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                                            : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                    }`}
                                >
                                    {module.name}
                                </span>
                            ))}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

