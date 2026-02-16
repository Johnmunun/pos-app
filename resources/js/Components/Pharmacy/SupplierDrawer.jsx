import React, { useState, useEffect } from 'react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { 
    Save,
    Truck,
    X,
    Building,
    User,
    Phone,
    Mail,
    MapPin
} from 'lucide-react';
import toast from 'react-hot-toast';
import axios from 'axios';

export default function SupplierDrawer({ 
    isOpen, 
    onClose, 
    supplier = null, 
    onSuccess = null,
    canCreate = false, 
    canUpdate = false 
}) {
    const isEditing = !!supplier;
    
    const [data, setData] = useState({
        name: '',
        contact_person: '',
        phone: '',
        email: '',
        address: ''
    });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    // Reset form when supplier changes
    useEffect(() => {
        if (supplier) {
            setData({
                name: supplier.name || '',
                contact_person: supplier.contact_person || '',
                phone: supplier.phone || '',
                email: supplier.email || '',
                address: supplier.address || ''
            });
        } else {
            setData({
                name: '',
                contact_person: '',
                phone: '',
                email: '',
                address: ''
            });
        }
        setErrors({});
    }, [supplier, isOpen]);

    const handleChange = (field, value) => {
        setData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: null }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        // Vérifier les permissions
        if (isEditing && !canUpdate) {
            toast.error('Vous n\'avez pas la permission de modifier un fournisseur');
            return;
        }

        if (!isEditing && !canCreate) {
            toast.error('Vous n\'avez pas la permission de créer un fournisseur');
            return;
        }

        setProcessing(true);
        setErrors({});

        try {
            let response;
            if (isEditing) {
                response = await axios.put(route('pharmacy.suppliers.update', supplier.id), data);
            } else {
                response = await axios.post(route('pharmacy.suppliers.store'), data);
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
            if (error.response?.status === 422) {
                if (error.response.data.errors) {
                    setErrors(error.response.data.errors);
                } else if (error.response.data.message) {
                    toast.error(error.response.data.message);
                }
            } else {
                toast.error('Erreur lors de l\'enregistrement du fournisseur');
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Drawer 
            isOpen={isOpen} 
            onClose={onClose} 
            title={isEditing ? 'Modifier le fournisseur' : 'Nouveau fournisseur'}
        >
            <form onSubmit={handleSubmit} className="flex flex-col h-full">
                <div className="flex-1 overflow-y-auto space-y-4">
                    {/* Nom */}
                    <div>
                        <Label htmlFor="name" className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <Building className="h-4 w-4 text-gray-400" />
                            Nom du fournisseur <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => handleChange('name', e.target.value)}
                            placeholder="Ex: Pharma Distribution SARL"
                            className="mt-1 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                            required
                            disabled={processing}
                        />
                        {errors.name && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                        )}
                    </div>

                    {/* Personne de contact */}
                    <div>
                        <Label htmlFor="contact_person" className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <User className="h-4 w-4 text-gray-400" />
                            Personne de contact
                        </Label>
                        <Input
                            id="contact_person"
                            type="text"
                            value={data.contact_person}
                            onChange={(e) => handleChange('contact_person', e.target.value)}
                            placeholder="Ex: Jean Dupont"
                            className="mt-1 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                            disabled={processing}
                        />
                        {errors.contact_person && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.contact_person}</p>
                        )}
                    </div>

                    {/* Téléphone */}
                    <div>
                        <Label htmlFor="phone" className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <Phone className="h-4 w-4 text-gray-400" />
                            Téléphone
                        </Label>
                        <Input
                            id="phone"
                            type="tel"
                            value={data.phone}
                            onChange={(e) => handleChange('phone', e.target.value)}
                            placeholder="Ex: +243 999 000 000"
                            className="mt-1 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                            disabled={processing}
                        />
                        {errors.phone && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.phone}</p>
                        )}
                    </div>

                    {/* Email */}
                    <div>
                        <Label htmlFor="email" className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <Mail className="h-4 w-4 text-gray-400" />
                            Email
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => handleChange('email', e.target.value)}
                            placeholder="Ex: contact@pharma.com"
                            className="mt-1 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                            disabled={processing}
                        />
                        {errors.email && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                        )}
                    </div>

                    {/* Adresse */}
                    <div>
                        <Label htmlFor="address" className="flex items-center gap-2 text-gray-700 dark:text-gray-300">
                            <MapPin className="h-4 w-4 text-gray-400" />
                            Adresse
                        </Label>
                        <Textarea
                            id="address"
                            value={data.address}
                            onChange={(e) => handleChange('address', e.target.value)}
                            placeholder="Ex: 123 Avenue du Commerce, Kinshasa"
                            rows={3}
                            className="mt-1 bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600"
                            disabled={processing}
                        />
                        {errors.address && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.address}</p>
                        )}
                    </div>
                </div>

                {/* Footer Actions */}
                <div className="mt-6 flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        className="w-full sm:w-auto"
                        disabled={processing}
                    >
                        <X className="h-4 w-4 mr-2" />
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        className="w-full sm:w-auto bg-amber-500 dark:bg-amber-600 text-white hover:bg-amber-600 dark:hover:bg-amber-700"
                        disabled={processing}
                    >
                        <Save className="h-4 w-4 mr-2" />
                        {processing ? 'Enregistrement...' : (isEditing ? 'Enregistrer' : 'Créer')}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
