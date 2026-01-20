import AppLayout from '@/Layouts/AppLayout';
import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import {
    DollarSign,
    ShoppingCart,
    Users,
    AlertTriangle,
    User,
    ClipboardList,
    AlertCircle,
    Plus,
    Package,
    BarChart,
} from 'lucide-react';

/**
 * Page: Dashboard
 * 
 * Dashboard principal avec widgets conditionnels
 * Version 1 : Frontend uniquement (placeholders)
 */
export default function Dashboard() {
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];

    // Widgets généraux (toujours visibles)
    const generalWidgets = [
        {
            title: 'Chiffre d\'affaires',
            value: '0 FCFA',
            icon: DollarSign,
            trend: '+0%',
            color: 'emerald',
        },
        {
            title: 'Nombre de ventes',
            value: '0',
            icon: ShoppingCart,
            trend: '+0',
            color: 'blue',
        },
        {
            title: 'Nombre de clients',
            value: '0',
            icon: Users,
            trend: '+0',
            color: 'purple',
        },
        {
            title: 'Produits en stock bas',
            value: '0',
            icon: AlertTriangle,
            trend: 'Alerte',
            color: 'amber',
        },
    ];

    // Widgets conditionnels (basés sur permissions)
    const conditionalWidgets = [];

    if (permissions.includes('sellers.view') || permissions.length === 0) {
        conditionalWidgets.push({
            title: 'Performance sellers',
            value: 'N/A',
            icon: User,
            description: 'Statistiques des vendeurs',
            permission: 'sellers.view',
        });
    }

    if (permissions.includes('activity.view') || permissions.length === 0) {
        conditionalWidgets.push({
            title: 'Activité récente',
            value: 'Aucune',
            icon: ClipboardList,
            description: 'Dernières actions',
            permission: 'activity.view',
        });
    }

    if (permissions.includes('support.admin') || permissions.length === 0) {
        conditionalWidgets.push({
            title: 'Alertes système',
            value: '0',
            icon: AlertCircle,
            description: 'Incidents en cours',
            permission: 'support.admin',
        });
    }

    // Actions rapides
    const quickActions = [
        { label: 'Nouvelle vente', href: '#', icon: Plus, permission: 'sales.create', color: 'amber' },
        { label: 'Ajouter produit', href: '#', icon: Package, permission: 'products.create', color: 'blue' },
        { label: 'Créer client', href: '#', icon: Users, permission: 'customers.create', color: 'purple' },
    ].filter(action => 
        permissions.includes(action.permission) || permissions.length === 0
    );

    return (
        <AppLayout
            header={
                <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Widgets généraux */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">
                        {generalWidgets.map((widget, index) => (
                            <div
                                key={index}
                                className="bg-white dark:bg-gray-800 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-shadow"
                            >
                                <div className="p-6">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                                {widget.title}
                                            </p>
                                            <p className="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                                                {widget.value}
                                            </p>
                                            <p className={`mt-1 text-sm font-medium ${
                                                widget.color === 'emerald' ? 'text-emerald-600 dark:text-emerald-400' :
                                                widget.color === 'blue' ? 'text-blue-600 dark:text-blue-400' :
                                                widget.color === 'purple' ? 'text-purple-600 dark:text-purple-400' :
                                                'text-amber-600 dark:text-amber-400'
                                            }`}>
                                                {widget.trend}
                                            </p>
                                        </div>
                                        <div className={`p-3 rounded-lg ${
                                            widget.color === 'emerald' ? 'bg-emerald-100 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400' :
                                            widget.color === 'blue' ? 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' :
                                            widget.color === 'purple' ? 'bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400' :
                                            'bg-amber-100 dark:bg-amber-900/20 text-amber-600 dark:text-amber-400'
                                        }`}>
                                            {widget.icon && <widget.icon className="h-8 w-8" />}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Actions rapides */}
                    {quickActions.length > 0 && (
                        <div className="mb-8">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Actions rapides
                            </h3>
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {quickActions.map((action, index) => (
                                    <a
                                        key={index}
                                        href={action.href}
                                        className={`flex items-center gap-4 p-4 rounded-xl border-2 border-transparent hover:border-${action.color}-500 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all`}
                                    >
                                        <div className={`p-3 rounded-lg bg-${action.color}-100 dark:bg-${action.color}-900/20 text-${action.color}-600 dark:text-${action.color}-400`}>
                                            {action.icon && <action.icon className="h-6 w-6" />}
                                        </div>
                                        <span className="font-medium text-gray-900 dark:text-white">
                                            {action.label}
                                        </span>
                                    </a>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Widgets conditionnels */}
                    {conditionalWidgets.length > 0 && (
                        <div className="mb-8">
                            <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                Informations supplémentaires
                            </h3>
                            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                {conditionalWidgets.map((widget, index) => (
                                    <div
                                        key={index}
                                        className="bg-white dark:bg-gray-800 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6"
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="p-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400">
                                                {widget.icon && <widget.icon className="h-8 w-8" />}
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                                                    {widget.title}
                                                </p>
                                                <p className="mt-1 text-2xl font-bold text-gray-900 dark:text-white">
                                                    {widget.value}
                                                </p>
                                                {widget.description && (
                                                    <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                        {widget.description}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Section vide pour développement futur */}
                    <div className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-8 text-center">
                        <div className="flex items-center justify-center gap-2 text-gray-500 dark:text-gray-400">
                            <BarChart className="h-5 w-5" />
                            <p>Les données réelles seront connectées dans les prochaines phases</p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
