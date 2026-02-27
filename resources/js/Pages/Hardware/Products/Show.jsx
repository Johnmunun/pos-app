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
                <div className="py-6 text-center">
                    <p className="text-gray-500">Produit introuvable.</p>
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
                <div className="flex items-center gap-3">
                    <Button variant="ghost" asChild>
                        <Link href={route('hardware.products')}><ArrowLeft className="h-4 w-4 mr-2" /> Retour</Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white">{product.name}</h2>
                </div>
            }
        >
            <Head title={product.name ? product.name + ' - Quincaillerie' : 'Produit - Quincaillerie'} />
            <div className="py-6">
                <div className="max-w-2xl mx-auto">
                    <Card className="bg-white dark:bg-gray-800">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Package className="h-5 w-5 mr-2" /> Détail du produit
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <span className="text-sm font-medium text-gray-500 dark:text-gray-400">Code</span>
                                <p className="text-gray-900 dark:text-white">{product.product_code}</p>
                            </div>
                            <div>
                                <span className="text-sm font-medium text-gray-500 dark:text-gray-400">Catégorie</span>
                                <p className="text-gray-900 dark:text-white">{product.category?.name || '-'}</p>
                            </div>
                            <div>
                                <span className="text-sm font-medium text-gray-500 dark:text-gray-400">Prix</span>
                                <p className="text-gray-900 dark:text-white">{product.price_currency} {Number(product.price_amount).toFixed(2)}</p>
                            </div>
                            <div>
                                <span className="text-sm font-medium text-gray-500 dark:text-gray-400">Stock</span>
                                <p className="text-gray-900 dark:text-white">{product.current_stock} / {product.minimum_stock} min</p>
                            </div>
                            <div>
                                <span className="text-sm font-medium text-gray-500 dark:text-gray-400">Unité</span>
                                <p className="text-gray-900 dark:text-white">{product.type_unite} - Qté/unité: {product.quantite_par_unite} - Fraction: {product.est_divisible ? 'Oui' : 'Non'}</p>
                            </div>
                            {product.description && (
                                <div>
                                    <span className="text-sm font-medium text-gray-500 dark:text-gray-400">Description</span>
                                    <p className="text-gray-900 dark:text-white">{product.description}</p>
                                </div>
                            )}
                            <div className="pt-4">
                                <Button asChild>
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
