import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { useForm } from '@inertiajs/react';
import { Percent, Gift } from 'lucide-react';

export default function ReferralSettings({ settings }) {
    const { data, setData, put, processing } = useForm({
        enabled: settings?.enabled ?? false,
        commission_type: settings?.commission_type ?? 'percentage',
        commission_value: settings?.commission_value ?? 0,
        max_levels: settings?.max_levels ?? 1,
        enabled_modules: settings?.enabled_modules ?? [],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        put(route('referrals.settings.update'), { preserveScroll: true });
    };

    const toggleModule = (moduleKey) => {
        const list = data.enabled_modules || [];
        if (list.includes(moduleKey)) {
            setData('enabled_modules', list.filter((m) => m !== moduleKey));
        } else {
            setData('enabled_modules', [...list, moduleKey]);
        }
    };

    const modules = [
        { key: 'pharmacy', label: 'Pharmacy' },
        { key: 'hardware', label: 'Hardware' },
        { key: 'commerce', label: 'Global Commerce' },
        { key: 'ecommerce', label: 'E-commerce' },
    ];

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                            Programme de parrainage
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Configurez les commissions et les niveaux de referral pour tous les modules.
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Paramètres Referral" />

            <div className="py-6 max-w-4xl mx-auto space-y-6">
                <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                    <CardHeader className="pb-3 flex flex-row items-center justify-between gap-3">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                                <Gift className="h-5 w-5 text-emerald-500" />
                                Configuration générale
                            </CardTitle>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Activez le système de parrainage et définissez les règles de commission.
                            </p>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <p className="text-sm font-medium text-gray-800 dark:text-gray-100">
                                        Activer le parrainage
                                    </p>
                                    <p className="text-xs text-gray-500 dark:text-gray-400">
                                        Lorsqu&apos;il est activé, les ventes des filleuls génèrent des commissions.
                                    </p>
                                </div>
                                <Switch
                                    checked={!!data.enabled}
                                    onCheckedChange={(val) => setData('enabled', val)}
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Type de commission
                                    </label>
                                    <select
                                        value={data.commission_type}
                                        onChange={(e) => setData('commission_type', e.target.value)}
                                        className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-sm text-gray-900 dark:text-white px-3 py-2"
                                    >
                                        <option value="percentage">Pourcentage (%)</option>
                                        <option value="fixed">Montant fixe</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                        Valeur de la commission
                                    </label>
                                    <div className="flex gap-2 items-center">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.commission_value}
                                            onChange={(e) => setData('commission_value', e.target.value)}
                                            className="flex-1"
                                        />
                                        {data.commission_type === 'percentage' && (
                                            <Percent className="h-4 w-4 text-gray-400" />
                                        )}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">
                                    Nombre de niveaux (multi-niveau)
                                </label>
                                <Input
                                    type="number"
                                    min="1"
                                    max="5"
                                    value={data.max_levels}
                                    onChange={(e) => setData('max_levels', e.target.value)}
                                    className="w-24"
                                />
                                <p className="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                    Exemple : 1 = uniquement filleuls directs, 2 = filleuls + filleuls de vos filleuls, etc.
                                </p>
                            </div>

                            <div>
                                <label className="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-2">
                                    Modules concernés
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    {modules.map((m) => {
                                        const checked = (data.enabled_modules || []).includes(m.key);
                                        return (
                                            <button
                                                key={m.key}
                                                type="button"
                                                onClick={() => toggleModule(m.key)}
                                                className={`flex items-center justify-between rounded-lg border px-3 py-2 text-sm ${
                                                    checked
                                                        ? 'border-emerald-500 bg-emerald-50 dark:border-emerald-400 dark:bg-emerald-900/20 text-emerald-800 dark:text-emerald-100'
                                                        : 'border-gray-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-gray-700 dark:text-gray-200'
                                                }`}
                                            >
                                                <span>{m.label}</span>
                                                <span
                                                    className={`h-2.5 w-2.5 rounded-full ${
                                                        checked ? 'bg-emerald-500' : 'bg-gray-300 dark:bg-slate-600'
                                                    }`}
                                                />
                                            </button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="flex justify-end pt-2">
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

