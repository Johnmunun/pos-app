/**
 * Page: SelectTenant
 *
 * Page de sélection du tenant après connexion pour le ROOT user
 * Le ROOT user peut accéder à n'importe quel tenant
 */
import { Link, usePage } from '@inertiajs/react';
import { Head } from '@inertiajs/react';
import FlashMessages from '@/Components/FlashMessages';

export default function SelectTenant() {
    const { tenants = [], auth } = usePage().props;
    const user = auth?.user;
    const permissions = auth?.permissions ?? [];
    const canViewTenants = permissions.includes('admin.tenants.view');
    const canViewUsers = permissions.includes('admin.users.view');

    return (
        <>
            <Head title="Sélectionner un tenant" />

            <div className="min-h-screen bg-white">
                {/* Header */}
                <div className="border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <Link href="/" className="flex items-center space-x-2 hover:opacity-80 transition-opacity">
                            <div className="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center">
                                <span className="text-white font-bold text-sm">POS</span>
                            </div>
                            <span className="font-bold text-gray-900">POS SaaS Admin</span>
                        </Link>
                    </div>
                </div>

                {/* Main Content */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div className="max-w-2xl mx-auto">
                        {/* Header */}
                        <div className="mb-12">
                            <h1 className="text-4xl font-bold text-gray-900 mb-4">
                                Bienvenue, {user?.first_name ?? 'Admin'}
                            </h1>
                            <p className="text-lg text-gray-600">
                                Sélectionnez un tenant à administrer
                            </p>
                        </div>

                        {/* Tenants Grid */}
                        <div className="space-y-4">
                            {tenants && tenants.length > 0 ? (
                                tenants.map((tenant) => (
                                    <Link
                                        key={tenant.id}
                                        href={route('admin.tenants.dashboard.view', tenant.id)}
                                        className="block p-6 bg-gray-50 hover:bg-white border border-gray-200 hover:border-amber-500 rounded-xl transition-all duration-200 hover:shadow-lg"
                                    >
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <h2 className="text-xl font-bold text-gray-900 mb-2">
                                                    {tenant.name}
                                                </h2>
                                                <div className="space-y-1 text-sm text-gray-600">
                                                    <p>
                                                        <strong>Slug:</strong> {tenant.slug}
                                                    </p>
                                                    <p>
                                                        <strong>Utilisateurs:</strong> {tenant.users_count || 0}
                                                    </p>
                                                    <p>
                                                        Créé: {new Date(tenant.created_at).toLocaleDateString('fr-FR')}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="text-right">
                                                <span className={`inline-block px-3 py-1 rounded-full text-sm font-semibold ${
                                                    tenant.is_active
                                                        ? 'bg-emerald-100 text-emerald-700'
                                                        : 'bg-red-100 text-red-700'
                                                }`}>
                                                    {tenant.is_active ? '✓ Actif' : '✗ Inactif'}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                ))
                            ) : (
                                <div className="p-8 bg-gray-50 border border-gray-200 rounded-xl text-center">
                                    <p className="text-gray-600 mb-4">Aucun tenant disponible</p>
                                    <Link
                                        href="/"
                                        className="text-amber-600 hover:text-amber-700 font-medium"
                                    >
                                        Retour à l'accueil
                                    </Link>
                                </div>
                            )}
                        </div>

                        {/* Admin Panel Links */}
                        <div className="mt-12 p-6 bg-blue-50 border border-blue-200 rounded-xl">
                            <h3 className="font-bold text-gray-900 mb-4">Panel d'administration</h3>
                            <div className="grid md:grid-cols-2 gap-4">
                                {canViewTenants ? (
                                    <Link
                                        href={route('admin.tenants.view')}
                                        className="p-3 bg-white border border-blue-200 hover:border-blue-500 rounded-lg text-center text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
                                    >
                                        Gérer les tenants
                                    </Link>
                                ) : (
                                    <div className="p-3 bg-gray-50 border border-gray-200 rounded-lg text-center text-sm text-gray-500">
                                        Tenants (non autorisé)
                                    </div>
                                )}
                                {canViewUsers ? (
                                    <Link
                                        href={route('admin.users.view')}
                                        className="p-3 bg-white border border-blue-200 hover:border-blue-500 rounded-lg text-center text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors"
                                    >
                                        Gérer les utilisateurs
                                    </Link>
                                ) : (
                                    <div className="p-3 bg-gray-50 border border-gray-200 rounded-lg text-center text-sm text-gray-500">
                                        Utilisateurs (non autorisé)
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Footer */}
                <div className="border-t border-gray-200 mt-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-600">
                        <form method="POST" action={route('logout')} className="inline">
                            <button
                                type="submit"
                                className="text-red-600 hover:text-red-700 font-medium transition-colors"
                            >
                                Déconnexion
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <FlashMessages />
        </>
    );
}
