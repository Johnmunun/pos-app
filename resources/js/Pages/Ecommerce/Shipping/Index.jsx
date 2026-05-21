import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Truck, Plus, Pencil, Trash2 } from 'lucide-react';
import { cardShell, pageY } from '@/lib/layoutClasses';

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
}

const TYPE_LABELS = {
    flat_rate: 'Tarif fixe',
    weight_based: 'Selon le poids',
    price_based: 'Selon le montant',
    free: 'Gratuit',
};

export default function EcommerceShippingIndex({ methods = [] }) {
    return (
        <AppLayout
            header={
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between w-full min-w-0">
                    <div className="min-w-0">
                        <h2 className="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white tracking-tight">
                            Méthodes de livraison
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1.5 hidden sm:block">
                            Tarifs et zones pour la vitrine e-commerce.
                        </p>
                    </div>
                    <Button asChild size="sm" className="shrink-0 w-full sm:w-auto">
                        <Link href={route('ecommerce.shipping.create')} className="inline-flex items-center gap-2">
                            <Plus className="h-4 w-4 shrink-0" />
                            Ajouter
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Livraisons - E-commerce" />

            <div className={pageY}>
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <Card className={cardShell}>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Truck className="h-5 w-5" />
                            {methods.length} méthode(s)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {methods.length === 0 ? (
                            <div className="text-center py-12">
                                <Truck className="h-12 w-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    Aucune méthode de livraison configurée.
                                </p>
                                <Button asChild>
                                    <Link href={route('ecommerce.shipping.create')} className="inline-flex items-center gap-2">
                                        <Plus className="h-4 w-4 shrink-0" />
                                        Ajouter une méthode
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {methods.map((m) => (
                                    <div
                                        key={m.id}
                                        className="flex items-center justify-between p-4 rounded-lg border border-gray-200 dark:border-slate-700"
                                    >
                                        <div>
                                            <div className="font-medium text-gray-900 dark:text-white">{m.name}</div>
                                            <div className="text-sm text-gray-500 dark:text-gray-400 flex gap-4 mt-1">
                                                <span>{TYPE_LABELS[m.type] || m.type}</span>
                                                <span>{m.type === 'free' ? 'Gratuit' : `${formatCurrency(m.base_cost)}`}</span>
                                                {m.free_shipping_threshold && (
                                                    <span>Gratuit dès {formatCurrency(m.free_shipping_threshold)}</span>
                                                )}
                                                {(m.estimated_days_min || m.estimated_days_max) && (
                                                    <span>
                                                        {m.estimated_days_min ?? '?'}-{m.estimated_days_max ?? '?'} jours
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant={m.is_active ? 'default' : 'secondary'}>
                                                {m.is_active ? 'Actif' : 'Inactif'}
                                            </Badge>
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={route('ecommerce.shipping.edit', m.id)} className="inline-flex items-center justify-center">
                                                    <Pencil className="h-4 w-4 shrink-0" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-red-600 hover:text-red-700 inline-flex items-center justify-center"
                                                onClick={() => {
                                                    if (confirm('Supprimer cette méthode ?')) {
                                                        router.delete(route('ecommerce.shipping.destroy', m.id));
                                                    }
                                                }}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
            </div>
        </AppLayout>
    );
}
