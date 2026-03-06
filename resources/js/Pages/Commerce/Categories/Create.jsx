import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Save, Tag } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function CommerceCategoriesCreate({ parentOptions = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        description: '',
        parent_id: '',
        sort_order: 0,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('commerce.categories.store'), {
            onSuccess: () => toast.success('Catégorie créée'),
            onError: (err) => toast.error(err?.message || 'Erreur'),
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center">
                    <Button variant="ghost" asChild className="mr-4">
                        <Link href={route('commerce.categories.index')}>
                            <ArrowLeft className="h-4 w-4 mr-2" /> Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Nouvelle catégorie — GlobalCommerce
                    </h2>
                </div>
            }
        >
            <Head title="Nouvelle catégorie - Commerce" />
            <div className="py-6">
                <div className="max-w-2xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Tag className="h-5 w-5 mr-2" /> Informations
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nom *</Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        placeholder="Nom de la catégorie"
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
                                        rows={3}
                                        placeholder="Description..."
                                    />
                                    {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="parent_id">Catégorie parente</Label>
                                    <select
                                        id="parent_id"
                                        value={data.parent_id}
                                        onChange={(e) => setData('parent_id', e.target.value)}
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                    >
                                        <option value="">— Aucune —</option>
                                        {parentOptions.map((c) => (
                                            <option key={c.id} value={c.id}>{c.name}</option>
                                        ))}
                                    </select>
                                    {errors.parent_id && <p className="text-sm text-red-600">{errors.parent_id}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="sort_order">Ordre d'affichage</Label>
                                    <Input
                                        id="sort_order"
                                        type="number"
                                        min={0}
                                        value={data.sort_order}
                                        onChange={(e) => setData('sort_order', parseInt(e.target.value, 10) || 0)}
                                    />
                                    {errors.sort_order && <p className="text-sm text-red-600">{errors.sort_order}</p>}
                                </div>
                                <div className="flex gap-3 pt-4">
                                    <Button type="button" variant="outline" asChild>
                                        <Link href={route('commerce.categories.index')}>Annuler</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4 mr-2" /> {processing ? 'Création...' : 'Créer'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
