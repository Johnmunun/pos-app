import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { HelpCircle } from 'lucide-react';
import CmsHelpModal from './CmsHelpModal';

const POSITIONS = [
    { value: 'homepage', label: "Page d'accueil" },
    { value: 'promotion', label: 'Promotion' },
    { value: 'slider', label: 'Slider' },
];

export default function CmsBannerDrawer({ isOpen, onClose, banner = null, positions = [] }) {
    const isEdit = !!banner?.id;
    const [helpOpen, setHelpOpen] = useState(false);
    const opts = positions.length ? positions : POSITIONS;

    const { data, setData, post, put, processing, errors } = useForm({
        title: banner?.title ?? '',
        image_path: banner?.image_path ?? '',
        image_file: null,
        link: banner?.link ?? '',
        position: banner?.position ?? 'homepage',
        is_active: banner?.is_active ?? true,
    });

    useEffect(() => {
        if (!isOpen) return;
        setData({
            title: banner?.title ?? '',
            image_path: banner?.image_path ?? '',
            image_file: null,
            link: banner?.link ?? '',
            position: banner?.position ?? 'homepage',
            is_active: banner?.is_active ?? true,
        });
    }, [isOpen, banner]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('ecommerce.cms.banners.update', banner.id), { onSuccess: onClose, forceFormData: true });
        else post(route('ecommerce.cms.banners.store'), { onSuccess: onClose, forceFormData: true });
    };

    return (
        <>
            <Drawer isOpen={isOpen} onClose={onClose} title={isEdit ? 'Modifier la bannière' : 'Nouvelle bannière'} size="md">
                <div className="flex justify-end mb-2">
                    <Button type="button" variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                        <HelpCircle className="h-4 w-4 mr-1" /> Tutoriel
                    </Button>
                </div>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label>Titre *</Label>
                        <Input value={data.title} onChange={(e) => setData('title', e.target.value)} required className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400" placeholder="Ex : Soldes d'été -50%" />
                        {errors.title && <p className="text-sm text-red-500 mt-1">{errors.title}</p>}
                    </div>
                    <div>
                        <Label>Position</Label>
                        <select value={data.position} onChange={(e) => setData('position', e.target.value)} className="mt-1 w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-3 py-2 text-gray-900 dark:text-gray-100">
                            {opts.map((p) => (
                                <option key={p.value} value={p.value}>{p.label}</option>
                            ))}
                        </select>
                        <p className="text-xs text-gray-500 mt-1">Homepage = image principale, Slider = carrousel</p>
                    </div>
                    <div>
                        <Label>Image (chemin ou URL)</Label>
                        <Input value={data.image_path} onChange={(e) => setData('image_path', e.target.value)} className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400" placeholder="Ex : ecommerce/cms/media/xxx/banner.jpg" />
                        {errors.image_path && <p className="text-sm text-red-500 mt-1">{errors.image_path}</p>}
                    </div>
                    <div>
                        <Label>Ou uploader une image</Label>
                        <input
                            type="file"
                            accept="image/*"
                            onChange={(e) => setData('image_file', e.target.files?.[0] || null)}
                            className="mt-1 block w-full text-sm text-gray-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200"
                        />
                        {errors.image_file && <p className="text-sm text-red-500 mt-1">{errors.image_file}</p>}
                    </div>
                    <div>
                        <Label>Lien (URL au clic)</Label>
                        <Input value={data.link} onChange={(e) => setData('link', e.target.value)} className="mt-1 placeholder:text-gray-500 dark:placeholder:text-gray-400" placeholder="Ex : https://... ou /ecommerce/catalog" />
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
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="banners" />
        </>
    );
}
