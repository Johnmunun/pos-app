import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Package, ArrowLeft, Edit } from 'lucide-react';

export default function HardwareProductShow({ product }) {
    if (!product) {
        return (
            <AppLayout>
                <Head title="Produit - Quincaillerie" />
                <div className="py-8 sm:py-10 text-center">
                    <p className="text-gray-500 dark:text-gray-400">Produit introuvable.</p>
                    <Button asChild className="mt-4">
                        <Link href={route('hardware.products')}><ArrowLeft className="h-4 w-4 mr-2" /> Retour</Link>
                    </Button>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout
            header={
                <div className="flex flex-wrap items-center gap-3">
                    <Button variant="ghost" asChild className="shrink-0">
                        <Link href={route('hardware.products')}><ArrowLeft className="h-4 w-4 mr-2" /> Retour</Link>
                    </Button>
                    <h2 className="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white tracking-tight min-w-0">
                        {product.name}
                    </h2>
                </div>
            }
        >
            <Head title={product.name ? product.name + ' - Quincaillerie' : 'Produit - Quincaillerie'} />
            <div className="py-8 sm:py-10">
                <div className="max-w-3xl mx-auto px-4 sm:px-0">
                    <Card className="overflow-hidden rounded-2xl border border-gray-200/80 bg-white/95 shadow-landing-soft backdrop-blur-sm dark:border-slate-700/80 dark:bg-slate-900/80">
                        <CardHeader className="border-b border-gray-100/90 dark:border-slate-700/80 pb-4">
                            <CardTitle className="flex items-center gap-2 text-lg font-semibold text-gray-900 dark:text-white">
                                <span className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600 dark:bg-amber-400/15 dark:text-amber-300">
                                    <Package className="h-5 w-5" />
                                </span>
                                Détail du produit
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-5 pt-6">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <div>
                                    <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Code</span>
                                    <p className="mt-1 text-gray-900 dark:text-white font-mono text-sm">{product.product_code}</p>
                                </div>
                                <div>
                                    <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Catégorie</span>
                                    <p className="mt-1 text-gray-900 dark:text-white">{product.category?.name || '-'}</p>
                                </div>
                                <div>
                                    <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Prix</span>
                                    <p className="mt-1 text-gray-900 dark:text-white font-medium tabular-nums">{product.price_currency} {Number(product.price_amount).toFixed(2)}</p>
                                </div>
                                <div>
                                    <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Stock</span>
                                    <p className="mt-1 text-gray-900 dark:text-white tabular-nums">{product.current_stock} / {product.minimum_stock} min</p>
                                </div>
                            </div>
                            <div>
                                <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Unité</span>
                                <p className="mt-1 text-gray-900 dark:text-white text-sm leading-relaxed">{product.type_unite} — Qté/unité : {product.quantite_par_unite} — Fraction : {product.est_divisible ? 'Oui' : 'Non'}</p>
                            </div>
                            {product.description && (
                                <div className="rounded-xl border border-gray-100/90 bg-gray-50/50 p-4 dark:border-slate-700/60 dark:bg-slate-800/40">
                                    <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</span>
                                    <p className="mt-2 text-gray-900 dark:text-white text-sm leading-relaxed whitespace-pre-wrap">{product.description}</p>
                                </div>
                            )}
                            <div className="pt-2">
                                <Button asChild className="bg-amber-500 hover:bg-amber-600 text-white shadow-md shadow-amber-500/20">
                                    <Link href={route('hardware.products.edit', product.id)}><Edit className="h-4 w-4 mr-2" /> Modifier</Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
