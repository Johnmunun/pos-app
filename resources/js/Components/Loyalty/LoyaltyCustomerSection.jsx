import { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import LoyaltyCard from '@/Components/Loyalty/LoyaltyCard';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Gift } from 'lucide-react';

function moduleFromRoutePrefix(routePrefix) {
    if (routePrefix === 'hardware') return 'hardware';
    if (routePrefix === 'commerce') return 'commerce';
    return 'pharmacy';
}

export default function LoyaltyCustomerSection({ customerId, customerName, routePrefix = 'pharmacy' }) {
    const [account, setAccount] = useState(null);
    const [history, setHistory] = useState([]);
    const [loading, setLoading] = useState(true);

    const loyaltyModule = moduleFromRoutePrefix(routePrefix);

    const load = useCallback(async () => {
        if (!customerId) {
            setAccount(null);
            setHistory([]);
            setLoading(false);
            return;
        }
        setLoading(true);
        try {
            const { data } = await axios.get(route('loyalty.account'), {
                params: {
                    module: loyaltyModule,
                    customer_id: String(customerId),
                    customer_name: customerName,
                },
            });
            const acc = data.account ?? null;
            setAccount(acc);
            if (acc?.id) {
                const hist = await axios.get(route('loyalty.history', acc.id));
                setHistory(hist.data.transactions ?? []);
            } else {
                setHistory([]);
            }
        } catch {
            setAccount(null);
            setHistory([]);
        } finally {
            setLoading(false);
        }
    }, [customerId, customerName, loyaltyModule]);

    useEffect(() => {
        load();
    }, [load]);

    if (loading) {
        return (
            <Card>
                <CardContent className="py-6 text-sm text-slate-500">Chargement fidélité…</CardContent>
            </Card>
        );
    }

    if (!account) {
        return null;
    }

    return (
        <div className="space-y-4">
            <LoyaltyCard account={{ ...account, customer_name: customerName || account.customer_name }} />
            {history.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="text-base flex items-center gap-2">
                            <Gift className="h-4 w-4" />
                            Historique fidélité
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="divide-y divide-slate-200 dark:divide-slate-700 text-sm max-h-64 overflow-y-auto">
                            {history.map((tx) => (
                                <li key={tx.id} className="py-2 flex justify-between gap-2">
                                    <span className="text-slate-600 dark:text-slate-400 capitalize">
                                        {tx.type}
                                        {tx.sale_id ? ` · vente ${String(tx.sale_id).slice(0, 8)}` : ''}
                                    </span>
                                    <span
                                        className={`font-medium tabular-nums ${
                                            tx.points >= 0 ? 'text-emerald-600' : 'text-rose-600'
                                        }`}
                                    >
                                        {tx.points >= 0 ? '+' : ''}
                                        {tx.points} pts
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            )}
        </div>
    );
}
