import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Tag, Plus, Pencil, Trash2 } from 'lucide-react';
import EcommercePageHeader from '@/Components/Ecommerce/EcommercePageHeader';

export default function EcommerceCouponsIndex({ coupons }) {
    const typeLabels = {
        percentage: 'Pourcentage',
        fixed_amount: 'Montant fixe',
        free_shipping: 'Livraison gratuite',
    };

    const formatDate = (d) => {
        if (!d) return '-';
        const date = new Date(d);
        return date.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric' });
    };

    return (
        <AppLayout
            header={
                <EcommercePageHeader title="Coupons" icon={Tag}>
                    <Button asChild>
                        <Link href={route('ecommerce.coupons.create')} className="inline-flex items-center justify-center gap-2 p-2 sm:px-3 sm:py-2 min-w-[36px] sm:min-w-0" title="Nouveau coupon">
                            <Plus className="h-4 w-4 shrink-0" />
                            <span className="hidden sm:inline">Nouveau coupon</span>
                        </Link>
                    </Button>
                </EcommercePageHeader>
            }
        >
            <Head title="Coupons - E-commerce" />
            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-0">
                        {/* Mobile: cartes */}
                        <div className="md:hidden divide-y divide-gray-200 dark:divide-slate-700">
                            {coupons.length === 0 ? (
                                <div className="px-4 py-8 text-center text-gray-500">
                                    Aucun coupon. <Link href={route('ecommerce.coupons.create')} className="text-blue-600 hover:underline">Créer un coupon</Link>
                                </div>
                            ) : (
                                coupons.map((c) => (
                                    <div key={c.id} className="p-4 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <code className="text-sm font-mono bg-gray-100 dark:bg-slate-700 px-2 py-1 rounded">{c.code}</code>
                                                <p className="font-medium mt-1">{c.name}</p>
                                                <p className="text-xs text-gray-500 mt-0.5">
                                                    {typeLabels[c.type] ?? c.type} • {c.type === 'percentage' ? `${c.discount_value}%` : c.type === 'fixed_amount' ? `${c.discount_value} €` : 'Gratuit'}
                                                </p>
                                                <p className="text-xs text-gray-500">{formatDate(c.starts_at)} → {formatDate(c.ends_at)}</p>
                                            </div>
                                            <div className="flex items-center gap-1 shrink-0">
                                                <Badge variant={c.is_active ? 'default' : 'secondary'} className="text-xs">{c.is_active ? 'Actif' : 'Inactif'}</Badge>
                                                <Button variant="ghost" size="sm" asChild><Link href={route('ecommerce.coupons.edit', c.id)}><Pencil className="h-4 w-4" /></Link></Button>
                                                <Button variant="ghost" size="sm" className="text-red-600" onClick={() => { if (confirm('Supprimer ce coupon ?')) router.delete(route('ecommerce.coupons.destroy', c.id)); }}><Trash2 className="h-4 w-4" /></Button>
                                            </div>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                        {/* Desktop: tableau */}
                        <div className="hidden md:block overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-slate-700">
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Code</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Nom</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Type</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Réduction</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Période</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Statut</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {coupons.length === 0 ? (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-8 text-center text-gray-500">
                                            Aucun coupon. <Link href={route('ecommerce.coupons.create')} className="text-blue-600 hover:underline">Créer un coupon</Link>
                                        </td>
                                    </tr>
                                ) : (
                                    coupons.map((c) => (
                                        <tr key={c.id} className="border-b border-gray-100 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                            <td className="px-4 py-3">
                                                <code className="px-2 py-1 bg-gray-100 dark:bg-slate-700 rounded text-sm font-mono">{c.code}</code>
                                            </td>
                                            <td className="px-4 py-3 font-medium">{c.name}</td>
                                            <td className="px-4 py-3">{typeLabels[c.type] ?? c.type}</td>
                                            <td className="px-4 py-3">
                                                {c.type === 'percentage' ? `${c.discount_value}%` : c.type === 'fixed_amount' ? `${c.discount_value} €` : 'Gratuit'}
                                                {c.minimum_purchase != null && c.minimum_purchase > 0 && (
                                                    <span className="text-gray-500 text-sm ml-1">(min. {c.minimum_purchase} €)</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {formatDate(c.starts_at)} → {formatDate(c.ends_at)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={c.is_active ? 'default' : 'secondary'}>
                                                    {c.is_active ? 'Actif' : 'Inactif'}
                                                </Badge>
                                                <span className="text-xs text-gray-500 ml-1">
                                                    ({c.used_count}{c.maximum_uses != null ? `/${c.maximum_uses}` : ''})
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={route('ecommerce.coupons.edit', c.id)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-red-600 hover:text-red-700"
                                                        onClick={() => {
                                                            if (confirm('Supprimer ce coupon ?')) {
                                                                router.delete(route('ecommerce.coupons.destroy', c.id));
                                                            }
                                                        }}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
