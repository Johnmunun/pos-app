import { useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Search, Code2, Activity, Sparkles, Lock, BarChart3 } from 'lucide-react';
import axios from 'axios';

export default function EcommerceMarketingIndex({
    shop,
    marketing,
    marketingProEnabled = false,
    audienceAnalyticsEnabled = false,
}) {
    const sanitizeShopName = (name) => String(name || '')
        .replace(/\s+[—-]\s+Point de vente principal$/i, '')
        .trim();

    const { auth } = usePage().props;
    const planFeatures = auth?.planFeatures || {};

    const { data, setData, put, processing, errors } = useForm({
        seo_title: marketing?.seo_title ?? '',
        seo_description: marketing?.seo_description ?? '',
        seo_keywords: marketing?.seo_keywords ?? '',
        seo_indexing_enabled: marketing?.seo_indexing_enabled ?? true,
        facebook_pixel_id: marketing?.facebook_pixel_id ?? '',
        tiktok_pixel_id: marketing?.tiktok_pixel_id ?? '',
        google_analytics_id: marketing?.google_analytics_id ?? '',
        google_tag_manager_id: marketing?.google_tag_manager_id ?? '',
        meta_verification: marketing?.meta_verification ?? '',
        marketing_notes: marketing?.marketing_notes ?? '',
    });

    const [aiBrief, setAiBrief] = useState('');
    const [aiChannel, setAiChannel] = useState('generic');
    const [aiTone, setAiTone] = useState('pro');
    const [aiOffer, setAiOffer] = useState('');
    const [aiAnswer, setAiAnswer] = useState('');
    const [aiLoading, setAiLoading] = useState(false);
    const [aiError, setAiError] = useState('');

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('ecommerce.marketing.update'), { preserveScroll: true });
    };

    const saveAll = () => {
        put(route('ecommerce.marketing.update'), { preserveScroll: true });
    };

    const runAi = async () => {
        setAiError('');
        setAiAnswer('');
        if (!aiBrief.trim()) {
            setAiError('Décrivez votre objectif ou campagne.');
            return;
        }
        setAiLoading(true);
        try {
            const { data: res } = await axios.post(route('ecommerce.marketing.ai-suggest'), {
                brief: aiBrief.trim(),
                channel: aiChannel,
                tone: aiTone,
                offer: aiOffer.trim() || null,
            });
            setAiAnswer(res?.answer || '');
        } catch (err) {
            const msg = err.response?.data?.message || err.message || 'Erreur lors de l’appel IA.';
            setAiError(msg);
        } finally {
            setAiLoading(false);
        }
    };

    const upgradeHint = (
        <div className="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50/90 dark:bg-amber-950/30 p-4 text-sm text-amber-900 dark:text-amber-100">
            <div className="flex items-start gap-2">
                <Lock className="h-5 w-5 shrink-0 mt-0.5" />
                <div>
                    <p className="font-medium">Inclus dans les plans Pro et Enterprise</p>
                    <p className="mt-1 text-amber-800/90 dark:text-amber-200/90">
                        Passez au plan supérieur pour activer les pixels (Meta, TikTok, Google), les balises GTM / GA4 et le studio IA
                        marketing. Le référencement SEO ci-dessous reste disponible sur tous les plans.
                    </p>
                </div>
            </div>
        </div>
    );

    const audienceHint = !audienceAnalyticsEnabled && (
        <p className="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-2">
            <BarChart3 className="h-3.5 w-3.5" />
            La carte &laquo; audience vitrine &raquo; sur le tableau de bord (pays / villes) nécessite les analytics avancés (plan Pro+).
        </p>
    );

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
                                Boutique : <span className="font-medium">{sanitizeShopName(shop.name)}</span>
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
                            <CardDescription>
                                Visible sur tous les plans — titre, description et indexation pour la vitrine publique.
                            </CardDescription>
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
                                        placeholder="Courte description pour Google, partages sociaux, etc."
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
                                        placeholder="ex: boutique, ecommerce, livraison"
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
                                        Autoriser l&apos;indexation (Google, Bing, etc.)
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

                    <Card className={`bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 ${!marketingProEnabled ? 'relative' : ''}`}>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Code2 className="h-5 w-5 text-sky-500" />
                                Pixels &amp; mesure
                            </CardTitle>
                            <CardDescription>
                                Meta Pixel, TikTok, Google Tag Manager ou GA4 — déclenchés sur la vitrine publique uniquement.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className={`space-y-3 ${!marketingProEnabled ? 'pointer-events-none opacity-60' : ''}`}>
                            {!marketingProEnabled && (
                                <div className="pointer-events-auto opacity-100 mb-2">{upgradeHint}</div>
                            )}
                            <div className="space-y-2">
                                <Label htmlFor="facebook_pixel_id">ID Pixel Facebook (Meta)</Label>
                                <Input
                                    id="facebook_pixel_id"
                                    value={data.facebook_pixel_id}
                                    onChange={(e) => setData('facebook_pixel_id', e.target.value)}
                                    placeholder="ex: 123456789012345"
                                    disabled={!marketingProEnabled}
                                />
                                {errors.facebook_pixel_id && (
                                    <p className="text-sm text-red-600">{errors.facebook_pixel_id}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tiktok_pixel_id">ID Pixel TikTok</Label>
                                <Input
                                    id="tiktok_pixel_id"
                                    value={data.tiktok_pixel_id}
                                    onChange={(e) => setData('tiktok_pixel_id', e.target.value)}
                                    placeholder="ex: XXXXXXXXXXXXXXXX"
                                    disabled={!marketingProEnabled}
                                />
                                {errors.tiktok_pixel_id && (
                                    <p className="text-sm text-red-600">{errors.tiktok_pixel_id}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="google_tag_manager_id">Google Tag Manager (recommandé)</Label>
                                <Input
                                    id="google_tag_manager_id"
                                    value={data.google_tag_manager_id}
                                    onChange={(e) => setData('google_tag_manager_id', e.target.value)}
                                    placeholder="GTM-XXXXXXX"
                                    disabled={!marketingProEnabled}
                                />
                                {errors.google_tag_manager_id && (
                                    <p className="text-sm text-red-600">{errors.google_tag_manager_id}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="google_analytics_id">Google Analytics (GA4)</Label>
                                <Input
                                    id="google_analytics_id"
                                    value={data.google_analytics_id}
                                    onChange={(e) => setData('google_analytics_id', e.target.value)}
                                    placeholder="G-XXXXXXXX (si pas de GTM)"
                                    disabled={!marketingProEnabled}
                                />
                                <p className="text-[11px] text-slate-500 dark:text-slate-400">
                                    Si GTM est renseigné, configurez GA4 dans GTM ; sinon ce champ charge gtag.js directement.
                                </p>
                                {errors.google_analytics_id && (
                                    <p className="text-sm text-red-600">{errors.google_analytics_id}</p>
                                )}
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="meta_verification">Vérification Google Search Console</Label>
                                <Input
                                    id="meta_verification"
                                    value={data.meta_verification}
                                    onChange={(e) => setData('meta_verification', e.target.value)}
                                    placeholder="Contenu du meta google-site-verification"
                                    disabled={!marketingProEnabled}
                                />
                                {errors.meta_verification && (
                                    <p className="text-sm text-red-600">{errors.meta_verification}</p>
                                )}
                            </div>
                            {audienceHint}
                            {marketingProEnabled && (
                                <div className="flex justify-end pt-2">
                                    <Button type="button" variant="secondary" size="sm" onClick={saveAll} disabled={processing}>
                                        Enregistrer les pixels
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className={`bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700 ${!marketingProEnabled ? '' : ''}`}>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Sparkles className="h-5 w-5 text-violet-500" />
                            Studio IA marketing
                        </CardTitle>
                        <CardDescription>
                            Briefs campagnes, accroches, structure d’offre, UTM — pensé comme un brief agence (plans Pro / Enterprise).
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {!marketingProEnabled ? (
                            upgradeHint
                        ) : (
                            <>
                                <div className="grid sm:grid-cols-3 gap-3">
                                    <div className="space-y-2">
                                        <Label>Canal</Label>
                                        <select
                                            className="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
                                            value={aiChannel}
                                            onChange={(e) => setAiChannel(e.target.value)}
                                        >
                                            <option value="generic">Multi-canal</option>
                                            <option value="facebook">Facebook Ads</option>
                                            <option value="instagram">Instagram</option>
                                            <option value="tiktok">TikTok</option>
                                            <option value="google">Google Ads</option>
                                            <option value="newsletter">Email / newsletter</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2">
                                        <Label>Ton</Label>
                                        <select
                                            className="w-full rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 px-3 py-2 text-sm"
                                            value={aiTone}
                                            onChange={(e) => setAiTone(e.target.value)}
                                        >
                                            <option value="pro">Professionnel</option>
                                            <option value="direct">Direct / performance</option>
                                            <option value="luxe">Premium</option>
                                            <option value="amicable">Chaleureux</option>
                                        </select>
                                    </div>
                                    <div className="space-y-2 sm:col-span-1">
                                        <Label htmlFor="ai_offer">Promo / offre (optionnel)</Label>
                                        <Input
                                            id="ai_offer"
                                            value={aiOffer}
                                            onChange={(e) => setAiOffer(e.target.value)}
                                            placeholder="-20 % cette semaine…"
                                        />
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="ai_brief">Brief marketing</Label>
                                    <Textarea
                                        id="ai_brief"
                                        value={aiBrief}
                                        onChange={(e) => setAiBrief(e.target.value)}
                                        placeholder="Objectif, produit cible, marché, budget approximatif, dates, contraintes légales…"
                                        rows={5}
                                    />
                                </div>
                                <div className="flex flex-wrap gap-2">
                                    <Button type="button" onClick={runAi} disabled={aiLoading}>
                                        {aiLoading ? 'Génération…' : 'Générer avec l’IA'}
                                    </Button>
                                    {planFeatures.ai_assistant === false && (
                                        <span className="text-xs text-amber-600 dark:text-amber-400 self-center">
                                            Astuce : l’assistant IA global du compte peut être limité selon le plan ; ce studio est piloté par
                                            l’API OpenAI côté serveur si une clé est configurée.
                                        </span>
                                    )}
                                </div>
                                {aiError && <p className="text-sm text-red-600">{aiError}</p>}
                                {aiAnswer && (
                                    <div className="rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-900/50 p-4 max-h-[480px] overflow-y-auto">
                                        <pre className="whitespace-pre-wrap text-sm text-slate-800 dark:text-slate-100 font-sans">
                                            {aiAnswer}
                                        </pre>
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>

                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <Activity className="h-5 w-5 text-amber-500" />
                            Notes internes
                        </CardTitle>
                        <CardDescription>
                            Mémo équipe (non affiché aux visiteurs). Idéal pour le suivi des campagnes et partenaires.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <Label htmlFor="marketing_notes">Notes</Label>
                        <Textarea
                            id="marketing_notes"
                            value={data.marketing_notes}
                            onChange={(e) => setData('marketing_notes', e.target.value)}
                            placeholder="Campagnes, codes partenaires, audiences sauvegardées…"
                            rows={4}
                        />
                        {errors.marketing_notes && (
                            <p className="text-sm text-red-600">{errors.marketing_notes}</p>
                        )}
                        <div className="flex justify-end pt-2">
                            <Button type="button" variant="outline" onClick={saveAll} disabled={processing}>
                                Enregistrer les notes
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <p className="text-xs text-slate-500 dark:text-slate-400">
                    <Link href={route('ecommerce.dashboard')} className="underline hover:text-slate-700 dark:hover:text-slate-200">
                        Tableau de bord e-commerce
                    </Link>
                    {' — '}statistiques de visites par pays (période des filtres) si votre plan inclut les analytics avancés.
                </p>
            </div>
        </AppLayout>
    );
}
