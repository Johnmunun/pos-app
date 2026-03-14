import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Settings, Truck, CreditCard, Sparkles, Globe2 } from 'lucide-react';

export default function EcommerceSettingsIndex({ shop, ecommerce_base_domain }) {
    const { props } = usePage();
    const globalCurrency = props?.shop?.currency || shop?.currency || 'USD';
    const baseDomain = ecommerce_base_domain || 'omnisolution.shop';

    const { data, setData, put, processing, errors } = useForm({
        subdomain: shop?.ecommerce_subdomain || '',
        is_online: shop?.ecommerce_is_online || false,
    });

    const handleSubmitDomain = (e) => {
        e.preventDefault();
        put(route('ecommerce.settings.domain.update'), {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">
                    Paramètres E-commerce
                </h2>
            }
        >
            <Head title="Paramètres - E-commerce" />

            <div className="py-6 space-y-6 max-w-2xl">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Settings className="h-5 w-5" />
                            Configuration générale
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-1">
                            <p className="text-sm text-gray-600 dark:text-gray-300">
                                Devise utilisée pour l&apos;e-commerce :
                            </p>
                            <p className="text-lg font-semibold text-gray-900 dark:text-white">
                                {globalCurrency}
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                La devise est gérée dans <strong>Paramètres &gt; Gestion des devises</strong> et partagée avec les autres modules.
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="text-base">Autres paramètres</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <Link
                            href={route('ecommerce.shipping.index')}
                            className="flex items-center gap-2 p-3 rounded-lg text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition"
                        >
                            <Truck className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            Méthodes de livraison
                        </Link>
                        <Link
                            href={route('ecommerce.payments.index')}
                            className="flex items-center gap-2 p-3 rounded-lg text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition"
                        >
                            <CreditCard className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            Méthodes de paiement
                        </Link>
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Sparkles className="h-5 w-5 text-amber-500" />
                            Vitrine e-commerce (CMS)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Personnalisez le contenu de la page boutique visible par vos clients (section héro, textes, etc.).
                        </p>
                        <Link
                            href={route('ecommerce.storefront.cms')}
                            className="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 dark:border-slate-700 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700/60 transition"
                        >
                            <Sparkles className="h-4 w-4 text-amber-500" />
                            Ouvrir le CMS vitrine
                        </Link>
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Globe2 className="h-5 w-5 text-blue-500" />
                            Domaine de la boutique en ligne
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Choisissez le sous-domaine de votre boutique. Exemple : si vous mettez <strong>kasashop</strong>,
                            vos clients accéderont à votre boutique sur <strong>kasashop.{baseDomain}</strong>.
                        </p>
                        <form onSubmit={handleSubmitDomain} className="space-y-3">
                            <div className="flex items-center gap-2">
                                <input
                                    type="text"
                                    value={data.subdomain}
                                    onChange={(e) => setData('subdomain', e.target.value)}
                                    className="flex-1 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
                                    placeholder="kasashop"
                                />
                                <span className="text-sm text-gray-600 dark:text-gray-300">.{baseDomain}</span>
                            </div>
                            {errors.subdomain && (
                                <p className="text-xs text-red-500 mt-1">{errors.subdomain}</p>
                            )}
                            {data.subdomain && (
                                <p className="text-xs text-emerald-600 dark:text-emerald-400">
                                    URL de votre boutique : <strong>{`${data.subdomain}.${baseDomain}`}</strong>
                                </p>
                            )}
                            <label className="inline-flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    checked={data.is_online}
                                    onChange={(e) => setData('is_online', e.target.checked)}
                                    className="rounded border-gray-300 dark:border-slate-600"
                                />
                                Mettre la boutique en ligne (accessible publiquement)
                            </label>
                            <button
                                type="submit"
                                disabled={processing}
                                className="mt-2 inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 disabled:opacity-50"
                            >
                                {processing ? 'Enregistrement...' : 'Enregistrer le domaine'}
                            </button>
                        </form>
                        <p className="text-[11px] text-gray-500 dark:text-gray-400">
                            Pensez à configurer un enregistrement DNS de type <strong>Wildcard CNAME</strong> ou
                            <strong> A</strong> pour que <code>*.{baseDomain}</code> pointe vers votre serveur.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
