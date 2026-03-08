import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { User, Phone, Mail, MapPin, Save, Loader2, X } from 'lucide-react';

export default function CustomerDrawer({ isOpen, onClose, customer = null, onSuccess = null, canCreate = false, canUpdate = false, routePrefix = 'commerce' }) {
    const { shop } = usePage().props;
    const isEditing = !!customer;

    const [data, setData] = useState({
        name: '',
        phone: '',
        email: '',
        address: '',
    });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (customer) {
            setData({
                name: customer.full_name || customer.name || '',
                phone: customer.phone || '',
                email: customer.email || '',
                address: customer.address || '',
            });
        } else {
            setData({
                name: '',
                phone: '',
                email: '',
                address: '',
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
            let response;
            if (isEditing) {
                response = await axios.put(route(`${routePrefix}.customers.update`, customer.id), data);
            } else {
                response = await axios.post(route(`${routePrefix}.customers.store`), data);
            }

            if (response.data.success) {
                toast.success(response.data.message || (isEditing ? 'Client mis à jour' : 'Client créé'));
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
                            required
                            disabled={processing}
                        />
                        {errors.name && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                        )}
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
                            disabled={processing}
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
                            disabled={processing}
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
                            disabled={processing}
                        />
                        {errors.address && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.address}</p>
                        )}
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
                        <X className="h-4 w-4 mr-2" />
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        className="flex-1 bg-amber-500 dark:bg-amber-600 text-white hover:bg-amber-600 dark:hover:bg-amber-700"
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
