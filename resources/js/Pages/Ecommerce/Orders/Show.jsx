import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import OrderDetailModal from '@/Components/Ecommerce/OrderDetailModal';
import { Button } from '@/Components/ui/button';
import { ArrowLeft } from 'lucide-react';
import { useState } from 'react';
import axios from 'axios';
import { toast } from 'react-hot-toast';

export default function OrderShow({ order }) {
    const [modalOpen, setModalOpen] = useState(true);

    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permissionExpression) => {
        if (auth?.user?.type === 'ROOT') return true;
        if (!permissionExpression) return false;
        const permsToCheck = String(permissionExpression)
            .split('|')
            .map((p) => p.trim())
            .filter(Boolean);
        return permsToCheck.some((p) => permissions.includes(p));
    };

    const canUpdateStatus = hasPermission('ecommerce.order.status.update|ecommerce.order.update|module.ecommerce');
    const canUpdatePayment = hasPermission('ecommerce.order.payment.update|ecommerce.order.update|module.ecommerce');

    const handleUpdateStatus = async (orderId, newStatus) => {
        if (!canUpdateStatus) {
            toast.error('Vous n\'avez pas la permission de modifier les commandes.');
            return;
        }
        try {
            await axios.put(route('ecommerce.orders.update-status', orderId), { status: newStatus });
            toast.success('Statut de la commande mis à jour');
            router.reload({ only: ['order'] });
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour du statut');
        }
    };

    const handleUpdatePaymentStatus = async (orderId, paymentStatus) => {
        if (!canUpdatePayment) {
            toast.error('Vous n\'avez pas la permission de modifier le paiement.');
            return;
        }
        try {
            await axios.put(route('ecommerce.orders.update-payment-status', orderId), { payment_status: paymentStatus });
            toast.success('Paiement mis à jour');
            router.reload({ only: ['order'] });
        } catch (error) {
            toast.error(error.response?.data?.message || 'Erreur lors de la mise à jour du paiement');
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('ecommerce.orders.index')}>
                            <Button variant="ghost" size="sm" className="inline-flex items-center gap-2">
                                <ArrowLeft className="h-4 w-4 shrink-0" />
                                <span>Retour</span>
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
                        onStatusUpdate={canUpdateStatus ? handleUpdateStatus : undefined}
                        onPaymentStatusUpdate={canUpdatePayment ? handleUpdatePaymentStatus : undefined}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
