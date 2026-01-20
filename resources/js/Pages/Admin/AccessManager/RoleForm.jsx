import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { X } from 'lucide-react';

/**
 * Page: Formulaire de création/édition de rôle
 * 
 * Permet de :
 * - Créer un nouveau rôle
 * - Éditer un rôle existant
 * - Assigner des permissions au rôle
 */
export default function RoleForm() {
    const { role, allPermissions, isRootRole } = usePage().props;
    const isEditing = !!role;

    const { data, setData, post, put, processing, errors } = useForm({
        name: role?.name || '',
        description: role?.description || '',
        permissions: role?.permissions?.map(p => p.id) || [],
    });

    const [selectedGroup, setSelectedGroup] = useState(null);
    const [permissionSearch, setPermissionSearch] = useState('');

    // Récupérer les détails des permissions sélectionnées
    const selectedPermissionsDetails = useMemo(() => {
        const allPerms = Object.values(allPermissions || {}).flat();
        return allPerms.filter(p => data.permissions.includes(p.id));
    }, [data.permissions, allPermissions]);

    const handleSubmit = (e) => {
        e.preventDefault();
        
        // Vérification avant soumission
        if (!data.name.trim()) {
            alert('Le nom du rôle est obligatoire');
            return;
        }

        if (data.permissions.length === 0) {
            const confirmSubmit = confirm('Aucune permission n\'est sélectionnée. Voulez-vous vraiment créer un rôle sans permission ?');
            if (!confirmSubmit) {
                return;
            }
        }

        if (isEditing) {
            put(`/admin/access/roles/${role.id}`);
        } else {
            post('/admin/access/roles');
        }
    };

    const togglePermission = (permissionId) => {
        setData('permissions', (prev) => {
            if (prev.includes(permissionId)) {
                return prev.filter(id => id !== permissionId);
            }
            return [...prev, permissionId];
        });
    };

    const toggleGroup = (groupPermissions) => {
        const groupPermissionIds = groupPermissions.map(p => p.id);
        const allSelected = groupPermissionIds.every(id => data.permissions.includes(id));

        setData('permissions', (prev) => {
            if (allSelected) {
                // Désélectionner toutes les permissions du groupe
                return prev.filter(id => !groupPermissionIds.includes(id));
            } else {
                // Sélectionner toutes les permissions du groupe
                const newPermissions = [...prev];
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
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        {isEditing ? 'Éditer le rôle' : 'Créer un rôle'}
                    </h2>
                    <Link
                        href="/admin/access/roles"
                        className="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                    >
                        Retour
                    </Link>
                </div>
            }
        >
            <Head title={isEditing ? 'Éditer le rôle' : 'Créer un rôle'} />

            <div className="py-6">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Informations de base */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
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
                        <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Permissions
                                </h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    {data.permissions.length} permission{data.permissions.length > 1 ? 's' : ''} sélectionnée{data.permissions.length > 1 ? 's' : ''}
                                </p>
                            </div>

                            {/* Badges des permissions sélectionnées */}
                            {selectedPermissionsDetails.length > 0 && (
                                <div className="mb-4 p-4 bg-gray-50 dark:bg-gray-900/50 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <p className="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                                        Permissions sélectionnées :
                                    </p>
                                    <div className="flex flex-wrap gap-2">
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
                                {Object.entries(allPermissions)
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

                                        const groupPermissionIds = filteredGroupPermissions.map(p => p.id);
                                        const allSelected = groupPermissionIds.every(id => data.permissions.includes(id));
                                        const someSelected = groupPermissionIds.some(id => data.permissions.includes(id));

                                        return (
                                            <div
                                                key={group}
                                                className="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                            >
                                                <div className="bg-gray-50 dark:bg-gray-900 px-4 py-3 flex items-center justify-between">
                                                    <div className="flex items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            checked={allSelected}
                                                            ref={(input) => {
                                                                if (input) input.indeterminate = someSelected && !allSelected;
                                                            }}
                                                            onChange={() => toggleGroup(filteredGroupPermissions)}
                                                            className="w-4 h-4 text-amber-600 rounded focus:ring-amber-500"
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
                                                    {filteredGroupPermissions.map((permission) => (
                                                        <label
                                                            key={permission.id}
                                                            className="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                checked={data.permissions.includes(permission.id)}
                                                                onChange={() => togglePermission(permission.id)}
                                                                className="w-4 h-4 text-amber-600 rounded focus:ring-amber-500"
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
                                                    ))}
                                                </div>
                                            </div>
                                        );
                                    })
                                    .filter(Boolean)}
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex items-center justify-end gap-3">
                            <Link
                                href="/admin/access/roles"
                                className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                            >
                                Annuler
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors"
                            >
                                {processing ? 'Enregistrement...' : isEditing ? 'Mettre à jour' : 'Créer'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}

