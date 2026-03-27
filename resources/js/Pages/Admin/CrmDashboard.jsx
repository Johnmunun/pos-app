import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, usePage } from '@inertiajs/react';

function StatCard({ title, value, subtitle }) {
    return (
        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
            <p className="text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                {title}
            </p>
            <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {value}
            </p>
            {subtitle ? (
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {subtitle}
                </p>
            ) : null}
        </div>
    );
}

export default function CrmDashboard({ range, kpis, actionsByModule, topRoutes, recentActions, whatsapp, shops = [], selectedShopId = null }) {
    const { auth } = usePage().props;
    const isRoot = auth?.user?.type === 'ROOT';
    const { data, setData, get, processing } = useForm({
        days: range?.days || 7,
    });

    const whatsappForm = useForm({
        whatsapp_number: whatsapp?.number || '',
        whatsapp_support_enabled: Boolean(whatsapp?.enabled),
        shop_id: selectedShopId || '',
    });

    const submit = (e) => {
        e.preventDefault();
        get(route('crm.dashboard'), { preserveScroll: true, preserveState: true });
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                            Dashboard Admin CRM
                        </h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Tracking d’activité et support (sur {range?.days} jours).
                        </p>
                    </div>
                    <form onSubmit={submit} className="flex items-end gap-3">
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Période
                            </label>
                            <select
                                value={data.days}
                                onChange={(e) => setData('days', e.target.value)}
                                className="mt-1 block w-40 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="1">24h</option>
                                <option value="7">7 jours</option>
                                <option value="14">14 jours</option>
                                <option value="30">30 jours</option>
                            </select>
                        </div>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 disabled:opacity-50"
                        >
                            Actualiser
                        </button>
                    </form>
                </div>
            }
        >
            <Head title="CRM Dashboard" />

            <div className="py-6 space-y-6">
                <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                        Configuration WhatsApp (bouton flottant)
                    </h3>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Ce numéro sera affiché sur les pages client avec un bouton WhatsApp flottant.
                    </p>

                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            whatsappForm.post(route('crm.whatsapp.update'), {
                                preserveScroll: true,
                            });
                        }}
                        className="mt-4 grid gap-4 md:grid-cols-[1fr,auto,auto]"
                    >
                        {isRoot ? (
                            <div className="md:col-span-3">
                                <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                    Boutique (ROOT)
                                </label>
                                <select
                                    value={whatsappForm.data.shop_id}
                                    onChange={(e) => {
                                        const next = e.target.value;
                                        whatsappForm.setData('shop_id', next);
                                        // refresh page to load WhatsApp config for this shop
                                        get(route('crm.dashboard'), { shop_id: next, days: data.days }, { preserveScroll: true, preserveState: false });
                                    }}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="">Sélectionner une boutique...</option>
                                    {shops.map((s) => (
                                        <option key={s.id} value={s.id}>{s.name}</option>
                                    ))}
                                </select>
                            </div>
                        ) : null}
                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Numéro WhatsApp
                            </label>
                            <input
                                type="text"
                                value={whatsappForm.data.whatsapp_number}
                                onChange={(e) => whatsappForm.setData('whatsapp_number', e.target.value)}
                                placeholder="Ex: +243812345678"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            />
                            {whatsappForm.errors.whatsapp_number ? (
                                <p className="mt-1 text-xs text-red-600">{whatsappForm.errors.whatsapp_number}</p>
                            ) : null}
                        </div>

                        <label className="flex items-center gap-2 mt-6">
                            <input
                                type="checkbox"
                                checked={Boolean(whatsappForm.data.whatsapp_support_enabled)}
                                onChange={(e) => whatsappForm.setData('whatsapp_support_enabled', e.target.checked)}
                                className="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                            />
                            <span className="text-sm text-gray-700 dark:text-gray-300">
                                Activer
                            </span>
                        </label>

                        <div className="mt-5 md:mt-6">
                            <button
                                type="submit"
                                disabled={whatsappForm.processing}
                                className="inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 disabled:opacity-50"
                            >
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard title="Actions enregistrées" value={kpis?.totalActions ?? 0} subtitle={`Depuis le ${range?.since || '-'}`} />
                    <StatCard title="Utilisateurs actifs" value={kpis?.uniqueUsers ?? 0} subtitle="Actions non-GET" />
                    <StatCard title="Tickets ouverts" value={kpis?.support?.open ?? 0} subtitle="Support" />
                    <StatCard title="Tickets en cours" value={kpis?.support?.in_progress ?? 0} subtitle="Support" />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                            Activité par module
                        </h3>
                        <div className="space-y-2">
                            {(actionsByModule || []).map((row) => (
                                <div key={row.module} className="flex items-center justify-between text-sm">
                                    <span className="text-gray-700 dark:text-gray-300 capitalize">
                                        {row.module}
                                    </span>
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {row.total}
                                    </span>
                                </div>
                            ))}
                            {(!actionsByModule || actionsByModule.length === 0) ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Aucune donnée.</p>
                            ) : null}
                        </div>
                    </div>

                    <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                            Top routes (actions)
                        </h3>
                        <div className="space-y-2">
                            {(topRoutes || []).map((row) => (
                                <div key={row.route} className="flex items-center justify-between gap-4 text-sm">
                                    <span className="text-gray-700 dark:text-gray-300 truncate">
                                        {row.route}
                                    </span>
                                    <span className="font-semibold text-gray-900 dark:text-white">
                                        {row.total}
                                    </span>
                                </div>
                            ))}
                            {(!topRoutes || topRoutes.length === 0) ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">Aucune donnée.</p>
                            ) : null}
                        </div>
                    </div>
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                    <div className="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                            Activité récente
                        </h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-xs">
                            <thead className="bg-gray-50 dark:bg-gray-900/40">
                                <tr>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Utilisateur</th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Action</th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Module</th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Route</th>
                                    <th className="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP</th>
                                </tr>
                            </thead>
                            <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
                                {(recentActions || []).map((a) => (
                                    <tr key={a.id} className="hover:bg-gray-50 dark:hover:bg-gray-900/40">
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-600 dark:text-gray-300">{a.created_at}</td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{a.user_name || '-'}</td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-900 dark:text-gray-100 font-semibold">{a.action}</td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-700 dark:text-gray-300">{a.module || '-'}</td>
                                        <td className="px-3 py-2 text-gray-700 dark:text-gray-300 max-w-xl truncate">{a.route || '-'}</td>
                                        <td className="px-3 py-2 whitespace-nowrap text-gray-500 dark:text-gray-400">{a.ip_address || '-'}</td>
                                    </tr>
                                ))}
                                {(!recentActions || recentActions.length === 0) ? (
                                    <tr>
                                        <td colSpan={6} className="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                                            Aucune activité.
                                        </td>
                                    </tr>
                                ) : null}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

