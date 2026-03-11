import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Sparkles, LayoutTemplate, Palette } from 'lucide-react';
import RichTextEditor from '@/Components/RichTextEditor';

export default function StorefrontCms({ shop, config }) {
    const { data, setData, put, processing } = useForm({
        hero_badge: config?.hero_badge || '',
        hero_title: config?.hero_title || '',
        hero_subtitle: config?.hero_subtitle || '',
        hero_description: config?.hero_description || '',
        hero_primary_label: config?.hero_primary_label || '',
        hero_secondary_label: config?.hero_secondary_label || '',
        social_facebook_url: config?.social_facebook_url || '',
        social_instagram_url: config?.social_instagram_url || '',
        social_tiktok_url: config?.social_tiktok_url || '',
        social_youtube_url: config?.social_youtube_url || '',
        whatsapp_number: config?.whatsapp_number || '',
        whatsapp_support_enabled: !!config?.whatsapp_support_enabled,
        theme_primary_color: config?.theme_primary_color || '#f59e0b',
        theme_secondary_color: config?.theme_secondary_color || '#d97706',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('ecommerce.storefront.cms.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                            Vitrine e-commerce
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Configurez le contenu de la page boutique vue par vos clients (et en aperçu admin).
                        </p>
                    </div>
                    <Button asChild size="sm" variant="outline">
                        <Link href={route('ecommerce.storefront.index')}>
                            <Sparkles className="h-4 w-4 mr-1" />
                            Voir l&apos;aperçu
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="CMS Vitrine E-commerce" />

            <div className="py-6 max-w-4xl space-y-6">
                <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                            <Palette className="h-5 w-5 text-violet-500" />
                            Couleurs du thème
                        </CardTitle>
                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Couleur primaire (boutons, liens) et secondaire (accents). Appliquées immédiatement, sans flash au rafraîchissement.
                        </p>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Couleur primaire
                                    </label>
                                    <div className="flex gap-2">
                                        <input
                                            type="color"
                                            value={data.theme_primary_color}
                                            onChange={(e) => setData('theme_primary_color', e.target.value)}
                                            className="h-10 w-14 rounded-lg border border-gray-300 dark:border-slate-600 cursor-pointer p-0"
                                        />
                                        <Input
                                            value={data.theme_primary_color}
                                            onChange={(e) => setData('theme_primary_color', e.target.value)}
                                            placeholder="#f59e0b"
                                            className="font-mono text-sm"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Couleur secondaire
                                    </label>
                                    <div className="flex gap-2">
                                        <input
                                            type="color"
                                            value={data.theme_secondary_color}
                                            onChange={(e) => setData('theme_secondary_color', e.target.value)}
                                            className="h-10 w-14 rounded-lg border border-gray-300 dark:border-slate-600 cursor-pointer p-0"
                                        />
                                        <Input
                                            value={data.theme_secondary_color}
                                            onChange={(e) => setData('theme_secondary_color', e.target.value)}
                                            placeholder="#d97706"
                                            className="font-mono text-sm"
                                        />
                                    </div>
                                </div>
                            </div>
                            <div className="pt-2 flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    Enregistrer
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                            <LayoutTemplate className="h-5 w-5 text-amber-500" />
                            Section héro principale
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Badge (petit texte au-dessus)
                                    </label>
                                    <Input
                                        value={data.hero_badge}
                                        onChange={(e) => setData('hero_badge', e.target.value)}
                                        placeholder="Season Sale"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Sous-titre (ex: Min. 35–70% Off)
                                    </label>
                                    <Input
                                        value={data.hero_subtitle}
                                        onChange={(e) => setData('hero_subtitle', e.target.value)}
                                        placeholder="Min. 35–70% Off"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                    Titre principal
                                </label>
                                <Input
                                    value={data.hero_title}
                                    onChange={(e) => setData('hero_title', e.target.value)}
                                    placeholder="MEN&apos;S FASHION"
                                />
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                    Description courte (texte riche)
                                </label>
                                <div className="mt-1">
                                    <RichTextEditor
                                        value={data.hero_description}
                                        onChange={(val) => setData('hero_description', val)}
                                        placeholder="Texte descriptif sous le titre."
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Bouton principal (label)
                                    </label>
                                    <Input
                                        value={data.hero_primary_label}
                                        onChange={(e) => setData('hero_primary_label', e.target.value)}
                                        placeholder="Voir la boutique"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Bouton secondaire (label)
                                    </label>
                                    <Input
                                        value={data.hero_secondary_label}
                                        onChange={(e) => setData('hero_secondary_label', e.target.value)}
                                        placeholder="Découvrir les nouveautés"
                                    />
                                </div>
                            </div>

                            <div className="pt-3 flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    Enregistrer
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                            Réseaux sociaux & support WhatsApp
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Lien Facebook
                                    </label>
                                    <Input
                                        value={data.social_facebook_url}
                                        onChange={(e) => setData('social_facebook_url', e.target.value)}
                                        placeholder="https://facebook.com/ma-boutique"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Lien Instagram
                                    </label>
                                    <Input
                                        value={data.social_instagram_url}
                                        onChange={(e) => setData('social_instagram_url', e.target.value)}
                                        placeholder="https://instagram.com/ma-boutique"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Lien TikTok
                                    </label>
                                    <Input
                                        value={data.social_tiktok_url}
                                        onChange={(e) => setData('social_tiktok_url', e.target.value)}
                                        placeholder="https://www.tiktok.com/@ma-boutique"
                                    />
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Lien YouTube
                                    </label>
                                    <Input
                                        value={data.social_youtube_url}
                                        onChange={(e) => setData('social_youtube_url', e.target.value)}
                                        placeholder="https://youtube.com/@ma-boutique"
                                    />
                                </div>
                            </div>

                            <div className="border-t border-gray-200 dark:border-slate-800 pt-4 space-y-3">
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Numéro WhatsApp (format international)
                                    </label>
                                    <Input
                                        value={data.whatsapp_number}
                                        onChange={(e) => setData('whatsapp_number', e.target.value)}
                                        placeholder="+243 999 000 000"
                                    />
                                    <p className="mt-1 text-[11px] text-gray-500 dark:text-gray-400">
                                        Exemple : +243 999 000 000 (le bouton support ouvrira WhatsApp avec ce numéro).
                                    </p>
                                </div>

                                <label className="inline-flex items-start gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        className="mt-0.5 h-4 w-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                        checked={data.whatsapp_support_enabled}
                                        onChange={(e) => setData('whatsapp_support_enabled', e.target.checked)}
                                    />
                                    <span className="text-xs text-gray-700 dark:text-gray-300">
                                        Activer le bouton de support WhatsApp flottant sur la vitrine
                                    </span>
                                </label>
                            </div>

                            <div className="pt-2 flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    Enregistrer
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

