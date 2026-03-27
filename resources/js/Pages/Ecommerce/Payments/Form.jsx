import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ArrowLeft, CreditCard } from 'lucide-react';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';

export default function EcommercePaymentsForm({ method }) {
    const isEdit = !!method?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        name: method?.name ?? '',
        code: method?.code ?? '',
        type: method?.type ?? 'cash_on_delivery',
        fee_percentage: method?.fee_percentage ?? 0,
        fee_fixed: method?.fee_fixed ?? 0,
        is_active: method?.is_active ?? true,
        is_default: method?.is_default ?? false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            put(route('ecommerce.payments.update', method.id), {
                onSuccess: () => toast.success('Méthode mise à jour'),
                onError: () => toast.error('Erreur'),
            });
        } else {
            post(route('ecommerce.payments.store'), {
                onSuccess: () => toast.success('Méthode créée'),
                onError: () => toast.error('Erreur'),
            });
        }
    };

    const handleClose = () => {
        window.history.length > 1
            ? window.history.back()
            : window.location.assign(route('ecommerce.payments.index'));
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('ecommerce.payments.index')} className="inline-flex items-center gap-2">
                            <ArrowLeft className="h-4 w-4 shrink-0" />
                            <span>Retour</span>
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                        {isEdit ? 'Modifier la méthode' : 'Nouvelle méthode de paiement'}
                    </h2>
                </div>
            }
        >
            <Head title={isEdit ? 'Modifier paiement' : 'Nouveau paiement'} />

            <Drawer
                isOpen={true}
                onClose={handleClose}
                title={isEdit ? 'Modifier la méthode de paiement' : 'Nouvelle méthode de paiement'}
                size="md"
            >
                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CreditCard className="h-5 w-5" />
                                Informations
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nom *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                            </div>
                            {!isEdit && (
                                <div className="space-y-2">
                                    <Label htmlFor="code">Code *</Label>
                                    <Input
                                        id="code"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value)}
                                        placeholder="ex: cash_on_delivery"
                                        required
                                    />
                                    {errors.code && <p className="text-sm text-red-600">{errors.code}</p>}
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="type">Type *</Label>
                                <select
                                    id="type"
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800"
                                >
                                    <option value="cash_on_delivery">Paiement à la livraison</option>
                                    <option value="fusionpay">FusionPay — paiement en ligne immédiat</option>
                                    <option value="card">Carte bancaire</option>
                                    <option value="wallet">Portefeuille</option>
                                    <option value="bank_transfer">Virement</option>
                                    <option value="other">Autre</option>
                                </select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="fee_percentage">Frais (%)</Label>
                                    <Input
                                        id="fee_percentage"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.fee_percentage}
                                        onChange={(e) => setData('fee_percentage', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="fee_fixed">Frais fixe</Label>
                                    <Input
                                        id="fee_fixed"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.fee_fixed}
                                        onChange={(e) => setData('fee_fixed', e.target.value)}
                                    />
                                </div>
                            </div>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                    className="rounded border-gray-300"
                                />
                                <span>Actif</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    checked={data.is_default}
                                    onChange={(e) => setData('is_default', e.target.checked)}
                                    className="rounded border-gray-300"
                                />
                                <span>Par défaut</span>
                            </label>
                            <div className="flex justify-end gap-2 pt-4">
                                <Button type="button" variant="outline" onClick={handleClose}>
                                    Annuler
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {isEdit ? 'Mettre à jour' : 'Créer'}
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </Drawer>
        </AppLayout>
    );
}
