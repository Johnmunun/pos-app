import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Percent, Plus, Pencil, Trash2 } from 'lucide-react';
import EcommercePageHeader from '@/Components/Ecommerce/EcommercePageHeader';

export default function EcommercePromotionsIndex({ promotions }) {
    const typeLabels = {
        percentage: 'Pourcentage',
        fixed_amount: 'Montant fixe',
        buy_x_get_y: 'Achat X obtenir Y',
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
                <EcommercePageHeader title="Promotions" icon={Percent}>
                    <Button asChild>
                        <Link href={route('ecommerce.promotions.create')} className="inline-flex items-center justify-center gap-2 p-2 sm:px-3 sm:py-2 min-w-[36px] sm:min-w-0" title="Nouvelle promotion">
                            <Plus className="h-4 w-4 shrink-0" />
                            <span className="hidden sm:inline">Nouvelle promotion</span>
                        </Link>
                    </Button>
                </EcommercePageHeader>
            }
        >
            <Head title="Promotions - E-commerce" />
            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-0">
                        {/* Mobile: cartes */}
                        <div className="md:hidden divide-y divide-gray-200 dark:divide-slate-700">
                            {promotions.length === 0 ? (
                                <div className="px-4 py-8 text-center text-gray-500">
                                    Aucune promotion. <Link href={route('ecommerce.promotions.create')} className="text-blue-600 hover:underline">Créer une promotion</Link>
                                </div>
                            ) : (
                                promotions.map((p) => (
                                    <div key={p.id} className="p-4 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                        <div className="flex items-start justify-between gap-2">
                                            <div className="min-w-0">
                                                <p className="font-medium">{p.name}</p>
                                                <p className="text-xs text-gray-500 mt-0.5">
                                                    {typeLabels[p.type] ?? p.type} • {p.type === 'percentage' && p.discount_value != null ? `${p.discount_value}%` : p.type === 'fixed_amount' ? `${p.discount_value} €` : p.type === 'free_shipping' ? 'Gratuit' : '-'}
                                                </p>
                                                <p className="text-xs text-gray-500">{formatDate(p.starts_at)} → {formatDate(p.ends_at)}</p>
                                            </div>
                                            <div className="flex items-center gap-1 shrink-0">
                                                <Badge variant={p.is_active ? 'default' : 'secondary'} className="text-xs">{p.is_active ? 'Actif' : 'Inactif'}</Badge>
                                                <Button variant="ghost" size="sm" asChild><Link href={route('ecommerce.promotions.edit', p.id)}><Pencil className="h-4 w-4" /></Link></Button>
                                                <Button variant="ghost" size="sm" className="text-red-600" onClick={() => { if (confirm('Supprimer cette promotion ?')) router.delete(route('ecommerce.promotions.destroy', p.id)); }}><Trash2 className="h-4 w-4" /></Button>
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
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Nom</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Type</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Réduction</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Période</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Statut</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {promotions.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                                            Aucune promotion. <Link href={route('ecommerce.promotions.create')} className="text-blue-600 hover:underline">Créer une promotion</Link>
                                        </td>
                                    </tr>
                                ) : (
                                    promotions.map((p) => (
                                        <tr key={p.id} className="border-b border-gray-100 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                            <td className="px-4 py-3">
                                                <span className="font-medium">{p.name}</span>
                                            </td>
                                            <td className="px-4 py-3">{typeLabels[p.type] ?? p.type}</td>
                                            <td className="px-4 py-3">
                                                {p.type === 'percentage' && p.discount_value != null
                                                    ? `${p.discount_value}%`
                                                    : p.type === 'fixed_amount' && p.discount_value != null
                                                        ? `${p.discount_value} €`
                                                        : p.type === 'free_shipping'
                                                            ? 'Gratuit'
                                                            : '-'}
                                                {p.minimum_purchase != null && p.minimum_purchase > 0 && (
                                                    <span className="text-gray-500 text-sm ml-1">(min. {p.minimum_purchase} €)</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm">
                                                {formatDate(p.starts_at)} → {formatDate(p.ends_at)}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge variant={p.is_active ? 'default' : 'secondary'}>
                                                    {p.is_active ? 'Actif' : 'Inactif'}
                                                </Badge>
                                                {p.maximum_uses != null && (
                                                    <span className="text-xs text-gray-500 ml-1">
                                                        ({p.used_count}/{p.maximum_uses})
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={route('ecommerce.promotions.edit', p.id)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-red-600 hover:text-red-700"
                                                        onClick={() => {
                                                            if (confirm('Supprimer cette promotion ?')) {
                                                                router.delete(route('ecommerce.promotions.destroy', p.id));
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
