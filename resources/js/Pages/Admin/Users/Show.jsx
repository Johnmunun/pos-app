import React from 'react';
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { 
    ArrowLeft,
    User,
    Mail,
    Shield,
    Building2,
    MapPin,
    Phone,
    Calendar,
    CheckCircle,
    XCircle,
    Clock,
    AlertCircle,
    Briefcase,
    FileText,
    Globe,
    CreditCard
} from 'lucide-react';

export default function ShowUser({ user, tenant }) {
    const getStatusBadge = (status) => {
        const badges = {
            'active': { bg: 'bg-emerald-100 dark:bg-emerald-900/20', text: 'text-emerald-800 dark:text-emerald-300', label: 'Actif', icon: CheckCircle },
            'pending': { bg: 'bg-amber-100 dark:bg-amber-900/20', text: 'text-amber-800 dark:text-amber-300', label: 'En attente', icon: Clock },
            'blocked': { bg: 'bg-red-100 dark:bg-red-900/20', text: 'text-red-800 dark:text-red-300', label: 'Bloqué', icon: XCircle },
            'suspended': { bg: 'bg-orange-100 dark:bg-orange-900/20', text: 'text-orange-800 dark:text-orange-300', label: 'Suspendu', icon: AlertCircle },
        };
        
        const badge = badges[status] || badges['pending'];
        const Icon = badge.icon;
        
        return (
            <span className={`inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-medium ${badge.bg} ${badge.text}`}>
                <Icon className="h-4 w-4" />
                {badge.label}
            </span>
        );
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

    const getSectorLabel = (sector) => {
        const sectors = {
            'pharmacy': 'Pharmacie',
            'kiosk': 'Kiosque',
            'supermarket': 'Supermarché',
            'butchery': 'Boucherie',
            'hardware': 'Quincaillerie',
            'other': 'Autre',
        };
        return sectors[sector] || sector;
    };

    const getBusinessTypeLabel = (businessType) => {
        const types = {
            'individual': 'Individuel',
            'company': 'Entreprise',
            'association': 'Association',
            'ngo': 'ONG',
        };
        return types[businessType] || businessType;
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Date(dateString).toLocaleDateString('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <AppLayout>
            <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                {/* Header */}
                <div className="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-4">
                                <Link
                                    href={route('admin.users.view')}
                                    className="p-2 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition"
                                >
                                    <ArrowLeft className="h-5 w-5" />
                                </Link>
                                <div>
                                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
                                        Détails de l'utilisateur
                                    </h1>
                                    <p className="mt-2 text-gray-600 dark:text-gray-400">
                                        Informations complètes de l'utilisateur et données d'inscription
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Content */}
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Informations utilisateur */}
                        <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                            <div className="flex items-center gap-3 mb-6">
                                <div className="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                                    <User className="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                    Informations personnelles
                                </h2>
                            </div>

                            <div className="space-y-4">
                                <div className="flex items-start gap-3">
                                    <User className="h-5 w-5 text-gray-400 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Nom complet</p>
                                        <p className="text-base font-medium text-gray-900 dark:text-white">
                                            {user.first_name} {user.last_name}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <Mail className="h-5 w-5 text-gray-400 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                        <p className="text-base font-medium text-gray-900 dark:text-white">
                                            {user.email}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <Shield className="h-5 w-5 text-gray-400 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Type d'utilisateur</p>
                                        <p className="text-base font-medium text-gray-900 dark:text-white">
                                            {getRoleLabel(user.type)}
                                        </p>
                                    </div>
                                </div>

                                <div className="flex items-start gap-3">
                                    <Shield className="h-5 w-5 text-gray-400 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Statut</p>
                                        <div className="mt-1">
                                            {getStatusBadge(user.status || 'pending')}
                                        </div>
                                    </div>
                                </div>

                                {user.roles && user.roles.length > 0 && (
                                    <div className="flex items-start gap-3">
                                        <Shield className="h-5 w-5 text-gray-400 mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-sm text-gray-500 dark:text-gray-400 mb-2">Rôles assignés</p>
                                            <div className="flex flex-wrap gap-2">
                                                {user.roles.map((role) => (
                                                    <span 
                                                        key={role.id} 
                                                        className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                                    >
                                                        {role.name}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                <div className="flex items-start gap-3">
                                    <Calendar className="h-5 w-5 text-gray-400 mt-0.5" />
                                    <div className="flex-1">
                                        <p className="text-sm text-gray-500 dark:text-gray-400">Date d'inscription</p>
                                        <p className="text-base font-medium text-gray-900 dark:text-white">
                                            {formatDate(user.created_at)}
                                        </p>
                                    </div>
                                </div>

                                {user.last_login_at && (
                                    <div className="flex items-start gap-3">
                                        <Calendar className="h-5 w-5 text-gray-400 mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Dernière connexion</p>
                                            <p className="text-base font-medium text-gray-900 dark:text-white">
                                                {formatDate(user.last_login_at)}
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Informations tenant / Données d'onboarding */}
                        {tenant ? (
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="p-3 bg-amber-100 dark:bg-amber-900/20 rounded-lg">
                                        <Building2 className="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                        Informations de l'entreprise
                                    </h2>
                                </div>

                                <div className="space-y-4">
                                    <div className="flex items-start gap-3">
                                        <Building2 className="h-5 w-5 text-gray-400 mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Nom de l'entreprise</p>
                                            <p className="text-base font-medium text-gray-900 dark:text-white">
                                                {tenant.name}
                                            </p>
                                        </div>
                                    </div>

                                    {tenant.code && (
                                        <div className="flex items-start gap-3">
                                            <FileText className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Code</p>
                                                <p className="text-base font-medium text-gray-900 dark:text-white">
                                                    {tenant.code}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {tenant.sector && (
                                        <div className="flex items-start gap-3">
                                            <Briefcase className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Secteur d'activité</p>
                                                <p className="text-base font-medium text-gray-900 dark:text-white">
                                                    {getSectorLabel(tenant.sector)}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {tenant.business_type && (
                                        <div className="flex items-start gap-3">
                                            <Briefcase className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Type de commerce</p>
                                                <p className="text-base font-medium text-gray-900 dark:text-white">
                                                    {getBusinessTypeLabel(tenant.business_type)}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {tenant.email && (
                                        <div className="flex items-start gap-3">
                                            <Mail className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Email</p>
                                                <p className="text-base font-medium text-gray-900 dark:text-white">
                                                    {tenant.email}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {tenant.phone && (
                                        <div className="flex items-start gap-3">
                                            <Phone className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Téléphone</p>
                                                <p className="text-base font-medium text-gray-900 dark:text-white">
                                                    {tenant.phone}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {tenant.address && (
                                        <div className="flex items-start gap-3">
                                            <MapPin className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Adresse</p>
                                                <p className="text-base font-medium text-gray-900 dark:text-white">
                                                    {tenant.address}
                                                    {tenant.city && `, ${tenant.city}`}
                                                    {tenant.country && `, ${tenant.country}`}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    {/* Documents légaux */}
                                    {(tenant.idnat || tenant.rccm || tenant.tax_id || tenant.registration_number) && (
                                        <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                                            <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">
                                                Documents légaux
                                            </h3>
                                            <div className="space-y-3">
                                                {tenant.idnat && (
                                                    <div className="flex items-start gap-3">
                                                        <FileText className="h-5 w-5 text-gray-400 mt-0.5" />
                                                        <div className="flex-1">
                                                            <p className="text-sm text-gray-500 dark:text-gray-400">IDNAT</p>
                                                            <p className="text-base font-medium text-gray-900 dark:text-white">
                                                                {tenant.idnat}
                                                            </p>
                                                        </div>
                                                    </div>
                                                )}

                                                {tenant.rccm && (
                                                    <div className="flex items-start gap-3">
                                                        <FileText className="h-5 w-5 text-gray-400 mt-0.5" />
                                                        <div className="flex-1">
                                                            <p className="text-sm text-gray-500 dark:text-gray-400">RCCM</p>
                                                            <p className="text-base font-medium text-gray-900 dark:text-white">
                                                                {tenant.rccm}
                                                            </p>
                                                        </div>
                                                    </div>
                                                )}

                                                {tenant.tax_id && (
                                                    <div className="flex items-start gap-3">
                                                        <FileText className="h-5 w-5 text-gray-400 mt-0.5" />
                                                        <div className="flex-1">
                                                            <p className="text-sm text-gray-500 dark:text-gray-400">Numéro fiscal</p>
                                                            <p className="text-base font-medium text-gray-900 dark:text-white">
                                                                {tenant.tax_id}
                                                            </p>
                                                        </div>
                                                    </div>
                                                )}

                                                {tenant.registration_number && (
                                                    <div className="flex items-start gap-3">
                                                        <FileText className="h-5 w-5 text-gray-400 mt-0.5" />
                                                        <div className="flex-1">
                                                            <p className="text-sm text-gray-500 dark:text-gray-400">Numéro d'enregistrement</p>
                                                            <p className="text-base font-medium text-gray-900 dark:text-white">
                                                                {tenant.registration_number}
                                                            </p>
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {tenant.currency_code && (
                                        <div className="flex items-start gap-3">
                                            <CreditCard className="h-5 w-5 text-gray-400 mt-0.5" />
                                            <div className="flex-1">
                                                <p className="text-sm text-gray-500 dark:text-gray-400">Devise</p>
                                                <p className="text-base font-medium text-gray-900 dark:text-white">
                                                    {tenant.currency_code}
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                    <div className="flex items-start gap-3">
                                        <Calendar className="h-5 w-5 text-gray-400 mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-sm text-gray-500 dark:text-gray-400">Date de création</p>
                                            <p className="text-base font-medium text-gray-900 dark:text-white">
                                                {formatDate(tenant.created_at)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <div className="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6">
                                <div className="flex items-center gap-3 mb-6">
                                    <div className="p-3 bg-gray-100 dark:bg-gray-700 rounded-lg">
                                        <Building2 className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <h2 className="text-xl font-bold text-gray-900 dark:text-white">
                                        Informations de l'entreprise
                                    </h2>
                                </div>
                                <p className="text-gray-500 dark:text-gray-400">
                                    Aucun tenant associé à cet utilisateur
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
