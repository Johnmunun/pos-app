import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';

export default function UserActions({ activities, filters }) {
    const { data, setData, get } = useForm({
        user_id: filters.user_id || '',
        module: filters.module || '',
        q: filters.q || '',
    });

    const submit = (e) => {
        e.preventDefault();
        get(route('logs.actions'), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Historique des actions
                    </h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Traçabilité des actions importantes effectuées par les utilisateurs.
                    </p>
                </div>
            }
        >
            <Head title="Historique des actions" />

            <div className="py-6">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-4">
                    {/* Filtres */}
                    <form
                        onSubmit={submit}
                        className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 flex flex-wrap gap-4 items-end"
                    >
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                ID utilisateur
                            </label>
                            <input
                                type="text"
                                value={data.user_id}
                                onChange={(e) => setData('user_id', e.target.value)}
                                className="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Module
                            </label>
                            <input
                                type="text"
                                value={data.module}
                                onChange={(e) => setData('module', e.target.value)}
                                placeholder="ex: pharmacy, hardware..."
                                className="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>

                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Recherche
                            </label>
                            <input
                                type="text"
                                value={data.q}
                                onChange={(e) => setData('q', e.target.value)}
                                placeholder="Action, route..."
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>

                        <button
                            type="submit"
                            className="ml-auto inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700"
                        >
                            Filtrer
                        </button>
                    </form>

                    {/* Tableau */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                            <thead className="bg-gray-50 dark:bg-gray-900/40">
                                <tr>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Date
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Utilisateur
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Action
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Module
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Route
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        IP
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                {activities.data.map((activity) => (
                                    <tr key={activity.id} className="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                            {activity.created_at}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                            {activity.user_id || '-'}
                                        </td>
                                        <td className="px-3 py-2 text-gray-800 dark:text-gray-100">
                                            {activity.action}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                            {activity.module || '-'}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {activity.route || '-'}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {activity.ip_address || '-'}
                                        </td>
                                    </tr>
                                ))}
                                {activities.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-3 py-4 text-center text-gray-500 dark:text-gray-400"
                                        >
                                            Aucune activité enregistrée.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

