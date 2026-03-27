import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, router } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import { useEffect, useState } from 'react';

export default function TicketShow({ ticket, replies, canManage }) {
    const { data, setData, post, processing, errors } = useForm({
        message: '',
        attachment: null,
    });

    const [status, setStatus] = useState(ticket.status);
    const [assignedTo, setAssignedTo] = useState(ticket.assigned_to?.id || '');
    const [liveReplies, setLiveReplies] = useState(Array.isArray(replies) ? replies : []);

    // Realtime via Firebase/FCM (foreground): refresh when a support notification arrives.
    useEffect(() => {
        const handler = (evt) => {
            const payload = evt?.detail || {};
            const data = payload?.data || {};
            const type = String(data?.type || '');
            const ticketId = String(data?.ticket_id || '');
            if (type !== 'support.ticket.reply') return;
            if (ticketId !== String(ticket.id)) return;

            router.reload({
                only: ['ticket', 'replies'],
                preserveScroll: true,
            });
        };

        window.addEventListener('fcm-notification', handler);
        return () => window.removeEventListener('fcm-notification', handler);
    }, [ticket.id]);

    // Keep UI in sync when Inertia reloads props
    useEffect(() => {
        setStatus(ticket.status);
    }, [ticket.status]);

    useEffect(() => {
        setLiveReplies(Array.isArray(replies) ? replies : []);
    }, [replies]);

    const submitReply = (e) => {
        e.preventDefault();
        post(route('support.tickets.reply', ticket.id), {
            forceFormData: true,
        });
    };

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        setData('attachment', file || null);
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                            Ticket #{ticket.id} · {ticket.title}
                        </h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Priorité {ticket.priority} · Module {ticket.module}
                        </p>
                    </div>
                </div>
            }
        >
            <Head title={`Ticket ${ticket.id}`} />

            <div className="py-6">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="grid gap-4 md:grid-cols-[2fr,1fr]">
                        {/* Détails du ticket */}
                        <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-4">
                            <div className="flex flex-wrap items-center gap-3 text-sm">
                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    Statut: {ticket.status}
                                </span>
                                <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    Catégorie: {ticket.category}
                                </span>
                                {ticket.user && (
                                    <span className="text-gray-600 dark:text-gray-400">
                                        Créé par <span className="font-medium">{ticket.user.name}</span>
                                    </span>
                                )}
                                {ticket.created_at && (
                                    <span className="text-gray-500 dark:text-gray-400">
                                        le {ticket.created_at}
                                    </span>
                                )}
                            </div>

                            <div className="prose dark:prose-invert max-w-none">
                                <div
                                    className="text-sm text-gray-800 dark:text-gray-100"
                                    dangerouslySetInnerHTML={{ __html: ticket.description }}
                                />
                            </div>

                            {ticket.attachment_url && (
                                <div className="mt-2">
                                    <a
                                        href={ticket.attachment_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-sm text-amber-600 dark:text-amber-400 hover:underline"
                                    >
                                        Voir la pièce jointe
                                    </a>
                                </div>
                            )}
                        </div>

                        {/* Panneau latéral: gestion */}
                        <div className="space-y-4">
                            {canManage && (
                                <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 space-y-3">
                                    <h3 className="text-sm font-semibold text-gray-900 dark:text-white">
                                        Gestion du ticket
                                    </h3>
                                    <div className="space-y-2">
                                        <InputLabel className="text-xs" value="Statut" />
                                        <select
                                            value={status}
                                            onChange={(e) => {
                                                const newStatus = e.target.value;
                                                setStatus(newStatus);
                                                router.post(
                                                    route('support.tickets.status', ticket.id),
                                                    { status: newStatus },
                                                    {
                                                        preserveScroll: true,
                                                        preserveState: true,
                                                    }
                                                );
                                            }}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                                        >
                                            <option value="open">Ouvert</option>
                                            <option value="in_progress">En cours</option>
                                            <option value="resolved">Résolu</option>
                                            <option value="closed">Fermé</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <InputLabel className="text-xs" value="Assigné à" />
                                        <input
                                            type="text"
                                            value={assignedTo}
                                            onChange={(e) => setAssignedTo(e.target.value)}
                                            className="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                                            placeholder="ID utilisateur"
                                        />
                                    </div>
                                </div>
                            )}

                            {/* Historique des réponses */}
                            <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 max-h-80 overflow-y-auto">
                                <h3 className="text-sm font-semibold text-gray-900 dark:text-white mb-3">
                                    Historique
                                </h3>
                                <div className="space-y-3 text-sm">
                                    {liveReplies.map((reply) => (
                                        <div key={reply.id} className="border-b border-gray-100 dark:border-gray-700 pb-3 last:border-0">
                                            <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                                <span>{reply.user?.name || 'Utilisateur'}</span>
                                                <span>{reply.created_at}</span>
                                            </div>
                                            <div className="text-gray-800 dark:text-gray-100 whitespace-pre-line">
                                                {reply.message}
                                            </div>
                                            {reply.attachment_url && (
                                                <a
                                                    href={reply.attachment_url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="mt-1 inline-block text-xs text-amber-600 dark:text-amber-400 hover:underline"
                                                >
                                                    Voir la pièce jointe
                                                </a>
                                            )}
                                        </div>
                                    ))}
                                    {liveReplies.length === 0 && (
                                        <p className="text-xs text-gray-500 dark:text-gray-400">
                                            Aucun commentaire pour le moment.
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Répondre */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                        <form onSubmit={submitReply} className="space-y-4">
                            <div>
                                <InputLabel value="Ajouter une réponse" />
                                <textarea
                                    value={data.message}
                                    onChange={(e) => setData('message', e.target.value)}
                                    rows={4}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                                />
                                <InputError message={errors.message} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel value="Pièce jointe (optionnel)" />
                                <input
                                    type="file"
                                    onChange={handleFileChange}
                                    className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:text-gray-300 dark:file:bg-gray-700 dark:file:text-gray-100"
                                />
                                <InputError message={errors.attachment} className="mt-2" />
                            </div>

                            <div className="flex items-center justify-end">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 transition"
                                >
                                    {processing ? 'Envoi...' : 'Envoyer'}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

