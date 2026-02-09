import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import FlashMessages from '@/Components/FlashMessages';
import { RefreshCw } from 'lucide-react';

export default function ManageTenants({ tenants }) {
    const [isToggling, setIsToggling] = useState(null);
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];
    const canUpdateTenants = permissions.includes('admin.tenants.update');

    const handleToggleTenant = (tenantId) => {
        if (!canUpdateTenants) {
            return;
        }

        setIsToggling(tenantId);
        router.post(route('admin.tenants.update', tenantId), {}, {
            onFinish: () => setIsToggling(null),
        });
    };

    return (
        <div className="min-h-screen bg-white">
            {/* Header */}
            <div className="bg-white border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">
                                Gestion des Tenants
                            </h1>
                            <p className="mt-2 text-gray-600">
                                Gérez tous les tenants de la plateforme
                            </p>
                        </div>
                        <div className="flex gap-3">
                            <button
                                onClick={() => router.reload({ only: ['tenants'] })}
                                className="inline-flex items-center gap-2 px-3 sm:px-4 py-2 rounded-lg text-gray-700 bg-gray-100 hover:bg-gray-200 transition"
                                title="Actualiser"
                            >
                                <RefreshCw className="h-4 w-4" />
                                <span className="hidden sm:inline">Actualiser</span>
                            </button>
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
                        <div className="text-sm font-medium text-gray-600">Tenants Total</div>
                        <div className="mt-2 text-3xl font-bold text-gray-900">
                            {tenants.length}
                        </div>
                    </div>
                    <div className="bg-emerald-50 rounded-lg p-6 border border-emerald-200">
                        <div className="text-sm font-medium text-emerald-700">Actifs</div>
                        <div className="mt-2 text-3xl font-bold text-emerald-900">
                            {tenants.filter(t => t.is_active).length}
                        </div>
                    </div>
                    <div className="bg-red-50 rounded-lg p-6 border border-red-200">
                        <div className="text-sm font-medium text-red-700">Inactifs</div>
                        <div className="mt-2 text-3xl font-bold text-red-900">
                            {tenants.filter(t => !t.is_active).length}
                        </div>
                    </div>
                </div>

                {/* Tenants Table */}
                <div className="bg-white rounded-lg border border-gray-200 overflow-hidden">
                    <div className="px-4 sm:px-6 py-3 border-b border-gray-200 flex items-center justify-between">
                        <h3 className="text-sm font-medium text-gray-700">
                            Liste des tenants
                        </h3>
                        <button
                            onClick={() => router.reload({ only: ['tenants'] })}
                            className="inline-flex items-center gap-2 px-3 py-1.5 text-xs sm:text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition"
                            title="Actualiser la liste"
                        >
                            <RefreshCw className="h-3.5 w-3.5 sm:h-4 sm:w-4" />
                            <span className="hidden sm:inline">Actualiser</span>
                        </button>
                    </div>
                    <table className="w-full">
                        <thead>
                            <tr className="bg-gray-50 border-b border-gray-200">
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Nom
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Slug
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Utilisateurs
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Statut
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Créé
                                </th>
                                <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-200">
                            {tenants.map((tenant) => (
                                <tr key={tenant.id} className="hover:bg-gray-50 transition">
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm font-medium text-gray-900">
                                            {tenant.name}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <code className="text-sm text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                            {tenant.slug}
                                        </code>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm text-gray-900">
                                            {tenant.users_count || 0}
                                        </div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {tenant.is_active ? (
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
                                        {new Date(tenant.created_at).toLocaleDateString('fr-FR')}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {canUpdateTenants ? (
                                            <button
                                                onClick={() => handleToggleTenant(tenant.id)}
                                                disabled={isToggling === tenant.id}
                                                className={`inline-flex items-center px-3 py-1 rounded-lg text-xs font-medium transition ${
                                                    tenant.is_active
                                                        ? 'bg-red-100 text-red-700 hover:bg-red-200 disabled:opacity-50'
                                                        : 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 disabled:opacity-50'
                                                }`}
                                            >
                                                {isToggling === tenant.id ? (
                                                    <>
                                                        <svg className="animate-spin -ml-1 mr-2 h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                                        </svg>
                                                        Chargement...
                                                    </>
                                                ) : (
                                                    tenant.is_active ? 'Désactiver' : 'Activer'
                                                )}
                                            </button>
                                        ) : (
                                            <span className="text-xs text-gray-500 italic">Non autorisé</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {tenants.length === 0 && (
                        <div className="px-6 py-12 text-center">
                            <p className="text-gray-500">Aucun tenant trouvé</p>
                        </div>
                    )}
                </div>
            </div>
            <FlashMessages />
        </div>
    );
}
