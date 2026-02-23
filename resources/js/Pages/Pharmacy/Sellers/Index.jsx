import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import SellerDrawer from '@/Components/Pharmacy/SellerDrawer';
import Modal from '@/Components/Modal';
import { toast } from 'react-hot-toast';
import {
    Plus,
    Search,
    Users,
    Edit,
    Trash2,
    Shield,
    Mail,
    User,
    RefreshCw,
    Info,
    UserCheck,
} from 'lucide-react';
import axios from 'axios';

export default function SellersIndex({ sellers = [], availableRoles = [], availableDepots = [], stats = {}, allPermissions = {} }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canCreate = hasPermission('pharmacy.seller.create');
    const canEdit = hasPermission('pharmacy.seller.edit');
    const canDelete = hasPermission('pharmacy.seller.delete');
    const canView = hasPermission('pharmacy.seller.view');
    const canImpersonate = canEdit;

    const [search, setSearch] = useState('');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedSeller, setSelectedSeller] = useState(null);
    const [deleting, setDeleting] = useState(null);
    const [impersonating, setImpersonating] = useState(null);
    const [impersonateModalSeller, setImpersonateModalSeller] = useState(null);

    const handleSearch = () => {
        router.get(route('pharmacy.sellers.index'), { search }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleKeyPress = (e) => {
        if (e.key === 'Enter') {
            handleSearch();
        }
    };

    const handleOpenCreate = () => {
        setSelectedSeller(null);
        setDrawerOpen(true);
    };

    const handleOpenEdit = (seller) => {
        setSelectedSeller(seller);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setSelectedSeller(null);
    };

    const handleDrawerSuccess = () => {
        router.reload({ only: ['sellers'] });
    };

    const handleOpenImpersonateModal = (seller) => {
        if (!canImpersonate) {
            toast.error('Vous n\'avez pas la permission d\'impersonner des vendeurs.');
            return;
        }
        setImpersonateModalSeller(seller);
    };

    const handleCloseImpersonateModal = () => {
        if (!impersonating) setImpersonateModalSeller(null);
    };

    const handleConfirmImpersonate = async () => {
        if (!impersonateModalSeller) return;
        const seller = impersonateModalSeller;
        setImpersonating(seller.id);
        try {
            const res = await axios.post(route('pharmacy.sellers.impersonate', seller.id), {}, {
                headers: { Accept: 'application/json' },
            });
            toast.success('Impersonation démarrée avec succès');
            setImpersonateModalSeller(null);
            router.visit(res.data?.redirect || route('pharmacy.sales.index'));
        } catch (error) {
            toast.error(error.response?.data?.message || error.response?.data?.errors?.error?.[0] || 'Erreur lors de l\'impersonation');
            setImpersonating(null);
        }
    };

    const handleDelete = async (seller) => {
        if (!confirm(`Êtes-vous sûr de vouloir désactiver le vendeur "${seller.name}" ?`)) {
            return;
        }

        if (!canDelete) {
            toast.error('Vous n\'avez pas la permission de supprimer des vendeurs.');
            return;
        }

        setDeleting(seller.id);
        try {
            await router.delete(route('pharmacy.sellers.destroy', seller.id), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success('Vendeur désactivé avec succès');
                },
                onError: () => {
                    toast.error('Erreur lors de la désactivation');
                },
                onFinish: () => setDeleting(null),
            });
        } catch (error) {
            toast.error('Erreur lors de la suppression');
            setDeleting(null);
        }
    };

    const getStatusBadge = (status) => {
        const statusMap = {
            'active': { label: 'Actif', color: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300' },
            'pending': { label: 'En attente', color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' },
            'blocked': { label: 'Bloqué', color: 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' },
        };
        const statusInfo = statusMap[status] || { label: status, color: 'bg-gray-100 text-gray-800' };
        return (
            <Badge className={statusInfo.color}>
                {statusInfo.label}
            </Badge>
        );
    };

    const filteredSellers = sellers.filter(seller => {
        if (!search) return true;
        const searchLower = search.toLowerCase();
        return (
            seller.name.toLowerCase().includes(searchLower) ||
            seller.email.toLowerCase().includes(searchLower)
        );
    });

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                            <Users className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                            <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                                Gestion des Vendeurs
                            </h2>
                        </div>
                    </div>
                    {canCreate && (
                        <Button onClick={handleOpenCreate} className="gap-2">
                            <Plus className="h-4 w-4" />
                            Ajouter un vendeur
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Gestion des Vendeurs" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {availableRoles.length === 0 && (
                        <div className="mb-4 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-3 flex items-start gap-2 text-sm text-amber-800 dark:text-amber-200">
                            <Info className="h-5 w-5 shrink-0 mt-0.5" />
                            <div>
                                <p className="font-medium">Aucun rôle à assigner pour l’instant.</p>
                                <p className="text-xs mt-1 opacity-90">
                                    L’administrateur peut créer des rôles système (ex. « Vendeur Pharmacie ») une fois ; ils seront visibles ici pour tous. Sinon, créez des rôles pour votre boutique avec des permissions de votre secteur.
                                    {hasPermission('access.roles.view') || auth?.user?.type === 'ROOT' ? (
                                        <> <Link href={route('admin.access.roles')} className="underline font-medium">Admin → Rôles</Link></>
                                    ) : (
                                        ' Demandez à l’administrateur d’exécuter le seeder des rôles par défaut.'
                                    )}
                                </p>
                            </div>
                        </div>
                    )}
                    {/* Stats */}
                    {stats && Object.keys(stats).length > 0 && (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                            <div className="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                                <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Total</div>
                                <div className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{stats.total ?? 0}</div>
                            </div>
                            <div className="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-4 border border-emerald-200 dark:border-emerald-800">
                                <div className="text-sm font-medium text-emerald-700 dark:text-emerald-400">Actifs</div>
                                <div className="mt-1 text-2xl font-bold text-emerald-900 dark:text-emerald-300">{stats.active ?? 0}</div>
                            </div>
                            <div className="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-4 border border-amber-200 dark:border-amber-800">
                                <div className="text-sm font-medium text-amber-700 dark:text-amber-400">En attente</div>
                                <div className="mt-1 text-2xl font-bold text-amber-900 dark:text-amber-300">{stats.pending ?? 0}</div>
                            </div>
                            <div className="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 border border-red-200 dark:border-red-800">
                                <div className="text-sm font-medium text-red-700 dark:text-red-400">Bloqués</div>
                                <div className="mt-1 text-2xl font-bold text-red-900 dark:text-red-300">{stats.blocked ?? 0}</div>
                            </div>
                        </div>
                    )}
                    {/* Barre de recherche */}
                    <div className="mb-6">
                        <div className="flex gap-4">
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Rechercher un vendeur..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    onKeyPress={handleKeyPress}
                                    className="pl-10"
                                />
                            </div>
                            <Button onClick={handleSearch} variant="outline">
                                Rechercher
                            </Button>
                            <Button
                                onClick={() => router.reload({ only: ['sellers'] })}
                                variant="outline"
                                size="icon"
                            >
                                <RefreshCw className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    {/* Liste des vendeurs */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {filteredSellers.length === 0 ? (
                            <div className="py-12 text-center">
                                <Users className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                    {search ? 'Aucun vendeur trouvé' : 'Aucun vendeur'}
                                </p>
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    {search
                                        ? 'Essayez avec d\'autres termes de recherche'
                                        : 'Commencez par créer votre premier vendeur'}
                                </p>
                                {canCreate && !search && (
                                    <Button onClick={handleOpenCreate} className="gap-2">
                                        <Plus className="h-4 w-4" />
                                        Ajouter un vendeur
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Vendeur
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Rôles
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Ventes
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Date de création
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {filteredSellers.map((seller) => (
                                            <tr
                                                key={seller.id}
                                                className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                            >
                                                <td className="px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <div className="h-10 w-10 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center">
                                                            <User className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-gray-900 dark:text-gray-100">
                                                                {seller.name}
                                                            </p>
                                                            <p className="text-sm text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                                                <Mail className="h-3 w-3" />
                                                                {seller.email}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        {seller.roles && seller.roles.length > 0 ? (
                                                            <div className="flex flex-wrap gap-2">
                                                                {seller.roles.map((role) => (
                                                                    <Badge
                                                                        key={role.id}
                                                                        variant="outline"
                                                                        className="text-xs"
                                                                    >
                                                                        <Shield className="h-3 w-3 mr-1" />
                                                                        {role.name}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                        ) : (
                                                            <span className="text-sm text-gray-400 italic">
                                                                Aucun rôle assigné
                                                            </span>
                                                        )}
                                                        {seller.depots && seller.depots.length > 0 && (
                                                            <div className="flex flex-wrap gap-1 mt-1">
                                                                {seller.depots.map((d) => (
                                                                    <Badge key={d.id} variant="secondary" className="text-xs">
                                                                        {d.name}
                                                                    </Badge>
                                                                ))}
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    {getStatusBadge(seller.status)}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {seller.sales_count ?? 0}
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {seller.created_at || '—'}
                                                </td>
                                                <td className="px-6 py-4 text-right">
                                                    <div className="flex items-center justify-end gap-2">
                                                        {canImpersonate && seller.status === 'active' && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleOpenImpersonateModal(seller)}
                                                                disabled={impersonating === seller.id}
                                                                title="Se connecter comme ce vendeur"
                                                                className="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300"
                                                            >
                                                                {impersonating === seller.id ? (
                                                                    <RefreshCw className="h-4 w-4 animate-spin" />
                                                                ) : (
                                                                    <UserCheck className="h-4 w-4" />
                                                                )}
                                                            </Button>
                                                        )}
                                                        {canEdit && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleOpenEdit(seller)}
                                                                className="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </Button>
                                                        )}
                                                        {canDelete && seller.status !== 'blocked' && (
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleDelete(seller)}
                                                                disabled={deleting === seller.id}
                                                                className="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                                                            >
                                                                {deleting === seller.id ? (
                                                                    <RefreshCw className="h-4 w-4 animate-spin" />
                                                                ) : (
                                                                    <Trash2 className="h-4 w-4" />
                                                                )}
                                                            </Button>
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

            {/* Drawer pour créer/modifier */}
            {drawerOpen && (
                <SellerDrawer
                    seller={selectedSeller}
                    availableRoles={availableRoles}
                    availableDepots={availableDepots}
                    open={drawerOpen}
                    onClose={handleCloseDrawer}
                    onSuccess={handleDrawerSuccess}
                />
            )}

            {/* Modal de confirmation d'impersonation */}
            <Modal
                show={!!impersonateModalSeller}
                onClose={handleCloseImpersonateModal}
                maxWidth="sm"
            >
                <div className="p-6">
                    <div className="flex items-start gap-3">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30">
                            <UserCheck className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div className="flex-1">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Confirmation d'impersonation
                            </h3>
                            <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                                Vous allez vous connecter comme <strong className="text-gray-900 dark:text-white">{impersonateModalSeller?.name}</strong>. Continuer ?
                            </p>
                        </div>
                    </div>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button
                            variant="outline"
                            onClick={handleCloseImpersonateModal}
                            disabled={!!impersonating}
                        >
                            Annuler
                        </Button>
                        <Button
                            onClick={handleConfirmImpersonate}
                            disabled={!!impersonating}
                            className="bg-amber-600 hover:bg-amber-700 text-white"
                        >
                            {impersonating ? (
                                <>
                                    <RefreshCw className="h-4 w-4 mr-2 animate-spin" />
                                    Connexion…
                                </>
                            ) : (
                                'Continuer'
                            )}
                        </Button>
                    </div>
                </div>
            </Modal>
        </AppLayout>
    );
}
