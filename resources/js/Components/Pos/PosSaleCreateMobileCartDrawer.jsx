import { Minus, Package, Plus, Receipt, Trash2, X } from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

/**
 * Panier mobile en bottom sheet (≤1023px) — le panier latéral reste sur desktop.
 */
export default function PosSaleCreateMobileCartDrawer({
    open,
    onClose,
    cart = [],
    selectedCartIndex,
    onSelectCartIndex,
    onUpdateQuantity,
    onRemoveFromCart,
    fmt,
    formatCartLine,
    total,
    hasCartStockExceeded,
    submitting,
    onCheckout,
}) {
    if (!open) {
        return null;
    }

    return (
        <div className="pos-sale-create__mobile-drawer-root fixed inset-0 z-40 flex flex-col justify-end lg:hidden">
            <button
                type="button"
                className="absolute inset-0 bg-black/45"
                aria-label="Fermer le panier"
                onClick={onClose}
            />
            <div
                className="relative z-10 flex max-h-[min(85dvh,640px)] flex-col rounded-t-2xl bg-white shadow-2xl dark:bg-slate-900"
                role="dialog"
                aria-modal="true"
                aria-label="Panier"
            >
                <div className="flex shrink-0 items-center justify-between border-b border-gray-200 px-4 py-3 dark:border-slate-700">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        Panier ({cart.length})
                    </h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg p-2 text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800"
                        aria-label="Fermer"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto overscroll-contain px-4 py-3">
                    {cart.length === 0 ? (
                        <p className="py-8 text-center text-sm text-gray-500">Panier vide</p>
                    ) : (
                        <div className="space-y-3">
                            {cart.map((item, index) => (
                                <div
                                    key={item.product_id}
                                    className={`flex items-start gap-3 rounded-xl border-2 p-3 ${
                                        index === selectedCartIndex
                                            ? 'border-amber-400 bg-amber-50 dark:border-amber-500 dark:bg-amber-900/20'
                                            : 'border-transparent bg-gray-50 dark:bg-slate-800'
                                    }`}
                                    onClick={() => onSelectCartIndex?.(index)}
                                >
                                    <div className="flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-gray-200 dark:bg-slate-700">
                                        {item.image_url ? (
                                            <img
                                                src={item.image_url}
                                                alt=""
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <Package className="h-6 w-6 text-gray-400" />
                                        )}
                                    </div>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-gray-900 dark:text-white">
                                            {item.name}
                                        </p>
                                        {item.discount_percent > 0 && (
                                            <Badge className="mt-1 border-0 bg-green-100 text-xs text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                -{item.discount_percent}%
                                            </Badge>
                                        )}
                                        <div className="mt-2 flex items-center gap-2">
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    onUpdateQuantity?.(
                                                        item.product_id,
                                                        item.est_divisible !== false
                                                            ? item.quantity - 0.5
                                                            : item.quantity - 1,
                                                    );
                                                }}
                                                className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 dark:bg-slate-600"
                                            >
                                                <Minus className="h-4 w-4" />
                                            </button>
                                            <span className="min-w-[2.5rem] text-center text-sm font-semibold tabular-nums">
                                                {item.quantity}
                                            </span>
                                            <button
                                                type="button"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    onUpdateQuantity?.(
                                                        item.product_id,
                                                        item.est_divisible !== false
                                                            ? item.quantity + 0.5
                                                            : item.quantity + 1,
                                                    );
                                                }}
                                                className="flex h-9 w-9 items-center justify-center rounded-full bg-gray-200 dark:bg-slate-600"
                                            >
                                                <Plus className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </div>
                                    <div className="text-right">
                                        <p className="text-sm font-semibold text-gray-900 dark:text-white">
                                            {formatCartLine
                                                ? formatCartLine(item)
                                                : fmt(item.price * item.quantity)}
                                        </p>
                                        <button
                                            type="button"
                                            onClick={(e) => {
                                                e.stopPropagation();
                                                onRemoveFromCart?.(item.product_id);
                                            }}
                                            className="mt-2 text-red-500"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                <div className="shrink-0 border-t border-gray-200 px-4 py-3 pb-[calc(0.75rem+env(safe-area-inset-bottom,0))] dark:border-slate-700">
                    <div className="mb-3 flex items-center justify-between rounded-xl bg-teal-600 px-4 py-3 text-white">
                        <span className="font-medium">Total</span>
                        <span className="text-xl font-bold tabular-nums">{fmt(total)}</span>
                    </div>
                    {hasCartStockExceeded && (
                        <p className="mb-2 text-sm font-medium text-red-600 dark:text-red-400">
                            Stock négatif interdit. Ajustez les quantités.
                        </p>
                    )}
                    <Button
                        type="button"
                        className="h-12 w-full bg-amber-500 text-base font-semibold hover:bg-amber-600"
                        disabled={cart.length === 0 || submitting || hasCartStockExceeded}
                        onClick={() => {
                            onCheckout?.();
                            onClose?.();
                        }}
                    >
                        <Receipt className="mr-2 h-5 w-5" />
                        {submitting ? 'Traitement…' : 'Finaliser la vente'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
