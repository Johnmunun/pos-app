import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { User, Phone, Mail, MapPin, Building2, CreditCard, FileText, Save, Loader2 } from 'lucide-react';

export default function CustomerDrawer({ isOpen, onClose, customer = null, onSuccess = null, canCreate = false, canUpdate = false, routePrefix = 'pharmacy' }) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'USD';
    const isEditing = !!customer;

    const [data, setData] = useState({
        name: '',
        phone: '',
        email: '',
        address: '',
        customer_type: 'individual',
        tax_number: '',
        credit_limit: '',
    });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (customer) {
            setData({
                name: customer.name || '',
                phone: customer.phone || '',
                email: customer.email || '',
                address: customer.address || '',
                customer_type: customer.customer_type || 'individual',
                tax_number: customer.tax_number || '',
                credit_limit: customer.credit_limit?.toString() || '',
            });
        } else {
            setData({
                name: '',
                phone: '',
                email: '',
                address: '',
                customer_type: 'individual',
                tax_number: '',
                credit_limit: '',
            });
        }
        setErrors({});
    }, [customer, isOpen]);

    const handleChange = (field, value) => {
        setData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: null }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (isEditing && !canUpdate) {
            toast.error('Vous n\'avez pas la permission de modifier ce client.');
            return;
        }

        if (!isEditing && !canCreate) {
            toast.error('Vous n\'avez pas la permission de créer un client.');
            return;
        }

        setProcessing(true);
        setErrors({});

        try {
            const payload = {
                ...data,
                credit_limit: data.credit_limit ? parseFloat(data.credit_limit) : null,
            };

            let response;
            if (isEditing) {
                response = await axios.put(route(`${routePrefix}.customers.update`, customer.id), payload);
            } else {
                response = await axios.post(route(`${routePrefix}.customers.store`), payload);
            }

            if (response.data.success) {
                toast.success(response.data.message);
                onClose();
                if (onSuccess) {
                    onSuccess();
                }
            } else {
                toast.error(response.data.message || 'Une erreur est survenue');
            }
        } catch (error) {
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                toast.error(error.response?.data?.message || 'Une erreur est survenue');
            }
        } finally {
            setProcessing(false);
        }
    };

    const canSubmit = isEditing ? canUpdate : canCreate;

    return (
        <Drawer
            isOpen={isOpen}
            onClose={onClose}
            title={isEditing ? 'Modifier le client' : 'Nouveau client'}
        >
            <form onSubmit={handleSubmit} className="flex flex-col h-full">
                <div className="flex-1 space-y-4 p-4 overflow-y-auto">
                    {/* Nom */}
                    <div className="space-y-2">
                        <Label htmlFor="name" className="flex items-center gap-2">
                            <User className="h-4 w-4" />
                            Nom <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => handleChange('name', e.target.value)}
                            placeholder="Nom du client"
                            className={errors.name ? 'border-red-500' : ''}
                        />
                        {errors.name && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                        )}
                    </div>

                    {/* Type de client */}
                    <div className="space-y-2">
                        <Label htmlFor="customer_type" className="flex items-center gap-2">
                            <Building2 className="h-4 w-4" />
                            Type de client
                        </Label>
                        <select
                            id="customer_type"
                            value={data.customer_type}
                            onChange={(e) => handleChange('customer_type', e.target.value)}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                        >
                            <option value="individual">Particulier</option>
                            <option value="company">Entreprise</option>
                        </select>
                    </div>

                    {/* Téléphone */}
                    <div className="space-y-2">
                        <Label htmlFor="phone" className="flex items-center gap-2">
                            <Phone className="h-4 w-4" />
                            Téléphone
                        </Label>
                        <Input
                            id="phone"
                            type="tel"
                            value={data.phone}
                            onChange={(e) => handleChange('phone', e.target.value)}
                            placeholder="+243 XXX XXX XXX"
                            className={errors.phone ? 'border-red-500' : ''}
                        />
                        {errors.phone && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.phone}</p>
                        )}
                    </div>

                    {/* Email */}
                    <div className="space-y-2">
                        <Label htmlFor="email" className="flex items-center gap-2">
                            <Mail className="h-4 w-4" />
                            Email
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => handleChange('email', e.target.value)}
                            placeholder="client@email.com"
                            className={errors.email ? 'border-red-500' : ''}
                        />
                        {errors.email && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                        )}
                    </div>

                    {/* Adresse */}
                    <div className="space-y-2">
                        <Label htmlFor="address" className="flex items-center gap-2">
                            <MapPin className="h-4 w-4" />
                            Adresse
                        </Label>
                        <Textarea
                            id="address"
                            value={data.address}
                            onChange={(e) => handleChange('address', e.target.value)}
                            placeholder="Adresse complète"
                            rows={2}
                            className={errors.address ? 'border-red-500' : ''}
                        />
                        {errors.address && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.address}</p>
                        )}
                    </div>

                    {/* Numéro fiscal (si entreprise) */}
                    {data.customer_type === 'company' && (
                        <div className="space-y-2">
                            <Label htmlFor="tax_number" className="flex items-center gap-2">
                                <FileText className="h-4 w-4" />
                                Numéro fiscal / RCCM
                            </Label>
                            <Input
                                id="tax_number"
                                value={data.tax_number}
                                onChange={(e) => handleChange('tax_number', e.target.value)}
                                placeholder="Numéro fiscal ou RCCM"
                                className={errors.tax_number ? 'border-red-500' : ''}
                            />
                            {errors.tax_number && (
                                <p className="text-sm text-red-600 dark:text-red-400">{errors.tax_number}</p>
                            )}
                        </div>
                    )}

                    {/* Limite de crédit */}
                    <div className="space-y-2">
                        <Label htmlFor="credit_limit" className="flex items-center gap-2">
                            <CreditCard className="h-4 w-4" />
                            Limite de crédit ({currency})
                        </Label>
                        <Input
                            id="credit_limit"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.credit_limit}
                            onChange={(e) => handleChange('credit_limit', e.target.value)}
                            placeholder="0.00"
                            className={errors.credit_limit ? 'border-red-500' : ''}
                        />
                        {errors.credit_limit && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.credit_limit}</p>
                        )}
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Laissez vide pour aucune limite de crédit
                        </p>
                    </div>
                </div>

                {/* Footer avec boutons */}
                <div className="border-t p-4 flex gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        className="flex-1"
                        disabled={processing}
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        className="flex-1"
                        disabled={processing || !canSubmit}
                    >
                        {processing ? (
                            <>
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                Traitement...
                            </>
                        ) : (
                            <>
                                <Save className="h-4 w-4 mr-2" />
                                {isEditing ? 'Mettre à jour' : 'Créer'}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
