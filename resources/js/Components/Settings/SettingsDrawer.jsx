import React, { useState, useEffect, useRef } from 'react';
import { useForm, router } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Button } from '@/Components/ui/button';
import { 
    Save,
    X,
    Upload,
    Trash2,
    WifiOff,
    CloudUpload,
    Building2
} from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import offlineStorage from '@/lib/offlineStorage';
import syncService from '@/lib/syncService';

export default function SettingsDrawer({ isOpen, onClose, settings = null, canUpdate = false }) {
    const isEditing = !!settings;
    const fileInputRef = useRef(null);
    const [logoPreview, setLogoPreview] = useState(settings?.logo_url || null);
    const [offlineStatus, setOfflineStatus] = useState(!navigator.onLine);
    
    const form = useForm({
        company_name: settings?.company_name || '',
        id_nat: settings?.id_nat || '',
        rccm: settings?.rccm || '',
        tax_number: settings?.tax_number || '',
        street: settings?.street || '',
        city: settings?.city || '',
        postal_code: settings?.postal_code || '',
        country: settings?.country || 'CM',
        phone: settings?.phone || '',
        email: settings?.email || '',
        logo: null,
        remove_logo: false,
        currency: (settings?.currency && settings.currency.trim()) || 'XAF',
        exchange_rate: settings?.exchange_rate || null,
        invoice_footer_text: settings?.invoice_footer_text || '',
        receipt_auto_print: settings?.receipt_auto_print ?? false,
    });

    const { data, setData, put, processing, errors, reset } = form;

    // Reset form when settings change
    useEffect(() => {
        if (settings) {
            setData({
                company_name: settings.company_name || '',
                id_nat: settings.id_nat || '',
                rccm: settings.rccm || '',
                tax_number: settings.tax_number || '',
                street: settings.street || '',
                city: settings.city || '',
                postal_code: settings.postal_code || '',
                country: settings.country || 'CM',
                phone: settings.phone || '',
                email: settings.email || '',
                logo: null,
                remove_logo: false,
                currency: (settings.currency && settings.currency.trim()) || 'XAF',
                exchange_rate: settings.exchange_rate || null,
                invoice_footer_text: settings.invoice_footer_text || '',
                receipt_auto_print: settings.receipt_auto_print ?? false,
            });
            setLogoPreview(settings.logo_url || null);
        } else {
            // Réinitialiser avec les valeurs par défaut
            setData({
                company_name: '',
                id_nat: '',
                rccm: '',
                tax_number: '',
                street: '',
                city: '',
                postal_code: '',
                country: 'CM',
                phone: '',
                email: '',
                logo: null,
                remove_logo: false,
                currency: 'XAF',
                exchange_rate: null,
                invoice_footer_text: '',
                receipt_auto_print: false,
            });
            setLogoPreview(null);
        }
    }, [settings]);

    // Vérifier le statut réseau
    useEffect(() => {
        const handleOnline = () => {
            setOfflineStatus(false);
            syncService.syncPendingSettings?.().then(() => {
                toast.success('Paramètres synchronisés');
            }).catch(() => {
                // Ignorer les erreurs silencieusement
            });
        };

        const handleOffline = () => {
            setOfflineStatus(true);
        };

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const handleLogoChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        // Validation
        const maxSize = 2 * 1024 * 1024; // 2 Mo
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];

        if (file.size > maxSize) {
            toast.error('Le fichier est trop volumineux (max 2 Mo)');
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            toast.error('Format non autorisé (JPG, JPEG, PNG, WebP uniquement)');
            return;
        }

        setData('logo', file);
        setData('remove_logo', false);

        // Preview
        const reader = new FileReader();
        reader.onloadend = () => {
            setLogoPreview(reader.result);
        };
        reader.readAsDataURL(file);
    };

    const handleRemoveLogo = () => {
        setData('logo', null);
        setData('remove_logo', true);
        setLogoPreview(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!canUpdate) {
            toast.error('Vous n\'avez pas la permission de modifier les paramètres');
            return;
        }

        if (offlineStatus) {
            // Stocker localement
            try {
                const settingsData = {
                    ...data,
                    logo_base64: data.logo ? await fileToBase64(data.logo) : null,
                    timestamp: new Date().toISOString(),
                };
                await offlineStorage.savePendingSettings(settingsData);
                toast.success('Paramètres enregistrés localement. Synchronisation automatique à la reconnexion.');
                onClose();
            } catch (error) {
                toast.error('Erreur lors de l\'enregistrement local');
            }
            return;
        }

        // Valider et nettoyer les données avant envoi
        const companyName = String(data.company_name || '').trim();
        const currency = String(data.currency || 'XAF').trim().toUpperCase().substring(0, 3);

        // Vérifier que les champs requis ne sont pas vides
        if (!companyName || companyName.length === 0) {
            toast.error('Le nom de l\'entreprise est obligatoire');
            return;
        }
        if (!currency || currency.length === 0 || currency.length !== 3) {
            toast.error('La devise est obligatoire et doit contenir 3 caractères (ex: XAF, EUR, USD)');
            return;
        }

        // Créer FormData manuellement pour garantir l'envoi correct
        const formData = new FormData();
        formData.append('_method', 'PUT'); // Laravel method spoofing
        formData.append('company_name', companyName);
        formData.append('currency', currency);
        
        // Ajouter les champs optionnels seulement s'ils ont une valeur
        if (data.id_nat) formData.append('id_nat', String(data.id_nat).trim());
        if (data.rccm) formData.append('rccm', String(data.rccm).trim());
        if (data.tax_number) formData.append('tax_number', String(data.tax_number).trim());
        if (data.street) formData.append('street', String(data.street).trim());
        if (data.city) formData.append('city', String(data.city).trim());
        if (data.postal_code) formData.append('postal_code', String(data.postal_code).trim());
        if (data.country) formData.append('country', String(data.country).trim());
        if (data.phone) formData.append('phone', String(data.phone).trim());
        if (data.email) formData.append('email', String(data.email).trim());
        if (data.exchange_rate !== null && data.exchange_rate !== undefined && data.exchange_rate !== '') {
            formData.append('exchange_rate', String(data.exchange_rate));
        }
        if (data.invoice_footer_text) formData.append('invoice_footer_text', String(data.invoice_footer_text).trim());
        formData.append('receipt_auto_print', data.receipt_auto_print ? '1' : '0');
        if (data.logo) formData.append('logo', data.logo);
        if (data.remove_logo) formData.append('remove_logo', '1');

        // Debug: afficher le contenu du FormData
        console.log('FormData contents:');
        for (let [key, value] of formData.entries()) {
            console.log(key + ':', value instanceof File ? value.name : value);
        }

        // Utiliser axios directement pour envoyer le FormData
        // Inertia ne gère pas bien FormData avec router.post/put
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        axios.post(route('settings.update'), formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then((response) => {
            toast.success('Paramètres mis à jour avec succès');
            // Recharger la page pour afficher les nouvelles données
            router.reload({ only: ['settings'] });
            onClose();
        })
        .catch((error) => {
            console.error('Settings update errors:', error.response?.data?.errors || error);
            const errors = error.response?.data?.errors || {};
            if (Object.keys(errors).length > 0) {
                Object.entries(errors).forEach(([field, messages]) => {
                    const message = Array.isArray(messages) ? messages[0] : messages;
                    toast.error(`${field}: ${message}`);
                });
            } else {
                toast.error('Erreur lors de la mise à jour des paramètres');
            }
        });
    };

    const fileToBase64 = (file) => {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.readAsDataURL(file);
            reader.onload = () => resolve(reader.result);
            reader.onerror = error => reject(error);
        });
    };

    return (
        <Drawer isOpen={isOpen} onClose={onClose} title={isEditing ? 'Modifier les paramètres' : 'Configurer les paramètres'}>
            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Statut offline */}
                {offlineStatus && (
                    <div className="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
                        <WifiOff className="h-4 w-4" />
                        <span>Mode hors ligne - Les données seront synchronisées à la reconnexion</span>
                    </div>
                )}

                {/* Identité entreprise */}
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                        <Building2 className="h-5 w-5" />
                        Identité de l'entreprise
                    </h3>

                    <div>
                        <Label htmlFor="company_name" className="text-gray-700 dark:text-gray-300">
                            Nom de l'entreprise <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="company_name"
                            type="text"
                            value={data.company_name}
                            onChange={(e) => setData('company_name', e.target.value)}
                            className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            required
                        />
                        {errors.company_name && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.company_name}</p>
                        )}
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="id_nat" className="text-gray-700 dark:text-gray-300">ID NAT</Label>
                            <Input
                                id="id_nat"
                                type="text"
                                value={data.id_nat}
                                onChange={(e) => setData('id_nat', e.target.value)}
                                className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <Label htmlFor="rccm" className="text-gray-700 dark:text-gray-300">RCCM</Label>
                            <Input
                                id="rccm"
                                type="text"
                                value={data.rccm}
                                onChange={(e) => setData('rccm', e.target.value)}
                                className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="tax_number" className="text-gray-700 dark:text-gray-300">Numéro fiscal</Label>
                        <Input
                            id="tax_number"
                            type="text"
                            value={data.tax_number}
                            onChange={(e) => setData('tax_number', e.target.value)}
                            className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        />
                    </div>

                    {/* Upload Logo */}
                    <div>
                        <Label className="text-gray-700 dark:text-gray-300">Logo (max 2 Mo, JPG/PNG/WebP)</Label>
                        <div className="mt-2 space-y-3">
                            {logoPreview && (
                                <div className="relative inline-block">
                                    <img
                                        src={logoPreview}
                                        alt="Logo preview"
                                        className="h-24 w-auto object-contain rounded border border-gray-300 dark:border-gray-600"
                                    />
                                    <button
                                        type="button"
                                        onClick={handleRemoveLogo}
                                        className="absolute -top-2 -right-2 p-1 bg-red-500 text-white rounded-full hover:bg-red-600"
                                    >
                                        <Trash2 className="h-4 w-4" />
                                    </button>
                                </div>
                            )}
                            <div>
                                <input
                                    ref={fileInputRef}
                                    type="file"
                                    accept="image/jpeg,image/jpg,image/png,image/webp"
                                    onChange={handleLogoChange}
                                    className="hidden"
                                    id="logo-upload"
                                />
                                <label
                                    htmlFor="logo-upload"
                                    className="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer"
                                >
                                    <Upload className="h-4 w-4 mr-2" />
                                    {logoPreview ? 'Remplacer le logo' : 'Choisir un logo'}
                                </label>
                            </div>
                        </div>
                        {errors.logo && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.logo}</p>
                        )}
                    </div>
                </div>

                {/* Adresse */}
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Adresse</h3>

                    <div>
                        <Label htmlFor="street" className="text-gray-700 dark:text-gray-300">Rue</Label>
                        <Input
                            id="street"
                            type="text"
                            value={data.street}
                            onChange={(e) => setData('street', e.target.value)}
                            className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        />
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="city" className="text-gray-700 dark:text-gray-300">Ville</Label>
                            <Input
                                id="city"
                                type="text"
                                value={data.city}
                                onChange={(e) => setData('city', e.target.value)}
                                className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>

                        <div>
                            <Label htmlFor="postal_code" className="text-gray-700 dark:text-gray-300">Code postal</Label>
                            <Input
                                id="postal_code"
                                type="text"
                                value={data.postal_code}
                                onChange={(e) => setData('postal_code', e.target.value)}
                                className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="country" className="text-gray-700 dark:text-gray-300">Pays</Label>
                        <Input
                            id="country"
                            type="text"
                            value={data.country}
                            onChange={(e) => setData('country', e.target.value)}
                            className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        />
                    </div>
                </div>

                {/* Coordonnées */}
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Coordonnées</h3>

                    <div>
                        <Label htmlFor="phone" className="text-gray-700 dark:text-gray-300">Téléphone</Label>
                        <Input
                            id="phone"
                            type="tel"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        />
                    </div>

                    <div>
                        <Label htmlFor="email" className="text-gray-700 dark:text-gray-300">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        />
                        {errors.email && (
                            <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                        )}
                    </div>
                </div>

                {/* Facturation */}
                <div className="space-y-4">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">Facturation</h3>

                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <Label htmlFor="currency" className="text-gray-700 dark:text-gray-300">
                                Devise par défaut <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="currency"
                                type="text"
                                value={data.currency}
                                onChange={(e) => setData('currency', e.target.value.toUpperCase())}
                                className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                maxLength={3}
                                required
                            />
                        </div>

                        <div>
                            <Label htmlFor="exchange_rate" className="text-gray-700 dark:text-gray-300">Taux de change</Label>
                            <Input
                                id="exchange_rate"
                                type="number"
                                step="0.0001"
                                value={data.exchange_rate || ''}
                                onChange={(e) => setData('exchange_rate', e.target.value ? parseFloat(e.target.value) : null)}
                                className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="invoice_footer_text" className="text-gray-700 dark:text-gray-300">Texte footer facture</Label>
                        <Textarea
                            id="invoice_footer_text"
                            value={data.invoice_footer_text}
                            onChange={(e) => setData('invoice_footer_text', e.target.value)}
                            rows={3}
                            className="mt-1 bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        />
                    </div>

                    <div className="flex items-start gap-2">
                        <input
                            id="receipt_auto_print"
                            type="checkbox"
                            checked={data.receipt_auto_print}
                            onChange={(e) => setData('receipt_auto_print', e.target.checked)}
                            className="mt-1 h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"
                        />
                        <div>
                            <Label htmlFor="receipt_auto_print" className="text-gray-700 dark:text-gray-300">
                                Impression automatique du reçu après une vente
                            </Label>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                Si activé, après validation d&apos;une vente, le reçu s&apos;ouvre automatiquement dans un nouvel onglet pour impression.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Actions */}
                <div className="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <Button
                        type="submit"
                        disabled={processing}
                        className="bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 text-white w-full sm:w-auto"
                    >
                        {processing ? (
                            <>
                                <CloudUpload className="h-4 w-4 mr-2 animate-spin" />
                                Enregistrement...
                            </>
                        ) : (
                            <>
                                <Save className="h-4 w-4 mr-2" />
                                Enregistrer
                            </>
                        )}
                    </Button>
                    <Button
                        type="button"
                        onClick={onClose}
                        variant="outline"
                        className="w-full sm:w-auto"
                    >
                        <X className="h-4 w-4 mr-2" />
                        Annuler
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
