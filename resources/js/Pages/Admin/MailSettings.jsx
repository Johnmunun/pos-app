import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function MailSettings({ mailSettings, mailHealth }) {
    const [showGuide, setShowGuide] = useState(false);
    const form = useForm({
        enabled: Boolean(mailSettings?.enabled),
        host: mailSettings?.host || '',
        port: mailSettings?.port || 587,
        encryption: mailSettings?.encryption || 'tls',
        username: mailSettings?.username || '',
        password: '',
        from_address: mailSettings?.from_address || '',
        from_name: mailSettings?.from_name || '',
        events: {
            account_activated: Boolean(mailSettings?.events?.account_activated ?? true),
            sale_completed: Boolean(mailSettings?.events?.sale_completed ?? true),
            stock_low: Boolean(mailSettings?.events?.stock_low ?? true),
            stock_expiration: Boolean(mailSettings?.events?.stock_expiration ?? true),
            ecommerce_order: Boolean(mailSettings?.events?.ecommerce_order ?? true),
            sales_monthly_limit: Boolean(mailSettings?.events?.sales_monthly_limit ?? true),
        },
    });

    const testForm = useForm({
        to_email: mailSettings?.from_address || '',
    });

    return (
        <AppLayout
            header={(
                <div>
                    <h2 className="text-2xl font-bold text-gray-900 dark:text-white">Configuration Mail (SMTP)</h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Gelez la configuration SMTP depuis l&apos;interface admin.
                    </p>
                </div>
            )}
        >
            <Head title="Configuration Mail" />

            <div className="py-6 space-y-6">
                <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <div className="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div>
                            <h3 className="text-sm font-semibold text-gray-900 dark:text-white">Mail Health</h3>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Verification automatique de la configuration SMTP enregistree.
                            </p>
                        </div>
                        <span
                            className={`inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold ${
                                mailHealth?.status === 'healthy'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300'
                            }`}
                        >
                            {mailHealth?.status === 'healthy' ? 'Sain' : 'Attention'}
                        </span>
                    </div>

                    <div className="mt-4 grid gap-2 md:grid-cols-3">
                        {[
                            { label: 'Service active', ok: Boolean(mailHealth?.checks?.enabled) },
                            { label: 'Host SMTP', ok: Boolean(mailHealth?.checks?.host) },
                            { label: 'Port SMTP', ok: Boolean(mailHealth?.checks?.port) },
                            { label: 'Username SMTP', ok: Boolean(mailHealth?.checks?.username) },
                            { label: 'Mot de passe SMTP', ok: Boolean(mailHealth?.checks?.password_set) },
                            { label: 'From address', ok: Boolean(mailHealth?.checks?.from_address) },
                        ].map((item) => (
                            <div
                                key={item.label}
                                className={`rounded-lg border px-3 py-2 text-xs ${
                                    item.ok
                                        ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300'
                                        : 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300'
                                }`}
                            >
                                {item.ok ? 'OK' : 'Manquant'} - {item.label}
                            </div>
                        ))}
                    </div>

                    {Array.isArray(mailHealth?.issues) && mailHealth.issues.length > 0 ? (
                        <div className="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-300">
                            <p className="font-semibold mb-1">Points a corriger:</p>
                            <ul className="space-y-1">
                                {mailHealth.issues.map((issue) => (
                                    <li key={issue}>- {issue}</li>
                                ))}
                            </ul>
                        </div>
                    ) : null}
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <div className="flex items-center justify-between mb-4">
                        <p className="text-sm text-gray-500 dark:text-gray-400">
                            Configure SMTP et active les notifications email.
                        </p>
                        <button
                            type="button"
                            onClick={() => setShowGuide(true)}
                            className="inline-flex items-center px-3 py-1.5 rounded-md border border-amber-300 text-amber-700 dark:text-amber-300 dark:border-amber-700 text-xs font-semibold hover:bg-amber-50 dark:hover:bg-amber-900/20"
                        >
                            Guide configuration
                        </button>
                    </div>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(route('admin.mail-settings.update'), { preserveScroll: true });
                        }}
                        className="grid gap-4 md:grid-cols-2"
                    >
                        <label className="md:col-span-2 inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                checked={Boolean(form.data.enabled)}
                                onChange={(e) => form.setData('enabled', e.target.checked)}
                                className="h-4 w-4 rounded border-gray-300"
                            />
                            Activer l&apos;envoi d&apos;emails
                        </label>

                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Host SMTP</label>
                            <input value={form.data.host} onChange={(e) => form.setData('host', e.target.value)} placeholder="Ex: smtp.gmail.com ou smtp-relay.brevo.com" className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Port SMTP</label>
                            <input type="number" value={form.data.port} onChange={(e) => form.setData('port', e.target.value)} placeholder="Ex: 587 (TLS) ou 465 (SSL)" className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Encryption</label>
                            <select value={form.data.encryption} onChange={(e) => form.setData('encryption', e.target.value)} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
                                <option value="none">Aucune</option>
                                <option value="tls">TLS</option>
                                <option value="ssl">SSL</option>
                            </select>
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Username</label>
                            <input value={form.data.username} onChange={(e) => form.setData('username', e.target.value)} placeholder="Ex: noreply@tondomaine.com" className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">Mot de passe SMTP</label>
                            <input type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} placeholder={mailSettings?.password_set ? 'Laisser vide pour conserver (Ex: App password/API key)' : 'Ex: App password/API key SMTP'} className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">From email</label>
                            <input type="email" value={form.data.from_address} onChange={(e) => form.setData('from_address', e.target.value)} placeholder="Ex: support@tondomaine.com" className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" />
                        </div>
                        <div>
                            <label className="text-xs font-semibold uppercase text-gray-500">From nom</label>
                            <input value={form.data.from_name} onChange={(e) => form.setData('from_name', e.target.value)} placeholder="Ex: OmniPOS Support" className="mt-1 w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500" />
                        </div>

                        <div className="md:col-span-2">
                            <p className="text-xs font-semibold uppercase text-gray-500 mb-2">Notifications par email</p>
                            <div className="grid gap-2 md:grid-cols-2">
                                <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" checked={Boolean(form.data.events.account_activated)} onChange={(e) => form.setData('events', { ...form.data.events, account_activated: e.target.checked })} className="h-4 w-4 rounded border-gray-300" />
                                    Activation de compte
                                </label>
                                <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" checked={Boolean(form.data.events.sale_completed)} onChange={(e) => form.setData('events', { ...form.data.events, sale_completed: e.target.checked })} className="h-4 w-4 rounded border-gray-300" />
                                    Vente finalisee (POS)
                                </label>
                                <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" checked={Boolean(form.data.events.stock_low)} onChange={(e) => form.setData('events', { ...form.data.events, stock_low: e.target.checked })} className="h-4 w-4 rounded border-gray-300" />
                                    Stock faible
                                </label>
                                <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" checked={Boolean(form.data.events.stock_expiration)} onChange={(e) => form.setData('events', { ...form.data.events, stock_expiration: e.target.checked })} className="h-4 w-4 rounded border-gray-300" />
                                    Lots en expiration
                                </label>
                                <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 md:col-span-2">
                                    <input type="checkbox" checked={Boolean(form.data.events.ecommerce_order)} onChange={(e) => form.setData('events', { ...form.data.events, ecommerce_order: e.target.checked })} className="h-4 w-4 rounded border-gray-300" />
                                    E-commerce (nouvelles commandes / paiement commande)
                                </label>
                                <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 md:col-span-2">
                                    <input type="checkbox" checked={Boolean(form.data.events.sales_monthly_limit)} onChange={(e) => form.setData('events', { ...form.data.events, sales_monthly_limit: e.target.checked })} className="h-4 w-4 rounded border-gray-300" />
                                    Plafond ventes mensuelles (plan billing)
                                </label>
                            </div>
                        </div>

                        <div className="md:col-span-2">
                            <button type="submit" disabled={form.processing} className="inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 disabled:opacity-50">
                                Enregistrer
                            </button>
                        </div>
                    </form>
                </div>

                <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5">
                    <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-3">Test SMTP</h3>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            testForm.post(route('admin.mail-settings.test'), { preserveScroll: true });
                        }}
                        className="flex flex-col md:flex-row gap-3"
                    >
                        <input
                            type="email"
                            value={testForm.data.to_email}
                            onChange={(e) => testForm.setData('to_email', e.target.value)}
                            placeholder="destinataire@exemple.com"
                            className="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500"
                        />
                        <button type="submit" disabled={testForm.processing} className="inline-flex items-center px-4 py-2 rounded-md bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900 text-sm font-semibold disabled:opacity-50">
                            Envoyer test
                        </button>
                    </form>
                </div>
            </div>
            {showGuide ? (
                <div className="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 shadow-xl">
                        <div className="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-white">Tuto rapide: configurer SMTP</h3>
                            <button
                                type="button"
                                onClick={() => setShowGuide(false)}
                                className="text-sm font-semibold text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                            >
                                Fermer
                            </button>
                        </div>
                        <div className="p-5 space-y-4 text-sm text-gray-700 dark:text-gray-300">
                            <p><strong>1.</strong> Active l&apos;envoi d&apos;emails.</p>
                            <p><strong>2.</strong> Renseigne Host, Port, Encryption, Username, Password SMTP.</p>
                            <p><strong>3.</strong> Mets l&apos;adresse d&apos;envoi: <code>from_address</code> et le nom <code>from_name</code>.</p>
                            <p><strong>4.</strong> Clique <strong>Enregistrer</strong>, puis teste avec <strong>Envoyer test</strong>.</p>
                            <div className="rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-3 space-y-1">
                                <p className="font-semibold text-gray-900 dark:text-white">Exemple Gmail</p>
                                <p>Host: smtp.gmail.com</p>
                                <p>Port: 587</p>
                                <p>Encryption: TLS</p>
                                <p>Username: ton email Gmail</p>
                                <p>Password: mot de passe d&apos;application Google</p>
                            </div>
                            <div className="rounded-lg bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 p-3 space-y-1">
                                <p className="font-semibold text-gray-900 dark:text-white">Exemple Brevo</p>
                                <p>Host: smtp-relay.brevo.com</p>
                                <p>Port: 587</p>
                                <p>Encryption: TLS</p>
                                <p>Username: email de compte Brevo</p>
                                <p>Password: cle SMTP Brevo</p>
                            </div>
                        </div>
                    </div>
                </div>
            ) : null}
        </AppLayout>
    );
}

