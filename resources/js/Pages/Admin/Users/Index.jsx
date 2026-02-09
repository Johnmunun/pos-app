import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import FlashMessages from '@/Components/FlashMessages';
import { toast } from 'react-hot-toast';
import { 
    MoreVertical, 
    UserCheck, 
    UserX, 
    Shield, 
    ShieldOff, 
    Lock, 
    Trash2, 
    UserCog,
    AlertCircle,
    CheckCircle,
    XCircle,
    Clock,
    RefreshCw
} from 'lucide-react';
import axios from 'axios';

export default function ManageUsers({ users, roles }) {
    const [isToggling, setIsToggling] = useState(null);
    const [actionLoading, setActionLoading] = useState({});
    const [showRoleModal, setShowRoleModal] = useState(null);
    const [showStatusModal, setShowStatusModal] = useState(null);
    const [showPasswordModal, setShowPasswordModal] = useState(null);
    const [showDeleteModal, setShowDeleteModal] = useState(null);
    const [selectedRole, setSelectedRole] = useState('');
    const [selectedStatus, setSelectedStatus] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [passwordError, setPasswordError] = useState('');
    
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];
    const isRoot = auth?.user?.type === 'ROOT';
    
    const canUpdateUsers = isRoot || permissions.includes('admin.users.update');
    const canAssignRole = isRoot || permissions.includes('users.assign_role');
    const canResetPassword = isRoot || permissions.includes('users.reset_password');
    const canDelete = isRoot || permissions.includes('users.delete');
    const canImpersonate = isRoot || permissions.includes('users.impersonate');
    const canBlock = isRoot || permissions.includes('users.block');
    const canActivate = isRoot || permissions.includes('users.activate');

    const getStatusBadge = (status) => {
        const badges = {
            'active': { bg: 'bg-emerald-100', text: 'text-emerald-800', label: 'Actif', icon: CheckCircle },
            'pending': { bg: 'bg-amber-100', text: 'text-amber-800', label: 'En attente', icon: Clock },
            'blocked': { bg: 'bg-red-100', text: 'text-red-800', label: 'Bloqué', icon: XCircle },
            'suspended': { bg: 'bg-orange-100', text: 'text-orange-800', label: 'Suspendu', icon: AlertCircle },
        };
        
        const badge = badges[status] || badges['pending'];
        const Icon = badge.icon;
        
        return (
            <span className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium ${badge.bg} ${badge.text}`}>
                <Icon className="h-3 w-3" />
                {badge.label}
            </span>
        );
    };

    const getRoleBadgeColor = (type) => {
        switch (type) {
            case 'ROOT':
                return 'bg-purple-100 text-purple-800';
            case 'TENANT_ADMIN':
                return 'bg-amber-100 text-amber-800';
            case 'MERCHANT':
                return 'bg-blue-100 text-blue-800';
            case 'SELLER':
                return 'bg-cyan-100 text-cyan-800';
            case 'STAFF':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getRoleLabel = (type) => {
        const labels = {
            'ROOT': 'Administrateur',
            'TENANT_ADMIN': 'Admin Tenant',
            'MERCHANT': 'Commerçant',
            'SELLER': 'Vendeur',
            'STAFF': 'Personnel',
        };
        return labels[type] || type;
    };

    const handleAssignRole = async (userId) => {
        if (!selectedRole) {
            toast.error('Veuillez sélectionner un rôle');
            return;
        }

        setActionLoading({ ...actionLoading, [`role-${userId}`]: true });

        try {
            const response = await axios.post(route('admin.users.assign-role', userId), {
                role_id: selectedRole,
                tenant_id: null,
            });

            toast.success(response.data?.message || 'Rôle assigné avec succès. L\'utilisateur devra se reconnecter pour voir les nouvelles permissions.', {
                duration: 5000,
            });
            // Recharger complètement la page pour avoir les données à jour
            router.reload();
            setShowRoleModal(null);
            setSelectedRole('');
        } catch (error) {
            toast.error(error.response?.data?.error || error.response?.data?.message || 'Erreur lors de l\'assignation du rôle');
        } finally {
            setActionLoading({ ...actionLoading, [`role-${userId}`]: false });
        }
    };

    const handleUpdateStatus = async (userId) => {
        if (!selectedStatus) {
            alert('Veuillez sélectionner un statut');
            return;
        }

        setActionLoading({ ...actionLoading, [`status-${userId}`]: true });

        try {
            await axios.post(route('admin.users.update-status', userId), {
                status: selectedStatus,
            });

            router.reload();
            setShowStatusModal(null);
            setSelectedStatus('');
        } catch (error) {
            alert(error.response?.data?.error || 'Erreur lors de la mise à jour du statut');
        } finally {
            setActionLoading({ ...actionLoading, [`status-${userId}`]: false });
        }
    };

    const handleResetPassword = async (userId) => {
        if (!newPassword || !confirmPassword) {
            setPasswordError('Veuillez remplir tous les champs');
            return;
        }

        if (newPassword.length < 8) {
            setPasswordError('Le mot de passe doit contenir au moins 8 caractères');
            return;
        }

        if (newPassword !== confirmPassword) {
            setPasswordError('Les mots de passe ne correspondent pas');
            return;
        }

        setActionLoading({ ...actionLoading, [`password-${userId}`]: true });
        setPasswordError('');

        try {
            await axios.post(route('admin.users.reset-password', userId), {
                password: newPassword,
                password_confirmation: confirmPassword,
            });

            alert('Mot de passe réinitialisé avec succès');
            router.reload();
            setShowPasswordModal(null);
            setNewPassword('');
            setConfirmPassword('');
        } catch (error) {
            setPasswordError(error.response?.data?.error || 'Erreur lors de la réinitialisation');
        } finally {
            setActionLoading({ ...actionLoading, [`password-${userId}`]: false });
        }
    };

    const handleDelete = async (userId) => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
            return;
        }

        setActionLoading({ ...actionLoading, [`delete-${userId}`]: true });

        try {
            await axios.delete(route('admin.users.delete', userId));
            router.reload();
            setShowDeleteModal(null);
        } catch (error) {
            alert(error.response?.data?.error || 'Erreur lors de la suppression');
        } finally {
            setActionLoading({ ...actionLoading, [`delete-${userId}`]: false });
        }
    };

    const handleImpersonate = async (userId) => {
        // Afficher un toast de confirmation personnalisé
        const user = users.find(u => u.id === userId);
        const userName = user ? `${user.first_name} ${user.last_name}` : 'cet utilisateur';
        
        toast.custom((t) => (
            <div className={`${t.visible ? 'animate-enter' : 'animate-leave'} max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5`}>
                <div className="flex-1 w-0 p-4">
                    <div className="flex items-start">
                        <div className="flex-shrink-0">
                            <UserCheck className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div className="ml-3 flex-1">
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                                Confirmation d'impersonation
                            </p>
                            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Vous allez vous connecter comme {userName}. Continuer ?
                            </p>
                        </div>
                    </div>
                    <div className="mt-4 flex gap-2">
                        <button
                            onClick={() => {
                                toast.dismiss(t.id);
                                setActionLoading({ ...actionLoading, [`impersonate-${userId}`]: true });
                                axios.post(route('admin.users.impersonate', userId))
                                    .then(() => {
                                        toast.success('Impersonation démarrée avec succès');
                                        router.visit(route('dashboard'));
                                    })
                                    .catch((error) => {
                                        toast.error(error.response?.data?.error || error.response?.data?.message || 'Erreur lors de l\'impersonation');
                                        setActionLoading({ ...actionLoading, [`impersonate-${userId}`]: false });
                                    });
                            }}
                            className="flex-1 bg-amber-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-amber-700 transition"
                        >
                            Continuer
                        </button>
                        <button
                            onClick={() => toast.dismiss(t.id)}
                            className="flex-1 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-md text-sm font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                        >
                            Annuler
                        </button>
                    </div>
                </div>
            </div>
        ), {
            duration: Infinity, // Le toast reste jusqu'à ce que l'utilisateur clique
        });
    };

    // Group users by tenant
    const usersByTenant = users.reduce((acc, user) => {
        const tenantName = user.tenant?.name || '(Aucun tenant)';
        if (!acc[tenantName]) {
            acc[tenantName] = [];
        }
        acc[tenantName].push(user);
        return acc;
    }, {});

    // Stats
    const stats = {
        total: users.length,
        active: users.filter(u => u.status === 'active').length,
        pending: users.filter(u => u.status === 'pending').length,
        blocked: users.filter(u => u.status === 'blocked').length,
        suspended: users.filter(u => u.status === 'suspended').length,
    };

    return (
        <AppLayout>
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                {/* Header */}
                <div className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                Gestion des Utilisateurs
                            </h1>
                            <p className="mt-2 text-gray-600 dark:text-gray-400">
                                Gérez tous les utilisateurs de la plateforme
                            </p>
                        </div>
                        <div className="flex gap-3">
                            <a
                                href={route('admin.tenants.select.view')}
                                className="inline-flex items-center px-4 py-2 rounded-lg text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition"
                            >
                                ← Retour
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {/* Content */}
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                {/* Stats Overview */}
                <div className="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                        <div className="text-sm font-medium text-gray-600 dark:text-gray-400">Total</div>
                        <div className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                            {stats.total}
                        </div>
                    </div>
                    <div className="bg-emerald-50 dark:bg-emerald-900/20 rounded-lg p-6 border border-emerald-200 dark:border-emerald-800">
                        <div className="text-sm font-medium text-emerald-700 dark:text-emerald-400">Actifs</div>
                        <div className="mt-2 text-3xl font-bold text-emerald-900 dark:text-emerald-300">
                            {stats.active}
                        </div>
                    </div>
                    <div className="bg-amber-50 dark:bg-amber-900/20 rounded-lg p-6 border border-amber-200 dark:border-amber-800">
                        <div className="text-sm font-medium text-amber-700 dark:text-amber-400">En attente</div>
                        <div className="mt-2 text-3xl font-bold text-amber-900 dark:text-amber-300">
                            {stats.pending}
                        </div>
                    </div>
                    <div className="bg-red-50 dark:bg-red-900/20 rounded-lg p-6 border border-red-200 dark:border-red-800">
                        <div className="text-sm font-medium text-red-700 dark:text-red-400">Bloqués</div>
                        <div className="mt-2 text-3xl font-bold text-red-900 dark:text-red-300">
                            {stats.blocked}
                        </div>
                    </div>
                    <div className="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-6 border border-orange-200 dark:border-orange-800">
                        <div className="text-sm font-medium text-orange-700 dark:text-orange-400">Suspendus</div>
                        <div className="mt-2 text-3xl font-bold text-orange-900 dark:text-orange-300">
                            {stats.suspended}
                        </div>
                    </div>
                </div>

                {/* Users by Tenant */}
                {Object.entries(usersByTenant).map(([tenantName, tenantUsers]) => (
                    <div key={tenantName} className="mb-8">
                        <div className="flex items-center justify-between mb-4">
                            <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                {tenantName}
                                <span className="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                                    ({tenantUsers.length})
                                </span>
                            </h2>
                            <button
                                onClick={() => router.reload({ only: ['users', 'roles'] })}
                                className="inline-flex items-center gap-2 px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-md transition"
                                title="Actualiser la liste"
                            >
                                <RefreshCw className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                                <span className="hidden sm:inline">Actualiser</span>
                            </button>
                        </div>

                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Nom
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Email
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Rôle
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Inscrit
                                            </th>
                                            <th className="px-6 py-3 text-right text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {tenantUsers.map((user) => (
                                            <tr key={user.id} className="hover:bg-gray-50 dark:hover:bg-gray-900/50 transition">
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {user.first_name} {user.last_name}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    <div className="text-sm text-gray-600 dark:text-gray-400">
                                                        {user.email}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {user.roles && user.roles.length > 0 ? (
                                                        <div className="flex flex-wrap gap-1">
                                                            {user.roles.map((role) => (
                                                                <span key={role.id} className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                                    {role.name}
                                                                </span>
                                                            ))}
                                                        </div>
                                                    ) : (
                                                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRoleBadgeColor(user.type)}`}>
                                                            {getRoleLabel(user.type)}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap">
                                                    {getStatusBadge(user.status || 'pending')}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                                                    {new Date(user.created_at).toLocaleDateString('fr-FR')}
                                                </td>
                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    {user.type !== 'ROOT' && (
                                                        <div className="flex items-center justify-end gap-2">
                                                            {/* Assign Role */}
                                                            {canAssignRole && (
                                                                <button
                                                                    onClick={() => {
                                                                        setShowRoleModal(user.id);
                                                                        setSelectedRole('');
                                                                    }}
                                                                    className="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition"
                                                                    title="Assigner un rôle"
                                                                >
                                                                    <UserCog className="h-4 w-4" />
                                                                </button>
                                                            )}

                                                            {/* Update Status */}
                                                            {(canActivate || canBlock) && (
                                                                <button
                                                                    onClick={() => {
                                                                        setShowStatusModal(user.id);
                                                                        setSelectedStatus(user.status || 'pending');
                                                                    }}
                                                                    className="p-2 text-amber-600 dark:text-amber-400 hover:bg-amber-50 dark:hover:bg-amber-900/20 rounded-lg transition"
                                                                    title="Modifier le statut"
                                                                >
                                                                    <Shield className="h-4 w-4" />
                                                                </button>
                                                            )}

                                                            {/* Reset Password */}
                                                            {canResetPassword && (
                                                                <button
                                                                    onClick={() => {
                                                                        setShowPasswordModal(user.id);
                                                                        setNewPassword('');
                                                                        setConfirmPassword('');
                                                                        setPasswordError('');
                                                                    }}
                                                                    className="p-2 text-purple-600 dark:text-purple-400 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition"
                                                                    title="Réinitialiser le mot de passe"
                                                                >
                                                                    <Lock className="h-4 w-4" />
                                                                </button>
                                                            )}

                                                            {/* Impersonate */}
                                                            {canImpersonate && (
                                                                <button
                                                                    onClick={() => handleImpersonate(user.id)}
                                                                    disabled={actionLoading[`impersonate-${user.id}`]}
                                                                    className="p-2 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20 rounded-lg transition disabled:opacity-50"
                                                                    title="Se connecter comme cet utilisateur"
                                                                >
                                                                    <UserCheck className="h-4 w-4" />
                                                                </button>
                                                            )}

                                                            {/* Delete */}
                                                            {canDelete && (
                                                                <button
                                                                    onClick={() => setShowDeleteModal(user.id)}
                                                                    className="p-2 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition"
                                                                    title="Supprimer"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    )}
                                                    {user.type === 'ROOT' && (
                                                        <span className="text-xs text-gray-500 dark:text-gray-400 italic">
                                                            Administrateur
                                                        </span>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                ))}

                {users.length === 0 && (
                    <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 px-6 py-12 text-center">
                        <p className="text-gray-500 dark:text-gray-400">Aucun utilisateur trouvé</p>
                    </div>
                )}
            </div>

            {/* Modals */}
            {/* Assign Role Modal */}
            {showRoleModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            Assigner un rôle
                        </h3>
                        <select
                            value={selectedRole}
                            onChange={(e) => setSelectedRole(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                            <option value="">Sélectionner un rôle</option>
                            {roles?.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {role.name}
                                </option>
                            ))}
                        </select>
                        <div className="flex gap-3 mt-6">
                            <button
                                onClick={() => {
                                    setShowRoleModal(null);
                                    setSelectedRole('');
                                }}
                                className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleAssignRole(showRoleModal)}
                                disabled={actionLoading[`role-${showRoleModal}`] || !selectedRole}
                                className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 transition"
                            >
                                {actionLoading[`role-${showRoleModal}`] ? 'En cours...' : 'Assigner'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Status Modal */}
            {showStatusModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            Modifier le statut
                        </h3>
                        <select
                            value={selectedStatus}
                            onChange={(e) => setSelectedStatus(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                            <option value="pending">En attente</option>
                            <option value="active">Actif</option>
                            <option value="blocked">Bloqué</option>
                            <option value="suspended">Suspendu</option>
                        </select>
                        <div className="flex gap-3 mt-6">
                            <button
                                onClick={() => {
                                    setShowStatusModal(null);
                                    setSelectedStatus('');
                                }}
                                className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleUpdateStatus(showStatusModal)}
                                disabled={actionLoading[`status-${showStatusModal}`]}
                                className="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 disabled:opacity-50 transition"
                            >
                                {actionLoading[`status-${showStatusModal}`] ? 'En cours...' : 'Mettre à jour'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Password Reset Modal */}
            {showPasswordModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-bold text-gray-900 dark:text-white mb-4">
                            Réinitialiser le mot de passe
                        </h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Nouveau mot de passe
                                </label>
                                <input
                                    type="password"
                                    value={newPassword}
                                    onChange={(e) => setNewPassword(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Minimum 8 caractères"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Confirmer le mot de passe
                                </label>
                                <input
                                    type="password"
                                    value={confirmPassword}
                                    onChange={(e) => setConfirmPassword(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                />
                            </div>
                            {passwordError && (
                                <div className="text-sm text-red-600 dark:text-red-400">
                                    {passwordError}
                                </div>
                            )}
                        </div>
                        <div className="flex gap-3 mt-6">
                            <button
                                onClick={() => {
                                    setShowPasswordModal(null);
                                    setNewPassword('');
                                    setConfirmPassword('');
                                    setPasswordError('');
                                }}
                                className="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleResetPassword(showPasswordModal)}
                                disabled={actionLoading[`password-${showPasswordModal}`] || !newPassword || !confirmPassword}
                                className="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 disabled:opacity-50 transition"
                            >
                                {actionLoading[`password-${showPasswordModal}`] ? 'En cours...' : 'Réinitialiser'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                        <div className="flex items-center gap-3 mb-4">
                            <AlertCircle className="h-6 w-6 text-red-600 dark:text-red-400" />
                            <h3 className="text-lg font-bold text-gray-900 dark:text-white">
                                Supprimer l'utilisateur
                            </h3>
                        </div>
                        <p className="text-gray-600 dark:text-gray-400 mb-6">
                            Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.
                        </p>
                        <div className="flex gap-3 justify-end">
                            <button
                                onClick={() => setShowDeleteModal(null)}
                                className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleDelete(showDeleteModal)}
                                disabled={actionLoading[`delete-${showDeleteModal}`]}
                                className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50 transition"
                            >
                                {actionLoading[`delete-${showDeleteModal}`] ? 'Suppression...' : 'Supprimer'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
            
            <FlashMessages />
            </div>
        </AppLayout>
    );
}
