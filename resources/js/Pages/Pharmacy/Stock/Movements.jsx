import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { History, Filter, ArrowLeft } from 'lucide-react';
import ExportButtons from '@/Components/Pharmacy/ExportButtons';

export default function StockMovements({ auth, movements = [], filters = {}, pagination = {} }) {
    const [type, setType] = useState(filters.type || '');
    const [from, setFrom] = useState(filters.from || '');
    const [to, setTo] = useState(filters.to || '');
    const [reference, setReference] = useState(filters.reference || '');

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(route('pharmacy.stock.movements.index'), {
            type: type || undefined,
            from: from || undefined,
            to: to || undefined,
            reference: reference || undefined,
        }, { preserveState: true });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('pharmacy.stock.index')}><ArrowLeft className="h-4 w-4 mr-1" /> Stock</Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Historique des mouvements de stock
                    </h2>
                </div>
            }
        >
            <Head title="Mouvements de stock" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <Card className="mb-6">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="flex items-center">
                                <Filter className="h-5 w-5 mr-2" />
                                Filtres
                            </CardTitle>
                            <ExportButtons
                                pdfUrl={route('pharmacy.exports.movements.pdf', { from, to, type, reference })}
                                excelUrl={route('pharmacy.exports.movements.excel', { from, to, type, reference })}
                                disabled={!movements.length}
                            />
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleFilter} className="flex flex-wrap gap-4 items-end">
                                <div>
                                    <label className="block text-sm font-medium mb-1">Type</label>
                                    <select
                                        className="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        value={type}
                                        onChange={(e) => setType(e.target.value)}
                                    >
                                        <option value="">Tous</option>
                                        <option value="IN">Entrée</option>
                                        <option value="OUT">Sortie</option>
                                        <option value="ADJUSTMENT">Ajustement</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Du</label>
                                    <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Au</label>
                                    <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">Référence</label>
                                    <Input placeholder="SALE-, PO-..." value={reference} onChange={(e) => setReference(e.target.value)} />
                                </div>
                                <Button type="submit">Appliquer</Button>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <History className="h-5 w-5 mr-2" />
                                Mouvements ({pagination.total ?? movements.length})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {movements.length === 0 ? (
                                <p className="text-gray-500 py-8 text-center">Aucun mouvement.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Date</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Produit</th>
                                                <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Quantité</th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Référence</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {movements.map((m) => (
                                                <tr key={m.id}>
                                                    <td className="px-4 py-2 text-sm text-gray-900 dark:text-white">{m.created_at}</td>
                                                    <td className="px-4 py-2">
                                                        <span className={`px-2 py-0.5 rounded text-xs font-medium ${
                                                            m.type === 'IN' ? 'bg-green-100 text-green-800 dark:bg-green-900/30' :
                                                            m.type === 'OUT' ? 'bg-red-100 text-red-800 dark:bg-red-900/30' :
                                                            'bg-gray-100 text-gray-800 dark:bg-gray-700'
                                                        }`}>{m.type}</span>
                                                    </td>
                                                    <td className="px-4 py-2 text-sm">{m.product_name ?? '—'} {m.product_code && `(${m.product_code})`}</td>
                                                    <td className="px-4 py-2 text-sm text-right">{m.quantity}</td>
                                                    <td className="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">{m.reference ?? '—'}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                            {pagination.last_page > 1 && (
                                <div className="mt-4 flex justify-between items-center">
                                    <span className="text-sm text-gray-600">Page {pagination.current_page} / {pagination.last_page}</span>
                                    <div className="flex gap-2">
                                        <Button variant="outline" size="sm" disabled={pagination.current_page <= 1} onClick={() => router.get(route('pharmacy.stock.movements.index'), { ...filters, page: pagination.current_page - 1 })}>Précédent</Button>
                                        <Button variant="outline" size="sm" disabled={pagination.current_page >= pagination.last_page} onClick={() => router.get(route('pharmacy.stock.movements.index'), { ...filters, page: pagination.current_page + 1 })}>Suivant</Button>
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
