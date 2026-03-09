import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import RichTextEditor from '@/Components/RichTextEditor';
import { HelpCircle } from 'lucide-react';
import CmsHelpModal from './CmsHelpModal';

export default function CmsPageDrawer({ isOpen, onClose, page = null }) {
    const isEdit = !!page?.id;
    const [helpOpen, setHelpOpen] = useState(false);

    const formatDateForInput = (d) => {
        if (!d) return '';
        const s = String(d).replace(' ', 'T');
        return s.length >= 16 ? s.slice(0, 16) : s;
    };

    const { data, setData, post, put, processing, errors, reset } = useForm({
        title: page?.title ?? '',
        slug: page?.slug ?? '',
        content: page?.content ?? '',
        image_path: page?.image_path ?? '',
        is_active: page?.is_active ?? true,
        published_at: formatDateForInput(page?.published_at ?? ''),
    });

    useEffect(() => {
        if (!isOpen) return;
        reset({
            title: page?.title ?? '',
            slug: page?.slug ?? '',
            content: page?.content ?? '',
            image_path: page?.image_path ?? '',
            is_active: page?.is_active ?? true,
            published_at: formatDateForInput(page?.published_at ?? ''),
        });
    }, [isOpen, page?.id]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('ecommerce.cms.pages.update', page.id), { onSuccess: onClose });
        else post(route('ecommerce.cms.pages.store'), { onSuccess: onClose });
    };

    const handleClose = () => {
        onClose();
    };

    return (
        <>
            <Drawer
                isOpen={isOpen}
                onClose={handleClose}
                title={isEdit ? 'Modifier la page' : 'Nouvelle page'}
                size="xl"
            >
                <div className="flex justify-end mb-2">
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={() => setHelpOpen(true)}
                        className="text-amber-600 hover:text-amber-700"
                    >
                        <HelpCircle className="h-4 w-4 mr-1" />
                        Tutoriel
                    </Button>
                </div>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label htmlFor="title">Titre de la page *</Label>
                        <Input
                            id="title"
                            value={data.title}
                            onChange={(e) => setData('title', e.target.value)}
                            required
                            className="mt-1"
                            placeholder="Ex : À propos, Contact, Conditions de vente"
                        />
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Le titre affiché en haut de la page et dans le menu.</p>
                        {errors.title && <p className="text-sm text-red-500 mt-1">{errors.title}</p>}
                    </div>
                    <div>
                        <Label htmlFor="slug">Slug (URL)</Label>
                        <Input
                            id="slug"
                            value={data.slug}
                            onChange={(e) => setData('slug', e.target.value)}
                            className="mt-1"
                            placeholder="Ex : a-propos (généré automatiquement si vide)"
                        />
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Utilisé dans l&apos;URL : /page/a-propos</p>
                        {errors.slug && <p className="text-sm text-red-500 mt-1">{errors.slug}</p>}
                    </div>
                    <div>
                        <Label>Contenu de la page</Label>
                        <div className="mt-1">
                            <RichTextEditor
                                value={data.content}
                                onChange={(v) => setData('content', v)}
                                placeholder="Rédigez le contenu de votre page : paragraphes, listes, liens..."
                            />
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Utilisez la barre d&apos;outils pour formater le texte.</p>
                        {errors.content && <p className="text-sm text-red-500 mt-1">{errors.content}</p>}
                    </div>
                    <div>
                        <Label htmlFor="image_path">Image (chemin ou URL)</Label>
                        <Input
                            id="image_path"
                            value={data.image_path}
                            onChange={(e) => setData('image_path', e.target.value)}
                            className="mt-1"
                            placeholder="Ex : ecommerce/pages/image.jpg ou https://..."
                        />
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Uploadez d&apos;abord dans Médias, puis copiez le chemin.</p>
                        {errors.image_path && <p className="text-sm text-red-500 mt-1">{errors.image_path}</p>}
                    </div>
                    <div className="flex flex-wrap gap-6 items-center">
                        <label className="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="rounded" />
                            <span className="text-sm">Publier la page (visible sur le site)</span>
                        </label>
                        <div className="flex-1 min-w-[200px]">
                            <Label htmlFor="published_at">Date de publication</Label>
                            <Input
                                id="published_at"
                                type="datetime-local"
                                value={data.published_at}
                                onChange={(e) => setData('published_at', e.target.value)}
                                className="mt-1"
                            />
                        </div>
                    </div>
                    <div className="flex gap-2 pt-4 border-t">
                        <Button type="button" variant="outline" onClick={handleClose}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Enregistrement...' : (isEdit ? 'Enregistrer' : 'Créer')}
                        </Button>
                    </div>
                </form>
            </Drawer>
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="pages" />
        </>
    );
}
