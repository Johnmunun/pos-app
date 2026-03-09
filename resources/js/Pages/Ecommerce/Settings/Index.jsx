import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Settings, Truck, CreditCard, Sparkles } from 'lucide-react';

export default function EcommerceSettingsIndex({ shop }) {
    const { props } = usePage();
    const globalCurrency = props?.shop?.currency || shop?.currency || 'USD';

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
                        <CardTitle className="text-base">Autres paramètres</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-2">
                        <Link
                            href={route('ecommerce.shipping.index')}
                            className="flex items-center gap-2 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700/50 transition"
                        >
                            <Truck className="h-5 w-5 text-gray-500" />
                            Méthodes de livraison
                        </Link>
                        <Link
                            href={route('ecommerce.payments.index')}
                            className="flex items-center gap-2 p-3 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-700/50 transition"
                        >
                            <CreditCard className="h-5 w-5 text-gray-500" />
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
            </div>
        </AppLayout>
    );
}
