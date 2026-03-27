import { Head } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, Download, Sparkles } from 'lucide-react';

export default function PaymentSuccess({ title = 'Paiement réussi', order, digital_items = [], has_physical_items = false, support }) {
    return (
        <>
            <Head title={title} />
            <style>{`
                @keyframes payment-success-pop {
                    0% { transform: scale(0.85); opacity: 0; }
                    55% { transform: scale(1.06); opacity: 1; }
                    100% { transform: scale(1); opacity: 1; }
                }
                @keyframes payment-success-ring {
                    0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.45); }
                    70% { box-shadow: 0 0 0 14px rgba(16, 185, 129, 0); }
                    100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
                }
                .payment-success-icon {
                    animation: payment-success-pop 0.75s cubic-bezier(0.34, 1.56, 0.64, 1) both,
                        payment-success-ring 1.4s ease-out 0.35s 1;
                }
            `}</style>
            <div className="min-h-screen flex items-center justify-center bg-gradient-to-b from-slate-50 via-white to-slate-50 dark:from-slate-950 dark:via-slate-950 dark:to-slate-900 px-4 py-10">
                <div className="w-full max-w-2xl rounded-3xl border border-slate-200/70 dark:border-slate-800 bg-white/90 dark:bg-slate-900/70 shadow-2xl shadow-slate-200/60 dark:shadow-slate-950/50 p-7 sm:p-9 animate-in fade-in zoom-in-95 duration-500">
                    <div className="flex flex-col items-center text-center gap-3">
                        <span className="payment-success-icon inline-flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 dark:bg-emerald-950/35 ring-1 ring-emerald-200/70 dark:ring-emerald-900/40">
                            <CheckCircle2 className="h-9 w-9 text-emerald-600 dark:text-emerald-400" />
                        </span>

                        <p className="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 dark:text-emerald-300 animate-in fade-in slide-in-from-bottom-2 duration-500 delay-150">
                            <Sparkles className="h-4 w-4 shrink-0" />
                            Félicitations !
                        </p>

                        <h1 className="text-2xl font-extrabold tracking-tight text-slate-900 dark:text-white">{title}</h1>

                        <p className="text-slate-600 dark:text-slate-300">
                            Votre commande{order?.order_number ? ` ${order.order_number}` : ''} est confirmée.
                        </p>
                    </div>

                    <div className="mt-7 space-y-4">
                        <div className="rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/60 dark:bg-slate-950/20 p-4 sm:p-5">
                            <h2 className="text-sm font-semibold text-slate-900 dark:text-white mb-3">
                                Vos produits numériques
                            </h2>

                            {Array.isArray(digital_items) && digital_items.length > 0 ? (
                                <div className="space-y-3">
                                    {digital_items.map((it) => (
                                        <div
                                            key={it.id}
                                            className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-2xl border border-slate-200/70 dark:border-slate-800 bg-white/70 dark:bg-slate-950/30 p-4"
                                        >
                                            <div className="min-w-0">
                                                <div className="font-semibold text-slate-900 dark:text-white truncate">
                                                    {it.product_name || 'Produit numérique'}
                                                </div>
                                                {it.expires_at ? (
                                                    <div className="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                                        Lien valable jusqu’au {new Date(it.expires_at).toLocaleString('fr-FR')}
                                                    </div>
                                                ) : null}
                                            </div>
                                            <a href={it.download_url} target="_blank" rel="noopener noreferrer" className="shrink-0">
                                                <Button className="h-11 rounded-2xl font-semibold gap-2 bg-emerald-600 hover:bg-emerald-700">
                                                    <Download className="h-5 w-5" />
                                                    Télécharger
                                                </Button>
                                            </a>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-sm text-slate-600 dark:text-slate-300">
                                    Aucun produit numérique détecté pour cette commande.
                                </p>
                            )}
                        </div>

                        {has_physical_items ? (
                            <div className="rounded-2xl border border-amber-200/70 dark:border-amber-900/40 bg-amber-50/80 dark:bg-amber-950/25 p-4 sm:p-5">
                                <p className="text-sm font-semibold text-amber-900 dark:text-amber-200">
                                    Les produits physiques seront validés par le vendeur (statut : en attente).
                                </p>
                            </div>
                        ) : null}

                        <p className="text-xs text-slate-500 dark:text-slate-400 text-center">
                            {support?.message || 'En cas de problème, contactez l’administrateur.'}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}
