import React, { useMemo, useState } from 'react';
import { Search, Pencil } from 'lucide-react';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import MacWindowModal from '@/Components/Commerce/MacWindowModal';

export default function ViewProductsModal({
    isOpen,
    onClose,
    products = [],
    categories = [],
    onEditProduct,
}) {
    const [q, setQ] = useState('');
    const [cat, setCat] = useState('');

    const categoryName = (id) => categories.find((c) => c.id === id)?.name ?? '—';

    const filtered = useMemo(() => {
        const s = q.trim().toLowerCase();
        return products.filter((p) => {
            if (cat && p.category_id !== cat) return false;
            if (!s) return true;
            return (
                (p.name || '').toLowerCase().includes(s) ||
                (p.sku || '').toLowerCase().includes(s) ||
                (p.barcode || '').toLowerCase().includes(s)
            );
        });
    }, [products, q, cat]);

    return (
        <MacWindowModal
            isOpen={isOpen}
            onClose={onClose}
            title="View products"
            subtitle={`${filtered.length} produit(s)`}
            size="2xl"
        >
            <div className="p-4 sm:p-6 space-y-4">
                <div className="flex flex-col sm:flex-row gap-3">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                        <Input
                            value={q}
                            onChange={(e) => setQ(e.target.value)}
                            placeholder="Rechercher (nom, SKU, code-barres)…"
                            className="pl-10"
                        />
                    </div>
                    <select
                        value={cat}
                        onChange={(e) => setCat(e.target.value)}
                        className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 h-10"
                    >
                        <option value="">Toutes les catégories</option>
                        {categories.map((c) => (
                            <option key={c.id} value={c.id}>
                                {c.name}
                            </option>
                        ))}
                    </select>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                    {filtered.map((p) => (
                        <div
                            key={p.id}
                            className="rounded-xl border border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4 flex gap-3"
                        >
                            <div className="h-12 w-12 rounded-lg overflow-hidden border border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-800 flex items-center justify-center shrink-0">
                                {p.image_url ? (
                                    <img
                                        src={p.image_url}
                                        alt={p.name}
                                        className="h-full w-full object-cover"
                                        loading="lazy"
                                    />
                                ) : (
                                    <div className="text-xs text-gray-400">—</div>
                                )}
                            </div>
                            <div className="min-w-0 flex-1">
                                <div className="flex items-start justify-between gap-2">
                                    <div className="min-w-0">
                                        <div className="font-semibold text-gray-900 dark:text-gray-100 truncate">
                                            {p.name}
                                        </div>
                                        <div className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            SKU: <span className="font-mono">{p.sku}</span> · {categoryName(p.category_id)}
                                        </div>
                                    </div>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => onEditProduct?.(p)}
                                        className="shrink-0"
                                        title="Modifier"
                                    >
                                        <Pencil className="h-4 w-4" />
                                    </Button>
                                </div>

                                <div className="mt-2 flex flex-wrap items-center gap-2">
                                    <Badge variant={p.is_active ? 'default' : 'secondary'}>
                                        {p.is_active ? 'Actif' : 'Inactif'}
                                    </Badge>
                                    <span className="text-xs text-gray-600 dark:text-gray-300">
                                        Stock: <span className="font-semibold">{p.stock}</span>
                                    </span>
                                    <span className="text-xs text-gray-600 dark:text-gray-300">
                                        Vente: <span className="font-semibold">{p.sale_price_amount}</span> {p.sale_price_currency}
                                    </span>
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {filtered.length === 0 ? (
                    <div className="text-center text-sm text-gray-500 dark:text-gray-400 py-10">
                        Aucun produit trouvé.
                    </div>
                ) : null}
            </div>
        </MacWindowModal>
    );
}

