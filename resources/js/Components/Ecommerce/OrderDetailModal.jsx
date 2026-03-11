import { Fragment } from 'react';
import Modal from '@/Components/Modal';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
    Package,
    User,
    Mail,
    Phone,
    MapPin,
    Calendar,
    DollarSign,
    CreditCard,
    FileText,
    Truck,
    CheckCircle,
    XCircle,
    Clock,
    Download,
} from 'lucide-react';

export default function OrderDetailModal({ order, show, onClose, onStatusUpdate, onPaymentStatusUpdate }) {
    if (!order) return null;

    const statusColors = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300',
        processing: 'bg-purple-100 text-purple-800 dark:bg-purple-900/50 dark:text-purple-300',
        shipped: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/50 dark:text-indigo-300',
        delivered: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
        cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
    };

    const paymentStatusColors = {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300',
        paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300',
        failed: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300',
        refunded: 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300',
    };

    const statusIcons = {
        pending: Clock,
        confirmed: CheckCircle,
        processing: Package,
        shipped: Truck,
        delivered: CheckCircle,
        cancelled: XCircle,
    };

    const statusLabels = {
        pending: 'En attente',
        confirmed: 'Confirmée',
        processing: 'En traitement',
        shipped: 'Expédiée',
        delivered: 'Livrée',
        cancelled: 'Annulée',
    };

    const paymentStatusLabels = {
        pending: 'En attente de paiement',
        paid: 'Payée',
        failed: 'Échec paiement',
        refunded: 'Remboursée',
    };

    const normalizedStatus = (order.status || '').toLowerCase();
    const normalizedPaymentStatus = (order.payment_status || '').toLowerCase();

    const StatusIcon = statusIcons[normalizedStatus] || Clock;

    const canValidateOrder = onStatusUpdate && normalizedStatus === 'pending';
    const canMarkAsPaid = onPaymentStatusUpdate && normalizedPaymentStatus === 'pending';

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl">
            <div className="p-5 sm:p-6">
                {/* Header */}
                <div className="flex items-start justify-between mb-4 sm:mb-6">
                    <div className="flex items-center gap-3">
                        <div className="p-2.5 bg-blue-100 dark:bg-blue-900/30 rounded-lg">
                            <Package className="h-5 w-5 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                                Commande {order.order_number}
                            </h2>
                            <p className="text-xs sm:text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                                Créée le {order.created_at}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Badge className={statusColors[normalizedStatus] || statusColors.pending}>
                            <StatusIcon className="h-3 w-3 mr-1" />
                            {statusLabels[normalizedStatus] || order.status}
                        </Badge>
                        <Badge className={paymentStatusColors[normalizedPaymentStatus] || paymentStatusColors.pending}>
                            {paymentStatusLabels[normalizedPaymentStatus] || order.payment_status}
                        </Badge>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-6">
                    {/* Informations client */}
                    <div className="space-y-4">
                        <h3 className="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <User className="h-5 w-5" />
                            Client
                        </h3>
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 space-y-2">
                            <div className="flex items-center gap-2">
                                <User className="h-4 w-4 text-gray-500" />
                                <span className="font-medium">{order.customer_name}</span>
                            </div>
                            <div className="flex items-center gap-2">
                                <Mail className="h-4 w-4 text-gray-500" />
                                <span className="text-sm">{order.customer_email}</span>
                            </div>
                            {order.customer_phone && (
                                <div className="flex items-center gap-2">
                                    <Phone className="h-4 w-4 text-gray-500" />
                                    <span className="text-sm">{order.customer_phone}</span>
                                </div>
                            )}
                        </div>

                        {/* Adresses */}
                        <div className="space-y-2.5">
                            <h4 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                <MapPin className="h-4 w-4" />
                                Adresse de livraison
                            </h4>
                            <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <p className="text-sm whitespace-pre-line">{order.shipping_address}</p>
                            </div>
                            {order.billing_address && (
                                <>
                                    <h4 className="font-medium text-gray-900 dark:text-white flex items-center gap-2">
                                        <MapPin className="h-4 w-4" />
                                        Adresse de facturation
                                    </h4>
                                    <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                        <p className="text-sm whitespace-pre-line">{order.billing_address}</p>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                    {/* Produits et totaux */}
                    <div className="space-y-4">
                        <h3 className="text-base font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                            <Package className="h-5 w-5" />
                            Produits
                        </h3>
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 space-y-3 max-h-80 overflow-y-auto">
                            {order.items?.map((item, index) => (
                                <div key={index} className="flex items-start gap-3 pb-3 border-b border-gray-200 dark:border-gray-700 last:border-0">
                                    {item.product_image_url && (
                                        <img
                                            src={item.product_image_url}
                                            alt={item.product_name}
                                            className="w-16 h-16 object-cover rounded"
                                        />
                                    )}
                                    <div className="flex-1">
                                        <p className="font-medium text-gray-900 dark:text-white">{item.product_name}</p>
                                        {item.product_sku && (
                                            <p className="text-xs text-gray-500 dark:text-gray-400">SKU: {item.product_sku}</p>
                                        )}
                                        <p className="text-sm text-gray-600 dark:text-gray-400">
                                            {item.quantity} × {item.unit_price} {order.currency}
                                            {item.discount_amount > 0 && (
                                                <span className="text-red-600 dark:text-red-400 ml-2">
                                                    -{item.discount_amount} {order.currency}
                                                </span>
                                            )}
                                        </p>
                                    </div>
                                    <div className="text-right flex flex-col items-end gap-1">
                                        <p className="font-medium text-gray-900 dark:text-white">
                                            {item.subtotal.toFixed(2)} {order.currency}
                                        </p>
                                        {item.is_digital && item.download_link && (
                                            <a
                                                href={item.download_link}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center gap-1 text-sm text-blue-600 dark:text-blue-400 hover:underline"
                                            >
                                                <Download className="h-4 w-4" />
                                                Télécharger
                                            </a>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {/* Totaux */}
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 space-y-2">
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600 dark:text-gray-400">Sous-total</span>
                                <span>{order.subtotal_amount.toFixed(2)} {order.currency}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600 dark:text-gray-400">Frais de livraison</span>
                                <span>{order.shipping_amount.toFixed(2)} {order.currency}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-gray-600 dark:text-gray-400">Taxes</span>
                                <span>{order.tax_amount.toFixed(2)} {order.currency}</span>
                            </div>
                            {order.discount_amount > 0 && (
                                <div className="flex justify-between text-sm text-red-600 dark:text-red-400">
                                    <span>Remise</span>
                                    <span>-{order.discount_amount.toFixed(2)} {order.currency}</span>
                                </div>
                            )}
                            <div className="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-2">
                                <span>Total</span>
                                <span>{order.total_amount.toFixed(2)} {order.currency}</span>
                            </div>
                        </div>

                        {/* Paiement */}
                        {order.payment_method && (
                            <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <h4 className="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                                    <CreditCard className="h-4 w-4" />
                                    Paiement
                                </h4>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                    Méthode: {order.payment_method}
                                </p>
                            </div>
                        )}

                        {/* Notes */}
                        {order.notes && (
                            <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
                                <h4 className="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                                    <FileText className="h-4 w-4" />
                                    Notes
                                </h4>
                                <p className="text-sm text-gray-600 dark:text-gray-400 whitespace-pre-line">
                                    {order.notes}
                                </p>
                            </div>
                        )}

                        {/* Dates importantes */}
                        <div className="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 space-y-2">
                            <h4 className="font-medium text-gray-900 dark:text-white flex items-center gap-2 mb-2">
                                <Calendar className="h-4 w-4" />
                                Dates
                            </h4>
                            {order.confirmed_at && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Confirmée le</span>
                                    <span>{order.confirmed_at}</span>
                                </div>
                            )}
                            {order.shipped_at && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Expédiée le</span>
                                    <span>{order.shipped_at}</span>
                                </div>
                            )}
                            {order.delivered_at && (
                                <div className="flex justify-between text-sm">
                                    <span className="text-gray-600 dark:text-gray-400">Livrée le</span>
                                    <span>{order.delivered_at}</span>
                                </div>
                            )}
                            {order.cancelled_at && (
                                <div className="flex justify-between text-sm text-red-600 dark:text-red-400">
                                    <span>Annulée le</span>
                                    <span>{order.cancelled_at}</span>
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {/* Actions */}
                <div className="mt-5 sm:mt-6 flex flex-col sm:flex-row sm:justify-end gap-2 sm:gap-3 border-t border-gray-200 dark:border-gray-700 pt-4">
                    {canMarkAsPaid && (
                        <Button
                            type="button"
                            onClick={() => onPaymentStatusUpdate(order.id, 'paid')}
                            className="order-last sm:order-first bg-emerald-600 hover:bg-emerald-700"
                        >
                            <CreditCard className="h-4 w-4 mr-2" />
                            Marquer comme payée
                        </Button>
                    )}
                    {canValidateOrder && (
                        <Button
                            type="button"
                            onClick={() => onStatusUpdate(order.id, 'confirmed')}
                            className="order-last sm:order-first"
                        >
                            Valider la commande
                        </Button>
                    )}
                    <Button variant="outline" onClick={onClose}>
                        Fermer
                    </Button>
                </div>
            </div>
        </Modal>
    );
}
