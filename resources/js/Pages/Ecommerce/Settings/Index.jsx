import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Settings, Truck, CreditCard, Sparkles, Globe2 } from 'lucide-react';

export default function EcommerceSettingsIndex({
    shop,
    ecommerce_base_domain,
    storefront_use_flat_shipping = false,
    storefront_flat_shipping_amount = 0,
    ai_support_enabled = false,
    ai_support_tone = 'friendly',
    ai_support_shipping_policy = '',
    ai_support_returns_policy = '',
    ai_semantic_search_enabled = false,
}) {
    const { props } = usePage();
    const planUsage = props?.auth?.planUsage || {};
    const productAiUsage = planUsage.ai_product_image_generate || { used: 0, limit: null, remaining: null };
    const mediaAiUsage = planUsage.ai_media_image_generate || { used: 0, limit: null, remaining: null };
    const globalCurrency = props?.shop?.currency || shop?.currency || 'USD';
    const baseDomain = ecommerce_base_domain || 'omnisolution.shop';

    const { data, setData, put, processing, errors } = useForm({
        subdomain: shop?.ecommerce_subdomain || '',
        is_online: shop?.ecommerce_is_online || false,
    });

    const shippingForm = useForm({
        storefront_use_flat_shipping: Boolean(storefront_use_flat_shipping),
        storefront_flat_shipping_amount:
            storefront_flat_shipping_amount !== undefined && storefront_flat_shipping_amount !== null
                ? String(storefront_flat_shipping_amount)
                : '0',
    });
    const aiSupportForm = useForm({
        ai_support_enabled: Boolean(ai_support_enabled),
        ai_support_tone: ai_support_tone || 'friendly',
        ai_support_shipping_policy: ai_support_shipping_policy || '',
        ai_support_returns_policy: ai_support_returns_policy || '',
        ai_semantic_search_enabled: Boolean(ai_semantic_search_enabled),
    });

    const handleSubmitDomain = (e) => {
        e.preventDefault();
        put(route('ecommerce.settings.domain.update'), {
            preserveScroll: true,
        });
    };

    const handleSubmitShipping = (e) => {
        e.preventDefault();
        const raw = String(shippingForm.data.storefront_flat_shipping_amount ?? '').replace(',', '.');
        const n = parseFloat(raw);
        router.put(route('ecommerce.settings.storefront-shipping.update'), {
            storefront_use_flat_shipping: Boolean(shippingForm.data.storefront_use_flat_shipping),
            storefront_flat_shipping_amount: Number.isFinite(n) ? Math.max(0, n) : 0,
        }, { preserveScroll: true });
    };

    const handleSubmitAiSupport = (e) => {
        e.preventDefault();
        router.put(route('ecommerce.settings.ai-support.update'), {
            ai_support_enabled: Boolean(aiSupportForm.data.ai_support_enabled),
            ai_support_tone: aiSupportForm.data.ai_support_tone || 'friendly',
            ai_support_shipping_policy: aiSupportForm.data.ai_support_shipping_policy || '',
            ai_support_returns_policy: aiSupportForm.data.ai_support_returns_policy || '',
            ai_semantic_search_enabled: Boolean(aiSupportForm.data.ai_semantic_search_enabled),
        }, { preserveScroll: true });
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
                        <CardTitle className="flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-amber-500" />
                            Support client IA e-commerce
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="rounded-md border border-amber-200/70 dark:border-amber-900/40 bg-amber-50/60 dark:bg-amber-950/20 p-3">
                            <p className="text-xs font-medium text-amber-900 dark:text-amber-200">
                                Boutique actuellement configurée
                            </p>
                            <p className="text-xs text-amber-800 dark:text-amber-300 mt-1">
                                {shop?.name || 'Boutique'}{shop?.id ? ` (ID: ${shop.id})` : ''}
                            </p>
                            <p className="text-[11px] text-amber-700/90 dark:text-amber-300/90 mt-1">
                                L’activation du support IA s’applique à cette boutique.
                            </p>
                        </div>
                        <form onSubmit={handleSubmitAiSupport} className="space-y-3">
                            <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    checked={Boolean(aiSupportForm.data.ai_support_enabled)}
                                    onChange={(e) => aiSupportForm.setData('ai_support_enabled', e.target.checked)}
                                    className="rounded border-gray-300 dark:border-slate-600"
                                />
                                Activer le support client IA sur la vitrine
                            </label>
                            <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    checked={Boolean(aiSupportForm.data.ai_semantic_search_enabled)}
                                    onChange={(e) => aiSupportForm.setData('ai_semantic_search_enabled', e.target.checked)}
                                    className="rounded border-gray-300 dark:border-slate-600"
                                />
                                Activer la recherche sémantique IA produits
                            </label>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Ton de réponse</label>
                                <select
                                    value={aiSupportForm.data.ai_support_tone}
                                    onChange={(e) => aiSupportForm.setData('ai_support_tone', e.target.value)}
                                    className="w-full max-w-xs rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
                                >
                                    <option value="friendly">Chaleureux</option>
                                    <option value="professional">Professionnel</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Politique livraison</label>
                                <textarea
                                    value={aiSupportForm.data.ai_support_shipping_policy}
                                    onChange={(e) => aiSupportForm.setData('ai_support_shipping_policy', e.target.value)}
                                    rows={3}
                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
                                    placeholder="Ex: Livraison en 24-72h selon zone, frais fixes, etc."
                                />
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Politique retours</label>
                                <textarea
                                    value={aiSupportForm.data.ai_support_returns_policy}
                                    onChange={(e) => aiSupportForm.setData('ai_support_returns_policy', e.target.value)}
                                    rows={3}
                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
                                    placeholder="Ex: Retours sous 7 jours si produit intact..."
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={aiSupportForm.processing}
                                className="inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 disabled:opacity-50"
                            >
                                {aiSupportForm.processing ? 'Enregistrement...' : 'Enregistrer support IA'}
                            </button>
                        </form>
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-amber-500" />
                            Quotas IA du mois
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <div className="rounded-md border border-amber-200/70 dark:border-amber-900/40 bg-amber-50/60 dark:bg-amber-950/20 p-3">
                            <p className="font-medium text-gray-900 dark:text-gray-100">Génération image produit IA</p>
                            <p className="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                {productAiUsage.limit == null
                                    ? `Utilisé: ${productAiUsage.used} (illimité)`
                                    : `Utilisé: ${productAiUsage.used}/${productAiUsage.limit} - Reste: ${Math.max(0, productAiUsage.remaining ?? 0)}`}
                            </p>
                        </div>
                        <div className="rounded-md border border-amber-200/70 dark:border-amber-900/40 bg-amber-50/60 dark:bg-amber-950/20 p-3">
                            <p className="font-medium text-gray-900 dark:text-gray-100">Génération image média IA (CMS)</p>
                            <p className="text-xs text-gray-600 dark:text-gray-300 mt-1">
                                {mediaAiUsage.limit == null
                                    ? `Utilisé: ${mediaAiUsage.used} (illimité)`
                                    : `Utilisé: ${mediaAiUsage.used}/${mediaAiUsage.limit} - Reste: ${Math.max(0, mediaAiUsage.remaining ?? 0)}`}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Truck className="h-5 w-5" />
                            Frais de livraison (vitrine)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Montant affiché sur le panier public (<code className="text-[11px]">/ecommerce/storefront/cart</code>). Le
                            client ne peut pas modifier ce montant. Exprimé en <strong>{globalCurrency}</strong> (même devise que
                            ci-dessus / devises tenant).
                        </p>
                        <form onSubmit={handleSubmitShipping} className="space-y-3">
                            <label className="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                <input
                                    type="checkbox"
                                    checked={Boolean(shippingForm.data.storefront_use_flat_shipping)}
                                    onChange={(e) =>
                                        shippingForm.setData('storefront_use_flat_shipping', e.target.checked)
                                    }
                                    className="rounded border-gray-300 dark:border-slate-600"
                                />
                                Utiliser des frais de livraison fixes sur la vitrine (le choix par méthode de livraison est masqué)
                            </label>
                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                    Montant ({globalCurrency})
                                </label>
                                <input
                                    type="text"
                                    inputMode="decimal"
                                    value={shippingForm.data.storefront_flat_shipping_amount}
                                    onChange={(e) => shippingForm.setData('storefront_flat_shipping_amount', e.target.value)}
                                    disabled={!shippingForm.data.storefront_use_flat_shipping}
                                    className="w-full max-w-xs rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100 disabled:opacity-50"
                                    placeholder="0"
                                />
                            </div>
                            {shippingForm.errors.storefront_flat_shipping_amount && (
                                <p className="text-xs text-red-500">{shippingForm.errors.storefront_flat_shipping_amount}</p>
                            )}
                            <button
                                type="submit"
                                disabled={shippingForm.processing}
                                className="inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 disabled:opacity-50"
                            >
                                {shippingForm.processing ? 'Enregistrement...' : 'Enregistrer les frais vitrine'}
                            </button>
                        </form>
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
