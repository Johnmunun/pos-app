import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import {
    Filter,
    RefreshCw,
    FileDown,
    Printer,
    Search,
    ArrowUpCircle,
    ArrowDownCircle,
    AlertCircle,
} from 'lucide-react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import MacWindowModal from '@/Components/Commerce/MacWindowModal';

const typeOptions = [
    { value: '', label: 'Tous' },
    { value: 'IN', label: 'Entrées' },
    { value: 'OUT', label: 'Sorties' },
    { value: 'ADJUSTMENT', label: 'Ajustements' },
];

export default function ProductMovementsMacModal({ isOpen, onClose }) {
    const { shop } = usePage().props;
    const [loading, setLoading] = useState(false);
    const [movements, setMovements] = useState([]);
    const [stats, setStats] = useState({ total_movements: 0, total_in: 0, total_out: 0, total_adjustment: 0 });
    const [filters, setFilters] = useState({
        product_name: '',
        product_code: '',
        type: '',
        from: '',
        to: '',
    });

    const paramsString = useMemo(() => {
        const p = new URLSearchParams();
        if (filters.product_name) p.append('product_name', filters.product_name);
        if (filters.product_code) p.append('product_code', filters.product_code);
        if (filters.type) p.append('type', filters.type);
        if (filters.from) p.append('from', filters.from);
        if (filters.to) p.append('to', filters.to);
        return p.toString();
    }, [filters]);

    const load = useCallback(async () => {
        setLoading(true);
        try {
            const url = `${route('commerce.api.product-movements.index')}?${paramsString}`;
            const { data } = await axios.get(url);
            setMovements(data.movements || []);
            setStats(data.stats || {});
        } catch (e) {
            console.error(e);
            toast.error('Erreur lors du chargement des mouvements.');
        } finally {
            setLoading(false);
        }
    }, [paramsString]);

    useEffect(() => {
        if (isOpen) load();
    }, [isOpen, load]);

    const downloadBlob = (blob, filename) => {
        const blobUrl = window.URL.createObjectURL(new Blob([blob]));
        const a = document.createElement('a');
        a.href = blobUrl;
        a.setAttribute('download', filename);
        document.body.appendChild(a);
        a.click();
        a.remove();
        window.URL.revokeObjectURL(blobUrl);
    };

    const exportPdf = async () => {
        try {
            const url = `${route('commerce.api.product-movements.pdf.global')}?${paramsString}`;
            const res = await axios.get(url, { responseType: 'blob' });
            downloadBlob(res.data, `mouvements_commerce_${new Date().toISOString().slice(0, 10)}.pdf`);
        } catch (e) {
            console.error(e);
            toast.error("Erreur lors de l'export PDF.");
        }
    };

    const exportExcel = async () => {
        try {
            const url = `${route('commerce.api.product-movements.excel')}?${paramsString}`;
            const res = await axios.get(url, { responseType: 'blob' });
            downloadBlob(res.data, `mouvements_commerce_${new Date().toISOString().slice(0, 10)}.xlsx`);
        } catch (e) {
            console.error(e);
            toast.error("Erreur lors de l'export Excel.");
        }
    };

    const typeBadge = (t) => {
        switch (t) {
            case 'IN':
                return (
                    <Badge className="bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                        <ArrowUpCircle className="h-3 w-3 mr-1" />
                        Entrée
                    </Badge>
                );
            case 'OUT':
                return (
                    <Badge className="bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                        <ArrowDownCircle className="h-3 w-3 mr-1" />
                        Sortie
                    </Badge>
                );
            case 'ADJUSTMENT':
                return (
                    <Badge className="bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                        <AlertCircle className="h-3 w-3 mr-1" />
                        Ajustement
                    </Badge>
                );
            default:
                return <Badge variant="secondary">{t}</Badge>;
        }
    };

    return (
        <MacWindowModal
            isOpen={isOpen}
            onClose={onClose}
            title="Mouvements de produits"
            subtitle={`${stats.total_movements ?? 0} mouvement(s)`}
            size="2xl"
        >
            <div className="p-4 sm:p-6 space-y-4">
                {shop?.name && (
                    <div className="text-xs text-gray-500 dark:text-gray-400 -mt-1">
                        Dépôt / Shop: <span className="font-semibold">{shop.name}</span>
                    </div>
                )}
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-3">
                    <div className="lg:col-span-2">
                        <label className="text-xs text-gray-500 dark:text-gray-400">Produit (nom)</label>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                value={filters.product_name}
                                onChange={(e) => setFilters((p) => ({ ...p, product_name: e.target.value }))}
                                placeholder="ex: Feron"
                                className="pl-10"
                            />
                        </div>
                    </div>
                    <div>
                        <label className="text-xs text-gray-500 dark:text-gray-400">SKU</label>
                        <Input
                            value={filters.product_code}
                            onChange={(e) => setFilters((p) => ({ ...p, product_code: e.target.value }))}
                            placeholder="ex: FERON-726"
                        />
                    </div>
                    <div>
                        <label className="text-xs text-gray-500 dark:text-gray-400">Type</label>
                        <select
                            value={filters.type}
                            onChange={(e) => setFilters((p) => ({ ...p, type: e.target.value }))}
                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white px-3 h-10"
                        >
                            {typeOptions.map((o) => (
                                <option key={o.value} value={o.value}>
                                    {o.label}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="text-xs text-gray-500 dark:text-gray-400">Du</label>
                        <Input
                            type="date"
                            value={filters.from}
                            onChange={(e) => setFilters((p) => ({ ...p, from: e.target.value }))}
                        />
                    </div>
                    <div>
                        <label className="text-xs text-gray-500 dark:text-gray-400">Au</label>
                        <Input
                            type="date"
                            value={filters.to}
                            onChange={(e) => setFilters((p) => ({ ...p, to: e.target.value }))}
                        />
                    </div>
                    <div className="lg:col-span-2 flex flex-wrap items-end gap-2">
                        <Button type="button" variant="outline" onClick={load} disabled={loading} className="inline-flex gap-2">
                            <RefreshCw className="h-4 w-4" />
                            Rafraîchir
                        </Button>
                        <Button type="button" variant="outline" onClick={() => setFilters({ product_name: '', product_code: '', type: '', from: '', to: '' })} className="inline-flex gap-2">
                            <Filter className="h-4 w-4" />
                            Reset filtres
                        </Button>
                        <Button type="button" onClick={exportExcel} className="inline-flex gap-2 bg-emerald-600 hover:bg-emerald-700 text-white">
                            <FileDown className="h-4 w-4" />
                            Excel
                        </Button>
                        <Button type="button" onClick={exportPdf} className="inline-flex gap-2 bg-rose-600 hover:bg-rose-700 text-white">
                            <Printer className="h-4 w-4" />
                            PDF
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                    <div className="rounded-xl border border-gray-200 dark:border-slate-700 p-3 bg-white dark:bg-slate-900">
                        <div className="text-xs text-gray-500 dark:text-gray-400">Total</div>
                        <div className="text-lg font-bold text-gray-900 dark:text-gray-100">{stats.total_movements ?? 0}</div>
                    </div>
                    <div className="rounded-xl border border-gray-200 dark:border-slate-700 p-3 bg-white dark:bg-slate-900">
                        <div className="text-xs text-gray-500 dark:text-gray-400">Entrées</div>
                        <div className="text-lg font-bold text-green-600 dark:text-green-400">+{stats.total_in ?? 0}</div>
                    </div>
                    <div className="rounded-xl border border-gray-200 dark:border-slate-700 p-3 bg-white dark:bg-slate-900">
                        <div className="text-xs text-gray-500 dark:text-gray-400">Sorties</div>
                        <div className="text-lg font-bold text-red-600 dark:text-red-400">-{stats.total_out ?? 0}</div>
                    </div>
                    <div className="rounded-xl border border-gray-200 dark:border-slate-700 p-3 bg-white dark:bg-slate-900">
                        <div className="text-xs text-gray-500 dark:text-gray-400">Ajustements</div>
                        <div className="text-lg font-bold text-amber-600 dark:text-amber-400">{stats.total_adjustment ?? 0}</div>
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border border-gray-200 dark:border-slate-700">
                    <table className="min-w-[900px] w-full text-sm bg-white dark:bg-slate-900">
                        <thead className="bg-gray-50 dark:bg-slate-800/70 border-b border-gray-200 dark:border-slate-700">
                            <tr>
                                <th className="text-left py-2 px-3 text-xs font-medium text-gray-600 dark:text-gray-300">Date</th>
                                <th className="text-left py-2 px-3 text-xs font-medium text-gray-600 dark:text-gray-300">Produit</th>
                                <th className="text-left py-2 px-3 text-xs font-medium text-gray-600 dark:text-gray-300">SKU</th>
                                <th className="text-left py-2 px-3 text-xs font-medium text-gray-600 dark:text-gray-300">Catégorie</th>
                                <th className="text-left py-2 px-3 text-xs font-medium text-gray-600 dark:text-gray-300">Type</th>
                                <th className="text-right py-2 px-3 text-xs font-medium text-gray-600 dark:text-gray-300">Qté</th>
                                <th className="text-left py-2 px-3 text-xs font-medium text-gray-600 dark:text-gray-300">Réf.</th>
                                <th className="text-left py-2 px-3 text-xs font-medium text-gray-600 dark:text-gray-300">Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody>
                            {movements.map((m) => (
                                <tr key={m.id} className="border-b border-gray-100 dark:border-slate-800">
                                    <td className="py-2 px-3 text-gray-700 dark:text-gray-200 whitespace-nowrap">{m.created_at_formatted || m.created_at}</td>
                                    <td className="py-2 px-3 text-gray-900 dark:text-gray-100">{m.product_name}</td>
                                    <td className="py-2 px-3 font-mono text-gray-700 dark:text-gray-200">{m.product_code}</td>
                                    <td className="py-2 px-3 text-gray-600 dark:text-gray-300">{m.category_name || '—'}</td>
                                    <td className="py-2 px-3">{typeBadge(m.type)}</td>
                                    <td className="py-2 px-3 text-right font-semibold text-gray-900 dark:text-gray-100">
                                        {m.type === 'IN' ? `+${m.quantity}` : m.type === 'OUT' ? `-${m.quantity}` : m.quantity}
                                    </td>
                                    <td className="py-2 px-3 text-gray-600 dark:text-gray-300">{m.reference || '—'}</td>
                                    <td className="py-2 px-3 text-gray-600 dark:text-gray-300">{m.created_by_name || '—'}</td>
                                </tr>
                            ))}
                            {movements.length === 0 ? (
                                <tr>
                                    <td colSpan={8} className="py-10 text-center text-gray-500 dark:text-gray-400">
                                        {loading ? 'Chargement…' : 'Aucun mouvement.'}
                                    </td>
                                </tr>
                            ) : null}
                        </tbody>
                    </table>
                </div>
            </div>
        </MacWindowModal>
    );
}

