import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { CheckCircle2, Package, AlertCircle } from 'lucide-react';
import { formatCurrency } from '@/lib/currency';

export default function SaleFinalizeConfirmModal({
    show = false,
    onClose,
    onConfirm,
    loading = false,
    sale = null,
    currency = 'CDF',
}) {
    const paidAmount = sale
        ? Number(sale.paid_amount) > 0
            ? Number(sale.paid_amount)
            : Number(sale.total_amount)
        : 0;

    return (
        <Modal show={show} onClose={onClose} maxWidth="md" closeable={!loading}>
            <div className="p-6 sm:p-7">
                <div className="flex flex-col items-center text-center sm:flex-row sm:items-start sm:text-left gap-4">
                    <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-green-100 dark:bg-green-900/40 ring-8 ring-green-50 dark:ring-green-950/50">
                        <CheckCircle2 className="h-7 w-7 text-green-600 dark:text-green-400" />
                    </div>
                    <div className="flex-1 min-w-0">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            Confirmer cette vente ?
                        </h3>
                        <p className="mt-2 text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                            Le stock sera mis à jour et la vente passera au statut{' '}
                            <span className="font-semibold text-green-700 dark:text-green-400">Terminée</span>.
                        </p>
                    </div>
                </div>

                {sale && (
                    <div className="mt-5 rounded-xl border border-gray-200 dark:border-slate-700 bg-gray-50/80 dark:bg-slate-800/60 px-4 py-3.5 space-y-2">
                        <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500 dark:text-gray-400">
                            {sale.created_at && <span>Date : {sale.created_at}</span>}
                            {sale.id && (
                                <span className="font-mono">
                                    #{String(sale.id).slice(0, 8).toUpperCase()}
                                </span>
                            )}
                        </div>
                        <p className="text-base font-bold text-gray-900 dark:text-gray-100 flex items-center justify-center sm:justify-start gap-2">
                            <Package className="h-4 w-4 text-amber-500 shrink-0" />
                            {formatCurrency(paidAmount, currency)}
                        </p>
                    </div>
                )}

                <div className="mt-4 flex items-start gap-2.5 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200/80 dark:border-amber-800/40 px-3.5 py-3">
                    <AlertCircle className="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <p className="text-xs sm:text-sm text-amber-900 dark:text-amber-100 text-left leading-relaxed">
                        Les quantités vendues seront déduites du stock disponible. Cette action est
                        définitive.
                    </p>
                </div>

                <div className="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        disabled={loading}
                        className="sm:min-w-[7rem]"
                    >
                        Annuler
                    </Button>
                    <Button
                        type="button"
                        onClick={onConfirm}
                        disabled={loading}
                        className="sm:min-w-[9rem] bg-green-600 hover:bg-green-700 text-white shadow-sm shadow-green-600/20"
                    >
                        {loading ? (
                            <span className="inline-flex items-center gap-2">
                                <span className="h-4 w-4 rounded-full border-2 border-white/30 border-t-white animate-spin" />
                                Confirmation...
                            </span>
                        ) : (
                            'Confirmer la vente'
                        )}
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
