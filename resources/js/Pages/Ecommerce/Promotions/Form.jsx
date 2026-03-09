import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Percent } from 'lucide-react';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';

export default function EcommercePromotionsForm({ promotion, products = [], categories = [] }) {
    const isEdit = !!promotion?.id;

    const { data, setData, post, put, processing, errors } = useForm({
        name: promotion?.name ?? '',
        description: promotion?.description ?? '',
        type: promotion?.type ?? 'percentage',
        discount_value: promotion?.discount_value ?? '',
        buy_quantity: promotion?.buy_quantity ?? '',
        get_quantity: promotion?.get_quantity ?? '',
        minimum_purchase: promotion?.minimum_purchase ?? '',
        maximum_uses: promotion?.maximum_uses ?? '',
        starts_at: promotion?.starts_at ?? new Date().toISOString().slice(0, 16),
        ends_at: promotion?.ends_at ?? '',
        is_active: promotion?.is_active ?? true,
        applicable_products: promotion?.applicable_products ?? [],
        applicable_categories: promotion?.applicable_categories ?? [],
    });

    const toggleProduct = (id) => {
        const ids = data.applicable_products || [];
        setData('applicable_products', ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]);
    };
    const toggleCategory = (id) => {
        const ids = data.applicable_categories || [];
        setData('applicable_categories', ids.includes(id) ? ids.filter((x) => x !== id) : [...ids, id]);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const payload = {
            ...data,
            discount_value: data.discount_value === '' ? null : parseFloat(data.discount_value),
            buy_quantity: data.buy_quantity === '' ? null : parseInt(data.buy_quantity, 10),
            get_quantity: data.get_quantity === '' ? null : parseInt(data.get_quantity, 10),
            minimum_purchase: data.minimum_purchase === '' ? null : parseFloat(data.minimum_purchase),
            maximum_uses: data.maximum_uses === '' ? null : parseInt(data.maximum_uses, 10),
            applicable_products: data.applicable_products?.length ? data.applicable_products : [],
            applicable_categories: data.applicable_categories?.length ? data.applicable_categories : [],
        };
        if (isEdit) {
            router.put(route('ecommerce.promotions.update', promotion.id), payload, {
                onSuccess: () => toast.success('Promotion mise à jour'),
                onError: () => toast.error('Erreur'),
            });
        } else {
            router.post(route('ecommerce.promotions.store'), payload, {
                onSuccess: () => toast.success('Promotion créée'),
                onError: () => toast.error('Erreur'),
            });
        }
    };

    const showDiscountValue = ['percentage', 'fixed_amount'].includes(data.type);
    const showBuyXGetY = data.type === 'buy_x_get_y';

    const handleClose = () => {
        window.history.length > 1
            ? window.history.back()
            : window.location.assign(route('ecommerce.promotions.index'));
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('ecommerce.promotions.index')} className="inline-flex items-center gap-2">
                            <ArrowLeft className="h-4 w-4 shrink-0" />
                            <span>Retour</span>
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                        {isEdit ? 'Modifier la promotion' : 'Nouvelle promotion'}
                    </h2>
                </div>
            }
        >
            <Head title={isEdit ? 'Modifier promotion' : 'Nouvelle promotion'} />

            <Drawer
                isOpen={true}
                onClose={handleClose}
                title={isEdit ? 'Modifier la promotion' : 'Nouvelle promotion'}
                size="lg"
            >
                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Percent className="h-5 w-5" />
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
                                    <option value="buy_x_get_y">Achat X obtenir Y</option>
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
                                    />
                                    {errors.discount_value && <p className="text-sm text-red-600">{errors.discount_value}</p>}
                                </div>
                            )}
                            {showBuyXGetY && (
                                <div className="grid grid-cols-2 gap-4 p-4 bg-gray-50 dark:bg-slate-800/50 rounded-lg">
                                    <div className="space-y-2">
                                        <Label htmlFor="buy_quantity">Acheter (quantité) *</Label>
                                        <Input
                                            id="buy_quantity"
                                            type="number"
                                            min="1"
                                            value={data.buy_quantity}
                                            onChange={(e) => setData('buy_quantity', e.target.value)}
                                            placeholder="ex: 2"
                                        />
                                        <p className="text-xs text-gray-500">Acheter X unités</p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="get_quantity">Obtenir (quantité) *</Label>
                                        <Input
                                            id="get_quantity"
                                            type="number"
                                            min="1"
                                            value={data.get_quantity}
                                            onChange={(e) => setData('get_quantity', e.target.value)}
                                            placeholder="ex: 1"
                                        />
                                        <p className="text-xs text-gray-500">Obtenir Y gratuit ou à -%</p>
                                    </div>
                                    <div className="col-span-2 space-y-2">
                                        <Label>Réduction sur Y (%)</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            value={data.discount_value}
                                            onChange={(e) => setData('discount_value', e.target.value)}
                                            placeholder="100 = gratuit"
                                        />
                                    </div>
                                </div>
                            )}
                            <div className="space-y-2">
                                <Label>Produits applicables (vide = tous)</Label>
                                <div className="max-h-32 overflow-y-auto border border-gray-200 dark:border-slate-600 rounded-md p-2 space-y-1">
                                    {(products || []).slice(0, 50).map((p) => (
                                        <label key={p.id} className="flex items-center gap-2 cursor-pointer text-sm">
                                            <input
                                                type="checkbox"
                                                checked={(data.applicable_products || []).includes(p.id)}
                                                onChange={() => toggleProduct(p.id)}
                                                className="rounded"
                                            />
                                            {p.name}
                                        </label>
                                    ))}
                                    {(!products || products.length === 0) && (
                                        <p className="text-sm text-gray-500">Aucun produit. Créez des produits dans le catalogue.</p>
                                    )}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>Catégories applicables (vide = toutes)</Label>
                                <div className="max-h-32 overflow-y-auto border border-gray-200 dark:border-slate-600 rounded-md p-2 space-y-1">
                                    {(categories || []).map((c) => (
                                        <label key={c.id} className="flex items-center gap-2 cursor-pointer text-sm">
                                            <input
                                                type="checkbox"
                                                checked={(data.applicable_categories || []).includes(c.id)}
                                                onChange={() => toggleCategory(c.id)}
                                                className="rounded"
                                            />
                                            {c.name}
                                        </label>
                                    ))}
                                    {(!categories || categories.length === 0) && (
                                        <p className="text-sm text-gray-500">Aucune catégorie.</p>
                                    )}
                                </div>
                            </div>
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
                                    <Label htmlFor="maximum_uses">Utilisations max.</Label>
                                    <Input
                                        id="maximum_uses"
                                        type="number"
                                        min="0"
                                        value={data.maximum_uses}
                                        onChange={(e) => setData('maximum_uses', e.target.value)}
                                    />
                                </div>
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
