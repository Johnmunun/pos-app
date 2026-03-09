import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { ArrowLeft, Image } from 'lucide-react';

export default function CmsBannersForm({ banner, positions = [] }) {
    const isEdit = !!banner?.id;

    const { data, setData, post, put, processing } = useForm({
        title: banner?.title ?? '',
        image_path: banner?.image_path ?? '',
        link: banner?.link ?? '',
        position: banner?.position ?? 'homepage',
        is_active: banner?.is_active ?? true,
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        if (isEdit) put(route('ecommerce.cms.banners.update', banner.id));
        else post(route('ecommerce.cms.banners.store'));
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={route('ecommerce.cms.banners.index')} className="inline-flex items-center gap-2">
                            <ArrowLeft className="h-4 w-4" /> Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                        {isEdit ? 'Modifier la bannière' : 'Nouvelle bannière'}
                    </h2>
                </div>
            }
        >
            <Head title={isEdit ? 'Modifier bannière' : 'Nouvelle bannière'} />
            <div className="py-6 max-w-xl">
                <form onSubmit={handleSubmit}>
                    <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2"><Image className="h-5 w-5" /> Bannière</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label htmlFor="title">Titre *</Label>
                                <Input id="title" value={data.title} onChange={(e) => setData('title', e.target.value)} required className="mt-1" />
                            </div>
                            <div>
                                <Label htmlFor="position">Position *</Label>
                                <select id="position" value={data.position} onChange={(e) => setData('position', e.target.value)} className="mt-1 w-full rounded-md border border-gray-300 dark:border-slate-600 dark:bg-slate-800 px-3 py-2">
                                    {positions.map((p) => (
                                        <option key={p.value} value={p.value}>{p.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <Label htmlFor="image_path">Chemin image</Label>
                                <Input id="image_path" value={data.image_path} onChange={(e) => setData('image_path', e.target.value)} placeholder="ecommerce/banners/..." className="mt-1" />
                            </div>
                            <div>
                                <Label htmlFor="link">Lien (URL)</Label>
                                <Input id="link" value={data.link} onChange={(e) => setData('link', e.target.value)} placeholder="https://..." className="mt-1" />
                            </div>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" checked={data.is_active} onChange={(e) => setData('is_active', e.target.checked)} className="rounded" />
                                <span className="text-sm">Actif</span>
                            </label>
                            <div className="pt-4 flex gap-2">
                                <Button type="submit" disabled={processing}>{isEdit ? 'Mettre à jour' : 'Créer'}</Button>
                                <Button variant="outline" asChild><Link href={route('ecommerce.cms.banners.index')}>Annuler</Link></Button>
                            </div>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
