import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';

export default function UserConnections({ logins, filters }) {
    const { data, setData, get } = useForm({
        user_id: filters.user_id || '',
        status: filters.status || '',
    });

    const submit = (e) => {
        e.preventDefault();
        get(route('logs.connections'), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Connexions utilisateurs
                    </h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Surveillez les connexions et identifiez les comportements suspects.
                    </p>
                </div>
            }
        >
            <Head title="Connexions utilisateurs" />

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
                                Statut
                            </label>
                            <select
                                value={data.status}
                                onChange={(e) => setData('status', e.target.value)}
                                className="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">Tous</option>
                                <option value="success">Succès</option>
                                <option value="failed">Échec</option>
                            </select>
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
                                        IP
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Navigateur
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Appareil
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Statut
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                {logins.data.map((login) => (
                                    <tr key={login.id} className="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                            {login.logged_in_at}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                            {login.user_id || '-'}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {login.ip_address || '-'}
                                        </td>
                                        <td className="px-3 py-2 text-gray-500 dark:text-gray-400 max-w-xs truncate">
                                            {login.user_agent || '-'}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {login.device || '-'}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap">
                                            <span
                                                className={`inline-flex px-2 py-0.5 rounded-full font-medium ${
                                                    login.status === 'failed'
                                                        ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
                                                        : 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                                                }`}
                                            >
                                                {login.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                                {logins.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-3 py-4 text-center text-gray-500 dark:text-gray-400"
                                        >
                                            Aucune connexion enregistrée.
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

