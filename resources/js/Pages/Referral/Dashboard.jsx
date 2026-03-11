import { Head, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardHeader, CardTitle, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Users, Link2, Copy, TrendingUp, Gift } from 'lucide-react';
import { useState } from 'react';

export default function ReferralDashboard({ account, stats, children = [], commissions = [] }) {
    const { auth } = usePage().props;
    const [copied, setCopied] = useState(false);

    const handleCopy = async () => {
        if (!account?.link) return;
        try {
            await navigator.clipboard.writeText(account.link);
            setCopied(true);
            setTimeout(() => setCopied(false), 1500);
        } catch {
            setCopied(false);
        }
    };

    const currency = stats?.currency || 'USD';

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                            Programme de parrainage
                        </h2>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Invitez des utilisateurs et gagnez des commissions sur leurs achats dans tous les modules.
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Mon parrainage" />

            <div className="py-6 max-w-6xl mx-auto space-y-6">
                {/* Lien de referral */}
                <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                    <CardHeader className="pb-3">
                        <CardTitle className="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                            <Link2 className="h-5 w-5 text-emerald-500" />
                            Mon lien de parrainage
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <p className="text-sm text-gray-600 dark:text-gray-300">
                            Partagez ce lien avec vos contacts. Lorsqu&apos;ils s&apos;inscrivent et effectuent des achats, vous gagnez des commissions.
                        </p>
                        <div className="flex flex-col sm:flex-row gap-2 sm:items-center">
                            <Input
                                readOnly
                                value={account?.link || ''}
                                className="flex-1 font-mono text-xs sm:text-sm"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleCopy}
                                className="inline-flex items-center gap-2"
                            >
                                <Copy className="h-4 w-4" />
                                {copied ? 'Copié !' : 'Copier'}
                            </Button>
                        </div>
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Code referral&nbsp;: <span className="font-mono font-semibold">{account?.code}</span>
                        </p>
                    </CardContent>
                </Card>

                {/* Stats rapides */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card className="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 border-0 shadow-lg text-white">
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium text-blue-100">Filleuls directs</p>
                                    <p className="mt-2 text-2xl font-bold">
                                        {stats?.total_direct_children ?? 0}
                                    </p>
                                </div>
                                <div className="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center">
                                    <Users className="h-5 w-5" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-gradient-to-br from-emerald-500 to-emerald-600 dark:from-emerald-600 dark:to-emerald-700 border-0 shadow-lg text-white">
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium text-emerald-100">Commissions gagnées</p>
                                    <p className="mt-2 text-2xl font-bold">
                                        {new Intl.NumberFormat('fr-FR', {
                                            style: 'currency',
                                            currency,
                                        }).format(stats?.total_commissions ?? 0)}
                                    </p>
                                </div>
                                <div className="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center">
                                    <Gift className="h-5 w-5" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-gradient-to-br from-amber-500 to-amber-600 dark:from-amber-600 dark:to-amber-700 border-0 shadow-lg text-white">
                        <CardContent className="p-5">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-xs font-medium text-amber-100">Nouveaux ce mois</p>
                                    <p className="mt-2 text-2xl font-bold">
                                        {stats?.new_referrals_this_month ?? 0}
                                    </p>
                                </div>
                                <div className="h-10 w-10 rounded-xl bg-white/20 flex items-center justify-center">
                                    <TrendingUp className="h-5 w-5" />
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700 shadow-sm">
                        <CardContent className="p-5">
                            <p className="text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">
                                Devise des commissions
                            </p>
                            <p className="text-2xl font-bold text-gray-900 dark:text-white">
                                {currency}
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Filleuls + commissions */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                                <Users className="h-5 w-5 text-blue-500" />
                                Mes filleuls directs
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {children.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Aucun filleul pour le moment. Partagez votre lien pour commencer à gagner des commissions.
                                </p>
                            ) : (
                                <div className="max-h-64 overflow-y-auto border border-gray-100 dark:border-slate-800 rounded-lg">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-gray-50 dark:bg-slate-800">
                                            <tr>
                                                <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Code
                                                </th>
                                                <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Utilisateur
                                                </th>
                                                <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Date
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-slate-800">
                                            {children.map((c) => (
                                                <tr key={c.id}>
                                                    <td className="px-3 py-2 font-mono text-xs">{c.code}</td>
                                                    <td className="px-3 py-2 text-xs text-gray-700 dark:text-gray-200">
                                                        #{c.user_id}
                                                    </td>
                                                    <td className="px-3 py-2 text-xs text-gray-500 dark:text-gray-400">
                                                        {c.created_at}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base text-gray-900 dark:text-white">
                                <Gift className="h-5 w-5 text-emerald-500" />
                                Mes dernières commissions
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {commissions.length === 0 ? (
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Aucune commission enregistrée pour le moment.
                                </p>
                            ) : (
                                <div className="max-h-64 overflow-y-auto border border-gray-100 dark:border-slate-800 rounded-lg">
                                    <table className="min-w-full text-sm">
                                        <thead className="bg-gray-50 dark:bg-slate-800">
                                            <tr>
                                                <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Montant
                                                </th>
                                                <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Source
                                                </th>
                                                <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Niveau
                                                </th>
                                                <th className="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400">
                                                    Statut
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-100 dark:divide-slate-800">
                                            {commissions.map((c) => (
                                                <tr key={c.id}>
                                                    <td className="px-3 py-2 text-xs text-gray-800 dark:text-gray-100">
                                                        {new Intl.NumberFormat('fr-FR', {
                                                            style: 'currency',
                                                            currency: c.currency,
                                                        }).format(c.amount)}
                                                    </td>
                                                    <td className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                                                        {c.source_type} #{c.source_id}
                                                    </td>
                                                    <td className="px-3 py-2 text-xs text-gray-600 dark:text-gray-300">
                                                        Niv. {c.level}
                                                    </td>
                                                    <td className="px-3 py-2 text-xs">
                                                        <span
                                                            className={`inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold ${
                                                                c.status === 'paid'
                                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200'
                                                                    : c.status === 'confirmed'
                                                                    ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200'
                                                                    : 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200'
                                                            }`}
                                                        >
                                                            {c.status}
                                                        </span>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

