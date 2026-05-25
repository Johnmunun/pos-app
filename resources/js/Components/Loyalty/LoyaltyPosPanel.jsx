import { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Gift, ScanLine } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function LoyaltyPosPanel({
    loyaltySettings,
    loyaltyModule = 'commerce',
    selectedCustomer,
    cartSubtotal = 0,
    pointsToRedeem,
    onPointsToRedeemChange,
    onAccountLoaded,
    onScanCustomer,
}) {
    const [account, setAccount] = useState(null);
    const [scanCode, setScanCode] = useState('');
    const [preview, setPreview] = useState(null);
    const [loading, setLoading] = useState(false);

    const enabled = loyaltySettings?.enabled === true;

    const loadAccount = useCallback(
        async (customerId, customerName) => {
            if (!enabled || !customerId) {
                setAccount(null);
                onAccountLoaded?.(null);
                return;
            }
            setLoading(true);
            try {
                const { data } = await axios.get(route('loyalty.account'), {
                    params: {
                        module: loyaltyModule,
                        customer_id: customerId,
                        customer_name: customerName,
                    },
                });
                const acc = data.account ?? null;
                setAccount(acc);
                onAccountLoaded?.(acc);
            } catch {
                setAccount(null);
                onAccountLoaded?.(null);
            } finally {
                setLoading(false);
            }
        },
        [enabled, loyaltyModule, onAccountLoaded],
    );

    useEffect(() => {
        if (selectedCustomer?.id) {
            loadAccount(selectedCustomer.id, selectedCustomer.full_name || selectedCustomer.name);
        } else {
            setAccount(null);
            onPointsToRedeemChange?.(0);
            onAccountLoaded?.(null);
        }
    }, [selectedCustomer?.id, selectedCustomer?.full_name, loadAccount, onAccountLoaded, onPointsToRedeemChange]);

    useEffect(() => {
        if (!enabled || !account || !selectedCustomer?.id || cartSubtotal <= 0) {
            setPreview(null);
            return;
        }
        const pts = Number(pointsToRedeem) || 0;
        if (pts <= 0) {
            setPreview(null);
            return;
        }
        const t = setTimeout(async () => {
            try {
                const { data } = await axios.get(route('loyalty.preview'), {
                    params: {
                        module: loyaltyModule,
                        customer_id: selectedCustomer.id,
                        points: pts,
                        sale_subtotal: cartSubtotal,
                    },
                });
                setPreview(data);
            } catch (e) {
                setPreview(null);
                if (e?.response?.data?.message) {
                    toast.error(e.response.data.message);
                }
            }
        }, 300);

        return () => clearTimeout(t);
    }, [pointsToRedeem, cartSubtotal, account, selectedCustomer?.id, loyaltyModule, enabled]);

    const handleScan = async () => {
        const code = scanCode.trim();
        if (!code) return;
        try {
            const { data } = await axios.get(route('loyalty.lookup'), { params: { code } });
            const acc = data.account;
            if (!acc) {
                toast.error('Carte fidélité introuvable.');
                return;
            }
            if (acc.module && acc.module !== loyaltyModule) {
                toast.error(`Cette carte appartient au module « ${acc.module} », pas à cette caisse.`);
                return;
            }
            setAccount(acc);
            onAccountLoaded?.(acc);
            onScanCustomer?.({
                id: String(acc.customer_id),
                full_name: acc.customer_name || '',
            });
            toast.success(`Carte trouvée — ${acc.points_balance} pts`);
            setScanCode('');
        } catch {
            toast.error('Carte fidélité introuvable.');
        }
    };

    if (!enabled) {
        return (
            <p className="text-xs text-slate-500 dark:text-slate-400">
                Fidélité désactivée — activez-la dans Paramètres → Fidélité ou passez à un plan supérieur.
            </p>
        );
    }

    if (!selectedCustomer?.id) {
        return (
            <div className="rounded-lg border border-dashed border-slate-300 dark:border-slate-600 p-3">
                <p className="text-xs text-slate-500">Sélectionnez un client pour la fidélité ou scannez une carte.</p>
                <div className="flex gap-2 mt-2">
                    <Input
                        placeholder="N° carte / scan"
                        value={scanCode}
                        onChange={(e) => setScanCode(e.target.value)}
                        onKeyDown={(e) => e.key === 'Enter' && handleScan()}
                        className="h-8 text-sm"
                    />
                    <Button type="button" size="sm" variant="outline" onClick={handleScan}>
                        <ScanLine className="h-4 w-4" />
                    </Button>
                </div>
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-indigo-200/60 dark:border-indigo-800/50 bg-indigo-50/50 dark:bg-indigo-950/20 p-3 space-y-3">
            <div className="flex items-center justify-between gap-2">
                <span className="text-sm font-medium text-indigo-900 dark:text-indigo-100 flex items-center gap-1.5">
                    <Gift className="h-4 w-4" />
                    Fidélité
                </span>
                {loading ? (
                    <span className="text-xs text-slate-500">Chargement…</span>
                ) : account ? (
                    <span className="text-sm font-bold tabular-nums text-indigo-700 dark:text-indigo-300">
                        {account.points_balance} pts
                    </span>
                ) : null}
            </div>
            {account && (
                <>
                    <p className="text-xs text-slate-600 dark:text-slate-400 font-mono">{account.loyalty_number}</p>
                    <div className="space-y-1">
                        <Label htmlFor="loyalty_points_redeem" className="text-xs">
                            Points à utiliser (min. {account.min_points_redeem ?? loyaltySettings.min_points_redeem})
                        </Label>
                        <Input
                            id="loyalty_points_redeem"
                            type="number"
                            min={0}
                            max={account.points_balance}
                            value={pointsToRedeem ?? ''}
                            onChange={(e) => onPointsToRedeemChange?.(Math.max(0, parseInt(e.target.value, 10) || 0))}
                            className="h-9"
                        />
                    </div>
                    {preview && preview.discount_amount > 0 && (
                        <p className="text-xs text-emerald-700 dark:text-emerald-400 font-medium">
                            Réduction fidélité : −{Number(preview.discount_amount).toFixed(2)}
                        </p>
                    )}
                </>
            )}
        </div>
    );
}
