import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import OrderDetailModal from '@/Components/Ecommerce/OrderDetailModal';
import { Button } from '@/Components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';

export default function OrderShow({ order }) {
    const [modalOpen, setModalOpen] = useState(true);

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('ecommerce.orders.index')}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Retour
                            </Button>
                        </Link>
                        <div className="flex items-center gap-2">
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Commande {order.order_number}
                            </h2>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`Commande ${order.order_number}`} />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <OrderDetailModal
                        order={order}
                        show={modalOpen}
                        onClose={() => {
                            setModalOpen(false);
                            window.history.back();
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
