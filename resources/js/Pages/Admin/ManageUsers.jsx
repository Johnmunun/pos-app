import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import FlashMessages from '@/Components/FlashMessages';
import axios from 'axios';
import { 
    MoreVertical, 
    UserCheck, 
    UserX, 
    Shield, 
    Lock, 
    Trash2, 
    UserCog,
    Key,
    AlertCircle
} from 'lucide-react';

export default function ManageUsers({ users, roles = [] }) {
    const [isToggling, setIsToggling] = useState(null);
    const [actionLoading, setActionLoading] = useState(null);
    const [showRoleModal, setShowRoleModal] = useState(null);
    const [showStatusModal, setShowStatusModal] = useState(null);
    const [showPasswordModal, setShowPasswordModal] = useState(null);
    const [showDeleteModal, setShowDeleteModal] = useState(null);
    const [openMenuId, setOpenMenuId] = useState(null);
    const [selectedRole, setSelectedRole] = useState('');
    const [selectedStatus, setSelectedStatus] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [passwordConfirm, setPasswordConfirm] = useState('');
    
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];
    const isRoot = auth?.user?.type === 'ROOT';
    
    const canAssignRole = isRoot || permissions.includes('users.assign_role');
    const canUpdateStatus = isRoot || permissions.includes('users.activate') || permissions.includes('users.block');
    const canResetPassword = isRoot || permissions.includes('users.reset_password');
    const canDelete = isRoot || permissions.includes('users.delete');
    const canImpersonate = isRoot || permissions.includes('users.impersonate');

    const handleToggleUser = (userId) => {
        if (!canUpdateUsers) {
            return;
        }

        setIsToggling(userId);
        router.post(route('admin.users.update', userId), {}, {
            onFinish: () => setIsToggling(null),
        });
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

    const getStatusBadge = (user) => {
        const status = user.status || (user.is_active ? 'active' : 'pending');
        const statusMap = {
            'active': { label: 'Actif', color: 'bg-emerald-100 text-emerald-800' },
            'pending': { label: 'En attente', color: 'bg-yellow-100 text-yellow-800' },
            'blocked': { label: 'Bloqué', color: 'bg-red-100 text-red-800' },
            'suspended': { label: 'Suspendu', color: 'bg-orange-100 text-orange-800' },
        };
        const statusInfo = statusMap[status] || { label: status, color: 'bg-gray-100 text-gray-800' };
        return (
            <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusInfo.color}`}>
                {statusInfo.label}
            </span>
        );
    };

    const handleAssignRole = async (userId) => {
        if (!selectedRole) {
            alert('Veuillez sélectionner un rôle');
            return;
        }

        setActionLoading(userId);
        try {
            await axios.post(route('admin.users.assign-role', userId), {
                role_id: selectedRole,
                tenant_id: null,
            });
            
            router.reload({ only: ['users'] });
            setShowRoleModal(null);
            setSelectedRole('');
        } catch (error) {
            alert(error.response?.data?.error || 'Erreur lors de l\'assignation du rôle');
        } finally {
            setActionLoading(null);
        }
    };

    const handleUpdateStatus = async (userId) => {
        if (!selectedStatus) {
            alert('Veuillez sélectionner un statut');
            return;
        }

        setActionLoading(userId);
        try {
            await axios.post(route('admin.users.update-status', userId), {
                status: selectedStatus,
            });
            
            router.reload({ only: ['users'] });
            setShowStatusModal(null);
            setSelectedStatus('');
        } catch (error) {
            alert(error.response?.data?.error || 'Erreur lors de la mise à jour du statut');
        } finally {
            setActionLoading(null);
        }
    };

    const handleResetPassword = async (userId) => {
        if (!newPassword || newPassword.length < 8) {
            alert('Le mot de passe doit contenir au moins 8 caractères');
            return;
        }

        if (newPassword !== passwordConfirm) {
            alert('Les mots de passe ne correspondent pas');
            return;
        }

        setActionLoading(userId);
        try {
            await axios.post(route('admin.users.reset-password', userId), {
                password: newPassword,
                password_confirmation: passwordConfirm,
            });
            
            alert('Mot de passe réinitialisé avec succès');
            setShowPasswordModal(null);
            setNewPassword('');
            setPasswordConfirm('');
        } catch (error) {
            alert(error.response?.data?.error || 'Erreur lors de la réinitialisation du mot de passe');
        } finally {
            setActionLoading(null);
        }
    };

    const handleDelete = async (userId) => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
            return;
        }

        setActionLoading(userId);
        try {
            await axios.delete(route('admin.users.delete', userId));
            
            router.reload({ only: ['users'] });
            setShowDeleteModal(null);
        } catch (error) {
            alert(error.response?.data?.error || 'Erreur lors de la suppression');
        } finally {
            setActionLoading(null);
        }
    };

    const handleImpersonate = async (userId) => {
        if (!confirm('Vous allez vous connecter comme cet utilisateur. Continuer ?')) {
            return;
        }

        setActionLoading(userId);
        try {
            await axios.post(route('admin.users.impersonate', userId));
            // La redirection sera gérée par Laravel/Inertia
            router.visit(route('dashboard'));
        } catch (error) {
            alert(error.response?.data?.error || error.response?.data?.message || 'Erreur lors de l\'impersonation');
            setActionLoading(null);
        }
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

    return (
        <div className="min-h-screen bg-white">
            {/* Header */}
            <div className="bg-white border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                Gestion des Utilisateurs
                            </h1>
                            <p className="mt-2 text-gray-600">
                                Gérez tous les utilisateurs de la plateforme
                            </p>
                        </div>
                        <div className="flex gap-3">
                            <a
                                href={route('admin.tenants.select.view')}
                                className="inline-flex items-center px-4 py-2 rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition"
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
                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div className="bg-gray-50 rounded-lg p-6 border border-gray-200">
                        <div className="text-sm font-medium text-gray-600">Utilisateurs Total</div>
                        <div className="mt-2 text-3xl font-bold text-gray-900">
                            {users.length}
                        </div>
                    </div>
                    <div className="bg-emerald-50 rounded-lg p-6 border border-emerald-200">
                        <div className="text-sm font-medium text-emerald-700">Actifs</div>
                        <div className="mt-2 text-3xl font-bold text-emerald-900">
                            {users.filter(u => u.is_active).length}
                        </div>
                    </div>
                    <div className="bg-red-50 rounded-lg p-6 border border-red-200">
                        <div className="text-sm font-medium text-red-700">Inactifs</div>
                        <div className="mt-2 text-3xl font-bold text-red-900">
                            {users.filter(u => !u.is_active).length}
                        </div>
                    </div>
                </div>

                {/* Users by Tenant */}
                {Object.entries(usersByTenant).map(([tenantName, tenantUsers]) => (
                    <div key={tenantName} className="mb-8">
                        <h2 className="text-xl font-bold text-gray-900 mb-4">
                            {tenantName}
                            <span className="ml-2 text-sm font-normal text-gray-500">
                                ({tenantUsers.length})
                            </span>
                        </h2>

                        <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                            <table className="w-full">
                                <thead>
                                    <tr className="bg-gray-50 border-b border-gray-200">
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Nom
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Email
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Rôle
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Inscrit
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {tenantUsers.map((user) => (
                                        <tr key={user.id} className="hover:bg-gray-50 transition">
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm font-medium text-gray-900">
                                                    {user.first_name} {user.last_name}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <div className="text-sm text-gray-600">
                                                    {user.email}
                                                </div>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getRoleBadgeColor(user.type)}`}>
                                                    {getRoleLabel(user.type)}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {getStatusBadge(user)}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {new Date(user.created_at).toLocaleDateString('fr-FR')}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {user.type === 'ROOT' ? (
                                                    <span className="text-xs text-gray-500 italic">Administrateur</span>
                                                ) : (
                                                    <div className="flex items-center gap-2">
                                                        {/* Menu Actions */}
                                                        <div className="relative">
                                                            <button
                                                                className="p-1 rounded hover:bg-gray-100 transition"
                                                                onClick={() => {
                                                                    setOpenMenuId(openMenuId === user.id ? null : user.id);
                                                                }}
                                                            >
                                                                <MoreVertical className="h-4 w-4 text-gray-600" />
                                                            </button>
                                                            
                                                            {/* Dropdown Menu */}
                                                            {openMenuId === user.id && (
                                                                <div className="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                                                {canAssignRole && (
                                                                    <button
                                                                        onClick={() => {
                                                                            setShowRoleModal(user.id);
                                                                            setSelectedRole('');
                                                                            setOpenMenuId(null);
                                                                        }}
                                                                        className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                                                                    >
                                                                        <Shield className="h-4 w-4" />
                                                                        Assigner rôle
                                                                    </button>
                                                                )}
                                                                
                                                                {canUpdateStatus && (
                                                                    <button
                                                                        onClick={() => {
                                                                            setShowStatusModal(user.id);
                                                                            setSelectedStatus(user.status || 'active');
                                                                            setOpenMenuId(null);
                                                                        }}
                                                                        className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                                                                    >
                                                                        <UserCog className="h-4 w-4" />
                                                                        Changer statut
                                                                    </button>
                                                                )}
                                                                
                                                                {canResetPassword && (
                                                                    <button
                                                                        onClick={() => {
                                                                            setShowPasswordModal(user.id);
                                                                            setNewPassword('');
                                                                            setPasswordConfirm('');
                                                                            setOpenMenuId(null);
                                                                        }}
                                                                        className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2"
                                                                    >
                                                                        <Key className="h-4 w-4" />
                                                                        Réinitialiser mot de passe
                                                                    </button>
                                                                )}
                                                                
                                                                {canImpersonate && (
                                                                    <button
                                                                        onClick={() => {
                                                                            setOpenMenuId(null);
                                                                            handleImpersonate(user.id);
                                                                        }}
                                                                        disabled={actionLoading === user.id}
                                                                        className="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 flex items-center gap-2 disabled:opacity-50"
                                                                    >
                                                                        <UserCheck className="h-4 w-4" />
                                                                        Impersonner
                                                                    </button>
                                                                )}
                                                                
                                                                {canDelete && (
                                                                    <>
                                                                        <div className="border-t border-gray-200 my-1" />
                                                                        <button
                                                                            onClick={() => {
                                                                                setShowDeleteModal(user.id);
                                                                                setOpenMenuId(null);
                                                                            }}
                                                                            className="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
                                                                        >
                                                                            <Trash2 className="h-4 w-4" />
                                                                            Supprimer
                                                                        </button>
                                                                    </>
                                                                )}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ))}

                {users.length === 0 && (
                    <div className="bg-white rounded-lg border border-gray-200 px-6 py-12 text-center">
                        <p className="text-gray-500">Aucun utilisateur trouvé</p>
                    </div>
                )}
            </div>
            
            {/* Modals */}
            {/* Role Assignment Modal */}
            {showRoleModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-bold mb-4">Assigner un rôle</h3>
                        <select
                            value={selectedRole}
                            onChange={(e) => setSelectedRole(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4"
                        >
                            <option value="">Sélectionner un rôle</option>
                            {roles.map((role) => (
                                <option key={role.id} value={role.id}>
                                    {role.name}
                                </option>
                            ))}
                        </select>
                        <div className="flex gap-3 justify-end">
                            <button
                                onClick={() => {
                                    setShowRoleModal(null);
                                    setSelectedRole('');
                                }}
                                className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleAssignRole(showRoleModal)}
                                disabled={!selectedRole || actionLoading === showRoleModal}
                                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                {actionLoading === showRoleModal ? 'Chargement...' : 'Assigner'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Status Update Modal */}
            {showStatusModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-bold mb-4">Changer le statut</h3>
                        <select
                            value={selectedStatus}
                            onChange={(e) => setSelectedStatus(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded-lg mb-4"
                        >
                            <option value="pending">En attente</option>
                            <option value="active">Actif</option>
                            <option value="blocked">Bloqué</option>
                            <option value="suspended">Suspendu</option>
                        </select>
                        <div className="flex gap-3 justify-end">
                            <button
                                onClick={() => {
                                    setShowStatusModal(null);
                                    setSelectedStatus('');
                                }}
                                className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleUpdateStatus(showStatusModal)}
                                disabled={!selectedStatus || actionLoading === showStatusModal}
                                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                {actionLoading === showStatusModal ? 'Chargement...' : 'Mettre à jour'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Password Reset Modal */}
            {showPasswordModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-bold mb-4">Réinitialiser le mot de passe</h3>
                        <div className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Nouveau mot de passe
                                </label>
                                <input
                                    type="password"
                                    value={newPassword}
                                    onChange={(e) => setNewPassword(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    placeholder="Minimum 8 caractères"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">
                                    Confirmer le mot de passe
                                </label>
                                <input
                                    type="password"
                                    value={passwordConfirm}
                                    onChange={(e) => setPasswordConfirm(e.target.value)}
                                    className="w-full px-3 py-2 border border-gray-300 rounded-lg"
                                    placeholder="Confirmer le mot de passe"
                                />
                            </div>
                        </div>
                        <div className="flex gap-3 justify-end mt-6">
                            <button
                                onClick={() => {
                                    setShowPasswordModal(null);
                                    setNewPassword('');
                                    setPasswordConfirm('');
                                }}
                                className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleResetPassword(showPasswordModal)}
                                disabled={!newPassword || actionLoading === showPasswordModal}
                                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50"
                            >
                                {actionLoading === showPasswordModal ? 'Chargement...' : 'Réinitialiser'}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Delete Confirmation Modal */}
            {showDeleteModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <div className="flex items-center gap-3 mb-4">
                            <AlertCircle className="h-6 w-6 text-red-600" />
                            <h3 className="text-lg font-bold">Supprimer l'utilisateur</h3>
                        </div>
                        <p className="text-gray-600 mb-6">
                            Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.
                        </p>
                        <div className="flex gap-3 justify-end">
                            <button
                                onClick={() => setShowDeleteModal(null)}
                                className="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleDelete(showDeleteModal)}
                                disabled={actionLoading === showDeleteModal}
                                className="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 disabled:opacity-50"
                            >
                                {actionLoading === showDeleteModal ? 'Suppression...' : 'Supprimer'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
            
            <FlashMessages />
        </div>
    );
}
