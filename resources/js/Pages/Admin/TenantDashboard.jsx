/**
 * Page: TenantDashboard
 *
 * Dashboard d'administration d'un tenant spécifique
 * Accessible uniquement par le ROOT user
 */
import { Link, usePage, router } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import FlashMessages from '@/Components/FlashMessages';

export default function TenantDashboard() {
    const { tenant, stats, users, auth } = usePage().props;
    const permissions = auth?.permissions ?? [];
    const canUpdateUsers = permissions.includes('admin.users.update');

    const toggleUser = (userId, isActive) => {
        if (!canUpdateUsers) {
            return;
        }

        if (confirm(`Are you sure you want to ${isActive ? 'deactivate' : 'activate'} this user?`)) {
            router.post(route('admin.users.update', userId));
        }
    };

    return (
        <>
            <Head title={`Admin - ${tenant.name}`} />

            <div className="min-h-screen bg-white">
                {/* Header */}
                <div className="border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex items-center justify-between">
                        <div>
                            <Link href={route('admin.tenants.select.view')} className="text-amber-600 hover:text-amber-700 text-sm font-medium">
                                ← Retour
                            </Link>
                            <h1 className="text-3xl font-bold text-gray-900 mt-2">{tenant.name}</h1>
                            <p className="text-gray-600 text-sm mt-1">Slug: <code className="bg-gray-100 px-2 py-1 rounded">{tenant.slug}</code></p>
                        </div>
                        <div className="text-right">
                            <span className={`inline-block px-4 py-2 rounded-full text-sm font-semibold ${
                                tenant.is_active
                                    ? 'bg-emerald-100 text-emerald-700'
                                    : 'bg-red-100 text-red-700'
                            }`}>
                                {tenant.is_active ? '✓ Actif' : '✗ Inactif'}
                            </span>
                        </div>
                    </div>
                </div>

                {/* Statistics */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div className="grid md:grid-cols-3 gap-6 mb-12">
                        {/* Total Users */}
                        <div className="bg-gray-50 border border-gray-200 rounded-xl p-6">
                            <p className="text-gray-600 text-sm font-medium mb-2">Utilisateurs totaux</p>
                            <p className="text-4xl font-bold text-gray-900">{stats.users_count}</p>
                        </div>

                        {/* Active Users */}
                        <div className="bg-emerald-50 border border-emerald-200 rounded-xl p-6">
                            <p className="text-emerald-700 text-sm font-medium mb-2">Utilisateurs actifs</p>
                            <p className="text-4xl font-bold text-emerald-700">{stats.active_users}</p>
                        </div>

                        {/* Last Activity */}
                        <div className="bg-blue-50 border border-blue-200 rounded-xl p-6">
                            <p className="text-blue-700 text-sm font-medium mb-2">Dernière activité</p>
                            {stats.last_login ? (
                                <>
                                    <p className="text-lg font-bold text-blue-700">{stats.last_login.first_name} {stats.last_login.last_name}</p>
                                    <p className="text-sm text-blue-600 mt-1">{new Date(stats.last_login.last_login_at).toLocaleString('fr-FR')}</p>
                                </>
                            ) : (
                                <p className="text-blue-600">Aucune activité</p>
                            )}
                        </div>
                    </div>

                    {/* Users Table */}
                    <div className="bg-white border border-gray-200 rounded-xl overflow-hidden">
                        <div className="px-6 py-4 border-b border-gray-200">
                            <h2 className="text-lg font-bold text-gray-900">Utilisateurs ({users.length})</h2>
                        </div>

                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead className="bg-gray-50 border-b border-gray-200">
                                    <tr>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Nom</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Email</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Rôle</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Statut</th>
                                        <th className="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Inscription</th>
                                        <th className="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Actions</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {users.map((user) => (
                                        <tr key={user.id} className="hover:bg-gray-50">
                                            <td className="px-6 py-4 text-sm text-gray-900 font-medium">
                                                {user.first_name} {user.last_name}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {user.email}
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <span className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${
                                                    user.type === 'TENANT_ADMIN'
                                                        ? 'bg-amber-100 text-amber-700'
                                                        : 'bg-gray-100 text-gray-700'
                                                }`}>
                                                    {user.type}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm">
                                                <span className={`inline-block px-3 py-1 rounded-full text-xs font-semibold ${
                                                    user.is_active
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-red-100 text-red-700'
                                                }`}>
                                                    {user.is_active ? 'Actif' : 'Inactif'}
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 text-sm text-gray-600">
                                                {new Date(user.created_at).toLocaleDateString('fr-FR')}
                                            </td>
                                            <td className="px-6 py-4 text-sm text-center">
                                                {canUpdateUsers ? (
                                                    <button
                                                        onClick={() => toggleUser(user.id, user.is_active)}
                                                        className={`px-3 py-1 rounded text-xs font-medium transition-colors ${
                                                            user.is_active
                                                                ? 'bg-red-100 text-red-600 hover:bg-red-200'
                                                                : 'bg-emerald-100 text-emerald-600 hover:bg-emerald-200'
                                                        }`}
                                                    >
                                                        {user.is_active ? 'Désactiver' : 'Activer'}
                                                    </button>
                                                ) : (
                                                    <span className="text-xs text-gray-500 italic">Non autorisé</span>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {users.length === 0 && (
                            <div className="px-6 py-12 text-center">
                                <p className="text-gray-600">Aucun utilisateur</p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
            <FlashMessages />
        </>
    );
}
