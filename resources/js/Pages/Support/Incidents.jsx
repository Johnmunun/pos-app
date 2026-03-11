import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';

export default function Incidents({ incidents }) {
    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Historique des incidents
                    </h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Vue synthétique des incidents déclarés via les tickets.
                    </p>
                </div>
            }
        >
            <Head title="Incidents" />

            <div className="py-6">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {incidents.map((incident) => (
                            <div
                                key={incident.id}
                                className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 space-y-2"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                                        {incident.title}
                                    </h3>
                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                        {incident.created_at}
                                    </span>
                                </div>
                                <div className="flex items-center gap-2 text-xs">
                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                        Module: {incident.module}
                                    </span>
                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-red-100 dark:bg-red-900/40 text-red-800 dark:text-red-300">
                                        Sévérité: {incident.severity}
                                    </span>
                                    <span className="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 dark:bg-amber-900/40 text-amber-800 dark:text-amber-300">
                                        Statut: {incident.status}
                                    </span>
                                </div>
                            </div>
                        ))}
                        {incidents.length === 0 && (
                            <div className="col-span-full text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucun incident enregistré pour le moment.
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

