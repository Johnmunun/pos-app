import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import RichTextEditor from '@/Components/RichTextEditor';
import { HelpCircle } from 'lucide-react';
import CmsHelpModal from './CmsHelpModal';

export default function CmsBlogDrawer({ isOpen, onClose, article = null, categories = [] }) {
    const isEdit = !!article?.id;
    const [helpOpen, setHelpOpen] = useState(false);

    const formatDateForInput = (d) => {
        if (!d) return '';
        return String(d).replace(' ', 'T').slice(0, 16);
    };

    const { data, setData, post, put, processing, errors, reset } = useForm({
        title: article?.title ?? '',
        slug: article?.slug ?? '',
        content: article?.content ?? '',
        excerpt: article?.excerpt ?? '',
        image_path: article?.image_path ?? '',
        category_id: article?.category_id ?? '',
        is_active: article?.is_active ?? true,
        published_at: formatDateForInput(article?.published_at ?? ''),
    });

    useEffect(() => {
        if (!isOpen) return;
        reset({
            title: article?.title ?? '',
            slug: article?.slug ?? '',
            content: article?.content ?? '',
            excerpt: article?.excerpt ?? '',
            image_path: article?.image_path ?? '',
            category_id: article?.category_id ?? '',
            is_active: article?.is_active ?? true,
            published_at: formatDateForInput(article?.published_at ?? ''),
        });
    }, [isOpen, article?.id]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('ecommerce.cms.blog.update', article.id), { onSuccess: onClose });
        else post(route('ecommerce.cms.blog.store'), { onSuccess: onClose });
    };

    return (
        <>
            <Drawer isOpen={isOpen} onClose={onClose} title={isEdit ? "Modifier l'article" : 'Nouvel article'} size="xl">
                <div className="flex justify-end mb-2">
                    <Button type="button" variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                        <HelpCircle className="h-4 w-4 mr-1" /> Tutoriel
                    </Button>
                </div>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label>Titre *</Label>
                        <Input value={data.title} onChange={(e) => setData('title', e.target.value)} required className="mt-1" placeholder="Ex : Nos 5 conseils pour bien choisir" />
                        {errors.title && <p className="text-sm text-red-500 mt-1">{errors.title}</p>}
                    </div>
                    <div>
                        <Label>Slug (URL)</Label>
                        <Input value={data.slug} onChange={(e) => setData('slug', e.target.value)} className="mt-1" placeholder="Auto si vide" />
                    </div>
                    <div>
                        <Label>Catégorie</Label>
                        <select value={data.category_id} onChange={(e) => setData('category_id', e.target.value)} className="mt-1 w-full rounded-md border border-gray-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2">
                            <option value="">— Aucune —</option>
                            {categories.map((c) => (
                                <option key={c.id} value={c.id}>{c.name}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label>Extrait (résumé court)</Label>
                        <Textarea value={data.excerpt} onChange={(e) => setData('excerpt', e.target.value)} rows={2} className="mt-1" placeholder="Résumé affiché dans la liste des articles" />
                    </div>
                    <div>
                        <Label>Contenu</Label>
                        <div className="mt-1">
                            <RichTextEditor value={data.content} onChange={(v) => setData('content', v)} placeholder="Rédigez votre article..." />
                        </div>
                        {errors.content && <p className="text-sm text-red-500 mt-1">{errors.content}</p>}
                    </div>
                    <div>
                        <Label>Image (chemin ou URL)</Label>
                        <Input value={data.image_path} onChange={(e) => setData('image_path', e.target.value)} className="mt-1" placeholder="Uploadez dans Médias puis copiez le chemin" />
                    </div>
                    <div>
                        <Label>Date de publication</Label>
                        <Input type="datetime-local" value={data.published_at} onChange={(e) => setData('published_at', e.target.value)} className="mt-1" />
                    </div>
                    <label className="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="rounded" />
                        <span className="text-sm">Actif</span>
                    </label>
                    <div className="flex gap-2 pt-4 border-t">
                        <Button type="button" variant="outline" onClick={onClose}>Annuler</Button>
                        <Button type="submit" disabled={processing}>{processing ? '...' : (isEdit ? 'Enregistrer' : 'Créer')}</Button>
                    </div>
                </form>
            </Drawer>
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="blog" />
        </>
    );
}
