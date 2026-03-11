import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function SystemLogs({ logs, filters }) {
    const { data, setData, get } = useForm({
        level: filters.level || '',
        q: filters.q || '',
    });

    const submit = (e) => {
        e.preventDefault();
        get(route('logs.system'), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const download = () => {
        const params = new URLSearchParams();
        if (data.level) params.set('level', data.level);
        if (data.q) params.set('q', data.q);
        window.location.href = route('logs.system.download') + '?' + params.toString();
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                            Logs système
                        </h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Suivi des événements techniques de l&apos;application OmniPOS.
                        </p>
                    </div>
                    <button
                        type="button"
                        onClick={download}
                        className="inline-flex items-center px-4 py-2 rounded-md bg-gray-900 text-white text-sm font-semibold hover:bg-black/80 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white"
                    >
                        Télécharger CSV
                    </button>
                </div>
            }
        >
            <Head title="Logs système" />

            <div className="py-6">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-4">
                    {/* Filtres */}
                    <form
                        onSubmit={submit}
                        className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 flex flex-wrap gap-4 items-end"
                    >
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Niveau
                            </label>
                            <select
                                value={data.level}
                                onChange={(e) => setData('level', e.target.value)}
                                className="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">Tous</option>
                                <option value="info">Info</option>
                                <option value="warning">Warning</option>
                                <option value="error">Error</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>

                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Recherche
                            </label>
                            <input
                                type="text"
                                value={data.q}
                                onChange={(e) => setData('q', e.target.value)}
                                placeholder="Rechercher dans les messages ou modules..."
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
                                        Niveau
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Module
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Utilisateur
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        IP
                                    </th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Message
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                {logs.data.map((log) => (
                                    <tr key={log.id} className="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">
                                            {log.logged_at}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap">
                                            <span
                                                className={`inline-flex px-2 py-0.5 rounded-full font-medium ${
                                                    log.level === 'critical'
                                                        ? 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300'
                                                        : log.level === 'error'
                                                        ? 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300'
                                                        : log.level === 'warning'
                                                        ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300'
                                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                                }`}
                                            >
                                                {log.level}
                                            </span>
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                            {log.module || '-'}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">
                                            {log.user_id || '-'}
                                        </td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {log.ip_address || '-'}
                                        </td>
                                        <td className="px-3 py-2 text-gray-800 dark:text-gray-100 max-w-xl truncate">
                                            {log.message}
                                        </td>
                                    </tr>
                                ))}
                                {logs.data.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={6}
                                            className="px-3 py-4 text-center text-gray-500 dark:text-gray-400"
                                        >
                                            Aucun log enregistré pour le moment.
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

