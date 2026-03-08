import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Plus, Truck, Pencil, Power, PowerOff, Mail, Phone, CheckCircle, XCircle, Search } from 'lucide-react';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import ImportModal from '@/Components/ImportModal';
import SupplierDrawer from '@/Components/Commerce/SupplierDrawer';
import { Input } from '@/Components/ui/input';

export default function CommerceSuppliersIndex({ suppliers = [] }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];
    
    const hasPermission = (perm) => {
        if (permissions.includes('*')) return true;
        return perm.split('|').some(p => permissions.includes(p));
    };

    const canCreate = hasPermission('commerce.supplier.create');
    const canEdit = hasPermission('commerce.supplier.edit');

    // Drawer state
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedSupplier, setSelectedSupplier] = useState(null);

    const handleOpenCreate = () => {
        setSelectedSupplier(null);
        setDrawerOpen(true);
    };

    const handleOpenEdit = (supplier) => {
        setSelectedSupplier(supplier);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setSelectedSupplier(null);
    };

    const handleDrawerSuccess = () => {
        router.reload({ only: ['suppliers'] });
    };

    const handleToggleActive = (id, isActive) => {
        if (!confirm(isActive ? 'Désactiver ce fournisseur ?' : 'Réactiver ce fournisseur ?')) return;
        router.post(route('commerce.suppliers.toggle-active', id), {}, {
            preserveScroll: true,
            onSuccess: () => toast.success('Statut mis à jour'),
            onError: () => toast.error('Erreur'),
        });
    };
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);

    const handleOpenImport = () => {
        setImportOpen(true);
        setImportFile(null);
    };

    const handleConfirmImport = async () => {
        if (!importFile) {
            toast.error('Veuillez sélectionner un fichier.');
            return;
        }
        setImporting(true);
        try {
            const formData = new FormData();
            formData.append('file', importFile);
            const { data } = await axios.post(route('commerce.suppliers.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success(data.message || 'Import fournisseurs terminé.');
            if (data.errors && data.errors.length) {
                console.warn('Erreurs import fournisseurs:', data.errors);
            }
            setImportOpen(false);
            setImportFile(null);
            router.reload({ only: ['suppliers'] });
        } catch (err) {
            const message =
                err.response?.data?.message ||
                "Erreur lors de l'import des fournisseurs.";
            toast.error(message);
        } finally {
            setImporting(false);
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Fournisseurs — Global Commerce
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="w-full sm:w-auto inline-flex items-center"
                            onClick={handleOpenImport}
                            disabled={importing}
                        >
                            <span>Importer</span>
                        </Button>
                        <Button
                            asChild
                            className="w-full sm:w-auto inline-flex items-center bg-rose-500 hover:bg-rose-600 text-white"
                        >
                            <a
                                href={route('commerce.exports.suppliers.pdf')}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>Exporter PDF</span>
                            </a>
                        </Button>
                        <Button
                            asChild
                            className="w-full sm:w-auto inline-flex items-center bg-emerald-500 hover:bg-emerald-600 text-white"
                        >
                            <a
                                href={route('commerce.exports.suppliers.excel')}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>Exporter Excel</span>
                            </a>
                        </Button>
                        {canCreate && (
                            <Button 
                                onClick={handleOpenCreate}
                                className="w-full sm:w-auto inline-flex items-center"
                            >
                                <span className="flex items-center gap-2">
                                    <Plus className="h-4 w-4" />
                                    <span>Nouveau fournisseur</span>
                                </span>
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Fournisseurs - Commerce" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Truck className="h-5 w-5 mr-2" /> Liste des fournisseurs
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {suppliers.length === 0 ? (
                                <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <Truck className="h-12 w-12 mx-auto mb-3 opacity-50" />
                                    <p>Aucun fournisseur. Créez-en un pour passer des bons de commande.</p>
                                    {canCreate && (
                                        <Button onClick={handleOpenCreate} className="mt-4">
                                            Nouveau fournisseur
                                        </Button>
                                    )}
                                </div>
                            ) : (
                                <>
                                    {/* Vue Mobile - Cartes */}
                                    <div className="md:hidden space-y-3">
                                        {suppliers.map((s) => (
                                            <div 
                                                key={s.id} 
                                                className="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 transition-colors"
                                            >
                                                <div className="flex items-start gap-3 p-4">
                                                    {/* Avatar circulaire */}
                                                    <div className="flex-shrink-0">
                                                        <div className="h-16 w-16 rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center border-2 border-gray-200 dark:border-gray-600">
                                                            <Truck className="h-8 w-8 text-white" />
                                                        </div>
                                                    </div>
                                                    
                                                    {/* Contenu principal */}
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-start justify-between gap-2 mb-2">
                                                            <h3 className="font-semibold text-gray-900 dark:text-white text-sm truncate">
                                                                {s.name}
                                                            </h3>
                                                            <div className="flex items-center gap-1 flex-shrink-0">
                                                                {canEdit && (
                                                                    <button
                                                                        onClick={() => handleOpenEdit(s)}
                                                                        className="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                                                        title="Modifier"
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </button>
                                                                )}
                                                                <button
                                                                    onClick={() => handleToggleActive(s.id, s.is_active)}
                                                                    className="p-1.5 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors"
                                                                    title={s.is_active ? 'Désactiver' : 'Activer'}
                                                                >
                                                                    {s.is_active ? <PowerOff className="h-4 w-4" /> : <Power className="h-4 w-4" />}
                                                                </button>
                                                            </div>
                                                        </div>
                                                        
                                                        {/* Informations de contact */}
                                                        <div className="space-y-1.5 mb-2">
                                                            {s.email && (
                                                                <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                                    <Mail className="h-3.5 w-3.5 flex-shrink-0" />
                                                                    <span className="truncate">{s.email}</span>
                                                                </div>
                                                            )}
                                                            {s.phone && (
                                                                <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                                    <Phone className="h-3.5 w-3.5 flex-shrink-0" />
                                                                    <span>{s.phone}</span>
                                                                </div>
                                                            )}
                                                        </div>
                                                        
                                                        {/* Badge statut */}
                                                        <div className="flex items-center gap-2">
                                                            {s.is_active ? (
                                                                <Badge className="text-[10px] px-2 py-0.5 bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 border-0 flex items-center gap-1">
                                                                    <CheckCircle className="h-3 w-3" />
                                                                    Actif
                                                                </Badge>
                                                            ) : (
                                                                <Badge className="text-[10px] px-2 py-0.5 bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-300 border-0 flex items-center gap-1">
                                                                    <XCircle className="h-3 w-3" />
                                                                    Inactif
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>

                                    {/* Vue Desktop - Tableau */}
                                    <div className="hidden md:block overflow-x-auto -mx-2 sm:mx-0">
                                        <table className="w-full text-sm min-w-[500px] bg-white dark:bg-slate-900">
                                            <thead>
                                                <tr className="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-slate-800/70">
                                                    <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                        Nom
                                                    </th>
                                                    <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 hidden md:table-cell">
                                                        Email
                                                    </th>
                                                    <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                        Téléphone
                                                    </th>
                                                    <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300 hidden lg:table-cell">
                                                        Statut
                                                    </th>
                                                    <th className="text-right py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {suppliers.map((s) => (
                                                    <tr key={s.id} className="border-b border-gray-100 dark:border-gray-800">
                                                        <td className="py-2 px-2 font-medium text-gray-900 dark:text-gray-100">
                                                            {s.name}
                                                        </td>
                                                        <td className="py-2 px-2 text-gray-600 dark:text-gray-400 hidden md:table-cell">
                                                            {s.email || '—'}
                                                        </td>
                                                        <td className="py-2 px-2 text-gray-600 dark:text-gray-400">
                                                            {s.phone || '—'}
                                                        </td>
                                                        <td className="py-2 px-2 hidden lg:table-cell">
                                                            <Badge variant={s.is_active ? 'default' : 'secondary'}>
                                                                {s.is_active ? 'Actif' : 'Inactif'}
                                                            </Badge>
                                                        </td>
                                                        <td className="py-2 px-2 text-right">
                                                            <div className="flex flex-wrap justify-end gap-1">
                                                                {canEdit && (
                                                                    <Button 
                                                                        variant="ghost" 
                                                                        size="sm" 
                                                                        onClick={() => handleOpenEdit(s)}
                                                                        title="Modifier"
                                                                    >
                                                                        <Pencil className="h-4 w-4" />
                                                                    </Button>
                                                                )}
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => handleToggleActive(s.id, s.is_active)}
                                                                    title={s.is_active ? 'Désactiver' : 'Activer'}
                                                                >
                                                                    {s.is_active ? <PowerOff className="h-4 w-4 text-amber-600" /> : <Power className="h-4 w-4 text-green-600" />}
                                                                </Button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* FAB Mobile - Ajouter fournisseur */}
                    {canCreate && (
                        <div className="md:hidden fixed bottom-20 right-4 z-30">
                            <Button
                                onClick={handleOpenCreate}
                                className="h-14 w-14 rounded-full bg-rose-500 hover:bg-rose-600 text-white shadow-lg flex items-center justify-center"
                                size="icon"
                            >
                                <Plus className="h-6 w-6" />
                            </Button>
                        </div>
                    )}
                </div>
            </div>
            <ImportModal
                show={importOpen}
                onClose={() => { setImportOpen(false); setImportFile(null); }}
                title="Importer des fournisseurs"
                summaryItems={[
                    'Importez vos fournisseurs via un fichier Excel (.xlsx) ou CSV.',
                    'Colonne obligatoire : nom. Les autres colonnes sont optionnelles.',
                    'Ne pas modifier la première ligne (en-têtes) ni renommer les colonnes du modèle.',
                    'Ne pas ajouter de nouvelles colonnes ni fusionner de cellules, et éviter les formules Excel dans les champs importés.',
                ]}
                examples={[
                    { values: { nom: 'Fournisseur A', email: 'contact@fournisseur-a.cd', phone: '+243 800 000 000' } },
                    { values: { nom: 'Fournisseur B', email: 'info@fournisseur-b.cd', phone: '+243 800 111 111' } },
                ]}
                templateUrl={route('commerce.suppliers.import.template')}
                accept=".xlsx,.csv,.txt"
                file={importFile}
                onFileChange={setImportFile}
                onConfirmImport={handleConfirmImport}
                confirmingImport={importing}
                directImport
            />
            <SupplierDrawer
                isOpen={drawerOpen}
                onClose={handleCloseDrawer}
                supplier={selectedSupplier}
                onSuccess={handleDrawerSuccess}
                canCreate={canCreate}
                canUpdate={canEdit}
                routePrefix="commerce"
            />
        </AppLayout>
    );
}
