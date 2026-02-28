import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import Modal from '@/Components/Modal';
import { Warehouse, Plus, Edit, Power, PowerOff, MapPin, Building2, Phone, Mail } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function HardwareDepotsIndex({ depots = [] }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];
    const userType = auth?.user?.type;
    const isRoot = userType === 'ROOT' || userType === 'root';
    const hasAllPermissions = permissions.includes('*');
    
    const hasPermission = (p) => {
        if (isRoot) return true;
        if (hasAllPermissions) return true;
        return permissions.includes(p);
    };
    
    const canCreate = hasPermission('hardware.warehouse.create');
    const canUpdate = hasPermission('hardware.warehouse.update');
    const canActivate = hasPermission('hardware.warehouse.activate');
    const canDeactivate = hasPermission('hardware.warehouse.deactivate');

    const [modalOpen, setModalOpen] = useState(false);
    const [editingDepot, setEditingDepot] = useState(null);
    const [form, setForm] = useState({ 
        name: '', 
        code: '', 
        address: '', 
        city: '', 
        postal_code: '', 
        country: 'CM',
        phone: '',
        email: ''
    });
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    const handleOpenCreate = () => {
        setEditingDepot(null);
        setForm({ 
            name: '', 
            code: '', 
            address: '', 
            city: '', 
            postal_code: '', 
            country: 'CM',
            phone: '',
            email: ''
        });
        setErrors({});
        setModalOpen(true);
    };

    const handleOpenEdit = (depot) => {
        setEditingDepot(depot);
        setForm({
            name: depot.name || '',
            code: depot.code || '',
            address: depot.address || '',
            city: depot.city || '',
            postal_code: depot.postal_code || '',
            country: depot.country || 'CM',
            phone: depot.phone || '',
            email: depot.email || '',
        });
        setErrors({});
        setModalOpen(true);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        
        const routeName = editingDepot 
            ? 'hardware.depots.update' 
            : 'hardware.depots.store';
        const method = editingDepot ? 'put' : 'post';
        const url = editingDepot 
            ? route('hardware.depots.update', editingDepot.id)
            : route('hardware.depots.store');

        router[method](url, form, {
            preserveScroll: true,
            onSuccess: () => {
                // Le toast sera affiché par FlashMessages depuis le backend
                setModalOpen(false);
                setEditingDepot(null);
                setForm({ 
                    name: '', 
                    code: '', 
                    address: '', 
                    city: '', 
                    postal_code: '', 
                    country: 'CM',
                    phone: '',
                    email: ''
                });
            },
            onError: (err) => setErrors(err || {}),
            onFinish: () => setSubmitting(false),
        });
    };

    const handleToggleActive = (depot) => {
        if (!depot.is_active && !canActivate) {
            toast.error('Permission refusée');
            return;
        }
        if (depot.is_active && !canDeactivate) {
            toast.error('Permission refusée');
            return;
        }

        const routeName = depot.is_active 
            ? 'hardware.depots.deactivate' 
            : 'hardware.depots.activate';
        
        router.post(route(routeName, depot.id), {}, {
            preserveScroll: true,
            onSuccess: () => {
                // Le toast sera affiché par FlashMessages depuis le backend
            },
            onError: () => {
                toast.error('Erreur lors de la modification');
            },
        });
    };
    
    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between w-full">
                    <div className="flex items-center gap-4">
                        <Warehouse className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Dépôts
                        </h2>
                    </div>
                    {canCreate && (
                        <Button onClick={handleOpenCreate} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Nouveau dépôt
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Dépôts - Hardware" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <p className="text-gray-600 dark:text-gray-400 mb-6">
                        Gérez vos dépôts. Sélectionnez un dépôt dans la barre de navigation pour accéder aux produits et au stock de ce dépôt.
                    </p>

                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {depots.length === 0 ? (
                            <div className="py-12 text-center">
                                <Warehouse className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                    Aucun dépôt
                                </p>
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    Créez votre premier dépôt pour commencer.
                                </p>
                                {canCreate && (
                                    <Button onClick={handleOpenCreate} className="gap-2">
                                        <Plus className="h-4 w-4" />
                                        Créer un dépôt
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Dépôt
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Adresse
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Contact
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {depots.map((depot) => (
                                            <tr key={depot.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="h-10 w-10 rounded-lg bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center">
                                                            <Warehouse className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                                        </div>
                                                        <span className="font-medium text-gray-900 dark:text-white">
                                                            {depot.name}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    <code className="bg-gray-100 dark:bg-gray-700 px-2 py-0.5 rounded">{depot.code}</code>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {depot.address || depot.city ? (
                                                        <span className="flex items-center gap-1">
                                                            <MapPin className="h-3 w-3 shrink-0" />
                                                            {[depot.address, depot.city, depot.postal_code].filter(Boolean).join(', ')}
                                                            {depot.country && depot.country !== 'CM' && ` (${depot.country})`}
                                                        </span>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    <div className="space-y-1">
                                                        {depot.phone && (
                                                            <div className="flex items-center gap-1">
                                                                <Phone className="h-3 w-3" />
                                                                {depot.phone}
                                                            </div>
                                                        )}
                                                        {depot.email && (
                                                            <div className="flex items-center gap-1">
                                                                <Mail className="h-3 w-3" />
                                                                {depot.email}
                                                            </div>
                                                        )}
                                                        {!depot.phone && !depot.email && '—'}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {depot.is_active ? (
                                                        <Badge className="bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                                            Actif
                                                        </Badge>
                                                    ) : (
                                                        <Badge variant="secondary">Inactif</Badge>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        {canUpdate && (
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() => handleOpenEdit(depot)}
                                                                className="gap-1"
                                                            >
                                                                <Edit className="h-3 w-3" />
                                                                Modifier
                                                            </Button>
                                                        )}
                                                        {depot.is_active ? (
                                                            canDeactivate && (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleToggleActive(depot)}
                                                                    className="gap-1 text-red-600 hover:text-red-700"
                                                                >
                                                                    <PowerOff className="h-3 w-3" />
                                                                    Désactiver
                                                                </Button>
                                                            )
                                                        ) : (
                                                            canActivate && (
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    onClick={() => handleToggleActive(depot)}
                                                                    className="gap-1 text-emerald-600 hover:text-emerald-700"
                                                                >
                                                                    <Power className="h-3 w-3" />
                                                                    Activer
                                                                </Button>
                                                            )
                                                        )}
                                                    </div>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <Modal show={modalOpen} onClose={() => !submitting && setModalOpen(false)} maxWidth="md">
                <form onSubmit={handleSubmit} className="p-6 bg-white dark:bg-gray-800">
                    <div className="mb-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <Building2 className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            {editingDepot ? 'Modifier le dépôt' : 'Nouveau dépôt'}
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {editingDepot ? 'Modifiez les informations du dépôt' : 'Remplissez les informations pour créer un nouveau dépôt'}
                        </p>
                    </div>
                    <div className="space-y-5">
                        <div>
                            <Label htmlFor="name" className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
                                Nom du dépôt <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="name"
                                value={form.name}
                                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                                placeholder="Ex: Dépôt principal"
                                required
                                className={`mt-1 ${errors.name ? 'border-red-500' : ''}`}
                            />
                            {errors.name && <p className="text-sm text-red-600 dark:text-red-400 mt-1">{errors.name}</p>}
                        </div>
                        <div>
                            <Label htmlFor="code" className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
                                Code du dépôt <span className="text-red-500">*</span>
                            </Label>
                            <Input
                                id="code"
                                value={form.code}
                                onChange={(e) => setForm((f) => ({ ...f, code: e.target.value.toUpperCase().replace(/\s/g, '-') }))}
                                placeholder="Ex: DEPOT-1"
                                required
                                className={`mt-1 ${errors.code ? 'border-red-500' : ''}`}
                            />
                            {errors.code && <p className="text-sm text-red-600 dark:text-red-400 mt-1">{errors.code}</p>}
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="address" className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
                                    Adresse
                                </Label>
                                <Input
                                    id="address"
                                    value={form.address}
                                    onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))}
                                    placeholder="Adresse complète"
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="city" className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
                                    Ville
                                </Label>
                                <Input
                                    id="city"
                                    value={form.city}
                                    onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))}
                                    placeholder="Ville"
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="postal_code" className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
                                    Code postal
                                </Label>
                                <Input
                                    id="postal_code"
                                    value={form.postal_code}
                                    onChange={(e) => setForm((f) => ({ ...f, postal_code: e.target.value }))}
                                    placeholder="Code postal"
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="country" className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
                                    Pays
                                </Label>
                                <Input
                                    id="country"
                                    value={form.country}
                                    onChange={(e) => setForm((f) => ({ ...f, country: e.target.value }))}
                                    placeholder="CM"
                                    className="mt-1"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="phone" className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
                                    Téléphone
                                </Label>
                                <Input
                                    id="phone"
                                    type="tel"
                                    value={form.phone}
                                    onChange={(e) => setForm((f) => ({ ...f, phone: e.target.value }))}
                                    placeholder="+237 6XX XXX XXX"
                                    className="mt-1"
                                />
                            </div>
                            <div>
                                <Label htmlFor="email" className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2 block">
                                    Email
                                </Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.email}
                                    onChange={(e) => setForm((f) => ({ ...f, email: e.target.value }))}
                                    placeholder="depot@example.com"
                                    className={`mt-1 ${errors.email ? 'border-red-500' : ''}`}
                                />
                                {errors.email && <p className="text-sm text-red-600 dark:text-red-400 mt-1">{errors.email}</p>}
                            </div>
                        </div>
                    </div>
                    <div className="mt-8 pt-4 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setModalOpen(false)} disabled={submitting}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={submitting} className="bg-amber-600 hover:bg-amber-700 text-white">
                            {submitting ? (editingDepot ? 'Mise à jour...' : 'Création...') : (editingDepot ? 'Mettre à jour' : 'Créer le dépôt')}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
