import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
    Pill,
    UtensilsCrossed,
    Store,
    ShoppingBag,
    Settings,
    CheckCircle2,
    XCircle,
    ArrowLeft,
    ToggleLeft,
    ToggleRight,
} from 'lucide-react';

/**
 * Page: Configuration d'un Module
 * 
 * Permet de configurer les options d'un module spécifique
 */
export default function ModuleConfig({ moduleCode }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions ?? [];
    const isRoot = auth?.user?.type === 'ROOT';

    // Données du module (mock - sera remplacé par backend)
    const modulesData = {
        pharmacy: {
            id: 'pharmacy',
            code: 'pharmacy',
            name: 'Pharmacie',
            description: 'Gestion complète pour les pharmacies',
            icon: Pill,
            color: 'blue',
            enabled: false,
            features: [
                {
                    code: 'medicines',
                    label: 'Gestion des médicaments',
                    description: 'Gérer le catalogue de médicaments avec DCI, formes pharmaceutiques, dosages',
                    enabled: false,
                    permissions: ['pharmacy.medicines.view', 'pharmacy.medicines.create', 'pharmacy.medicines.update'],
                },
                {
                    code: 'batches',
                    label: 'Lots & dates d\'expiration',
                    description: 'Traçabilité des lots de médicaments avec dates d\'expiration',
                    enabled: false,
                    permissions: ['pharmacy.batches.view', 'pharmacy.batches.manage'],
                },
                {
                    code: 'prescriptions',
                    label: 'Ordonnances',
                    description: 'Gestion des prescriptions médicales et validation',
                    enabled: false,
                    permissions: ['pharmacy.prescriptions.view', 'pharmacy.prescriptions.create'],
                },
                {
                    code: 'suppliers',
                    label: 'Fournisseurs pharmaceutiques',
                    description: 'Gestion des fournisseurs spécialisés en produits pharmaceutiques',
                    enabled: false,
                    permissions: ['pharmacy.suppliers.view', 'pharmacy.suppliers.manage'],
                },
            ],
        },
        butchery: {
            id: 'butchery',
            code: 'butchery',
            name: 'Boucherie',
            description: 'Gestion spécialisée pour les boucheries',
            icon: UtensilsCrossed,
            color: 'red',
            enabled: false,
            features: [
                {
                    code: 'meat_products',
                    label: 'Produits de viande',
                    description: 'Gérer les produits de viande avec types, coupes, origines',
                    enabled: false,
                    permissions: ['butchery.meat_products.view', 'butchery.meat_products.manage'],
                },
                {
                    code: 'batches',
                    label: 'Lots & traçabilité',
                    description: 'Traçabilité des lots de viande avec dates d\'abattage et réception',
                    enabled: false,
                    permissions: ['butchery.batches.view', 'butchery.batches.manage'],
                },
                {
                    code: 'waste',
                    label: 'Gestion des déchets',
                    description: 'Enregistrer et suivre les pertes et déchets',
                    enabled: false,
                    permissions: ['butchery.waste.view', 'butchery.waste.manage'],
                },
                {
                    code: 'cutting',
                    label: 'Découpe & transformation',
                    description: 'Gérer les opérations de découpe et transformation',
                    enabled: false,
                    permissions: ['butchery.cutting.view', 'butchery.cutting.manage'],
                },
            ],
        },
        kiosk: {
            id: 'kiosk',
            code: 'kiosk',
            name: 'Kiosque',
            description: 'Solution simplifiée pour kiosques',
            icon: Store,
            color: 'green',
            enabled: false,
            features: [
                {
                    code: 'quick_sale',
                    label: 'Vente rapide',
                    description: 'Interface de vente optimisée pour transactions rapides',
                    enabled: false,
                    permissions: ['kiosk.quick_sale.view', 'kiosk.quick_sale.create'],
                },
                {
                    code: 'simple_stock',
                    label: 'Stock simplifié',
                    description: 'Gestion de stock basique sans traçabilité complexe',
                    enabled: false,
                    permissions: ['kiosk.stock.view', 'kiosk.stock.manage'],
                },
                {
                    code: 'unit_products',
                    label: 'Produits unitaires',
                    description: 'Gestion de produits vendus à l\'unité',
                    enabled: false,
                    permissions: ['kiosk.products.view', 'kiosk.products.manage'],
                },
            ],
        },
        supermarket: {
            id: 'supermarket',
            code: 'supermarket',
            name: 'Supermarché',
            description: 'Solution complète pour supermarchés',
            icon: ShoppingBag,
            color: 'amber',
            enabled: false,
            features: [
                {
                    code: 'multi_aisles',
                    label: 'Multi-rayons',
                    description: 'Organisation par rayons et catégories',
                    enabled: false,
                    permissions: ['supermarket.aisles.view', 'supermarket.aisles.manage'],
                },
                {
                    code: 'variants',
                    label: 'Variantes produits',
                    description: 'Gérer les variantes (taille, couleur, poids)',
                    enabled: false,
                    permissions: ['supermarket.variants.view', 'supermarket.variants.manage'],
                },
                {
                    code: 'promotions',
                    label: 'Promotions & réductions',
                    description: 'Gérer les promotions, codes promo, réductions',
                    enabled: false,
                    permissions: ['supermarket.promotions.view', 'supermarket.promotions.manage'],
                },
                {
                    code: 'loyalty',
                    label: 'Fidélité clients',
                    description: 'Programme de fidélité et cartes de fidélité',
                    enabled: false,
                    permissions: ['supermarket.loyalty.view', 'supermarket.loyalty.manage'],
                },
            ],
        },
    };

    const module = modulesData[moduleCode];
    
    if (!module) {
        return (
            <AppLayout>
                <Head title="Module introuvable" />
                <div className="py-6">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-6">
                            <p className="text-red-800 dark:text-red-300">
                                Module introuvable.
                            </p>
                            <Link
                                href="/admin/modules"
                                className="mt-4 inline-flex items-center gap-2 text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300"
                            >
                                <ArrowLeft className="h-4 w-4" />
                                Retour aux modules
                            </Link>
                        </div>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const IconComponent = module.icon;
    const [localFeatures, setLocalFeatures] = useState(module.features);
    const [moduleEnabled, setModuleEnabled] = useState(module.enabled);

    const getColorClasses = (color) => {
        const colors = {
            blue: {
                bg: 'bg-blue-100 dark:bg-blue-900/30',
                text: 'text-blue-800 dark:text-blue-300',
                border: 'border-blue-200 dark:border-blue-800',
            },
            red: {
                bg: 'bg-red-100 dark:bg-red-900/30',
                text: 'text-red-800 dark:text-red-300',
                border: 'border-red-200 dark:border-red-800',
            },
            green: {
                bg: 'bg-green-100 dark:bg-green-900/30',
                text: 'text-green-800 dark:text-green-300',
                border: 'border-green-200 dark:border-green-800',
            },
            amber: {
                bg: 'bg-amber-100 dark:bg-amber-900/30',
                text: 'text-amber-800 dark:text-amber-300',
                border: 'border-amber-200 dark:border-amber-800',
            },
        };
        return colors[color] || colors.blue;
    };

    const colors = getColorClasses(module.color);

    const toggleFeature = (featureCode) => {
        setLocalFeatures(prev =>
            prev.map(f =>
                f.code === featureCode ? { ...f, enabled: !f.enabled } : f
            )
        );
    };

    const handleSave = () => {
        // TODO: Envoyer les données au backend
        console.log('Saving module config:', {
            module: module.code,
            enabled: moduleEnabled,
            features: localFeatures,
        });
        // Pour l'instant, juste un message
        alert('Configuration sauvegardée (backend non implémenté)');
    };

    const enabledFeaturesCount = localFeatures.filter(f => f.enabled).length;
    const totalFeaturesCount = localFeatures.length;

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link
                            href="/admin/modules"
                            className="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
                        >
                            <ArrowLeft className="h-5 w-5" />
                        </Link>
                        <div className="flex items-center gap-3">
                            <div className={`p-2 rounded-lg ${colors.bg} ${colors.text}`}>
                                <IconComponent className="h-6 w-6" />
                            </div>
                            <div>
                                <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                                    Configuration - {module.name}
                                </h2>
                                <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                    {enabledFeaturesCount}/{totalFeaturesCount} fonctionnalités activées
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            }
        >
            <Head title={`Configuration - ${module.name}`} />

            <div className="py-6">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    {/* Description */}
                    <div className="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            {module.description}
                        </p>
                    </div>

                    {/* Activation du module */}
                    <div className="mb-6 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-1">
                                    Activer le module
                                </h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Activez ce module pour votre entreprise
                                </p>
                            </div>
                            <button
                                onClick={() => setModuleEnabled(!moduleEnabled)}
                                className={`relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                    moduleEnabled
                                        ? 'bg-amber-600'
                                        : 'bg-gray-200 dark:bg-gray-700'
                                }`}
                            >
                                <span
                                    className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                        moduleEnabled ? 'translate-x-6' : 'translate-x-1'
                                    }`}
                                />
                            </button>
                        </div>
                    </div>

                    {/* Liste des fonctionnalités */}
                    <div className="space-y-4">
                        <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                            Fonctionnalités
                        </h3>

                        {localFeatures.map((feature) => (
                            <div
                                key={feature.code}
                                className="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-6"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex-1">
                                        <div className="flex items-center gap-3 mb-2">
                                            <h4 className="text-base font-semibold text-gray-900 dark:text-white">
                                                {feature.label}
                                            </h4>
                                            {feature.enabled && (
                                                <span className="px-2 py-1 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded-full">
                                                    Activé
                                                </span>
                                            )}
                                        </div>
                                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                            {feature.description}
                                        </p>
                                        {feature.permissions && feature.permissions.length > 0 && (
                                            <div className="mt-3">
                                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                    Permissions associées :
                                                </p>
                                                <div className="flex flex-wrap gap-2">
                                                    {feature.permissions.map((perm) => (
                                                        <span
                                                            key={perm}
                                                            className="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded"
                                                        >
                                                            {perm}
                                                        </span>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                    <button
                                        onClick={() => toggleFeature(feature.code)}
                                        className={`flex-shrink-0 relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${
                                            feature.enabled
                                                ? 'bg-amber-600'
                                                : 'bg-gray-200 dark:bg-gray-700'
                                        }`}
                                    >
                                        <span
                                            className={`inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${
                                                feature.enabled ? 'translate-x-6' : 'translate-x-1'
                                            }`}
                                        />
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Actions */}
                    <div className="mt-6 flex items-center justify-end gap-3">
                        <Link
                            href="/admin/modules"
                            className="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors"
                        >
                            Annuler
                        </Link>
                        <button
                            onClick={handleSave}
                            className="px-4 py-2 text-sm font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition-colors"
                        >
                            Enregistrer
                        </button>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}








