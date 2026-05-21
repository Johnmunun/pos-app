import { useState } from 'react';
import axios from 'axios';
import toast from 'react-hot-toast';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { Flag, ShieldAlert } from 'lucide-react';
import { usePage } from '@inertiajs/react';

const REASONS = [
    { value: 'scam', label: 'Arnaque ou fraude' },
    { value: 'counterfeit', label: 'Produits contrefaits / trompeurs' },
    { value: 'inappropriate', label: 'Contenu inapproprié' },
    { value: 'spam', label: 'Spam ou publicité abusive' },
    { value: 'other', label: 'Autre' },
];

/**
 * Lien + modal « Signaler cette boutique » (vitrine publique).
 */
export default function StorefrontReportShop({ className = '' }) {
    const { props } = usePage();
    const isPublic = !!props?.storefrontIsPublic;
    const [open, setOpen] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [form, setForm] = useState({
        reason: 'scam',
        details: '',
        reporter_name: '',
        reporter_email: '',
    });

    if (!isPublic) {
        return null;
    }

    const endpoint =
        typeof window !== 'undefined' &&
        window.Ziggy?.routes &&
        Object.prototype.hasOwnProperty.call(window.Ziggy.routes, 'public.storefront.shop-report.store')
            ? route('public.storefront.shop-report.store')
            : '/report-shop';

    const submit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        try {
            await axios.post(endpoint, form);
            toast.success('Signalement envoyé. Merci pour votre vigilance.');
            setOpen(false);
            setForm({ reason: 'scam', details: '', reporter_name: '', reporter_email: '' });
        } catch (err) {
            toast.error(err.response?.data?.message || 'Impossible d\'envoyer le signalement.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <>
            <button
                type="button"
                onClick={() => setOpen(true)}
                className={`inline-flex items-center gap-1.5 text-xs font-medium text-slate-600 dark:text-slate-400 hover:text-rose-600 dark:hover:text-rose-400 transition-colors ${className}`}
            >
                <Flag className="h-3.5 w-3.5" />
                Signaler cette boutique
            </button>

            <Modal show={open} onClose={() => !submitting && setOpen(false)} maxWidth="md">
                <form onSubmit={submit} className="p-6 space-y-4">
                    <div className="flex items-start gap-3">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-rose-100 dark:bg-rose-950/50">
                            <ShieldAlert className="h-5 w-5 text-rose-600 dark:text-rose-400" />
                        </div>
                        <div>
                            <h3 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Signaler cette boutique
                            </h3>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                Les signalements sont examinés par notre équipe. En cas d&apos;abus répétés, la boutique
                                peut être suspendue.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Motif *
                        </label>
                        <select
                            value={form.reason}
                            onChange={(e) => setForm((f) => ({ ...f, reason: e.target.value }))}
                            className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
                            required
                        >
                            {REASONS.map((r) => (
                                <option key={r.value} value={r.value}>
                                    {r.label}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                            Détails (optionnel)
                        </label>
                        <textarea
                            value={form.details}
                            onChange={(e) => setForm((f) => ({ ...f, details: e.target.value }))}
                            rows={4}
                            maxLength={2000}
                            className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
                            placeholder="Décrivez le problème rencontré..."
                        />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                Votre nom (optionnel)
                            </label>
                            <input
                                type="text"
                                value={form.reporter_name}
                                onChange={(e) => setForm((f) => ({ ...f, reporter_name: e.target.value }))}
                                className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
                            />
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1">
                                E-mail (optionnel)
                            </label>
                            <input
                                type="email"
                                value={form.reporter_email}
                                onChange={(e) => setForm((f) => ({ ...f, reporter_email: e.target.value }))}
                                className="w-full rounded-lg border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
                            />
                        </div>
                    </div>

                    <div className="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2">
                        <Button type="button" variant="outline" onClick={() => setOpen(false)} disabled={submitting}>
                            Annuler
                        </Button>
                        <Button type="submit" variant="destructive" disabled={submitting}>
                            {submitting ? 'Envoi...' : 'Envoyer le signalement'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </>
    );
}

/**
 * Bandeau footer réutilisable sur toutes les pages vitrine.
 */
export function StorefrontFooterReportBar({ shopName }) {
    return (
        <div className="border-t border-slate-200/80 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-950/80">
            <div className="max-w-6xl mx-auto px-4 sm:px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 text-center sm:text-left">
                <p className="text-[11px] text-slate-500 dark:text-slate-400">
                    © {new Date().getFullYear()} {shopName || 'Boutique'}. Marketplace sécurisée.
                </p>
                <StorefrontReportShop />
            </div>
        </div>
    );
}
