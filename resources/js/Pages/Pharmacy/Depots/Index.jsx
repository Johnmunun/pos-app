import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import Modal from '@/Components/Modal';
import { Warehouse, Plus, MapPin, Building2 } from 'lucide-react';
import { toast } from 'react-hot-toast';

export default function DepotsIndex({ depots = [] }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];
    const hasPermission = (p) => auth?.user?.type === 'ROOT' || permissions.includes(p);
    const canCreate = hasPermission('pharmacy.seller.create');

    const [modalOpen, setModalOpen] = useState(false);
    const [form, setForm] = useState({ name: '', code: '', address: '', city: '' });
    const [errors, setErrors] = useState({});
    const [submitting, setSubmitting] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitting(true);
        setErrors({});
        router.post(route('pharmacy.depots.store'), form, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Dépôt créé avec succès');
                setModalOpen(false);
                setForm({ name: '', code: '', address: '', city: '' });
            },
            onError: (err) => setErrors(err || {}),
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Warehouse className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                            Dépôts
                        </h2>
                    </div>
                    {canCreate && (
                        <Button onClick={() => setModalOpen(true)} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Nouveau dépôt
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Dépôts" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <p className="text-gray-600 dark:text-gray-400 mb-6">
                        Sélectionnez un dépôt dans la barre de navigation pour accéder aux produits et au stock. Les vendeurs peuvent être affectés à un ou plusieurs dépôts.
                    </p>

                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {depots.length === 0 ? (
                            <div className="py-12 text-center">
                                <Warehouse className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                    Aucun dépôt
                                </p>
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    Un dépôt par défaut a été créé pour votre tenant. Vous pouvez en ajouter d&apos;autres.
                                </p>
                                {canCreate && (
                                    <Button onClick={() => setModalOpen(true)} className="gap-2">
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
                                                Statut
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
                                                            {(depot.address || depot.city) && (
                                                                <>
                                                                    <MapPin className="h-3 w-3 shrink-0" />
                                                                    {[depot.address, depot.city].filter(Boolean).join(', ')}
                                                                </>
                                                            )}
                                                        </span>
                                                    ) : (
                                                        '—'
                                                    )}
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
                <form onSubmit={handleSubmit} className="p-6">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                        <Building2 className="h-5 w-5" />
                        Nouveau dépôt
                    </h3>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nom *</Label>
                            <Input
                                id="name"
                                value={form.name}
                                onChange={(e) => setForm((f) => ({ ...f, name: e.target.value }))}
                                placeholder="Ex: Dépôt principal"
                                required
                                className={errors.name ? 'border-red-500' : ''}
                            />
                            {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                        </div>
                        <div>
                            <Label htmlFor="code">Code *</Label>
                            <Input
                                id="code"
                                value={form.code}
                                onChange={(e) => setForm((f) => ({ ...f, code: e.target.value.toUpperCase().replace(/\s/g, '-') }))}
                                placeholder="Ex: DEPOT-1"
                                required
                                className={errors.code ? 'border-red-500' : ''}
                            />
                            {errors.code && <p className="text-sm text-red-600 mt-1">{errors.code}</p>}
                        </div>
                        <div>
                            <Label htmlFor="address">Adresse</Label>
                            <Input
                                id="address"
                                value={form.address}
                                onChange={(e) => setForm((f) => ({ ...f, address: e.target.value }))}
                                placeholder="Adresse complète"
                            />
                        </div>
                        <div>
                            <Label htmlFor="city">Ville</Label>
                            <Input
                                id="city"
                                value={form.city}
                                onChange={(e) => setForm((f) => ({ ...f, city: e.target.value }))}
                                placeholder="Ville"
                            />
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button type="button" variant="outline" onClick={() => setModalOpen(false)} disabled={submitting}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={submitting}>
                            {submitting ? 'Création...' : 'Créer le dépôt'}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AppLayout>
    );
}
