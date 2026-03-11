import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Search, Code2, Activity } from 'lucide-react';

export default function EcommerceMarketingIndex({ shop, marketing }) {
    const { data, setData, put, processing, errors } = useForm({
        seo_title: marketing?.seo_title ?? '',
        seo_description: marketing?.seo_description ?? '',
        seo_keywords: marketing?.seo_keywords ?? '',
        seo_indexing_enabled: marketing?.seo_indexing_enabled ?? true,
        facebook_pixel_id: marketing?.facebook_pixel_id ?? '',
        tiktok_pixel_id: marketing?.tiktok_pixel_id ?? '',
        google_analytics_id: marketing?.google_analytics_id ?? '',
        meta_verification: marketing?.meta_verification ?? '',
        marketing_notes: marketing?.marketing_notes ?? '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        const payload = {
            ...data,
            seo_indexing_enabled: !!data.seo_indexing_enabled,
        };
        put(route('ecommerce.marketing.update'), {
            preserveScroll: true,
            data: payload,
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                            Marketing &amp; SEO
                        </h2>
                        {shop && (
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Boutique : <span className="font-medium">{shop.name}</span>
                            </p>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Marketing & SEO - E-commerce" />

            <div className="py-6 space-y-6 max-w-5xl">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Search className="h-5 w-5 text-emerald-500" />
                                Référencement (SEO)
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="seo_title">Titre par défaut</Label>
                                    <Input
                                        id="seo_title"
                                        value={data.seo_title}
                                        onChange={(e) => setData('seo_title', e.target.value)}
                                        placeholder="Titre de la boutique pour les moteurs de recherche"
                                    />
                                    {errors.seo_title && (
                                        <p className="text-sm text-red-600">{errors.seo_title}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="seo_description">Description</Label>
                                    <Textarea
                                        id="seo_description"
                                        value={data.seo_description}
                                        onChange={(e) => setData('seo_description', e.target.value)}
                                        placeholder="Courte description de la boutique pour Google, Facebook, etc."
                                        rows={3}
                                    />
                                    {errors.seo_description && (
                                        <p className="text-sm text-red-600">{errors.seo_description}</p>
                                    )}
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="seo_keywords">Mots clés (séparés par des virgules)</Label>
                                    <Input
                                        id="seo_keywords"
                                        value={data.seo_keywords}
                                        onChange={(e) => setData('seo_keywords', e.target.value)}
                                        placeholder="ex: boutique, ecommerce, vêtements, électroniques"
                                    />
                                    {errors.seo_keywords && (
                                        <p className="text-sm text-red-600">{errors.seo_keywords}</p>
                                    )}
                                </div>
                                <label className="flex items-center gap-2 cursor-pointer pt-2">
                                    <input
                                        type="checkbox"
                                        checked={!!data.seo_indexing_enabled}
                                        onChange={(e) => setData('seo_indexing_enabled', e.target.checked)}
                                        className="rounded border-gray-300"
                                    />
                                    <span className="text-sm text-gray-700 dark:text-gray-200">
                                        Autoriser l&apos;indexation par les moteurs de recherche (Google, Bing, etc.)
                                    </span>
                                </label>

                                <div className="flex justify-end pt-4">
                                    <Button type="submit" disabled={processing}>
                                        Enregistrer
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Code2 className="h-5 w-5 text-sky-500" />
                                Pixels &amp; Analytics
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="space-y-2">
                                <Label htmlFor="facebook_pixel_id">Facebook Pixel ID</Label>
                                <Input
                                    id="facebook_pixel_id"
                                    value={data.facebook_pixel_id}
                                    onChange={(e) => setData('facebook_pixel_id', e.target.value)}
                                    placeholder="ex: 123456789012345"
                                />
                                {errors.facebook_pixel_id && (
                                    <p className="text-sm text-red-600">{errors.facebook_pixel_id}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tiktok_pixel_id">TikTok Pixel ID</Label>
                                <Input
                                    id="tiktok_pixel_id"
                                    value={data.tiktok_pixel_id}
                                    onChange={(e) => setData('tiktok_pixel_id', e.target.value)}
                                    placeholder="ex: CBN1A2B3C4D5E6F"
                                />
                                {errors.tiktok_pixel_id && (
                                    <p className="text-sm text-red-600">{errors.tiktok_pixel_id}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="google_analytics_id">Google Analytics (GA4 / UA)</Label>
                                <Input
                                    id="google_analytics_id"
                                    value={data.google_analytics_id}
                                    onChange={(e) => setData('google_analytics_id', e.target.value)}
                                    placeholder="ex: G-XXXXXXX ou UA-XXXXXX-X"
                                />
                                {errors.google_analytics_id && (
                                    <p className="text-sm text-red-600">{errors.google_analytics_id}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="meta_verification">Meta / Google site verification</Label>
                                <Input
                                    id="meta_verification"
                                    value={data.meta_verification}
                                    onChange={(e) => setData('meta_verification', e.target.value)}
                                    placeholder="Collez ici la valeur du meta tag de vérification"
                                />
                                {errors.meta_verification && (
                                    <p className="text-sm text-red-600">{errors.meta_verification}</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Activity className="h-5 w-5 text-amber-500" />
                            Notes &amp; campagnes
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <Label htmlFor="marketing_notes">Notes internes marketing</Label>
                        <Textarea
                            id="marketing_notes"
                            value={data.marketing_notes}
                            onChange={(e) => setData('marketing_notes', e.target.value)}
                            placeholder="Gardez ici des notes sur vos campagnes, codes promo, audiences, etc. (non visible par les clients)."
                            rows={4}
                        />
                        {errors.marketing_notes && (
                            <p className="text-sm text-red-600">{errors.marketing_notes}</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

