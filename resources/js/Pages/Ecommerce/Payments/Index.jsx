import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { CreditCard, Plus, Pencil, Trash2 } from 'lucide-react';

function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2,
    }).format(amount);
}

const TYPE_LABELS = {
    card: 'Carte bancaire',
    wallet: 'Portefeuille',
    bank_transfer: 'Virement',
    cash_on_delivery: 'Paiement à la livraison',
    other: 'Autre',
};

export default function EcommercePaymentsIndex({ methods = [] }) {
    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Méthodes de paiement
                    </h2>
                    <Button asChild size="sm">
                        <Link href={route('ecommerce.payments.create')} className="inline-flex items-center gap-2">
                            <Plus className="h-4 w-4 shrink-0" />
                            Ajouter
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Paiements - E-commerce" />

            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <CreditCard className="h-5 w-5" />
                            {methods.length} méthode(s)
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {methods.length === 0 ? (
                            <div className="text-center py-12">
                                <CreditCard className="h-12 w-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" />
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    Aucune méthode de paiement configurée.
                                </p>
                                <Button asChild>
                                    <Link href={route('ecommerce.payments.create')} className="inline-flex items-center gap-2 justify-center">
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
                                            <div className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                                {m.name}
                                                {m.is_default && (
                                                    <Badge variant="secondary" className="text-xs">Par défaut</Badge>
                                                )}
                                            </div>
                                            <div className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                {TYPE_LABELS[m.type] || m.type}
                                                {(m.fee_percentage > 0 || m.fee_fixed > 0) && (
                                                    <span className="ml-2">
                                                        Frais: {m.fee_percentage}% + {formatCurrency(m.fee_fixed)}
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant={m.is_active ? 'default' : 'secondary'}>
                                                {m.is_active ? 'Actif' : 'Inactif'}
                                            </Badge>
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={route('ecommerce.payments.edit', m.id)}>
                                                    <Pencil className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="text-red-600 hover:text-red-700"
                                                onClick={() => {
                                                    if (confirm('Supprimer cette méthode ?')) {
                                                        router.delete(route('ecommerce.payments.destroy', m.id));
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
        </AppLayout>
    );
}
