import React, { useMemo, useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import SettingsDrawer from '@/Components/Settings/SettingsDrawer';
import { Link, usePage } from '@inertiajs/react';
import Modal from '@/Components/Modal';
import { 
  Settings, 
  Edit,
  Building2,
  MapPin,
  Phone,
  Mail,
  FileText,
  Crown,
  Info,
  AlertCircle
} from 'lucide-react';

export default function SettingsIndex({ auth, settings, permissions }) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [planDetailsOpen, setPlanDetailsOpen] = useState(false);
    const page = usePage();
    const billingSummary = page?.props?.auth?.billingSummary ?? null;
    const expiryLabel = useMemo(() => {
        if (!billingSummary?.expires_at) return '-';
        const d = new Date(billingSummary.expires_at);
        if (Number.isNaN(d.getTime())) return String(billingSummary.expires_at);
        return d.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long', day: 'numeric' });
    }, [billingSummary?.expires_at]);

    // Vérifier les permissions
    const canView = permissions?.view || false;
    const canUpdate = permissions?.update || false;

    // Si pas de permission view, rediriger
    if (!canView) {
        return (
            <AppLayout>
                <Head title="Accès refusé" />
                <div className="py-12">
                    <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                        <Card className="bg-white dark:bg-gray-800">
                            <CardContent className="p-6 text-center">
                                <h2 className="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                                    Accès refusé
                                </h2>
                                <p className="text-gray-600 dark:text-gray-400">
                                    Vous n'avez pas la permission de voir les paramètres.
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </AppLayout>
        );
    }

    const isComplete = settings?.is_complete || false;

    return (
        <AppLayout>
            <Head title="Paramètres" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                                Paramètres de la boutique
                            </h1>
                            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                                Gérez les informations de votre entreprise
                            </p>
                        </div>
                        {canUpdate && (
                            <Button
                                onClick={() => setDrawerOpen(true)}
                                className="bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 text-white w-full sm:w-auto"
                            >
                                <Edit className="h-4 w-4 mr-2" />
                                <span className="hidden sm:inline">Modifier</span>
                                <span className="sm:hidden">Modifier</span>
                            </Button>
                        )}
                    </div>

                    {/* Badge d'alerte si incomplet */}
                    {!isComplete && (
                        <div className="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg flex items-start gap-3">
                            <AlertCircle className="h-5 w-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
                            <div>
                                <h3 className="text-sm font-semibold text-amber-900 dark:text-amber-200 mb-1">
                                    Informations incomplètes
                                </h3>
                                <p className="text-sm text-amber-700 dark:text-amber-300">
                                    Veuillez compléter les informations de votre entreprise pour utiliser toutes les fonctionnalités.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* Contenu */}
                    {settings ? (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {/* Abonnement */}
                            <Card className="bg-white dark:bg-gray-800 lg:col-span-2">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-white">
                                        <Crown className="h-5 w-5" />
                                        Abonnement
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Plan actuel
                                        </label>
                                        <p className="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {billingSummary?.plan_name || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Date d’expiration
                                        </label>
                                        <p className="mt-1 text-sm font-semibold text-gray-900 dark:text-white">
                                            {expiryLabel}
                                        </p>
                                    </div>
                                    <div className="flex items-end justify-start sm:justify-end">
                                        <div className="w-full sm:w-auto flex flex-col sm:flex-row gap-2">
                                            <button
                                                type="button"
                                                onClick={() => setPlanDetailsOpen(true)}
                                                className="inline-flex h-11 items-center justify-center rounded-2xl border border-gray-300 dark:border-gray-600 text-gray-800 dark:text-gray-200 px-4 text-sm font-semibold w-full sm:w-auto hover:bg-gray-50 dark:hover:bg-gray-900/30"
                                            >
                                                <Info className="h-4 w-4 mr-2" />
                                                Détails
                                            </button>
                                            <Link
                                                href={route('billing.onboarding.payment')}
                                                className="inline-flex h-11 items-center justify-center rounded-2xl bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 text-white px-4 text-sm font-semibold w-full sm:w-auto"
                                            >
                                                Changer de plan
                                            </Link>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <Modal show={planDetailsOpen} onClose={() => setPlanDetailsOpen(false)} maxWidth="lg">
                                <div className="p-6">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <h3 className="text-lg font-extrabold text-gray-900 dark:text-white">
                                                Détails de l’abonnement
                                            </h3>
                                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                Résumé des limites et de l’état actuel.
                                            </p>
                                        </div>
                                        <button
                                            type="button"
                                            onClick={() => setPlanDetailsOpen(false)}
                                            className="rounded-xl px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700"
                                        >
                                            Fermer
                                        </button>
                                    </div>

                                    <div className="mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div className="rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
                                            <div className="text-xs font-semibold text-gray-500 dark:text-gray-400">Plan</div>
                                            <div className="mt-1 font-bold text-gray-900 dark:text-white">
                                                {billingSummary?.plan_name || '-'}
                                            </div>
                                        </div>
                                        <div className="rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
                                            <div className="text-xs font-semibold text-gray-500 dark:text-gray-400">Expiration</div>
                                            <div className="mt-1 font-bold text-gray-900 dark:text-white">
                                                {expiryLabel}
                                            </div>
                                        </div>
                                        <div className="rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
                                            <div className="text-xs font-semibold text-gray-500 dark:text-gray-400">Produits</div>
                                            <div className="mt-1 font-bold text-gray-900 dark:text-white">
                                                {billingSummary?.products_used ?? 0}/{billingSummary?.products_limit ?? 'illimité'}
                                            </div>
                                        </div>
                                        <div className="rounded-2xl border border-gray-200 dark:border-gray-700 p-4">
                                            <div className="text-xs font-semibold text-gray-500 dark:text-gray-400">Utilisateurs</div>
                                            <div className="mt-1 font-bold text-gray-900 dark:text-white">
                                                {billingSummary?.users_used ?? 0}/{billingSummary?.users_limit ?? 'illimité'}
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-6 flex flex-col sm:flex-row gap-2 sm:justify-end">
                                        <Link
                                            href={route('billing.onboarding.payment')}
                                            className="inline-flex h-11 items-center justify-center rounded-2xl bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 text-white px-4 text-sm font-semibold w-full sm:w-auto"
                                        >
                                            Changer de plan
                                        </Link>
                                    </div>
                                </div>
                            </Modal>

                            {/* Identité entreprise */}
                            <Card className="bg-white dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-white">
                                        <Building2 className="h-5 w-5" />
                                        Identité de l'entreprise
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Nom de l'entreprise
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.company_name || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            ID NAT
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.id_nat || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            RCCM
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.rccm || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Numéro fiscal
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.tax_number || '-'}
                                        </p>
                                    </div>
                                    {settings.logo_url && (
                                        <div>
                                            <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                                Logo
                                            </label>
                                            <div className="mt-2">
                                                <img 
                                                    src={settings.logo_url} 
                                                    alt="Logo" 
                                                    className="h-20 w-auto object-contain rounded"
                                                />
                                            </div>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Adresse */}
                            <Card className="bg-white dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-white">
                                        <MapPin className="h-5 w-5" />
                                        Adresse
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Rue
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.street || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Ville
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.city || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Code postal
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.postal_code || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Pays
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.country || '-'}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Coordonnées */}
                            <Card className="bg-white dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-white">
                                        <Phone className="h-5 w-5" />
                                        Coordonnées
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Téléphone
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.phone || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Email
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.email || '-'}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Facturation */}
                            <Card className="bg-white dark:bg-gray-800">
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-white">
                                        <FileText className="h-5 w-5" />
                                        Facturation
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Devise par défaut
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.currency || 'XAF'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Taux de change
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.exchange_rate ? settings.exchange_rate.toFixed(4) : '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Texte footer facture
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white whitespace-pre-line">
                                            {settings.invoice_footer_text || '-'}
                                        </p>
                                    </div>
                                    <div>
                                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                                            Impression automatique du reçu
                                        </label>
                                        <p className="mt-1 text-sm text-gray-900 dark:text-white">
                                            {settings.receipt_auto_print ? 'Activée' : 'Désactivée'}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    ) : (
                        <Card className="bg-white dark:bg-gray-800">
                            <CardContent className="p-6 text-center">
                                <Settings className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    Aucun paramètre configuré
                                </h3>
                                <p className="text-gray-600 dark:text-gray-400 mb-4">
                                    Commencez par configurer les paramètres de votre boutique.
                                </p>
                                {canUpdate && (
                                    <Button
                                        onClick={() => setDrawerOpen(true)}
                                        className="bg-amber-500 hover:bg-amber-600 dark:bg-amber-600 dark:hover:bg-amber-700 text-white"
                                    >
                                        <Settings className="h-4 w-4 mr-2" />
                                        Configurer
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    )}

                    {/* Drawer */}
                    {canUpdate && (
                        <SettingsDrawer
                            isOpen={drawerOpen}
                            onClose={() => setDrawerOpen(false)}
                            settings={settings}
                            canUpdate={canUpdate}
                        />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
