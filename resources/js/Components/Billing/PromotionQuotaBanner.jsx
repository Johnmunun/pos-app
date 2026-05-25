import { Link } from '@inertiajs/react';
import { AlertTriangle, Sparkles } from 'lucide-react';
import { Button } from '@/Components/ui/button';

function formatLimit(limit) {
    if (limit === null || limit === undefined) return 'illimité';
    return String(limit);
}

/**
 * @param {{ quota?: { products?: object, campaigns?: object, plan_name?: string|null }, mode?: 'products'|'campaigns'|'both' }} props
 */
export default function PromotionQuotaBanner({ quota, mode = 'campaigns' }) {
    if (!quota) return null;

    const showProducts = mode === 'products' || mode === 'both';
    const showCampaigns = mode === 'campaigns' || mode === 'both';

    const blocks = [];
    if (showCampaigns && quota.campaigns?.enabled) {
        blocks.push({
            label: 'Promotions actives',
            used: quota.campaigns.used ?? 0,
            limit: quota.campaigns.limit,
            atLimit: quota.campaigns.at_limit,
        });
    }
    if (showProducts && quota.products?.enabled) {
        blocks.push({
            label: 'Produits en promotion',
            used: quota.products.used ?? 0,
            limit: quota.products.limit,
            atLimit: quota.products.at_limit,
        });
    }

    if (blocks.length === 0) return null;

    const anyAtLimit = blocks.some((b) => b.atLimit);
    const planLabel = quota.plan_name ? `Plan ${quota.plan_name}` : 'Votre plan';

    return (
        <div
            className={`rounded-xl border px-4 py-3 mb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 ${
                anyAtLimit
                    ? 'border-amber-300/80 bg-amber-50 dark:bg-amber-950/30 dark:border-amber-700/60'
                    : 'border-slate-200 dark:border-slate-700 bg-slate-50/80 dark:bg-slate-800/40'
            }`}
        >
            <div className="flex items-start gap-3 min-w-0">
                {anyAtLimit ? (
                    <AlertTriangle className="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                ) : (
                    <Sparkles className="h-5 w-5 text-indigo-600 dark:text-indigo-400 shrink-0 mt-0.5" />
                )}
                <div className="min-w-0">
                    <p className="text-sm font-medium text-slate-900 dark:text-slate-100">
                        {blocks.map((b, i) => (
                            <span key={b.label}>
                                {i > 0 ? ' · ' : ''}
                                {b.label} :{' '}
                                <span className={b.atLimit ? 'text-amber-700 dark:text-amber-300' : ''}>
                                    {b.used} / {formatLimit(b.limit)}
                                </span>
                            </span>
                        ))}
                    </p>
                    <p className="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                        {planLabel}
                        {anyAtLimit
                            ? ' — limite atteinte. Seules les promotions expirées ou désactivées libèrent une place.'
                            : ' — les promotions expirées ou désactivées ne comptent pas dans le quota.'}
                    </p>
                </div>
            </div>
            {anyAtLimit && (
                <Button variant="outline" size="sm" className="shrink-0" asChild>
                    <Link href={route('billing.onboarding.payment')}>Mettre à niveau</Link>
                </Button>
            )}
        </div>
    );
}
