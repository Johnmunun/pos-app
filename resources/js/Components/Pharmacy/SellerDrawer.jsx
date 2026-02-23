import { useState, useEffect } from 'react';
import { usePage, router } from '@inertiajs/react';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { User, Mail, Lock, Shield, CheckCircle, X, Warehouse } from 'lucide-react';

export default function SellerDrawer({ seller = null, availableRoles = [], availableDepots = [], open, onClose, onSuccess }) {
    const { auth } = usePage().props;
    const tenantSector = auth?.tenantSector || 'pharmacy';
    const isEditing = !!seller;
    
    // Labels des secteurs
    const sectorLabels = {
        'pharmacy': 'Pharmacie',
        'butchery': 'Boucherie',
        'kiosk': 'Kiosque',
        'supermarket': 'Supermarché',
        'hardware': 'Quincaillerie',
    };
    
    const sectorLabel = sectorLabels[tenantSector] || tenantSector;

    const [data, setData] = useState({
        first_name: '',
        last_name: '',
        email: '',
        password: '',
        password_confirmation: '',
        role_ids: [],
        depot_ids: [],
        is_active: true,
    });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);
    const [selectedRoles, setSelectedRoles] = useState([]);
    const [selectedDepots, setSelectedDepots] = useState([]);
    const [roleSearch, setRoleSearch] = useState('');

    useEffect(() => {
        if (seller) {
            setData({
                first_name: seller.name?.split(' ')[0] || '',
                last_name: seller.name?.split(' ').slice(1).join(' ') || '',
                email: seller.email || '',
                password: '',
                password_confirmation: '',
                role_ids: seller.roles?.map(r => r.id) || [],
                depot_ids: seller.depot_ids || seller.depots?.map(d => d.id) || [],
                is_active: seller.status === 'active',
            });
            setSelectedRoles(seller.roles || []);
            setSelectedDepots(seller.depots || []);
        } else {
            setData({
                first_name: '',
                last_name: '',
                email: '',
                password: '',
                password_confirmation: '',
                role_ids: [],
                depot_ids: [],
                is_active: true,
            });
            setSelectedRoles([]);
            setSelectedDepots([]);
        }
        setErrors({});
        setRoleSearch('');
    }, [seller, open]);

    const handleChange = (field, value) => {
        setData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: null }));
        }
    };

    const toggleDepot = (depot) => {
        const isSelected = selectedDepots.some(d => d.id === depot.id);
        let newSelected;
        if (isSelected) {
            newSelected = selectedDepots.filter(d => d.id !== depot.id);
        } else {
            newSelected = [...selectedDepots, depot];
        }
        setSelectedDepots(newSelected);
        setData(prev => ({ ...prev, depot_ids: newSelected.map(d => d.id) }));
    };

    const toggleRole = (role) => {
        const isSelected = selectedRoles.some(r => r.id === role.id);
        let newSelectedRoles;
        
        if (isSelected) {
            newSelectedRoles = selectedRoles.filter(r => r.id !== role.id);
        } else {
            newSelectedRoles = [...selectedRoles, role];
        }
        
        setSelectedRoles(newSelectedRoles);
        setData(prev => ({
            ...prev,
            role_ids: newSelectedRoles.map(r => r.id),
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setErrors({});

        const payload = {
            ...data,
            role_ids: selectedRoles.map(r => r.id),
            depot_ids: selectedDepots.map(d => d.id),
        };

        const url = isEditing 
            ? route('pharmacy.sellers.update', seller.id)
            : route('pharmacy.sellers.store');

        const method = isEditing ? 'put' : 'post';

        router[method](url, payload, {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                if (onSuccess) {
                    onSuccess();
                }
            },
            onError: (errors) => {
                setErrors(errors || {});
                const errorMessages = Object.values(errors || {}).flat();
                errorMessages.forEach(msg => toast.error(msg));
            },
            onFinish: () => {
                setProcessing(false);
            },
        });
    };

    const filteredRoles = availableRoles.filter(role =>
        role.name.toLowerCase().includes(roleSearch.toLowerCase()) ||
        role.description?.toLowerCase().includes(roleSearch.toLowerCase())
    );

    return (
        <Drawer
            isOpen={open}
            onClose={onClose}
            title={isEditing ? 'Modifier le vendeur' : 'Nouveau vendeur'}
        >
            <form onSubmit={handleSubmit} className="flex flex-col h-full">
                <div className="flex-1 space-y-4 p-4 overflow-y-auto">
                    {/* Prénom */}
                    <div className="space-y-2">
                        <Label htmlFor="first_name" className="flex items-center gap-2">
                            <User className="h-4 w-4" />
                            Prénom <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="first_name"
                            value={data.first_name}
                            onChange={(e) => handleChange('first_name', e.target.value)}
                            placeholder="Prénom du vendeur"
                            className={errors.first_name ? 'border-red-500' : ''}
                            required
                        />
                        {errors.first_name && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.first_name}</p>
                        )}
                    </div>

                    {/* Nom */}
                    <div className="space-y-2">
                        <Label htmlFor="last_name" className="flex items-center gap-2">
                            <User className="h-4 w-4" />
                            Nom <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="last_name"
                            value={data.last_name}
                            onChange={(e) => handleChange('last_name', e.target.value)}
                            placeholder="Nom du vendeur"
                            className={errors.last_name ? 'border-red-500' : ''}
                            required
                        />
                        {errors.last_name && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.last_name}</p>
                        )}
                    </div>

                    {/* Email */}
                    <div className="space-y-2">
                        <Label htmlFor="email" className="flex items-center gap-2">
                            <Mail className="h-4 w-4" />
                            Email <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => handleChange('email', e.target.value)}
                            placeholder="email@example.com"
                            className={errors.email ? 'border-red-500' : ''}
                            required
                        />
                        {errors.email && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                        )}
                    </div>

                    {/* Mot de passe */}
                    {!isEditing && (
                        <>
                            <div className="space-y-2">
                                <Label htmlFor="password" className="flex items-center gap-2">
                                    <Lock className="h-4 w-4" />
                                    Mot de passe <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => handleChange('password', e.target.value)}
                                    placeholder="Minimum 8 caractères"
                                    className={errors.password ? 'border-red-500' : ''}
                                    required={!isEditing}
                                    minLength={8}
                                />
                                {errors.password && (
                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.password}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="password_confirmation" className="flex items-center gap-2">
                                    <Lock className="h-4 w-4" />
                                    Confirmer le mot de passe <span className="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => handleChange('password_confirmation', e.target.value)}
                                    placeholder="Confirmer le mot de passe"
                                    className={errors.password_confirmation ? 'border-red-500' : ''}
                                    required={!isEditing}
                                />
                                {errors.password_confirmation && (
                                    <p className="text-sm text-red-600 dark:text-red-400">{errors.password_confirmation}</p>
                                )}
                            </div>
                        </>
                    )}

                    {/* Dépôts */}
                    {availableDepots.length > 0 && (
                        <div className="space-y-2">
                            <Label className="flex items-center gap-2">
                                <Warehouse className="h-4 w-4" />
                                Affecter aux dépôts
                            </Label>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                                Le vendeur pourra accéder aux produits des dépôts sélectionnés.
                            </p>
                            <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-3 max-h-40 overflow-y-auto bg-gray-50 dark:bg-gray-900/50">
                                <div className="space-y-2">
                                    {availableDepots.map((depot) => {
                                        const isSelected = selectedDepots.some(d => d.id === depot.id);
                                        return (
                                            <div
                                                key={depot.id}
                                                onClick={() => toggleDepot(depot)}
                                                className={`p-3 rounded-lg border cursor-pointer transition-colors ${
                                                    isSelected
                                                        ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                                }`}
                                            >
                                                <div className="flex items-center gap-2">
                                                    {isSelected ? (
                                                        <CheckCircle className="h-4 w-4 text-amber-600 dark:text-amber-400 shrink-0" />
                                                    ) : (
                                                        <div className="h-4 w-4 rounded-full border-2 border-gray-300 dark:border-gray-600 shrink-0" />
                                                    )}
                                                    <span className="font-medium text-gray-900 dark:text-gray-100">{depot.name}</span>
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">({depot.code})</span>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                            {selectedDepots.length > 0 && (
                                <div className="mt-2 flex flex-wrap gap-2">
                                    {selectedDepots.map((d) => (
                                        <Badge key={d.id} variant="outline" className="flex items-center gap-1">
                                            <Warehouse className="h-3 w-3" />
                                            {d.name}
                                            <button type="button" onClick={() => toggleDepot(d)} className="ml-1 hover:text-red-600">
                                                <X className="h-3 w-3" />
                                            </button>
                                        </Badge>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}

                    {/* Rôles */}
                    <div className="space-y-2">
                        <Label className="flex items-center gap-2">
                            <Shield className="h-4 w-4" />
                            Assigner un rôle
                        </Label>
                        
                        {availableRoles.length === 0 ? (
                            <div className="rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3 text-sm text-amber-800 dark:text-amber-200">
                                <p className="font-medium mb-1">Aucun rôle disponible.</p>
                                <p className="text-xs opacity-90">
                                    Des rôles système (ex. « Vendeur Pharmacie ») peuvent être créés par l’administrateur une fois ; ils apparaîtront ici pour tous les propriétaires. Sinon, créez un rôle dans <strong>Admin → Gestion des accès → Rôles</strong> avec des permissions {sectorLabel} uniquement.
                                </p>
                            </div>
                        ) : (
                            <>
                                <p className="text-xs text-blue-600 dark:text-blue-400 font-medium mb-2">
                                    Cliquez sur un rôle pour l’assigner (récliquez pour retirer).
                                </p>
                                <Input
                                    type="text"
                                    placeholder="Rechercher un rôle..."
                                    value={roleSearch}
                                    onChange={(e) => setRoleSearch(e.target.value)}
                                    className="mb-2"
                                />
                                <div className="border border-gray-200 dark:border-gray-700 rounded-lg p-3 max-h-64 overflow-y-auto bg-gray-50 dark:bg-gray-900/50">
                                    {filteredRoles.length === 0 ? (
                                        <p className="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                                            Aucun rôle trouvé avec cette recherche
                                        </p>
                                    ) : (
                                        <div className="space-y-2">
                                            {filteredRoles.map((role) => {
                                                const isSelected = selectedRoles.some(r => r.id === role.id);
                                                return (
                                                    <div
                                                        key={role.id}
                                                        onClick={() => toggleRole(role)}
                                                        className={`p-3 rounded-lg border cursor-pointer transition-colors ${
                                                            isSelected
                                                                ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20'
                                                                : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                                                        }`}
                                                    >
                                                        <div className="flex items-start justify-between">
                                                            <div className="flex-1">
                                                                <div className="flex items-center gap-2 mb-1">
                                                                    {isSelected ? (
                                                                        <CheckCircle className="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                                                    ) : (
                                                                        <div className="h-4 w-4 rounded-full border-2 border-gray-300 dark:border-gray-600" />
                                                                    )}
                                                                    <span className="font-medium text-gray-900 dark:text-gray-100">
                                                                        {role.name}
                                                                    </span>
                                                                </div>
                                                                {role.description && (
                                                                    <p className="text-xs text-gray-500 dark:text-gray-400 ml-6">
                                                                        {role.description}
                                                                    </p>
                                                                )}
                                                        <div className="mt-2 ml-6 flex items-center gap-2 flex-wrap">
                                                            {role.is_global && (
                                                                <Badge variant="secondary" className="text-xs">
                                                                    Rôle système
                                                                </Badge>
                                                            )}
                                                            <Badge variant="outline" className="text-xs">
                                                                {role.permissions_count || 0} permission{role.permissions_count !== 1 ? 's' : ''}
                                                            </Badge>
                                                        </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}
                                </div>
                            </>
                        )}

                        {/* Rôles sélectionnés */}
                        {selectedRoles.length > 0 && (
                            <div className="mt-3">
                                <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Rôles sélectionnés ({selectedRoles.length})
                                </p>
                                <div className="flex flex-wrap gap-2">
                                    {selectedRoles.map((role) => (
                                        <Badge
                                            key={role.id}
                                            variant="outline"
                                            className="flex items-center gap-1"
                                        >
                                            <Shield className="h-3 w-3" />
                                            {role.name}
                                            <button
                                                type="button"
                                                onClick={() => toggleRole(role)}
                                                className="ml-1 hover:text-red-600 dark:hover:text-red-400"
                                            >
                                                <X className="h-3 w-3" />
                                            </button>
                                        </Badge>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Statut */}
                    <div className="space-y-2">
                        <Label className="flex items-center gap-2">
                            Statut
                        </Label>
                        <div className="flex items-center gap-4">
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="status"
                                    value="active"
                                    checked={data.is_active}
                                    onChange={() => handleChange('is_active', true)}
                                    className="w-4 h-4 text-blue-600"
                                />
                                <span className="text-sm text-gray-700 dark:text-gray-300">Actif</span>
                            </label>
                            <label className="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="status"
                                    value="pending"
                                    checked={!data.is_active}
                                    onChange={() => handleChange('is_active', false)}
                                    className="w-4 h-4 text-blue-600"
                                />
                                <span className="text-sm text-gray-700 dark:text-gray-300">En attente</span>
                            </label>
                        </div>
                    </div>
                </div>

                {/* Footer avec boutons */}
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
                                {isEditing ? 'Mise à jour...' : 'Création...'}
                            </>
                        ) : (
                            <>
                                <CheckCircle className="h-4 w-4" />
                                {isEditing ? 'Mettre à jour' : 'Créer le vendeur'}
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
