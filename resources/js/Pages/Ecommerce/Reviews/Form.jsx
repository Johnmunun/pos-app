import { Head, Link, useForm, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Star } from 'lucide-react';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';

export default function EcommerceReviewsForm({ review, products }) {
    const isEdit = !!review?.id;

    const { data, setData, processing, errors } = useForm({
        product_id: review?.product_id ?? '',
        customer_name: review?.customer_name ?? '',
        customer_email: review?.customer_email ?? '',
        rating: review?.rating ?? 5,
        title: review?.title ?? '',
        comment: review?.comment ?? '',
        is_approved: review?.is_approved ?? false,
        is_featured: review?.is_featured ?? false,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) {
            router.put(route('ecommerce.reviews.update', review.id), data, {
                onSuccess: () => toast.success('Avis mis à jour'),
                onError: () => toast.error('Erreur'),
            });
        } else {
            router.post(route('ecommerce.reviews.store'), data, {
                onSuccess: () => toast.success('Avis créé'),
                onError: () => toast.error('Erreur'),
            });
        }
    };

    const handleClose = () => {
        window.history.length > 1
            ? window.history.back()
            : window.location.assign(route('ecommerce.reviews.index'));
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('ecommerce.reviews.index')} className="inline-flex items-center gap-2">
                            <ArrowLeft className="h-4 w-4 shrink-0" />
                            <span>Retour</span>
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                        {isEdit ? "Modifier l'avis" : 'Nouvel avis'}
                    </h2>
                </div>
            }
        >
            <Head title={isEdit ? "Modifier avis" : 'Nouvel avis'} />

            <Drawer
                isOpen={true}
                onClose={handleClose}
                title={isEdit ? "Modifier l'avis" : 'Nouvel avis'}
                size="md"
            >
                <form onSubmit={handleSubmit}>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Star className="h-5 w-5" />
                                Informations
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="product_id">Produit *</Label>
                                <select
                                    id="product_id"
                                    value={data.product_id}
                                    onChange={(e) => setData('product_id', e.target.value)}
                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2"
                                    required
                                >
                                    <option value="">Sélectionner un produit</option>
                                    {(products ?? []).map((p) => (
                                        <option key={p.id} value={p.id}>{p.name}</option>
                                    ))}
                                </select>
                                {errors.product_id && <p className="text-sm text-red-600">{errors.product_id}</p>}
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="customer_name">Nom du client *</Label>
                                    <Input
                                        id="customer_name"
                                        value={data.customer_name}
                                        onChange={(e) => setData('customer_name', e.target.value)}
                                        required
                                    />
                                    {errors.customer_name && <p className="text-sm text-red-600">{errors.customer_name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="customer_email">Email</Label>
                                    <Input
                                        id="customer_email"
                                        type="email"
                                        value={data.customer_email}
                                        onChange={(e) => setData('customer_email', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label>Note *</Label>
                                <div className="flex gap-2">
                                    {[1, 2, 3, 4, 5].map((s) => (
                                        <button
                                            key={s}
                                            type="button"
                                            onClick={() => setData('rating', s)}
                                            className={`p-2 rounded ${data.rating >= s ? 'bg-amber-100 dark:bg-amber-900/30 text-amber-600' : 'bg-gray-100 dark:bg-gray-700 text-gray-400'}`}
                                        >
                                            <Star className={`h-6 w-6 ${data.rating >= s ? 'fill-amber-500' : ''}`} />
                                        </button>
                                    ))}
                                </div>
                                {errors.rating && <p className="text-sm text-red-600">{errors.rating}</p>}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="title">Titre</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    placeholder="Résumé de l'avis"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="comment">Commentaire</Label>
                                <Textarea
                                    id="comment"
                                    value={data.comment}
                                    onChange={(e) => setData('comment', e.target.value)}
                                    rows={4}
                                />
                            </div>
                            <div className="flex gap-6">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_approved}
                                        onChange={(e) => setData('is_approved', e.target.checked)}
                                        className="rounded border-gray-300"
                                    />
                                    <span>Approuvé (visible sur le catalogue)</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={data.is_featured}
                                        onChange={(e) => setData('is_featured', e.target.checked)}
                                        className="rounded border-gray-300"
                                    />
                                    <span>Vedette</span>
                                </label>
                            </div>
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
