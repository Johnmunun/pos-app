import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import RichTextEditor from '@/Components/RichTextEditor';
import { HelpCircle, Image as ImageIcon } from 'lucide-react';
import CmsHelpModal from './CmsHelpModal';

const METADATA_DEFAULT = { address: '', phone: '', email: '', hours: '' };

export default function CmsPageDrawer({ isOpen, onClose, page = null, media = [] }) {
    const isEdit = !!page?.id;
    const [helpOpen, setHelpOpen] = useState(false);
    const [showMediaPicker, setShowMediaPicker] = useState(false);

    const formatDateForInput = (d) => {
        if (!d) return '';
        const s = String(d).replace(' ', 'T');
        return s.length >= 16 ? s.slice(0, 16) : s;
    };

    const meta = page?.metadata && typeof page.metadata === 'object' ? page.metadata : {};
    const metadataInit = { ...METADATA_DEFAULT, ...meta };

    const { data, setData, post, put, processing, errors, reset } = useForm({
        title: page?.title ?? '',
        slug: page?.slug ?? '',
        template: page?.template ?? 'standard',
        content: page?.content ?? '',
        image_path: page?.image_path ?? '',
        metadata: metadataInit,
        is_active: page?.is_active ?? true,
        published_at: formatDateForInput(page?.published_at ?? ''),
    });

    const isContact = data.template === 'contact';
    const imagesOnly = (media || []).filter((m) => m.file_type === 'image');

    useEffect(() => {
        if (!isOpen) return;
        const meta = page?.metadata && typeof page.metadata === 'object' ? page.metadata : {};
        reset({
            title: page?.title ?? '',
            slug: page?.slug ?? '',
            template: page?.template ?? 'standard',
            content: page?.content ?? '',
            image_path: page?.image_path ?? '',
            metadata: { ...METADATA_DEFAULT, ...meta },
            is_active: page?.is_active ?? true,
            published_at: formatDateForInput(page?.published_at ?? ''),
        });
    // eslint-disable-next-line react-hooks/exhaustive-deps -- page fields used in reset
    }, [isOpen, page?.id]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('ecommerce.cms.pages.update', page.id), { onSuccess: onClose });
        else post(route('ecommerce.cms.pages.store'), { onSuccess: onClose });
    };

    const handleClose = () => {
        setShowMediaPicker(false);
        onClose();
    };

    const updateMetadata = (key, value) => {
        setData('metadata', { ...data.metadata, [key]: value });
    };

    const selectImage = (filePath) => {
        setData('image_path', filePath);
        setShowMediaPicker(false);
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
                            className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400"
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
                            className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                            placeholder="Ex : a-propos (généré automatiquement si vide)"
                        />
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">Utilisé dans l&apos;URL : /page/a-propos</p>
                        {errors.slug && <p className="text-sm text-red-500 mt-1">{errors.slug}</p>}
                    </div>
                    <div>
                        <Label>Type de page</Label>
                        <select
                            value={data.template}
                            onChange={(e) => setData('template', e.target.value)}
                            className="mt-1 flex h-9 w-full rounded-md border border-input bg-white dark:bg-slate-800 px-3 py-1 text-sm text-gray-900 dark:text-gray-100 shadow-sm transition-colors"
                        >
                            <option value="standard">Page standard (texte + image)</option>
                            <option value="contact">Page Contact (avec adresse, téléphone, email)</option>
                        </select>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            La page Contact affiche vos informations de contact dans une mise en page dédiée.
                        </p>
                    </div>

                    {isContact && (
                        <div className="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/50 dark:bg-amber-950/20 p-4 space-y-3">
                            <h4 className="text-sm font-semibold text-amber-800 dark:text-amber-200">Informations de contact</h4>
                            <p className="text-xs text-amber-700 dark:text-amber-300">
                                Ces informations seront affichées sur la page Contact. Remplissez vos propres coordonnées.
                            </p>
                            <div>
                                <Label htmlFor="meta_address">Adresse</Label>
                                <Input
                                    id="meta_address"
                                    value={data.metadata?.address ?? ''}
                                    onChange={(e) => updateMetadata('address', e.target.value)}
                                    placeholder="123 Avenue ..., Ville"
                                    className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                />
                            </div>
                            <div>
                                <Label htmlFor="meta_phone">Téléphone</Label>
                                <Input
                                    id="meta_phone"
                                    value={data.metadata?.phone ?? ''}
                                    onChange={(e) => updateMetadata('phone', e.target.value)}
                                    placeholder="+243 XXX XXX XXX"
                                    className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                />
                            </div>
                            <div>
                                <Label htmlFor="meta_email">Email</Label>
                                <Input
                                    id="meta_email"
                                    type="email"
                                    value={data.metadata?.email ?? ''}
                                    onChange={(e) => updateMetadata('email', e.target.value)}
                                    placeholder="contact@votreboutique.com"
                                    className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                />
                            </div>
                            <div>
                                <Label htmlFor="meta_hours">Horaires d&apos;ouverture</Label>
                                <Input
                                    id="meta_hours"
                                    value={data.metadata?.hours ?? ''}
                                    onChange={(e) => updateMetadata('hours', e.target.value)}
                                    placeholder="Lun - Ven : 9h - 18h"
                                    className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                />
                            </div>
                        </div>
                    )}

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
                        <div className="flex items-center justify-between gap-2">
                            <Label htmlFor="image_path">Image de la page</Label>
                            {imagesOnly.length > 0 && (
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setShowMediaPicker(!showMediaPicker)}
                                    className="text-amber-600"
                                >
                                    <ImageIcon className="h-4 w-4 mr-1" />
                                    Choisir dans la bibliothèque
                                </Button>
                            )}
                        </div>
                        {showMediaPicker && imagesOnly.length > 0 && (
                            <div className="mt-2 p-3 rounded-lg border border-gray-200 dark:border-slate-600 bg-gray-50 dark:bg-slate-800/50 max-h-48 overflow-y-auto">
                                <div className="grid grid-cols-4 sm:grid-cols-6 gap-2">
                                    {imagesOnly.map((m) => (
                                        <button
                                            key={m.id}
                                            type="button"
                                            onClick={() => selectImage(m.file_path)}
                                            className={`aspect-square rounded-lg overflow-hidden border-2 transition-colors ${
                                                data.image_path === m.file_path
                                                    ? 'border-amber-500 ring-2 ring-amber-200'
                                                    : 'border-transparent hover:border-amber-300'
                                            }`}
                                        >
                                            {m.url ? (
                                                <img src={m.url} alt={m.name} className="w-full h-full object-cover" />
                                            ) : (
                                                <div className="w-full h-full flex items-center justify-center bg-slate-200 dark:bg-slate-700">
                                                    <ImageIcon className="h-8 w-8 text-slate-400" />
                                                </div>
                                            )}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}
                        <Input
                            id="image_path"
                            value={data.image_path}
                            onChange={(e) => setData('image_path', e.target.value)}
                            className="mt-2 placeholder:text-gray-500 dark:placeholder:text-gray-400"
                            placeholder="Chemin (ecommerce/...) ou URL https://..."
                        />
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Choisissez une image dans la bibliothèque ou uploadez d&apos;abord dans Médias.
                        </p>
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
