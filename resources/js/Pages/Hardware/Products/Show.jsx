import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Package, ArrowLeft } from 'lucide-react';

/**
 * Détail produit - Quincaillerie.
 * Redirige vers la liste ; le détail s’affiche via le modal sur la page Index.
 */
export default function HardwareProductShow({ product }) {
    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                    Détail du produit
                </h2>
            }
        >
            <Head title={product?.name ? `${product.name} - Quincaillerie` : 'Produit - Quincaillerie'} />
            <div className="py-6">
                <div className="max-w-md mx-auto text-center">
                    <Package className="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                        Consultez le détail des produits depuis la liste (bouton Voir).
                    </p>
                    <Button asChild>
                        <Link href={route('hardware.products')}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour à la liste
                        </Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
