import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState, useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Plus, X, AlertCircle, RefreshCw } from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';

/**
 * Page: Gestion des Rôles
 * 
 * Permet de :
 * - Lister les rôles
 * - Rechercher un rôle
 * - Créer un rôle (via drawer)
 * - Éditer un rôle (via drawer)
 * - Supprimer un rôle (sauf ROOT)
 */
export default function Roles() {
    const { roles, search: initialSearch, allPermissions, flash } = usePage().props;
    const [search, setSearch] = useState(initialSearch || '');
    const [permissionSearch, setPermissionSearch] = useState('');
    const [showDeleteModal, setShowDeleteModal] = useState(null);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingRole, setEditingRole] = useState(null);
    const [isRootRole, setIsRootRole] = useState(false);
    const [loadingRole, setLoadingRole] = useState(false);

    // Afficher les flash messages comme toasts
    useEffect(() => {
        if (flash?.success) {
            toast.success(flash.success);
        }
        if (flash?.error) {
            toast.error(flash.error);
        }
    }, [flash]);

    const { data, setData, post, put, processing, errors, reset } = useForm({
        name: '',
        description: '',
        permissions: [],
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(route('admin.access.roles'), { search }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDelete = (roleId) => {
        router.delete(route('admin.access.roles.delete', roleId), {
            preserveScroll: true,
            onSuccess: () => setShowDeleteModal(null),
        });
    };

    const openCreateDrawer = () => {
        reset();
        setEditingRole(null);
        setIsRootRole(false);
        setDrawerOpen(true);
    };

    const openEditDrawer = async (roleId) => {
        setLoadingRole(true);
        setDrawerOpen(true);
        try {
            const response = await axios.get(route('admin.access.roles.get', roleId));
            const roleData = response.data.role;
            setEditingRole(roleData);
            setIsRootRole(response.data.isRootRole);
            
            // S'assurer que permissions est toujours un tableau
            let permissionsArray = [];
            if (Array.isArray(roleData.permissions)) {
                permissionsArray = roleData.permissions.map(p => p.id || p);
            } else if (roleData.permissions) {
                // Si c'est un objet, essayer de le convertir
                permissionsArray = Object.values(roleData.permissions).map(p => p.id || p);
            }
            
            setData({
                name: roleData.name || '',
                description: roleData.description || '',
                permissions: permissionsArray,
            });
        } catch (error) {
            console.error('Error loading role:', error);
            setDrawerOpen(false);
        } finally {
            setLoadingRole(false);
        }
    };

    const closeDrawer = () => {
        setDrawerOpen(false);
        setEditingRole(null);
        setIsRootRole(false);
        setPermissionSearch('');
        reset();
    };

    // Récupérer les détails des permissions sélectionnées
    const selectedPermissionsDetails = useMemo(() => {
        const allPerms = Object.values(allPermissions || {}).flat();
        const permissions = Array.isArray(data.permissions) 
            ? data.permissions.map(id => typeof id === 'number' ? id : parseInt(id, 10)).filter(id => !isNaN(id))
            : [];
        return allPerms.filter(p => {
            const permissionId = typeof p.id === 'number' ? p.id : parseInt(p.id, 10);
            return !isNaN(permissionId) && permissions.includes(permissionId);
        });
    }, [data.permissions, allPermissions]);

    const handleSubmit = (e) => {
        e.preventDefault();
        e.stopPropagation();
        
        // Vérification avant soumission
        if (!data.name.trim()) {
            toast.error('Le nom du rôle est obligatoire');
            return;
        }

        // S'assurer que permissions est un tableau d'IDs numériques
        let permissionsArray = Array.isArray(data.permissions) ? data.permissions : [];
        // Convertir tous les IDs en nombres pour s'assurer qu'ils sont bien formatés
        permissionsArray = permissionsArray.map(id => typeof id === 'number' ? id : parseInt(id, 10)).filter(id => !isNaN(id));
        
        if (permissionsArray.length === 0) {
            // Afficher un toast de confirmation personnalisé
            const proceedWithSubmit = () => {
                // S'assurer que les permissions sont bien formatées avant la soumission
                setData('permissions', permissionsArray);
                setData('name', data.name.trim());
                setData('description', data.description || '');

                if (editingRole) {
                    put(route('admin.access.roles.update', editingRole.id), {
                        preserveScroll: true,
                        onSuccess: () => {
                            closeDrawer();
                            toast.success('Rôle mis à jour avec succès');
                        },
                        onError: (errors) => {
                            console.error('Erreur lors de la mise à jour:', errors);
                            toast.error('Erreur lors de la mise à jour du rôle');
                        },
                    });
                } else {
                    post(route('admin.access.roles.store'), {
                        preserveScroll: true,
                        onSuccess: () => {
                            closeDrawer();
                            toast.success('Rôle créé avec succès');
                        },
                        onError: (errors) => {
                            console.error('Erreur lors de la création:', errors);
                            toast.error('Erreur lors de la création du rôle');
                        },
                    });
                }
            };

            toast.custom((t) => (
                <div className={`${t.visible ? 'animate-enter' : 'animate-leave'} max-w-md w-full bg-white dark:bg-gray-800 shadow-lg rounded-lg pointer-events-auto flex ring-1 ring-black ring-opacity-5`}>
                    <div className="flex-1 w-0 p-4">
                        <div className="flex items-start">
                            <div className="flex-shrink-0">
                                <AlertCircle className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div className="ml-3 flex-1">
                                <p className="text-sm font-medium text-gray-900 dark:text-white">
                                    Aucune permission sélectionnée
                                </p>
                                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Voulez-vous vraiment créer un rôle sans permission ?
                                </p>
                            </div>
                        </div>
                        <div className="mt-4 flex gap-2">
                            <button
                                onClick={() => {
                                    toast.dismiss(t.id);
                                    proceedWithSubmit();
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
                duration: Infinity,
            });
            return; // Arrêter ici, la soumission se fera via le bouton "Continuer" du toast
        }

        // S'assurer que les permissions sont bien formatées avant la soumission
        setData('permissions', permissionsArray);
        setData('name', data.name.trim());
        setData('description', data.description || '');

        if (editingRole) {
            put(route('admin.access.roles.update', editingRole.id), {
                preserveScroll: true,
                onSuccess: () => {
                    closeDrawer();
                    toast.success('Rôle mis à jour avec succès');
                },
                onError: (errors) => {
                    console.error('Erreur lors de la mise à jour:', errors);
                    console.error('Données envoyées:', { name: data.name, permissions: permissionsArray });
                    toast.error('Erreur lors de la mise à jour du rôle');
                },
            });
        } else {
            post(route('admin.access.roles.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    closeDrawer();
                    toast.success('Rôle créé avec succès');
                },
                onError: (errors) => {
                    console.error('Erreur lors de la création:', errors);
                    console.error('Données envoyées:', { name: data.name, permissions: permissionsArray });
                    toast.error('Erreur lors de la création du rôle');
                },
            });
        }
    };

    const togglePermission = (permissionId) => {
        console.log('togglePermission appelé avec:', permissionId, 'Type:', typeof permissionId);
        console.log('data.permissions avant:', data.permissions);
        
        // S'assurer que permissionId est un nombre
        const id = typeof permissionId === 'number' ? permissionId : parseInt(permissionId, 10);
        if (isNaN(id)) {
            console.error('ID de permission invalide:', permissionId);
            return;
        }
        
        // Récupérer les permissions actuelles
        const currentPermissions = Array.isArray(data.permissions) 
            ? data.permissions.map(pId => typeof pId === 'number' ? pId : parseInt(pId, 10)).filter(pId => !isNaN(pId))
            : [];
        
        console.log('Permissions actuelles normalisées:', currentPermissions);
        console.log('ID à toggle:', id, 'Est inclus?', currentPermissions.includes(id));
        
        // Créer le nouveau tableau
        const newPermissions = currentPermissions.includes(id)
            ? currentPermissions.filter(pId => pId !== id)
            : [...currentPermissions, id];
        
        console.log('Nouvelles permissions:', newPermissions);
        
        // Mettre à jour directement avec le nouveau tableau
        setData('permissions', newPermissions);
        
        // Vérifier après un court délai
        setTimeout(() => {
            console.log('data.permissions après setData:', data.permissions);
        }, 100);
    };

    const toggleGroup = (groupPermissions) => {
        const groupPermissionIds = groupPermissions.map(p => {
            const id = typeof p.id === 'number' ? p.id : parseInt(p.id, 10);
            return isNaN(id) ? null : id;
        }).filter(id => id !== null);
        const currentPermissions = Array.isArray(data.permissions) ? data.permissions.map(id => typeof id === 'number' ? id : parseInt(id, 10)) : [];
        const allSelected = groupPermissionIds.every(id => currentPermissions.includes(id));

        setData('permissions', (prev) => {
            const permissions = Array.isArray(prev) ? prev : [];
            if (allSelected) {
                return permissions.filter(id => !groupPermissionIds.includes(id));
            } else {
                const newPermissions = [...permissions];
                groupPermissionIds.forEach(id => {
                    if (!newPermissions.includes(id)) {
                        newPermissions.push(id);
                    }
                });
                return newPermissions;
            }
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <h2 className="text-xl sm:text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Gestion des Rôles
                    </h2>
                    <button
                        onClick={openCreateDrawer}
                        className="inline-flex items-center gap-2 px-3 sm:px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-medium rounded-lg transition-colors shrink-0"
                        aria-label="Créer un rôle"
                    >
                        <Plus className="w-5 h-5" />
                        <span className="hidden sm:inline">Créer un rôle</span>
                    </button>
                </div>
            }
        >
            <Head title="Gestion des Rôles" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Barre de recherche */}
                    <div className="mb-6">
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="flex-1">
                                <input
                                    type="text"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Rechercher un rôle..."
                                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                />
                            </div>
                            <button
                                type="submit"
                                className="px-6 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors font-medium"
                            >
                                Rechercher
                            </button>
                        </form>
                    </div>

                    {/* Liste des rôles */}
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead className="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Nom
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Description
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Utilisateurs
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Permissions
                                        </th>
                                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Statut
                                        </th>
                                        <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    {roles.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                                Aucun rôle trouvé
                                            </td>
                                        </tr>
                                    ) : (
                                        roles.map((role) => {
                                            const isRootRoleItem = role.name === 'ROOT' && role.tenant_id === null;
                                            return (
                                                <tr key={role.id} className="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <div className="flex items-center gap-2">
                                                            <span className="text-sm font-medium text-gray-900 dark:text-white">
                                                                {role.name}
                                                            </span>
                                                            {isRootRoleItem && (
                                                                <span className="px-2 py-1 text-xs font-semibold bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded">
                                                                    ROOT
                                                                </span>
                                                            )}
                                                        </div>
                                                    </td>
                                                    <td className="px-6 py-4">
                                                        <p className="text-sm text-gray-500 dark:text-gray-400 line-clamp-2">
                                                            {role.description || '—'}
                                                        </p>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className="text-sm text-gray-900 dark:text-white">
                                                            {role.users_count || 0}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className="text-sm text-gray-900 dark:text-white">
                                                            {role.permissions_count || 0}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap">
                                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                                            role.is_active
                                                                ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300'
                                                                : 'bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300'
                                                        }`}>
                                                            {role.is_active ? 'Actif' : 'Inactif'}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                        <div className="flex items-center justify-end gap-2">
                                                            <button
                                                                onClick={() => openEditDrawer(role.id)}
                                                                className="text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-300"
                                                            >
                                                                Éditer
                                                            </button>
                                                            {!isRootRoleItem && (
                                                                <button
                                                                    onClick={() => setShowDeleteModal(role.id)}
                                                                    className="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                                                                >
                                                                    Supprimer
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {/* Drawer pour création/édition */}
            <Drawer
                isOpen={drawerOpen}
                onClose={closeDrawer}
                title={editingRole ? 'Éditer le rôle' : 'Créer un rôle'}
                size="lg"
            >
                {loadingRole ? (
                    <div className="flex items-center justify-center py-12">
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-amber-600"></div>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit} className="space-y-6" noValidate>
                        {/* Informations de base */}
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Informations du rôle
                            </h3>

                            <div className="space-y-4">
                                {/* Nom */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Nom du rôle *
                                    </label>
                                    <input
                                        type="text"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        disabled={isRootRole}
                                        className={`w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent ${
                                            errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                                        } ${isRootRole ? 'opacity-50 cursor-not-allowed' : ''}`}
                                        required
                                    />
                                    {errors.name && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                                    )}
                                    {isRootRole && (
                                        <p className="mt-1 text-sm text-amber-600 dark:text-amber-400">
                                            Le nom du rôle ROOT ne peut pas être modifié
                                        </p>
                                    )}
                                </div>

                                {/* Description */}
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        rows={3}
                                        className={`w-full px-4 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent ${
                                            errors.description ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'
                                        }`}
                                    />
                                    {errors.description && (
                                        <p className="mt-1 text-sm text-red-600 dark:text-red-400">{errors.description}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Permissions */}
                        <div>
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Permissions
                                </h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {Array.isArray(data.permissions) ? data.permissions.length : 0} permission{(Array.isArray(data.permissions) ? data.permissions.length : 0) > 1 ? 's' : ''} sélectionnée{(Array.isArray(data.permissions) ? data.permissions.length : 0) > 1 ? 's' : ''}
                                </p>
                            </div>

                            {/* Badges des permissions sélectionnées */}
                            {selectedPermissionsDetails.length > 0 && (
                                <div className="mb-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        Permissions sélectionnées :
                                    </p>
                                    <div className="flex flex-wrap gap-2 max-h-32 overflow-y-auto">
                                        {selectedPermissionsDetails.map((permission) => (
                                            <span
                                                key={permission.id}
                                                className="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-100 dark:bg-amber-900/30 text-amber-800 dark:text-amber-300 rounded-lg text-sm font-medium border border-amber-200 dark:border-amber-800"
                                            >
                                                <span>{permission.code}</span>
                                                <button
                                                    type="button"
                                                    onClick={() => togglePermission(permission.id)}
                                                    className="hover:bg-amber-200 dark:hover:bg-amber-800 rounded-full p-0.5 transition-colors"
                                                    aria-label={`Retirer ${permission.code}`}
                                                >
                                                    <X className="w-3.5 h-3.5" />
                                                </button>
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            )}

                            {/* Champ de recherche des permissions */}
                            <div className="mb-4">
                                <input
                                    type="text"
                                    value={permissionSearch}
                                    onChange={(e) => setPermissionSearch(e.target.value)}
                                    placeholder="Rechercher une permission..."
                                    className="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-500 focus:border-transparent"
                                />
                            </div>

                            <div className="space-y-4 max-h-96 overflow-y-auto">
                                {Object.entries(allPermissions || {})
                                    .filter(([group, groupPermissions]) => {
                                        if (!permissionSearch.trim()) return true;
                                        const searchLower = permissionSearch.toLowerCase();
                                        return group.toLowerCase().includes(searchLower) ||
                                            groupPermissions.some(p => 
                                                p.code.toLowerCase().includes(searchLower) ||
                                                (p.description && p.description.toLowerCase().includes(searchLower))
                                            );
                                    })
                                    .map(([group, groupPermissions]) => {
                                        // Filtrer les permissions dans le groupe selon la recherche
                                        const filteredGroupPermissions = groupPermissions.filter(permission => {
                                            if (!permissionSearch.trim()) return true;
                                            const searchLower = permissionSearch.toLowerCase();
                                            return permission.code.toLowerCase().includes(searchLower) ||
                                                (permission.description && permission.description.toLowerCase().includes(searchLower));
                                        });

                                        // Ne pas afficher le groupe s'il n'y a plus de permissions après le filtrage
                                        if (filteredGroupPermissions.length === 0) return null;

                                        const currentPermissions = Array.isArray(data.permissions) ? data.permissions : [];
                                        const filteredGroupPermissionIds = filteredGroupPermissions.map(p => p.id);
                                        const allSelected = filteredGroupPermissionIds.every(id => currentPermissions.includes(id));
                                        const someSelected = filteredGroupPermissionIds.some(id => currentPermissions.includes(id));

                                        return (
                                            <div
                                                key={group}
                                                className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                            >
                                                <div 
                                                    className="bg-gray-50 dark:bg-gray-900 px-4 py-3 flex items-center justify-between cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800"
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        toggleGroup(filteredGroupPermissions);
                                                    }}
                                                >
                                                    <div className="flex items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            checked={allSelected}
                                                            ref={(input) => {
                                                                if (input) input.indeterminate = someSelected && !allSelected;
                                                            }}
                                                            onChange={(e) => {
                                                                e.stopPropagation();
                                                                toggleGroup(filteredGroupPermissions);
                                                            }}
                                                            className="w-4 h-4 text-amber-600 rounded focus:ring-amber-500 cursor-pointer"
                                                        />
                                                        <h4 className="font-medium text-gray-900 dark:text-white">
                                                            {group || 'Sans groupe'}
                                                        </h4>
                                                    </div>
                                                    <span className="text-sm text-gray-500 dark:text-gray-400">
                                                        {filteredGroupPermissions.length} permission{filteredGroupPermissions.length > 1 ? 's' : ''}
                                                    </span>
                                                </div>
                                                <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                                    {filteredGroupPermissions.map((permission) => {
                                                        const currentPermissions = Array.isArray(data.permissions) 
                                                            ? data.permissions.map(id => typeof id === 'number' ? id : parseInt(id, 10))
                                                            : [];
                                                        const permissionId = typeof permission.id === 'number' ? permission.id : parseInt(permission.id, 10);
                                                        const isChecked = !isNaN(permissionId) && currentPermissions.includes(permissionId);
                                                        return (
                                                            <label
                                                                key={permission.id}
                                                                className="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    togglePermission(permission.id);
                                                                }}
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    checked={isChecked}
                                                                    onChange={(e) => {
                                                                        e.stopPropagation();
                                                                        e.preventDefault();
                                                                        togglePermission(permission.id);
                                                                    }}
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                    }}
                                                                    className="w-4 h-4 text-amber-600 rounded focus:ring-amber-500 cursor-pointer"
                                                                />
                                                                <div className="flex-1">
                                                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                                        {permission.code}
                                                                    </p>
                                                                    {permission.description && (
                                                                        <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                                            {permission.description}
                                                                        </p>
                                                                    )}
                                                                </div>
                                                            </label>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        );
                                    })
                                    .filter(Boolean)}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button
                                type="button"
                                onClick={closeDrawer}
                                className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                            >
                                {processing ? 'Enregistrement...' : editingRole ? 'Mettre à jour' : 'Créer'}
                            </button>
                        </div>
                    </form>
                )}
            </Drawer>

            {/* Modal de confirmation de suppression */}
            {showDeleteModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Confirmer la suppression
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Êtes-vous sûr de vouloir supprimer ce rôle ? Cette action est irréversible.
                        </p>
                        <div className="flex gap-3 justify-end">
                            <button
                                onClick={() => setShowDeleteModal(null)}
                                className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                onClick={() => handleDelete(showDeleteModal)}
                                className="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors"
                            >
                                Supprimer
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
