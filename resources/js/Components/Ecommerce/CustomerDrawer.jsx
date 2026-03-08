import { useState, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { 
    Save,
    User,
    Mail,
    Phone,
    MapPin,
} from 'lucide-react';
import axios from 'axios';

export default function CustomerDrawer({ isOpen, onClose, customer = null }) {
    const isEditing = !!customer;

    const [formData, setFormData] = useState({
        email: '',
        first_name: '',
        last_name: '',
        phone: '',
        default_shipping_address: '',
        default_billing_address: '',
    });

    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    useEffect(() => {
        if (customer) {
            setFormData({
                email: customer.email || '',
                first_name: customer.first_name || '',
                last_name: customer.last_name || '',
                phone: customer.phone || '',
                default_shipping_address: customer.default_shipping_address || '',
                default_billing_address: customer.default_billing_address || '',
            });
        } else {
            setFormData({
                email: '',
                first_name: '',
                last_name: '',
                phone: '',
                default_shipping_address: '',
                default_billing_address: '',
            });
        }
        setErrors({});
    }, [customer, isOpen]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        try {
            const response = await axios.post(route('ecommerce.customers.store'), formData);
            toast.success(response.data.message || 'Client créé avec succès');
            onClose();
            router.reload({ only: ['customers'] });
        } catch (error) {
            if (error.response?.data?.errors) {
                setErrors(error.response.data.errors);
                const errorMessages = Object.values(error.response.data.errors).flat();
                errorMessages.forEach(msg => toast.error(msg));
            } else {
                toast.error(error.response?.data?.message || 'Erreur lors de la création du client');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Drawer
            isOpen={isOpen}
            onClose={onClose}
            title={isEditing ? 'Modifier le client' : 'Nouveau client'}
        >
            <form onSubmit={handleSubmit} className="flex flex-col h-full">
                <div className="flex-1 space-y-4 p-4 overflow-y-auto">
                    <div className="space-y-2">
                        <Label htmlFor="email" className="flex items-center gap-2">
                            <Mail className="h-4 w-4" />
                            Email <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={formData.email}
                            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                            required
                            disabled={isEditing}
                        />
                        {errors.email && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="first_name" className="flex items-center gap-2">
                                <User className="h-4 w-4" />
                                Prénom <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="first_name"
                                value={formData.first_name}
                                onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                                required
                            />
                            {errors.first_name && (
                                <p className="text-sm text-red-600 dark:text-red-400">{errors.first_name}</p>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="last_name" className="flex items-center gap-2">
                                <User className="h-4 w-4" />
                                Nom <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="last_name"
                                value={formData.last_name}
                                onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                                required
                            />
                            {errors.last_name && (
                                <p className="text-sm text-red-600 dark:text-red-400">{errors.last_name}</p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="phone" className="flex items-center gap-2">
                            <Phone className="h-4 w-4" />
                            Téléphone
                        </Label>
                        <Input
                            id="phone"
                            value={formData.phone}
                            onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                        />
                        {errors.phone && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.phone}</p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="default_shipping_address" className="flex items-center gap-2">
                            <MapPin className="h-4 w-4" />
                            Adresse de livraison par défaut
                        </Label>
                        <Textarea
                            id="default_shipping_address"
                            value={formData.default_shipping_address}
                            onChange={(e) => setFormData({ ...formData, default_shipping_address: e.target.value })}
                            rows={3}
                        />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="default_billing_address" className="flex items-center gap-2">
                            <MapPin className="h-4 w-4" />
                            Adresse de facturation par défaut
                        </Label>
                        <Textarea
                            id="default_billing_address"
                            value={formData.default_billing_address}
                            onChange={(e) => setFormData({ ...formData, default_billing_address: e.target.value })}
                            rows={3}
                        />
                    </div>
                </div>

                <div className="border-t border-gray-200 dark:border-gray-700 p-4 flex gap-3 justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        disabled={processing}
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="gap-2"
                    >
                        {processing ? (
                            <>
                                <div className="h-4 w-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                Création...
                            </>
                        ) : (
                            <>
                                <Save className="h-4 w-4" />
                                Créer le client
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
