import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, BookOpen } from 'lucide-react';

export default function CmsBlogForm({ article, categories = [] }) {
    const isEdit = !!article?.id;

    const { data, setData, post, put, processing } = useForm({
        title: article?.title ?? '',
        slug: article?.slug ?? '',
        content: article?.content ?? '',
        image_path: article?.image_path ?? '',
        excerpt: article?.excerpt ?? '',
        category_id: article?.category_id ?? '',
        is_active: article?.is_active ?? true,
        published_at: article?.published_at ?? '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('ecommerce.cms.blog.update', article.id));
        else post(route('ecommerce.cms.blog.store'));
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('ecommerce.cms.blog.index')} className="inline-flex items-center gap-2">
                            <ArrowLeft className="h-4 w-4" /> Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                        {isEdit ? 'Modifier l\'article' : 'Nouvel article'}
                    </h2>
                </div>
            }
        >
            <Head title={isEdit ? 'Modifier article' : 'Nouvel article'} />
            <div className="py-6 max-w-2xl">
                <form onSubmit={handleSubmit}>
                    <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><BookOpen className="h-5 w-5" /> Article</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="title">Titre *</Label>
                                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required className="mt-1" />
                            </div>
                            <div>
                                <Label htmlFor="slug">Slug (URL)</Label>
                                <Input id="slug" value={data.slug} onChange={(e) => setData('slug', e.target.value)} placeholder="auto-généré si vide" className="mt-1" />
                            </div>
                            <div>
                                <Label htmlFor="category_id">Catégorie</Label>
                                <select id="category_id" value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} className="mt-1 w-full rounded-md border border-gray-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2">
                                    <option value="">— Aucune —</option>
                                    {categories.map((c) => (
                                        <option key={c.id} value={c.id}>{c.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="excerpt">Extrait</Label>
                                <Textarea id="excerpt" value={data.excerpt} onChange={(e) => setData('excerpt', e.target.value)} rows={2} className="mt-1" />
                            </div>
                            <div>
                                <Label htmlFor="content">Contenu</Label>
                                <Textarea id="content" value={data.content} onChange={(e) => setData('content', e.target.value)} rows={6} className="mt-1" />
                            </div>
                            <div>
                                <Label htmlFor="image_path">Chemin image</Label>
                                <Input id="image_path" value={data.image_path} onChange={(e) => setData('image_path', e.target.value)} placeholder="ecommerce/blog/..." className="mt-1" />
                            </div>
                            <div>
                                <Label htmlFor="published_at">Date de publication</Label>
                                <Input id="published_at" type="datetime-local" value={data.published_at} onChange={(e) => setData('published_at', e.target.value)} className="mt-1" />
                            </div>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="rounded" />
                                <span className="text-sm">Actif</span>
                            </label>
                            <div className="pt-4 flex gap-2">
                                <Button type="submit" disabled={processing}>{isEdit ? 'Mettre à jour' : 'Créer'}</Button>
                                <Button variant="outline" asChild><Link href={route('ecommerce.cms.blog.index')}>Annuler</Link></Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
