import AppLayout from '@/Layouts/AppLayout';
import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { RefreshCw } from 'lucide-react';

/**
 * Page: Gestion des Permissions
 * 
 * Permet de :
 * - Lister les permissions (groupées)
 * - Rechercher une permission
 * - Supprimer une permission
 * - Générer/synchroniser depuis permissions.yaml
 */
export default function Permissions() {
    const { permissions, search: initialSearch } = usePage().props;
    const [search, setSearch] = useState(initialSearch || '');
    const [showDeleteModal, setShowDeleteModal] = useState(null);
    const [isSyncing, setIsSyncing] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get('/admin/access/permissions', { search }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleDelete = (permissionId) => {
        router.delete(`/admin/access/permissions/${permissionId}`, {
            preserveScroll: true,
            onSuccess: () => setShowDeleteModal(null),
        });
    };

    const handleSync = () => {
        setIsSyncing(true);
        router.post('/admin/access/permissions/sync', {}, {
            preserveScroll: true,
            onFinish: () => setIsSyncing(false),
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h2 className="text-xl sm:text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                            Gestion des Permissions
                        </h2>
                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {Object.values(permissions).flat().length} permission{Object.values(permissions).flat().length > 1 ? 's' : ''} au total
                        </p>
                    </div>
                    <button
                        onClick={handleSync}
                        disabled={isSyncing}
                        className="inline-flex items-center gap-2 px-3 sm:px-4 py-2 bg-amber-600 hover:bg-amber-700 disabled:bg-gray-400 disabled:cursor-not-allowed text-white font-medium rounded-lg transition-colors shrink-0"
                        aria-label="Générer depuis permissions.yaml"
                    >
                        {isSyncing ? (
                            <>
                                <RefreshCw className="h-5 w-5 animate-spin" />
                                <span className="hidden md:inline">Synchronisation...</span>
                            </>
                        ) : (
                            <>
                                <RefreshCw className="h-5 w-5" />
                                <span className="hidden lg:inline">Générer depuis permissions.yaml</span>
                            </>
                        )}
                    </button>
                </div>
            }
        >
            <Head title="Gestion des Permissions" />

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
                                    placeholder="Rechercher une permission..."
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

                    {/* Liste des permissions groupées */}
                    {Object.keys(permissions).length === 0 ? (
                        <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-8 text-center">
                            <p className="text-gray-500 dark:text-gray-400">
                                Aucune permission trouvée
                            </p>
                        </div>
                    ) : (
                        <div className="space-y-6">
                            {Object.entries(permissions).map(([group, groupPermissions]) => (
                                <div
                                    key={group}
                                    className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden"
                                >
                                    <div className="px-6 py-4 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                            {group || 'Sans groupe'}
                                        </h3>
                                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                            {groupPermissions.length} permission{groupPermissions.length > 1 ? 's' : ''}
                                        </p>
                                    </div>
                                    <div className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {groupPermissions.map((permission) => (
                                            <div
                                                key={permission.id}
                                                className="px-6 py-4 flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors"
                                            >
                                                <div className="flex-1">
                                                    <p className="text-sm font-medium text-gray-900 dark:text-white">
                                                        {permission.code}
                                                    </p>
                                                    {permission.description && (
                                                        <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                            {permission.description}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="flex items-center gap-4">
                                                    <span className="text-xs text-gray-500 dark:text-gray-400">
                                                        {permission.roles_count || 0} rôle{permission.roles_count !== 1 ? 's' : ''}
                                                    </span>
                                                    <button
                                                        onClick={() => setShowDeleteModal(permission.id)}
                                                        className="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 text-sm font-medium"
                                                    >
                                                        Supprimer
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>

            {/* Modal de confirmation de suppression */}
            {showDeleteModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div className="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-md w-full mx-4 p-6">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                            Confirmer la suppression
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-6">
                            Êtes-vous sûr de vouloir supprimer cette permission ? Cette action est irréversible.
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

