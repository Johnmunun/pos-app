import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Tag } from 'lucide-react';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';

export default function EcommerceCouponsForm({ coupon }) {
    const isEdit = !!coupon?.id;

    const { data, setData, processing, errors } = useForm({
        code: coupon?.code ?? '',
        name: coupon?.name ?? '',
        description: coupon?.description ?? '',
        type: coupon?.type ?? 'percentage',
        discount_value: coupon?.discount_value ?? '',
        minimum_purchase: coupon?.minimum_purchase ?? '',
        maximum_uses: coupon?.maximum_uses ?? '',
        maximum_uses_per_customer: coupon?.maximum_uses_per_customer ?? '',
        starts_at: coupon?.starts_at ?? new Date().toISOString().slice(0, 16),
        ends_at: coupon?.ends_at ?? '',
        is_active: coupon?.is_active ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        const payload = {
            ...data,
            discount_value: showDiscountValue ? (parseFloat(data.discount_value) || 0) : 0,
            minimum_purchase: data.minimum_purchase === '' ? null : parseFloat(data.minimum_purchase),
            maximum_uses: data.maximum_uses === '' ? null : parseInt(data.maximum_uses, 10),
            maximum_uses_per_customer: data.maximum_uses_per_customer === '' ? null : parseInt(data.maximum_uses_per_customer, 10),
        };
        if (isEdit) {
            router.put(route('ecommerce.coupons.update', coupon.id), payload, {
                onSuccess: () => toast.success('Coupon mis à jour'),
                onError: () => toast.error('Erreur'),
            });
        } else {
            router.post(route('ecommerce.coupons.store'), payload, {
                onSuccess: () => toast.success('Coupon créé'),
                onError: () => toast.error('Erreur'),
            });
        }
    };

    const showDiscountValue = ['percentage', 'fixed_amount'].includes(data.type);

    const handleClose = () => {
        window.history.length > 1
            ? window.history.back()
            : window.location.assign(route('ecommerce.coupons.index'));
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('ecommerce.coupons.index')} className="inline-flex items-center gap-2">
                            <ArrowLeft className="h-4 w-4 shrink-0" />
                            <span>Retour</span>
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                        {isEdit ? 'Modifier le coupon' : 'Nouveau coupon'}
                    </h2>
                </div>
            }
        >
            <Head title={isEdit ? 'Modifier coupon' : 'Nouveau coupon'} />

            <Drawer
                isOpen={true}
                onClose={handleClose}
                title={isEdit ? 'Modifier le coupon' : 'Nouveau coupon'}
                size="md"
            >
                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Tag className="h-5 w-5" />
                                Informations
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {!isEdit && (
                                <div className="space-y-2">
                                    <Label htmlFor="code">Code *</Label>
                                    <Input
                                        id="code"
                                        value={data.code}
                                        onChange={(e) => setData('code', e.target.value.toUpperCase().replace(/\s/g, ''))}
                                        placeholder="ex: PROMO20"
                                        required
                                    />
                                    <p className="text-xs text-gray-500">Lettres et chiffres uniquement, sans espaces.</p>
                                    {errors.code && <p className="text-sm text-red-600">{errors.code}</p>}
                                </div>
                            )}
                            {isEdit && (
                                <div className="space-y-2">
                                    <Label>Code</Label>
                                    <p className="font-mono px-3 py-2 bg-gray-100 dark:bg-slate-700 rounded">{data.code}</p>
                                    <p className="text-xs text-gray-500">Le code ne peut pas être modifié.</p>
                                </div>
                            )}
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
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={2}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="type">Type *</Label>
                                <select
                                    id="type"
                                    value={data.type}
                                    onChange={(e) => setData('type', e.target.value)}
                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2"
                                >
                                    <option value="percentage">Pourcentage</option>
                                    <option value="fixed_amount">Montant fixe</option>
                                    <option value="free_shipping">Livraison gratuite</option>
                                </select>
                            </div>
                            {showDiscountValue && (
                                <div className="space-y-2">
                                    <Label htmlFor="discount_value">
                                        {data.type === 'percentage' ? 'Pourcentage (%)' : 'Montant (€)'} *
                                    </Label>
                                    <Input
                                        id="discount_value"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.discount_value}
                                        onChange={(e) => setData('discount_value', e.target.value)}
                                        required
                                    />
                                    {errors.discount_value && <p className="text-sm text-red-600">{errors.discount_value}</p>}
                                </div>
                            )}
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="minimum_purchase">Achat minimum (€)</Label>
                                    <Input
                                        id="minimum_purchase"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        value={data.minimum_purchase}
                                        onChange={(e) => setData('minimum_purchase', e.target.value)}
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="maximum_uses">Utilisations max totales</Label>
                                    <Input
                                        id="maximum_uses"
                                        type="number"
                                        min="0"
                                        value={data.maximum_uses}
                                        onChange={(e) => setData('maximum_uses', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="maximum_uses_per_customer">Utilisations max par client</Label>
                                <Input
                                    id="maximum_uses_per_customer"
                                    type="number"
                                    min="0"
                                    value={data.maximum_uses_per_customer}
                                    onChange={(e) => setData('maximum_uses_per_customer', e.target.value)}
                                />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="starts_at">Début *</Label>
                                    <Input
                                        id="starts_at"
                                        type="datetime-local"
                                        value={data.starts_at}
                                        onChange={(e) => setData('starts_at', e.target.value)}
                                        required
                                    />
                                    {errors.starts_at && <p className="text-sm text-red-600">{errors.starts_at}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="ends_at">Fin *</Label>
                                    <Input
                                        id="ends_at"
                                        type="datetime-local"
                                        value={data.ends_at}
                                        onChange={(e) => setData('ends_at', e.target.value)}
                                        required
                                    />
                                    {errors.ends_at && <p className="text-sm text-red-600">{errors.ends_at}</p>}
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
                        </CardContent>
                    </Card>
                </form>
            </Drawer>
        </AppLayout>
    );
}
