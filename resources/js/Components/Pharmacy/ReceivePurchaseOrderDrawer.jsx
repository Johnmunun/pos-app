import React, { useState, useEffect } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import {
    X,
    Package,
    Calendar,
    Hash,
    Check,
    Loader2,
    AlertTriangle
} from 'lucide-react';
import axios from 'axios';
import toast from 'react-hot-toast';

/**
 * Drawer for receiving purchase orders with batch information.
 * Each line requires a batch number and expiration date.
 */
export default function ReceivePurchaseOrderDrawer({
    isOpen,
    onClose,
    purchaseOrder,
    lines = [],
    currency = 'USD',
    onSuccess
}) {
    const [receiving, setReceiving] = useState(false);
    const [lineData, setLineData] = useState([]);

    // Initialize line data when lines change
    useEffect(() => {
        if (lines.length > 0) {
            const initialData = lines
                .filter(line => (line.ordered_quantity - (line.received_quantity || 0)) > 0)
                .map(line => ({
                    line_id: line.id,
                    product_name: line.product_name,
                    ordered_quantity: line.ordered_quantity,
                    received_quantity: line.received_quantity || 0,
                    remaining: line.ordered_quantity - (line.received_quantity || 0),
                    batch_number: '',
                    expiration_date: '',
                    quantity: line.ordered_quantity - (line.received_quantity || 0), // Default to remaining
                    errors: {}
                }));
            setLineData(initialData);
        }
    }, [lines]);

    const updateLineField = (lineId, field, value) => {
        setLineData(prev => prev.map(line => 
            line.line_id === lineId 
                ? { ...line, [field]: value, errors: { ...line.errors, [field]: null } }
                : line
        ));
    };

    const validateData = () => {
        let isValid = true;
        const updatedData = lineData.map(line => {
            const errors = {};
            
            if (!line.batch_number.trim()) {
                errors.batch_number = 'Numéro de lot requis';
                isValid = false;
            }
            
            if (!line.expiration_date) {
                errors.expiration_date = 'Date d\'expiration requise';
                isValid = false;
            } else {
                const expDate = new Date(line.expiration_date);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                if (expDate <= today) {
                    errors.expiration_date = 'La date doit être dans le futur';
                    isValid = false;
                }
            }
            
            if (line.quantity < 1 || line.quantity > line.remaining) {
                errors.quantity = `Entre 1 et ${line.remaining}`;
                isValid = false;
            }

            return { ...line, errors };
        });

        setLineData(updatedData);
        return isValid;
    };

    const handleSubmit = async () => {
        if (!validateData()) {
            toast.error('Veuillez corriger les erreurs');
            return;
        }

        setReceiving(true);

        try {
            const payload = {
                lines: lineData.map(line => ({
                    line_id: line.line_id,
                    batch_number: line.batch_number.trim(),
                    expiration_date: line.expiration_date,
                    quantity: parseInt(line.quantity, 10)
                }))
            };

            await axios.post(route('pharmacy.purchases.receive', purchaseOrder.id), payload);
            toast.success('Réception enregistrée avec succès');
            onSuccess?.();
            onClose();
        } catch (err) {
            toast.error(err.response?.data?.message || 'Erreur lors de la réception');
        } finally {
            setReceiving(false);
        }
    };

    const formatCurrency = (amount) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency,
        }).format(amount);
    };

    // Calculate totals
    const totalToReceive = lineData.reduce((sum, l) => sum + parseInt(l.quantity || 0, 10), 0);

    if (!isOpen) return null;

    return (
        <>
            {/* Backdrop */}
            <div 
                className="fixed inset-0 bg-black/50 z-40 transition-opacity"
                onClick={onClose}
            />

            {/* Drawer */}
            <div className="fixed inset-y-0 right-0 z-50 w-full max-w-2xl bg-white dark:bg-gray-800 shadow-xl transform transition-transform flex flex-col">
                {/* Header */}
                <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gray-50 dark:bg-gray-900">
                    <div className="flex items-center gap-3">
                        <div className="h-10 w-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center">
                            <Package className="h-5 w-5 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Réception de marchandise
                            </h2>
                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                Bon #{purchaseOrder?.id?.slice(0, 8)}
                            </p>
                        </div>
                    </div>
                    <button
                        onClick={onClose}
                        className="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    >
                        <X className="h-5 w-5 text-gray-500" />
                    </button>
                </div>

                {/* Content */}
                <div className="flex-1 overflow-y-auto p-6">
                    {/* Alert */}
                    <div className="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4 mb-6">
                        <div className="flex items-start gap-3">
                            <AlertTriangle className="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
                            <div>
                                <p className="font-medium text-amber-800 dark:text-amber-300">
                                    Information obligatoire
                                </p>
                                <p className="text-sm text-amber-700 dark:text-amber-400 mt-1">
                                    Pour chaque produit, vous devez saisir le numéro de lot et la date d'expiration.
                                    Ces informations sont essentielles pour la traçabilité pharmaceutique.
                                </p>
                            </div>
                        </div>
                    </div>

                    {lineData.length === 0 ? (
                        <div className="text-center py-12">
                            <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                            <p className="text-gray-500 dark:text-gray-400">
                                Tous les produits ont été reçus
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-4">
                            {lineData.map((line, index) => (
                                <div 
                                    key={line.line_id}
                                    className="bg-gray-50 dark:bg-gray-700/30 rounded-lg p-4 border border-gray-200 dark:border-gray-600"
                                >
                                    {/* Product info */}
                                    <div className="flex items-center justify-between mb-4">
                                        <div className="flex items-center gap-3">
                                            <div className="h-10 w-10 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg flex items-center justify-center">
                                                <Package className="h-5 w-5 text-gray-500" />
                                            </div>
                                            <div>
                                                <p className="font-medium text-gray-900 dark:text-gray-100">
                                                    {line.product_name}
                                                </p>
                                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                                    Commandé: {line.ordered_quantity} | Déjà reçu: {line.received_quantity} | Restant: {line.remaining}
                                                </p>
                                            </div>
                                        </div>
                                        <Badge className="bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                            {line.remaining} à recevoir
                                        </Badge>
                                    </div>

                                    {/* Inputs */}
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        {/* Batch Number */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <Hash className="h-3 w-3 inline mr-1" />
                                                N° Lot *
                                            </label>
                                            <Input
                                                type="text"
                                                placeholder="LOT-001"
                                                value={line.batch_number}
                                                onChange={(e) => updateLineField(line.line_id, 'batch_number', e.target.value)}
                                                className={`${line.errors.batch_number ? 'border-red-500' : ''} bg-white dark:bg-gray-800`}
                                            />
                                            {line.errors.batch_number && (
                                                <p className="text-xs text-red-500 mt-1">{line.errors.batch_number}</p>
                                            )}
                                        </div>

                                        {/* Expiration Date */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                <Calendar className="h-3 w-3 inline mr-1" />
                                                Date d'expiration *
                                            </label>
                                            <Input
                                                type="date"
                                                value={line.expiration_date}
                                                onChange={(e) => updateLineField(line.line_id, 'expiration_date', e.target.value)}
                                                className={`${line.errors.expiration_date ? 'border-red-500' : ''} bg-white dark:bg-gray-800`}
                                            />
                                            {line.errors.expiration_date && (
                                                <p className="text-xs text-red-500 mt-1">{line.errors.expiration_date}</p>
                                            )}
                                        </div>

                                        {/* Quantity */}
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                                Quantité reçue
                                            </label>
                                            <Input
                                                type="number"
                                                min={1}
                                                max={line.remaining}
                                                value={line.quantity}
                                                onChange={(e) => updateLineField(line.line_id, 'quantity', e.target.value)}
                                                className={`${line.errors.quantity ? 'border-red-500' : ''} bg-white dark:bg-gray-800`}
                                            />
                                            {line.errors.quantity && (
                                                <p className="text-xs text-red-500 mt-1">{line.errors.quantity}</p>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Footer */}
                {lineData.length > 0 && (
                    <div className="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900">
                        <div className="flex items-center justify-between mb-4">
                            <span className="text-gray-600 dark:text-gray-300">
                                Total à réceptionner:
                            </span>
                            <span className="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {totalToReceive} unité(s)
                            </span>
                        </div>
                        <div className="flex gap-3">
                            <Button
                                variant="outline"
                                onClick={onClose}
                                disabled={receiving}
                                className="flex-1"
                            >
                                Annuler
                            </Button>
                            <Button
                                onClick={handleSubmit}
                                disabled={receiving}
                                className="flex-1 bg-green-600 hover:bg-green-700"
                            >
                                {receiving ? (
                                    <>
                                        <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                        Réception...
                                    </>
                                ) : (
                                    <>
                                        <Check className="h-4 w-4 mr-2" />
                                        Confirmer la réception
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
