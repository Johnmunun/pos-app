import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Settings, Truck, CreditCard, Sparkles, Globe2, Star, Search, ArrowUp, ArrowDown, X, Package, Plus, Download, HelpCircle, Trash2 } from 'lucide-react';
import { cardShell, pageY } from '@/lib/layoutClasses';
import { toast } from 'react-hot-toast';
import { formatCurrency } from '@/lib/currency';

export default function EcommerceSettingsIndex({
    shop,
    ecommerce_base_domain,
    storefront_use_flat_shipping = false,
    storefront_flat_shipping_amount = 0,
    ai_support_enabled = false,
    ai_support_tone = 'friendly',
    ai_support_shipping_policy = '',
    ai_support_returns_policy = '',
    ai_support_welcome_message = '',
    ai_support_faq = [],
    ai_support_stats = {},
    ai_semantic_search_enabled = false,
    featured_product_ids = [],
    published_products = [],
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
        ai_support_welcome_message: ai_support_welcome_message || '',
        ai_support_faq: Array.isArray(ai_support_faq) ? ai_support_faq : [],
        ai_semantic_search_enabled: Boolean(ai_semantic_search_enabled),
    });

    const MAX_FAQ = 10;
    const [faqSuggestions, setFaqSuggestions] = useState([]);
    const [faqSuggestLoading, setFaqSuggestLoading] = useState(false);

    const generateFaqSuggestions = async () => {
        const current = (aiSupportForm.data.ai_support_faq || []).filter(
            (f) => String(f?.question || '').trim() && String(f?.answer || '').trim()
        );
        if (current.length >= MAX_FAQ) {
            toast.error(`Maximum ${MAX_FAQ} FAQ atteint.`);
            return;
        }
        setFaqSuggestLoading(true);
        setFaqSuggestions([]);
        try {
            const { data } = await axios.post(route('ecommerce.settings.ai-support.suggest-faq'), {
                count: Math.min(5, MAX_FAQ - current.length),
                shipping_policy: aiSupportForm.data.ai_support_shipping_policy || '',
                returns_policy: aiSupportForm.data.ai_support_returns_policy || '',
                tone: aiSupportForm.data.ai_support_tone || 'friendly',
                existing_faq: current,
            });
            const list = data?.suggestions || [];
            setFaqSuggestions(list);
            if (list.length === 0) {
                toast.error(data?.message || 'Aucune suggestion générée.');
            } else {
                toast.success(data?.message || `${list.length} suggestion(s) prête(s).`);
            }
        } catch (err) {
            toast.error(err.response?.data?.message || 'Impossible de générer les FAQ.');
        } finally {
            setFaqSuggestLoading(false);
        }
    };

    const addFaqSuggestion = (item) => {
        const current = aiSupportForm.data.ai_support_faq || [];
        if (current.length >= MAX_FAQ) {
            toast.error(`Maximum ${MAX_FAQ} FAQ.`);
            return;
        }
        aiSupportForm.setData('ai_support_faq', [...current, { question: item.question, answer: item.answer }]);
        setFaqSuggestions((prev) => prev.filter((s) => s.question !== item.question));
        toast.success('FAQ ajoutée — enregistrez pour publier.');
    };

    const addAllFaqSuggestions = () => {
        const current = [...(aiSupportForm.data.ai_support_faq || [])];
        let added = 0;
        for (const item of faqSuggestions) {
            if (current.length >= MAX_FAQ) break;
            current.push({ question: item.question, answer: item.answer });
            added++;
        }
        aiSupportForm.setData('ai_support_faq', current);
        setFaqSuggestions([]);
        if (added > 0) {
            toast.success(`${added} FAQ ajoutée(s). Enregistrez pour publier.`);
        }
    };

    const MAX_FEATURED = 12;
    const [selectedFeaturedIds, setSelectedFeaturedIds] = useState(featured_product_ids || []);
    const [featuredSearch, setFeaturedSearch] = useState('');
    const [savingFeatured, setSavingFeatured] = useState(false);

    const publishedById = useMemo(
        () => Object.fromEntries((published_products || []).map((p) => [p.id, p])),
        [published_products]
    );

    const filteredPublished = useMemo(() => {
        const q = featuredSearch.trim().toLowerCase();
        if (!q) return published_products || [];
        return (published_products || []).filter((p) => p.name?.toLowerCase().includes(q));
    }, [published_products, featuredSearch]);

    const toggleFeatured = (productId) => {
        setSelectedFeaturedIds((prev) => {
            if (prev.includes(productId)) {
                return prev.filter((id) => id !== productId);
            }
            if (prev.length >= MAX_FEATURED) {
                toast.error(`Maximum ${MAX_FEATURED} produits en vedette.`);
                return prev;
            }
            return [...prev, productId];
        });
    };

    const moveFeatured = (index, direction) => {
        setSelectedFeaturedIds((prev) => {
            const next = [...prev];
            const target = index + direction;
            if (target < 0 || target >= next.length) return prev;
            [next[index], next[target]] = [next[target], next[index]];
            return next;
        });
    };

    const handleSaveFeatured = () => {
        setSavingFeatured(true);
        router.put(
            route('ecommerce.settings.featured-products.update'),
            { featured_product_ids: selectedFeaturedIds },
            {
                preserveScroll: true,
                onSuccess: () => toast.success('Produits en vedette enregistrés.'),
                onError: () => toast.error('Impossible d\'enregistrer les produits en vedette.'),
                onFinish: () => setSavingFeatured(false),
            }
        );
    };

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
            ai_support_welcome_message: aiSupportForm.data.ai_support_welcome_message || '',
            ai_support_faq: (aiSupportForm.data.ai_support_faq || []).filter(
                (f) => String(f?.question || '').trim() && String(f?.answer || '').trim()
            ),
            ai_semantic_search_enabled: Boolean(aiSupportForm.data.ai_semantic_search_enabled),
        }, { preserveScroll: true });
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white tracking-tight">
                        Paramètres E-commerce
                    </h2>
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1.5 max-w-2xl hidden sm:block">
                        Domaine vitrine, livraison, IA et options boutique.
                    </p>
                </div>
            }
        >
            <Head title="Paramètres - E-commerce" />

            <div className={pageY}>
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6 lg:space-y-8">
                {/* Boutique : devise + domaine */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
                <Card className={`${cardShell} h-full`}>
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

                <Card className={`${cardShell} h-full`}>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Globe2 className="h-5 w-5 text-blue-500" />
                            Domaine de la boutique en ligne
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 flex flex-col h-full">
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Choisissez le sous-domaine de votre boutique. Exemple : si vous mettez <strong>kasashop</strong>,
                            vos clients accéderont à votre boutique sur <strong>kasashop.{baseDomain}</strong>.
                        </p>
                        <form onSubmit={handleSubmitDomain} className="space-y-3 flex-1 flex flex-col">
                            <div className="flex flex-col sm:flex-row sm:items-center gap-2">
                                <input
                                    type="text"
                                    value={data.subdomain}
                                    onChange={(e) => setData('subdomain', e.target.value)}
                                    className="flex-1 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
                                    placeholder="kasashop"
                                />
                                <span className="text-sm text-gray-600 dark:text-gray-300 shrink-0">.{baseDomain}</span>
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
                            <div className="pt-1 mt-auto">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 disabled:opacity-50"
                                >
                                    {processing ? 'Enregistrement...' : 'Enregistrer le domaine'}
                                </button>
                            </div>
                        </form>
                        <p className="text-[11px] text-gray-500 dark:text-gray-400">
                            Pensez à configurer un enregistrement DNS de type <strong>Wildcard CNAME</strong> ou
                            <strong> A</strong> pour que <code>*.{baseDomain}</code> pointe vers votre serveur.
                        </p>
                    </CardContent>
                </Card>
                </div>

                {/* Livraison, raccourcis, CMS */}
                <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6 items-stretch">
                <Card className={`${cardShell} h-full`}>
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

                <Card className={`${cardShell} h-full`}>
                    <CardHeader>
                        <CardTitle className="text-base">Autres paramètres</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <Link
                            href={route('ecommerce.shipping.index')}
                            className="flex items-center gap-2 p-3 rounded-lg border border-gray-100 dark:border-slate-700/80 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition"
                        >
                            <Truck className="h-5 w-5 text-gray-500 dark:text-gray-400 shrink-0" />
                            <span className="text-sm font-medium">Méthodes de livraison</span>
                        </Link>
                        <Link
                            href={route('ecommerce.payments.index')}
                            className="flex items-center gap-2 p-3 rounded-lg border border-gray-100 dark:border-slate-700/80 text-gray-900 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition"
                        >
                            <CreditCard className="h-5 w-5 text-gray-500 dark:text-gray-400 shrink-0" />
                            <span className="text-sm font-medium">Méthodes de paiement</span>
                        </Link>
                        </div>
                    </CardContent>
                </Card>

                <Card className={`${cardShell} h-full`}>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Sparkles className="h-5 w-5 text-amber-500" />
                            Vitrine e-commerce (CMS)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3 flex flex-col h-full">
                        <p className="text-xs text-gray-500 dark:text-gray-400 flex-1">
                            Personnalisez le contenu de la page boutique visible par vos clients (section héro, textes, etc.).
                        </p>
                        <Link
                            href={route('ecommerce.storefront.cms')}
                            className="inline-flex items-center justify-center gap-2 px-3 py-2.5 rounded-lg border border-gray-200 dark:border-slate-700 text-xs font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-slate-700/60 transition w-full sm:w-auto"
                        >
                            <Sparkles className="h-4 w-4 text-amber-500" />
                            Ouvrir le CMS vitrine
                        </Link>
                    </CardContent>
                </Card>
                </div>

                {/* IA : support + quotas */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
                <Card className={`${cardShell} h-full lg:col-span-2`}>
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
                        <form onSubmit={handleSubmitAiSupport} className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Ton de réponse</label>
                                <select
                                    value={aiSupportForm.data.ai_support_tone}
                                    onChange={(e) => aiSupportForm.setData('ai_support_tone', e.target.value)}
                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
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
                            <div className="md:col-span-2">
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                    Message d&apos;accueil assistant (optionnel)
                                </label>
                                <textarea
                                    value={aiSupportForm.data.ai_support_welcome_message}
                                    onChange={(e) => aiSupportForm.setData('ai_support_welcome_message', e.target.value)}
                                    rows={2}
                                    maxLength={500}
                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-3 py-2 text-gray-900 dark:text-gray-100"
                                    placeholder="Laissez vide pour le message automatique avec le nom de la boutique."
                                />
                            </div>

                            <div className="md:col-span-2 rounded-xl border border-slate-200 dark:border-slate-700 p-4 space-y-3">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                    <p className="text-xs font-semibold text-gray-700 dark:text-gray-200 flex items-center gap-1.5">
                                        <HelpCircle className="h-4 w-4 text-amber-500" />
                                        FAQ assistant (max {MAX_FAQ})
                                    </p>
                                    <div className="flex flex-wrap gap-2">
                                        <button
                                            type="button"
                                            disabled={faqSuggestLoading || (aiSupportForm.data.ai_support_faq || []).length >= MAX_FAQ}
                                            onClick={generateFaqSuggestions}
                                            className="inline-flex items-center gap-1 rounded-md border border-violet-300 dark:border-violet-700 bg-violet-50 dark:bg-violet-950/40 px-2.5 py-1.5 text-xs font-medium text-violet-800 dark:text-violet-200 hover:bg-violet-100 dark:hover:bg-violet-950/60 disabled:opacity-50"
                                        >
                                            <Sparkles className={`h-3.5 w-3.5 ${faqSuggestLoading ? 'animate-pulse' : ''}`} />
                                            {faqSuggestLoading ? 'Génération…' : 'Suggérer avec l’IA'}
                                        </button>
                                        <button
                                            type="button"
                                            disabled={(aiSupportForm.data.ai_support_faq || []).length >= MAX_FAQ}
                                            onClick={() =>
                                                aiSupportForm.setData('ai_support_faq', [
                                                    ...(aiSupportForm.data.ai_support_faq || []),
                                                    { question: '', answer: '' },
                                                ])
                                            }
                                            className="inline-flex items-center gap-1 text-xs font-medium text-amber-700 dark:text-amber-300 hover:underline disabled:opacity-50"
                                        >
                                            <Plus className="h-3.5 w-3.5" />
                                            Ajouter vide
                                        </button>
                                    </div>
                                </div>
                                <p className="text-[11px] text-gray-500 dark:text-gray-400">
                                    Réponses prioritaires sur la vitrine. L’IA propose des FAQ à partir de vos politiques livraison/retours
                                    (complétez-les ci-dessus avant de générer).
                                </p>

                                {faqSuggestions.length > 0 && (
                                    <div className="rounded-lg border border-violet-200 dark:border-violet-800 bg-violet-50/50 dark:bg-violet-950/20 p-3 space-y-2">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <p className="text-xs font-semibold text-violet-900 dark:text-violet-200">
                                                Suggestions IA ({faqSuggestions.length})
                                            </p>
                                            <button
                                                type="button"
                                                onClick={addAllFaqSuggestions}
                                                className="text-xs font-medium text-violet-700 dark:text-violet-300 hover:underline"
                                            >
                                                Tout ajouter
                                            </button>
                                        </div>
                                        <ul className="space-y-2 max-h-48 overflow-y-auto">
                                            {faqSuggestions.map((item, idx) => (
                                                <li
                                                    key={`${item.question}-${idx}`}
                                                    className="rounded-md bg-white dark:bg-slate-900 border border-violet-100 dark:border-violet-900/50 p-2 text-xs"
                                                >
                                                    <p className="font-medium text-slate-800 dark:text-slate-100">{item.question}</p>
                                                    <p className="mt-1 text-slate-600 dark:text-slate-400 line-clamp-2">{item.answer}</p>
                                                    <button
                                                        type="button"
                                                        onClick={() => addFaqSuggestion(item)}
                                                        className="mt-2 text-violet-700 dark:text-violet-300 font-medium hover:underline"
                                                    >
                                                        + Ajouter cette FAQ
                                                    </button>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                {(aiSupportForm.data.ai_support_faq || []).length === 0 ? (
                                    <p className="text-xs text-slate-500 italic">Aucune FAQ — l’IA s’appuie sur les politiques et OpenAI.</p>
                                ) : (
                                    <ul className="space-y-3">
                                        {(aiSupportForm.data.ai_support_faq || []).map((item, index) => (
                                            <li
                                                key={index}
                                                className="rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50/80 dark:bg-slate-900/50 p-3 space-y-2"
                                            >
                                                <div className="flex justify-between gap-2">
                                                    <span className="text-[10px] font-medium text-slate-500">#{index + 1}</span>
                                                    <button
                                                        type="button"
                                                        onClick={() => {
                                                            const next = [...(aiSupportForm.data.ai_support_faq || [])];
                                                            next.splice(index, 1);
                                                            aiSupportForm.setData('ai_support_faq', next);
                                                        }}
                                                        className="text-slate-400 hover:text-rose-600"
                                                        aria-label="Supprimer"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                                <input
                                                    value={item.question || ''}
                                                    onChange={(e) => {
                                                        const next = [...(aiSupportForm.data.ai_support_faq || [])];
                                                        next[index] = { ...next[index], question: e.target.value };
                                                        aiSupportForm.setData('ai_support_faq', next);
                                                    }}
                                                    placeholder="Question (ex: Quels sont vos horaires ?)"
                                                    maxLength={200}
                                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-2 py-1.5"
                                                />
                                                <textarea
                                                    value={item.answer || ''}
                                                    onChange={(e) => {
                                                        const next = [...(aiSupportForm.data.ai_support_faq || [])];
                                                        next[index] = { ...next[index], answer: e.target.value };
                                                        aiSupportForm.setData('ai_support_faq', next);
                                                    }}
                                                    rows={2}
                                                    maxLength={800}
                                                    placeholder="Réponse officielle de la boutique"
                                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm px-2 py-1.5"
                                                />
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                            </div>
                            <button
                                type="submit"
                                disabled={aiSupportForm.processing}
                                className="inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 disabled:opacity-50"
                            >
                                {aiSupportForm.processing ? 'Enregistrement...' : 'Enregistrer support IA'}
                            </button>
                        </form>

                        <div className="mt-6 pt-6 border-t border-slate-200 dark:border-slate-700">
                                <div className="flex flex-wrap items-center justify-between gap-2 mb-3">
                                    <p className="text-xs font-semibold text-slate-700 dark:text-slate-200">
                                        Statistiques assistant ({ai_support_stats?.days ?? 30} derniers jours)
                                    </p>
                                    <a
                                        href={route('ecommerce.settings.ai-support.export', { days: ai_support_stats?.days ?? 30 })}
                                        className="inline-flex items-center gap-1.5 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-800 px-2.5 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-700"
                                    >
                                        <Download className="h-3.5 w-3.5" />
                                        Export CSV
                                    </a>
                                </div>
                        {ai_support_stats?.total_asks > 0 || ai_support_stats?.feedback_helpful > 0 ? (
                            <>
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 text-center">
                                    <div className="rounded-lg bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 p-2">
                                        <p className="text-lg font-bold text-slate-900 dark:text-white">{ai_support_stats.total_asks ?? 0}</p>
                                        <p className="text-[10px] text-slate-500">Questions</p>
                                    </div>
                                    <div className="rounded-lg bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 p-2">
                                        <p className="text-lg font-bold text-violet-600">{ai_support_stats.product_suggestions ?? 0}</p>
                                        <p className="text-[10px] text-slate-500">Avec produits</p>
                                    </div>
                                    <div className="rounded-lg bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 p-2">
                                        <p className="text-lg font-bold text-emerald-600">{ai_support_stats.feedback_helpful ?? 0}</p>
                                        <p className="text-[10px] text-slate-500">👍 Utiles</p>
                                    </div>
                                    <div className="rounded-lg bg-slate-50 dark:bg-slate-900/80 border border-slate-200 dark:border-slate-700 p-2">
                                        <p className="text-lg font-bold text-slate-900 dark:text-white">
                                            {ai_support_stats.feedback_rate_percent != null
                                                ? `${ai_support_stats.feedback_rate_percent}%`
                                                : '—'}
                                        </p>
                                        <p className="text-[10px] text-slate-500">Satisfaction</p>
                                    </div>
                                </div>
                                {(ai_support_stats.top_topics || []).length > 0 && (
                                    <ul className="mt-3 space-y-1 text-xs text-slate-600 dark:text-slate-300">
                                        {ai_support_stats.top_topics.map((row) => (
                                            <li key={row.topic} className="flex justify-between gap-2">
                                                <span>
                                                    {{
                                                        shipping: 'Livraison',
                                                        returns: 'Retours',
                                                        order_status: 'Commande',
                                                        availability: 'Stock',
                                                        product_search: 'Recherche',
                                                        general: 'Général',
                                                    }[row.topic] || row.topic}
                                                </span>
                                                <span className="font-medium">{row.total}</span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </>
                        ) : (
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                Les statistiques apparaîtront lorsque des clients utiliseront l’assistant sur la vitrine.
                            </p>
                        )}
                        </div>
                    </CardContent>
                </Card>

                <Card className={`${cardShell} h-full`}>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Sparkles className="h-5 w-5 text-amber-500" />
                            Quotas IA du mois
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-1 gap-3 text-sm h-full content-start">
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
                </div>

                <Card className={cardShell}>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Star className="h-5 w-5 text-amber-500" />
                            Produits en vedette (page d&apos;accueil)
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Choisissez jusqu&apos;à {MAX_FEATURED} produits publiés sur e-commerce. Ils apparaissent en premier sur la vitrine.
                            L&apos;ordre défini ici est respecté sur la boutique.
                        </p>

                        <div className="grid grid-cols-1 xl:grid-cols-2 gap-6">
                        <div className="space-y-4 min-w-0">
                        {selectedFeaturedIds.length > 0 && (
                            <div className="rounded-xl border border-amber-200/80 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-950/20 p-3 space-y-2">
                                <p className="text-xs font-semibold text-amber-800 dark:text-amber-200">
                                    Sélection ({selectedFeaturedIds.length}/{MAX_FEATURED})
                                </p>
                                <ul className="space-y-2">
                                    {selectedFeaturedIds.map((id, index) => {
                                        const product = publishedById[id];
                                        if (!product) return null;
                                        return (
                                            <li
                                                key={id}
                                                className="flex items-center gap-2 rounded-lg bg-white dark:bg-slate-900/80 border border-amber-100 dark:border-amber-900/30 p-2"
                                            >
                                                <div className="h-10 w-10 rounded-lg bg-gray-100 dark:bg-slate-800 overflow-hidden shrink-0 flex items-center justify-center">
                                                    {product.image_url ? (
                                                        <img src={product.image_url} alt="" className="h-full w-full object-cover" />
                                                    ) : (
                                                        <Package className="h-4 w-4 text-gray-400" />
                                                    )}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">{product.name}</p>
                                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                                        {formatCurrency(product.price_amount, product.price_currency)}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-1 shrink-0">
                                                    <button
                                                        type="button"
                                                        onClick={() => moveFeatured(index, -1)}
                                                        disabled={index === 0}
                                                        className="p-1.5 rounded-md border border-gray-200 dark:border-slate-700 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-slate-800"
                                                        title="Monter"
                                                    >
                                                        <ArrowUp className="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => moveFeatured(index, 1)}
                                                        disabled={index === selectedFeaturedIds.length - 1}
                                                        className="p-1.5 rounded-md border border-gray-200 dark:border-slate-700 disabled:opacity-40 hover:bg-gray-50 dark:hover:bg-slate-800"
                                                        title="Descendre"
                                                    >
                                                        <ArrowDown className="h-3.5 w-3.5" />
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleFeatured(id)}
                                                        className="p-1.5 rounded-md border border-red-200 dark:border-red-900/40 text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30"
                                                        title="Retirer"
                                                    >
                                                        <X className="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        )}

                        {selectedFeaturedIds.length === 0 && (
                            <div className="rounded-xl border border-dashed border-gray-200 dark:border-slate-700 p-6 text-center">
                                <Star className="h-8 w-8 text-gray-300 dark:text-slate-600 mx-auto mb-2" />
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Aucun produit en vedette. Sélectionnez des articles dans la liste à droite.
                                </p>
                            </div>
                        )}

                        <button
                            type="button"
                            onClick={handleSaveFeatured}
                            disabled={savingFeatured}
                            className="inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-xs font-semibold hover:bg-amber-700 disabled:opacity-50 w-full sm:w-auto"
                        >
                            {savingFeatured ? 'Enregistrement...' : 'Enregistrer les produits en vedette'}
                        </button>
                        </div>

                        <div className="space-y-3 min-w-0 flex flex-col">
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                type="search"
                                value={featuredSearch}
                                onChange={(e) => setFeaturedSearch(e.target.value)}
                                placeholder="Rechercher un produit publié..."
                                className="pl-9"
                            />
                        </div>

                        <div className="flex-1 min-h-[12rem] max-h-[min(28rem,50vh)] overflow-y-auto rounded-xl border border-gray-200 dark:border-slate-700 divide-y divide-gray-100 dark:divide-slate-800">
                            {filteredPublished.length === 0 ? (
                                <p className="p-4 text-sm text-gray-500 dark:text-gray-400">
                                    Aucun produit publié sur e-commerce. Activez &quot;Publier sur e-commerce&quot; sur vos fiches produits.
                                </p>
                            ) : (
                                filteredPublished.map((product) => {
                                    const checked = selectedFeaturedIds.includes(product.id);
                                    return (
                                        <label
                                            key={product.id}
                                            className={`flex items-center gap-3 p-3 cursor-pointer transition-colors ${
                                                checked ? 'bg-amber-50/80 dark:bg-amber-950/20' : 'hover:bg-gray-50 dark:hover:bg-slate-800/50'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={checked}
                                                onChange={() => toggleFeatured(product.id)}
                                                className="rounded border-gray-300 dark:border-slate-600 text-amber-600"
                                            />
                                            <div className="h-10 w-10 rounded-lg bg-gray-100 dark:bg-slate-800 overflow-hidden shrink-0 flex items-center justify-center">
                                                {product.image_url ? (
                                                    <img src={product.image_url} alt="" className="h-full w-full object-cover" />
                                                ) : (
                                                    <Package className="h-4 w-4 text-gray-400" />
                                                )}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-gray-900 dark:text-white truncate">{product.name}</p>
                                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                                    {formatCurrency(product.price_amount, product.price_currency)}
                                                </p>
                                            </div>
                                            {checked && (
                                                <Star className="h-4 w-4 text-amber-500 shrink-0 fill-amber-500" />
                                            )}
                                        </label>
                                    );
                                })
                            )}
                        </div>
                        </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
            </div>
        </AppLayout>
    );
}
