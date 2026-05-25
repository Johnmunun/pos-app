import { Head, useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Gift, ArrowLeft, BarChart3 } from 'lucide-react';

export default function LoyaltySettings({ settings, stats }) {
    const { data, setData, put, processing } = useForm({
        enabled: settings.enabled ?? false,
        earn_amount_per_point: settings.earn_amount_per_point ?? 1,
        points_per_earn_unit: settings.points_per_earn_unit ?? 1,
        redeem_value_per_point: settings.redeem_value_per_point ?? 0.05,
        min_points_redeem: settings.min_points_redeem ?? 100,
        max_discount_percent: settings.max_discount_percent ?? 50,
        points_expire_days: settings.points_expire_days ?? '',
        tier_thresholds: {
            silver: settings.tier_thresholds?.silver ?? 500,
            gold: settings.tier_thresholds?.gold ?? 2000,
            vip: settings.tier_thresholds?.vip ?? 10000,
        },
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('loyalty.settings.update'));
    };

    return (
        <AppLayout>
            <Head title="Fidélité — Paramètres" />
            <div className="py-6 max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div className="mb-6 flex items-center gap-3">
                    <Link
                        href={route('settings.index')}
                        className="text-slate-500 hover:text-slate-800 dark:hover:text-slate-200"
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </Link>
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <Gift className="h-7 w-7 text-indigo-600" />
                            Programme fidélité
                        </h1>
                        <p className="text-sm text-slate-500 mt-1">
                            Points, conversion et niveaux — compatible commerce, pharmacie et quincaillerie.
                        </p>
                    </div>
                    <Link
                        href={route('loyalty.reports.index')}
                        className="inline-flex items-center gap-2 rounded-lg border border-indigo-200 dark:border-indigo-800 px-3 py-2 text-sm font-medium text-indigo-700 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-950/40"
                    >
                        <BarChart3 className="h-4 w-4" />
                        Rapports
                    </Link>
                </div>

                {stats && (
                    <div className="grid grid-cols-3 gap-3 mb-6">
                        <Card>
                            <CardContent className="pt-4 pb-4 text-center">
                                <p className="text-2xl font-bold">{stats.accounts}</p>
                                <p className="text-xs text-slate-500">Cartes actives</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-4 pb-4 text-center">
                                <p className="text-2xl font-bold">{stats.points_issued}</p>
                                <p className="text-xs text-slate-500">Points distribués</p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="pt-4 pb-4 text-center">
                                <p className="text-2xl font-bold">{stats.points_redeemed}</p>
                                <p className="text-xs text-slate-500">Points utilisés</p>
                            </CardContent>
                        </Card>
                    </div>
                )}

                <form onSubmit={submit}>
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuration</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <label className="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    checked={data.enabled}
                                    onChange={(e) => setData('enabled', e.target.checked)}
                                    className="rounded"
                                />
                                <span className="font-medium">Activer la fidélité</span>
                            </label>

                            <div className="grid sm:grid-cols-2 gap-4">
                                <div>
                                    <Label>Montant dépensé pour 1 unité de gain</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={data.earn_amount_per_point}
                                        onChange={(e) => setData('earn_amount_per_point', e.target.value)}
                                    />
                                    <p className="text-xs text-slate-500 mt-1">Ex. 1 = 1 point par 1$</p>
                                </div>
                                <div>
                                    <Label>Points gagnés par unité</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        value={data.points_per_earn_unit}
                                        onChange={(e) => setData('points_per_earn_unit', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <Label>Valeur d&apos;1 point (réduction)</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        value={data.redeem_value_per_point}
                                        onChange={(e) => setData('redeem_value_per_point', e.target.value)}
                                    />
                                    <p className="text-xs text-slate-500 mt-1">Ex. 100 pts = 5$ si 0.05</p>
                                </div>
                                <div>
                                    <Label>Minimum points pour utiliser</Label>
                                    <Input
                                        type="number"
                                        min={0}
                                        value={data.min_points_redeem}
                                        onChange={(e) => setData('min_points_redeem', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <Label>Réduction max (% du panier)</Label>
                                    <Input
                                        type="number"
                                        min={0}
                                        max={100}
                                        value={data.max_discount_percent}
                                        onChange={(e) => setData('max_discount_percent', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <Label>Expiration points (jours, vide = jamais)</Label>
                                    <Input
                                        type="number"
                                        min={1}
                                        value={data.points_expire_days}
                                        onChange={(e) => setData('points_expire_days', e.target.value)}
                                    />
                                </div>
                            </div>

                            <div>
                                <Label className="mb-2 block">Seuils niveaux (points cumulés)</Label>
                                <div className="grid grid-cols-3 gap-3">
                                    {['silver', 'gold', 'vip'].map((tier) => (
                                        <div key={tier}>
                                            <Label className="capitalize text-xs">{tier}</Label>
                                            <Input
                                                type="number"
                                                value={data.tier_thresholds[tier]}
                                                onChange={(e) =>
                                                    setData('tier_thresholds', {
                                                        ...data.tier_thresholds,
                                                        [tier]: e.target.value,
                                                    })
                                                }
                                            />
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <Button type="submit" disabled={processing}>
                                Enregistrer
                            </Button>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AppLayout>
    );
}
