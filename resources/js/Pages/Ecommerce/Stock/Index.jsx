import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Package, AlertTriangle, AlertCircle, ArrowRight } from 'lucide-react';
import EcommercePageHeader from '@/Components/Ecommerce/EcommercePageHeader';

export default function EcommerceStockIndex({ products = [], out_of_stock_count = 0, low_stock_count = 0, filters = {} }) {
    const formatCurrency = (amount, currency) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const statusConfig = {
        out: { label: 'Rupture', variant: 'destructive', icon: AlertCircle },
        low: { label: 'Stock bas', variant: 'warning', icon: AlertTriangle },
        ok: { label: 'OK', variant: 'default', icon: Package },
    };

    return (
        <AppLayout
            header={
                <EcommercePageHeader title="Stock E-commerce" icon={Package}>
                    <Link href={route('ecommerce.products.index')}>
                        <Button variant="outline" size="sm" className="inline-flex items-center justify-center gap-2 p-2 sm:px-3 sm:py-2 min-w-[36px] sm:min-w-0" title="Voir les produits">
                            <ArrowRight className="h-4 w-4 shrink-0" />
                            <span className="hidden sm:inline">Voir les produits</span>
                        </Button>
                    </Link>
                </EcommercePageHeader>
            }
        >
            <Head title="Stock - E-commerce" />

            <div className="py-6 space-y-6">
                {/* KPIs */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-gray-500 dark:text-gray-400">Total produits</p>
                                    <p className="text-2xl font-bold text-gray-900 dark:text-white">{products.length}</p>
                                </div>
                                <Package className="h-10 w-10 text-gray-400" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="bg-white dark:bg-slate-800 border border-red-200 dark:border-red-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-red-600 dark:text-red-400">Rupture de stock</p>
                                    <p className="text-2xl font-bold text-red-600 dark:text-red-400">{out_of_stock_count}</p>
                                </div>
                                <AlertCircle className="h-10 w-10 text-red-500" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="bg-white dark:bg-slate-800 border border-amber-200 dark:border-amber-900/50">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-amber-600 dark:text-amber-400">Stock bas</p>
                                    <p className="text-2xl font-bold text-amber-600 dark:text-amber-400">{low_stock_count}</p>
                                </div>
                                <AlertTriangle className="h-10 w-10 text-amber-500" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filtres */}
                <div className="flex gap-2">
                    <Link
                        href={route('ecommerce.stock.index', { status: 'all' })}
                    >
                        <Button variant={filters.status === 'all' || !filters.status ? 'default' : 'outline'} size="sm">
                            Tous
                        </Button>
                    </Link>
                    <Link
                        href={route('ecommerce.stock.index', { status: 'out' })}
                    >
                        <Button variant={filters.status === 'out' ? 'destructive' : 'outline'} size="sm">
                            Rupture ({out_of_stock_count})
                        </Button>
                    </Link>
                    <Link
                        href={route('ecommerce.stock.index', { status: 'low' })}
                    >
                        <Button variant={filters.status === 'low' ? 'default' : 'outline'} size="sm">
                            Stock bas ({low_stock_count})
                        </Button>
                    </Link>
                </div>

                {/* Liste */}
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-0">
                        {/* Mobile: cartes */}
                        <div className="md:hidden divide-y divide-gray-200 dark:divide-slate-700">
                            {products.length === 0 ? (
                                <div className="px-4 py-8 text-center text-gray-500">
                                    Aucun produit. <Link href={route('ecommerce.products.index')} className="text-blue-600 hover:underline">Voir les produits</Link>
                                </div>
                            ) : (
                                products.map((p) => {
                                    const cfg = statusConfig[p.status] || statusConfig.ok;
                                    const Icon = cfg.icon;
                                    return (
                                        <div key={p.id} className="p-4 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="min-w-0">
                                                    <p className="font-medium text-gray-900 dark:text-white">{p.name}</p>
                                                    <p className="text-xs text-gray-500">{p.sku}</p>
                                                    <div className="mt-2 flex items-center gap-2">
                                                        <span className="text-sm">Stock: <strong>{p.stock}</strong></span>
                                                        <span className="text-xs text-gray-500">Min: {p.minimum_stock}</span>
                                                    </div>
                                                    <p className="text-sm font-medium mt-1">{formatCurrency(p.sale_price, p.currency)}</p>
                                                </div>
                                                <Badge variant={cfg.variant} className="shrink-0"><Icon className="h-3 w-3 mr-1" />{cfg.label}</Badge>
                                            </div>
                                        </div>
                                    );
                                })
                            )}
                        </div>
                        {/* Desktop: tableau */}
                        <div className="hidden md:block overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-slate-700">
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Produit</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">SKU</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Stock</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Seuil min.</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Statut</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Prix</th>
                                </tr>
                            </thead>
                            <tbody>
                                {products.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                                            Aucun produit. Le stock est géré via le catalogue. <Link href={route('ecommerce.products.index')} className="text-blue-600 hover:underline">Voir les produits</Link>
                                        </td>
                                    </tr>
                                ) : (
                                    products.map((p) => {
                                        const cfg = statusConfig[p.status] || statusConfig.ok;
                                        const Icon = cfg.icon;
                                        return (
                                            <tr key={p.id} className="border-b border-gray-100 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                                <td className="px-4 py-3 font-medium">{p.name}</td>
                                                <td className="px-4 py-3 text-gray-600 dark:text-gray-400">{p.sku}</td>
                                                <td className="px-4 py-3">{p.stock}</td>
                                                <td className="px-4 py-3">{p.minimum_stock}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={cfg.variant} className="flex items-center gap-1 w-fit">
                                                        <Icon className="h-3 w-3" />
                                                        {cfg.label}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">{formatCurrency(p.sale_price, p.currency)}</td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
