import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ArrowLeft, Truck } from 'lucide-react';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';

export default function EcommerceShippingForm({ method }) {
    const isEdit = !!method?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        name: method?.name ?? '',
        type: method?.type ?? 'flat_rate',
        base_cost: method?.base_cost ?? 0,
        free_shipping_threshold: method?.free_shipping_threshold ?? '',
        estimated_days_min: method?.estimated_days_min ?? '',
        estimated_days_max: method?.estimated_days_max ?? '',
        is_active: method?.is_active ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        const payload = {
            name: data.name,
            type: data.type,
            base_cost: parseFloat(data.base_cost) || 0,
            free_shipping_threshold: data.free_shipping_threshold ? parseFloat(data.free_shipping_threshold) : null,
            estimated_days_min: data.estimated_days_min ? parseInt(data.estimated_days_min, 10) : null,
            estimated_days_max: data.estimated_days_max ? parseInt(data.estimated_days_max, 10) : null,
            is_active: data.is_active,
        };
        if (isEdit) {
            router.put(route('ecommerce.shipping.update', method.id), payload, {
                onSuccess: () => toast.success('Méthode mise à jour'),
                onError: () => toast.error('Erreur'),
            });
        } else {
            router.post(route('ecommerce.shipping.store'), payload, {
                onSuccess: () => toast.success('Méthode créée'),
                onError: () => toast.error('Erreur'),
            });
        }
    };

    const handleClose = () => {
        router.visit(route('ecommerce.shipping.index'));
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('ecommerce.shipping.index')} className="inline-flex items-center gap-2">
                            <ArrowLeft className="h-4 w-4 shrink-0" /> Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                        {isEdit ? 'Modifier la méthode' : 'Nouvelle méthode de livraison'}
                    </h2>
                </div>
            }
        >
            <Head title={isEdit ? 'Modifier livraison' : 'Nouvelle livraison'} />

            <Drawer
                isOpen={true}
                onClose={handleClose}
                title={isEdit ? 'Modifier la méthode de livraison' : 'Nouvelle méthode de livraison'}
                size="md"
            >
                <form onSubmit={handleSubmit} className="space-y-4">
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
                    <div className="space-y-2">
                        <Label htmlFor="type">Type *</Label>
                        <select
                            id="type"
                            value={data.type}
                            onChange={(e) => setData('type', e.target.value)}
                            className="w-full rounded-md border-gray-300 dark:border-slate-600 dark:bg-slate-800"
                        >
                            <option value="flat_rate">Tarif fixe</option>
                            <option value="weight_based">Selon le poids</option>
                            <option value="price_based">Selon le montant</option>
                            <option value="free">Gratuit</option>
                        </select>
                    </div>
                    {data.type !== 'free' && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="base_cost">Coût de base</Label>
                                <Input
                                    id="base_cost"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.base_cost}
                                    onChange={(e) => setData('base_cost', e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="free_shipping_threshold">Gratuit à partir de (montant)</Label>
                                <Input
                                    id="free_shipping_threshold"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    value={data.free_shipping_threshold}
                                    onChange={(e) => setData('free_shipping_threshold', e.target.value)}
                                    placeholder="Optionnel"
                                />
                            </div>
                        </>
                    )}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="estimated_days_min">Délai min (jours)</Label>
                            <Input
                                id="estimated_days_min"
                                type="number"
                                min="0"
                                value={data.estimated_days_min}
                                onChange={(e) => setData('estimated_days_min', e.target.value)}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="estimated_days_max">Délai max (jours)</Label>
                            <Input
                                id="estimated_days_max"
                                type="number"
                                min="0"
                                value={data.estimated_days_max}
                                onChange={(e) => setData('estimated_days_max', e.target.value)}
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
                    <div className="flex justify-end gap-2 pt-4">
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {isEdit ? 'Mettre à jour' : 'Créer'}
                        </Button>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}
