import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import FlashMessages from '@/Components/FlashMessages';

export default function ManageUsers({ users }) {
    const [isToggling, setIsToggling] = useState(null);
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];
    const canUpdateUsers = permissions.includes('admin.users.update');

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
                                                {user.is_active ? (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                                        Actif
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                        Inactif
                                                    </span>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                {new Date(user.created_at).toLocaleDateString('fr-FR')}
                                            </td>
                                            <td className="px-6 py-4 whitespace-nowrap">
                                                {user.type !== 'ROOT' && canUpdateUsers ? (
                                                    <button
                                                        onClick={() => handleToggleUser(user.id)}
                                                        disabled={isToggling === user.id}
                                                        className={`inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium transition ${
                                                            user.is_active
                                                                ? 'bg-red-100 text-red-700 hover:bg-red-200 disabled:opacity-50'
                                                                : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 disabled:opacity-50'
                                                        }`}
                                                    >
                                                        {isToggling === user.id ? (
                                                            <>
                                                                <svg className="animate-spin -ml-1 mr-2 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                                </svg>
                                                                Chargement...
                                                            </>
                                                        ) : (
                                                            user.is_active ? 'Désactiver' : 'Activer'
                                                        )}
                                                    </button>
                                                ) : (
                                                    <span className="text-xs text-gray-500 italic">
                                                        {user.type === 'ROOT' ? 'Administrateur' : 'Non autorisé'}
                                                    </span>
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
            <FlashMessages />
        </div>
    );
}
