import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Package, ArrowLeft } from 'lucide-react';

/**
 * Édition produit - Quincaillerie.
 * Redirige vers la liste ; l’édition se fait via le drawer sur la page Index.
 */
export default function HardwareProductEdit({ product }) {
    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                    Modifier le produit
                </h2>
            }
        >
            <Head title="Modifier le produit - Quincaillerie" />
            <div className="py-6">
                <div className="max-w-md mx-auto text-center">
                    <Package className="h-12 w-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                    <p className="text-gray-600 dark:text-gray-400 mb-4">
                        Modifiez « {product?.name ?? 'ce produit'} » depuis la liste des produits (bouton Modifier).
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
