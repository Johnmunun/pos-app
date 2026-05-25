import { useEffect, useRef } from 'react';
import { Badge } from '@/Components/ui/badge';
import { Crown, QrCode } from 'lucide-react';

const TIER_LABELS = {
    bronze: 'Bronze',
    silver: 'Silver',
    gold: 'Gold',
    vip: 'VIP',
};

const TIER_STYLES = {
    bronze: 'from-amber-700/90 to-amber-900',
    silver: 'from-slate-400/90 to-slate-600',
    gold: 'from-yellow-500/90 to-amber-600',
    vip: 'from-violet-600/90 to-indigo-900',
};

export default function LoyaltyCard({ account, compact = false }) {
    const canvasRef = useRef(null);

    useEffect(() => {
        if (!account?.loyalty_number || !canvasRef.current) return;
        let cancelled = false;
        import('qrcode').then((QRCode) => {
            if (cancelled || !canvasRef.current) return;
            QRCode.toCanvas(canvasRef.current, account.loyalty_number, {
                width: compact ? 96 : 128,
                margin: 1,
                color: { dark: '#0f172a', light: '#ffffff' },
            }).catch(() => {});
        }).catch(() => {});

        return () => {
            cancelled = true;
        };
    }, [account?.loyalty_number, compact]);

    if (!account) return null;

    const tier = account.tier || 'bronze';
    const gradient = TIER_STYLES[tier] || TIER_STYLES.bronze;

    return (
        <div
            className={`relative overflow-hidden rounded-2xl bg-gradient-to-br ${gradient} text-white shadow-lg ${
                compact ? 'p-4' : 'p-5'
            }`}
        >
            <div className="absolute -right-6 -top-6 h-24 w-24 rounded-full bg-white/10" />
            <div className="absolute -bottom-8 -left-4 h-28 w-28 rounded-full bg-white/5" />
            <div className="relative flex flex-col sm:flex-row gap-4 items-start sm:items-center justify-between">
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2 mb-1">
                        <Crown className="h-4 w-4 opacity-90" />
                        <span className="text-xs uppercase tracking-widest opacity-80">Carte fidélité</span>
                    </div>
                    <p className={`font-semibold truncate ${compact ? 'text-base' : 'text-lg'}`}>
                        {account.customer_name || 'Client fidèle'}
                    </p>
                    <p className="font-mono text-sm mt-1 tracking-wider opacity-95">{account.loyalty_number}</p>
                    <div className="mt-3 flex flex-wrap items-center gap-2">
                        <Badge className="bg-white/20 text-white border-0 hover:bg-white/20">
                            {TIER_LABELS[tier] || tier}
                        </Badge>
                        <span className="text-2xl font-bold tabular-nums">
                            {account.points_balance?.toLocaleString('fr-FR') ?? 0}{' '}
                            <span className="text-sm font-normal opacity-80">pts</span>
                        </span>
                    </div>
                </div>
                <div className="shrink-0 flex flex-col items-center gap-1 bg-white rounded-xl p-2">
                    <canvas ref={canvasRef} className="rounded" />
                    <span className="text-[10px] text-slate-600 flex items-center gap-0.5">
                        <QrCode className="h-3 w-3" /> Scan caisse
                    </span>
                </div>
            </div>
        </div>
    );
}
