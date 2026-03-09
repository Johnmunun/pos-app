import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Sparkles, LayoutTemplate } from 'lucide-react';

export default function StorefrontCms({ shop, config }) {
    const { data, setData, put, processing } = useForm({
        hero_badge: config?.hero_badge || '',
        hero_title: config?.hero_title || '',
        hero_subtitle: config?.hero_subtitle || '',
        hero_description: config?.hero_description || '',
        hero_primary_label: config?.hero_primary_label || '',
        hero_secondary_label: config?.hero_secondary_label || '',
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

            <div className="py-6 max-w-3xl space-y-6">
                <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                            <LayoutTemplate className="h-5 w-5 text-amber-500" />
                            Section héro principale
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
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
                                    Description courte
                                </label>
                                <textarea
                                    value={data.hero_description}
                                    onChange={(e) => setData('hero_description', e.target.value)}
                                    rows={3}
                                    className="w-full rounded-md border border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-500/40"
                                    placeholder="Texte descriptif sous le titre."
                                />
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
            </div>
        </AppLayout>
    );
}

