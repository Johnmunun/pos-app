import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Users, Plus, Search, Pencil, Mail, Phone, CheckCircle, XCircle } from 'lucide-react';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import ImportModal from '@/Components/ImportModal';
import CustomerDrawer from '@/Components/Commerce/CustomerDrawer';

export default function CommerceCustomersIndex({ customers = [], filters = {} }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];
    
    const hasPermission = (perm) => {
        if (permissions.includes('*')) return true;
        return perm.split('|').some(p => permissions.includes(p));
    };

    const canCreate = hasPermission('commerce.customer.create');
    const canEdit = hasPermission('commerce.customer.edit');

    // Drawer state
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedCustomer, setSelectedCustomer] = useState(null);

    const handleOpenCreate = () => {
        setSelectedCustomer(null);
        setDrawerOpen(true);
    };

    const handleOpenEdit = (customer) => {
        setSelectedCustomer(customer);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setSelectedCustomer(null);
    };

    const handleDrawerSuccess = () => {
        router.reload({ only: ['customers'] });
    };

    const [search, setSearch] = useState(filters.search || '');
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importing, setImporting] = useState(false);

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route('commerce.customers.index'),
            { search: search || undefined },
            { preserveState: true }
        );
    };

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
            const { data } = await axios.post(route('commerce.customers.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success(data.message || 'Import clients terminé.');
            if (data.errors && data.errors.length) {
                console.warn('Erreurs import clients:', data.errors);
            }
            setImportOpen(false);
            setImportFile(null);
            router.reload({ only: ['customers'] });
        } catch (err) {
            const message =
                err.response?.data?.message ||
                "Erreur lors de l'import des clients.";
            toast.error(message);
        } finally {
            setImporting(false);
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <h2 className="font-semibold text-lg sm:text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Clients — Global Commerce
                    </h2>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            className="inline-flex items-center"
                            onClick={handleOpenImport}
                            disabled={importing}
                        >
                            <span>Importer</span>
                        </Button>
                        <Button
                            asChild
                            className="inline-flex items-center bg-rose-500 hover:bg-rose-600 text-white"
                        >
                            <a
                                href={route('commerce.exports.customers.pdf')}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>Exporter PDF</span>
                            </a>
                        </Button>
                        <Button
                            asChild
                            className="inline-flex items-center bg-emerald-500 hover:bg-emerald-600 text-white"
                        >
                            <a
                                href={route('commerce.exports.customers.excel')}
                                target="_blank"
                                rel="noopener noreferrer"
                            >
                                <span>Exporter Excel</span>
                            </a>
                        </Button>
                        {canCreate && (
                            <Button 
                                onClick={handleOpenCreate}
                                className="inline-flex items-center gap-1.5 sm:gap-2"
                            >
                                <Plus className="h-4 w-4 flex-shrink-0" />
                                <span className="hidden sm:inline">Nouveau </span>Client
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title="Clients - Commerce" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Recherche - Mobile optimisée */}
                    <Card className="mb-6 bg-white dark:bg-gray-800">
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center text-gray-900 dark:text-white text-base sm:text-lg">
                                <Search className="h-4 w-4 sm:h-5 sm:w-5 mr-2" /> 
                                <span className="hidden sm:inline">Recherche</span>
                                <span className="sm:hidden">Rechercher clients...</span>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSearch} className="space-y-3">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        placeholder="Rechercher clients..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                                <Button type="submit" className="w-full sm:w-auto">
                                    <Search className="h-4 w-4 mr-2" />
                                    <span className="hidden sm:inline">Filtrer</span>
                                    <span className="sm:hidden">Rechercher</span>
                                </Button>
                                <div className="md:hidden flex items-center justify-between text-sm text-gray-600 dark:text-gray-400 pt-2 border-t border-gray-200 dark:border-gray-700">
                                    <span>Affichage de {customers.length} client{customers.length > 1 ? 's' : ''}</span>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-white">
                                <Users className="h-5 w-5" /> Liste des clients
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {customers.length === 0 ? (
                                <div className="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <Users className="h-12 w-12 mx-auto mb-3 opacity-50" />
                                    <p>Aucun client. Créez-en un pour commencer.</p>
                                    {canCreate && (
                                        <Button onClick={handleOpenCreate} className="mt-4 inline-flex items-center gap-1.5 sm:gap-2">
                                            <Plus className="h-4 w-4 flex-shrink-0" />
                                            <span className="hidden sm:inline">Nouveau </span>Client
                                        </Button>
                                    )}
                                </div>
                            ) : (
                                <>
                                    {/* Vue Mobile - Cartes */}
                                    <div className="md:hidden space-y-3">
                                        {customers.map((c) => (
                                            <div 
                                                key={c.id} 
                                                className="bg-white dark:bg-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700 transition-colors"
                                            >
                                                <div className="flex items-start gap-3 p-4">
                                                    {/* Avatar circulaire */}
                                                    <div className="flex-shrink-0">
                                                        <div className="h-16 w-16 rounded-full bg-gradient-to-br from-rose-400 to-rose-600 flex items-center justify-center border-2 border-gray-200 dark:border-gray-600">
                                                            <Users className="h-8 w-8 text-white" />
                                                        </div>
                                                    </div>
                                                    
                                                    {/* Contenu principal */}
                                                    <div className="flex-1 min-w-0">
                                                        <div className="flex items-start justify-between gap-2 mb-2">
                                                            <h3 className="font-semibold text-gray-900 dark:text-white text-sm truncate">
                                                                {c.full_name}
                                                            </h3>
                                                            {canEdit && (
                                                                <button
                                                                    onClick={() => handleOpenEdit(c)}
                                                                    className="p-1.5 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors flex-shrink-0"
                                                                    title="Modifier"
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </button>
                                                            )}
                                                        </div>
                                                        
                                                        {/* Informations de contact */}
                                                        <div className="space-y-1.5 mb-2">
                                                            {c.email && (
                                                                <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                                    <Mail className="h-3.5 w-3.5 flex-shrink-0" />
                                                                    <span className="truncate">{c.email}</span>
                                                                </div>
                                                            )}
                                                            {c.phone && (
                                                                <div className="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                                                    <Phone className="h-3.5 w-3.5 flex-shrink-0" />
                                                                    <span>{c.phone}</span>
                                                                </div>
                                                            )}
                                                        </div>
                                                        
                                                        {/* Badge statut */}
                                                        <div className="flex items-center gap-2">
                                                            {c.is_active ? (
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
                                                    <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                        Email
                                                    </th>
                                                    <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                        Téléphone
                                                    </th>
                                                    <th className="text-left py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                        Statut
                                                    </th>
                                                    <th className="text-right py-2 px-2 text-xs sm:text-sm font-medium text-gray-600 dark:text-gray-300">
                                                        Actions
                                                    </th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {customers.map((c) => (
                                                    <tr key={c.id} className="border-b border-gray-100 dark:border-gray-800">
                                                        <td className="py-2 px-2 font-medium">{c.full_name}</td>
                                                        <td className="py-2 px-2 text-gray-600 dark:text-gray-400">
                                                            {c.email || '—'}
                                                        </td>
                                                        <td className="py-2 px-2 text-gray-600 dark:text-gray-400">
                                                            {c.phone || '—'}
                                                        </td>
                                                        <td className="py-2 px-2">
                                                            <Badge variant={c.is_active ? 'default' : 'secondary'}>
                                                                {c.is_active ? 'Actif' : 'Inactif'}
                                                            </Badge>
                                                        </td>
                                                        <td className="py-2 px-2 text-right">
                                                            {canEdit && (
                                                                <Button 
                                                                    variant="ghost" 
                                                                    size="sm" 
                                                                    onClick={() => handleOpenEdit(c)}
                                                                    title="Modifier"
                                                                >
                                                                    <Pencil className="h-4 w-4" />
                                                                </Button>
                                                            )}
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

                    {/* FAB Mobile - Ajouter client */}
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
                title="Importer des clients"
                summaryItems={[
                    'Importez vos clients via un fichier Excel (.xlsx) ou CSV.',
                    'Colonne obligatoire : nom (ou full_name). Les autres colonnes sont optionnelles.',
                    'Ne pas modifier la première ligne (en-têtes) ni renommer les colonnes du modèle.',
                    'Ne pas ajouter de nouvelles colonnes ni fusionner de cellules, et éviter les formules Excel dans les champs importés.',
                ]}
                examples={[
                    { values: { nom: 'Jean Dupont', email: 'jean@exemple.com', phone: '+243 800 000 000' } },
                    { values: { nom: 'Société ABC', email: 'contact@abc.cd', phone: '+243 800 111 111' } },
                ]}
                templateUrl={route('commerce.customers.import.template')}
                accept=".xlsx,.csv,.txt"
                file={importFile}
                onFileChange={setImportFile}
                onConfirmImport={handleConfirmImport}
                confirmingImport={importing}
                directImport
            />
            <CustomerDrawer
                isOpen={drawerOpen}
                onClose={handleCloseDrawer}
                customer={selectedCustomer}
                onSuccess={handleDrawerSuccess}
                canCreate={canCreate}
                canUpdate={canEdit}
                routePrefix="commerce"
            />
        </AppLayout>
    );
}

