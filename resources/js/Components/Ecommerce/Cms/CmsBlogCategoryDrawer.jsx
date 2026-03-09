import { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { HelpCircle } from 'lucide-react';
import CmsHelpModal from './CmsHelpModal';

export default function CmsBlogCategoryDrawer({ isOpen, onClose, category = null }) {
    const isEdit = !!category?.id;
    const [helpOpen, setHelpOpen] = useState(false);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: category?.name ?? '',
        slug: category?.slug ?? '',
        description: category?.description ?? '',
    });

    useEffect(() => {
        if (!isOpen) return;
        reset({
            name: category?.name ?? '',
            slug: category?.slug ?? '',
            description: category?.description ?? '',
        });
    }, [isOpen, category?.id]);

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('ecommerce.cms.blog.categories.update', category.id), { onSuccess: onClose });
        else post(route('ecommerce.cms.blog.categories.store'), { onSuccess: onClose });
    };

    return (
        <>
            <Drawer isOpen={isOpen} onClose={onClose} title={isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie'} size="md">
                <div className="flex justify-end mb-2">
                    <Button type="button" variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                        <HelpCircle className="h-4 w-4 mr-1" /> Tutoriel
                    </Button>
                </div>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label>Nom *</Label>
                        <Input value={data.name} onChange={(e) => setData('name', e.target.value)} required className="mt-1" placeholder="Ex : Actualités, Conseils" />
                        {errors.name && <p className="text-sm text-red-500 mt-1">{errors.name}</p>}
                    </div>
                    <div>
                        <Label>Slug</Label>
                        <Input value={data.slug} onChange={(e) => setData('slug', e.target.value)} className="mt-1" placeholder="Auto si vide (ex : actualites)" />
                    </div>
                    <div>
                        <Label>Description</Label>
                        <Textarea value={data.description} onChange={(e) => setData('description', e.target.value)} rows={2} className="mt-1" placeholder="Description optionnelle" />
                    </div>
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
