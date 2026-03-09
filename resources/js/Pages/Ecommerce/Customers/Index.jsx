import { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import CustomerDrawer from '@/Components/Ecommerce/CustomerDrawer';
import ImportModal from '@/Components/ImportModal';
import { toast } from 'react-hot-toast';
import axios from 'axios';
import {
    Plus,
    Search,
    Users,
    Mail,
    Phone,
    RefreshCw,
    Upload,
    Download,
} from 'lucide-react';
import EcommercePageHeader from '@/Components/Ecommerce/EcommercePageHeader';
import EcommerceActionButton from '@/Components/Ecommerce/EcommerceActionButton';

export default function CustomersIndex({ customers = [] }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];

    const hasPermission = (permission) => {
        if (auth?.user?.type === 'ROOT') return true;
        return permissions.includes(permission);
    };

    const canCreate = hasPermission('ecommerce.create');
    const canView = hasPermission('ecommerce.view');

    const [search, setSearch] = useState('');
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedCustomer, setSelectedCustomer] = useState(null);
    const [importOpen, setImportOpen] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [importPreview, setImportPreview] = useState(null);
    const [previewLoading, setPreviewLoading] = useState(false);
    const [importing, setImporting] = useState(false);

    const handleOpenImport = () => {
        setImportOpen(true);
        setImportFile(null);
        setImportPreview(null);
    };

    const handleGeneratePreview = async (e) => {
        e.preventDefault();
        if (!importFile) {
            toast.error('Veuillez sélectionner un fichier.');
            return;
        }
        setPreviewLoading(true);
        setImportPreview(null);
        try {
            const formData = new FormData();
            formData.append('file', importFile);
            const res = await axios.post(route('ecommerce.customers.import.preview'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            setImportPreview(res.data);
        } catch (err) {
            toast.error(err.response?.data?.message || "Erreur lors de l'aperçu.");
        } finally {
            setPreviewLoading(false);
        }
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
            const { data } = await axios.post(route('ecommerce.customers.import'), formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            toast.success(data.message || 'Import clients terminé.');
            if (data.errors?.length) {
                console.warn('Erreurs import clients:', data.errors);
            }
            setImportOpen(false);
            setImportFile(null);
            setImportPreview(null);
            router.reload();
        } catch (err) {
            toast.error(err.response?.data?.message || "Erreur lors de l'import des clients.");
        } finally {
            setImporting(false);
        }
    };

    const handleOpenCreate = () => {
        setSelectedCustomer(null);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setSelectedCustomer(null);
    };

    const formatCurrency = (amount, currency) => {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: currency || 'USD',
        }).format(amount);
    };

    const filteredCustomers = customers.filter(customer => {
        if (!search) return true;
        const searchLower = search.toLowerCase();
        return (
            customer.full_name.toLowerCase().includes(searchLower) ||
            customer.email.toLowerCase().includes(searchLower) ||
            (customer.phone && customer.phone.toLowerCase().includes(searchLower))
        );
    });

    return (
        <AppLayout
            header={
                <EcommercePageHeader title="Clients Ecommerce" icon={Users}>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="inline-flex items-center justify-center gap-2 p-2 sm:px-3 sm:py-2 min-w-[36px] sm:min-w-0 border-emerald-500 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-400 dark:text-emerald-300 dark:hover:bg-emerald-900/20"
                    >
                        <a href={route('ecommerce.exports.customers.excel')} target="_blank" rel="noopener noreferrer" title="Export Excel">
                            <Download className="h-4 w-4 shrink-0" />
                            <span className="hidden sm:inline">Export Excel</span>
                        </a>
                    </Button>
                    <Button
                        asChild
                        variant="outline"
                        size="sm"
                        className="inline-flex items-center justify-center gap-2 p-2 sm:px-3 sm:py-2 min-w-[36px] sm:min-w-0 border-red-500 text-red-700 hover:bg-red-50 dark:border-red-400 dark:text-red-300 dark:hover:bg-red-900/20"
                    >
                        <a href={route('ecommerce.exports.customers.pdf')} target="_blank" rel="noopener noreferrer" title="Export PDF">
                            <Download className="h-4 w-4 shrink-0" />
                            <span className="hidden sm:inline">Export PDF</span>
                        </a>
                    </Button>
                    <EcommerceActionButton icon={Upload} label="Importer" variant="outline" onClick={handleOpenImport} />
                    {canCreate && (
                        <EcommerceActionButton icon={Plus} label="Nouveau client" onClick={handleOpenCreate} />
                    )}
                </EcommercePageHeader>
            }
        >
            <Head title="Clients Ecommerce" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Barre de recherche */}
                    <div className="mb-6">
                        <div className="flex gap-4">
                            <div className="flex-1 relative">
                                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                <Input
                                    type="text"
                                    placeholder="Rechercher un client..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button
                                onClick={() => router.reload({ only: ['customers'] })}
                                variant="outline"
                                size="icon"
                            >
                                <RefreshCw className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    {/* Liste des clients */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        {filteredCustomers.length === 0 ? (
                            <div className="py-12 text-center">
                                <Users className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                <p className="text-lg font-medium text-gray-600 dark:text-gray-300 mb-2">
                                    {search ? 'Aucun client trouvé' : 'Aucun client'}
                                </p>
                                <p className="text-gray-500 dark:text-gray-400 mb-4">
                                    {search
                                        ? 'Essayez avec d\'autres termes de recherche'
                                        : 'Commencez par créer votre premier client'}
                                </p>
                                {canCreate && !search && (
                                    <Button onClick={handleOpenCreate} className="gap-2">
                                        <Plus className="h-4 w-4" />
                                        Nouveau client
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <>
                                {/* Mobile: cartes */}
                                <div className="md:hidden divide-y divide-gray-200 dark:divide-gray-700">
                                    {filteredCustomers.map((customer) => (
                                        <div
                                            key={customer.id}
                                            className="p-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                        >
                                            <div className="flex items-start justify-between gap-2">
                                                <div className="min-w-0">
                                                    <p className="font-medium text-gray-900 dark:text-white">{customer.full_name}</p>
                                                    <div className="mt-1 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                        <Mail className="h-3 w-3 shrink-0" />
                                                        <span className="truncate">{customer.email}</span>
                                                    </div>
                                                    {customer.phone && (
                                                        <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                                            <Phone className="h-3 w-3 shrink-0" />
                                                            {customer.phone}
                                                        </div>
                                                    )}
                                                </div>
                                                <Badge className={customer.is_active
                                                    ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300 shrink-0'
                                                    : 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300 shrink-0'
                                                }>
                                                    {customer.is_active ? 'Actif' : 'Inactif'}
                                                </Badge>
                                            </div>
                                            <div className="mt-3 grid grid-cols-2 gap-2 text-sm">
                                                <div>
                                                    <span className="text-gray-500 dark:text-gray-400">Commandes</span>
                                                    <p className="font-medium text-gray-900 dark:text-white">{customer.total_orders}</p>
                                                </div>
                                                <div>
                                                    <span className="text-gray-500 dark:text-gray-400">Total</span>
                                                    <p className="font-medium text-gray-900 dark:text-white">{formatCurrency(customer.total_spent, 'USD')}</p>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Desktop: tableau */}
                                <div className="hidden md:block overflow-x-auto">
                                <table className="w-full">
                                    <thead className="bg-gray-50 dark:bg-gray-700/50 border-b border-gray-200 dark:border-gray-600">
                                        <tr>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Client
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Contact
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Commandes
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Total dépensé
                                            </th>
                                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                                Statut
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                        {filteredCustomers.map((customer) => (
                                            <tr
                                                key={customer.id}
                                                className="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                                            >
                                                <td className="px-6 py-4">
                                                    <div className="font-medium text-gray-900 dark:text-gray-100">
                                                        {customer.full_name}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-1">
                                                        <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                            <Mail className="h-3 w-3" />
                                                            {customer.email}
                                                        </div>
                                                        {customer.phone && (
                                                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                                                <Phone className="h-3 w-3" />
                                                                {customer.phone}
                                                            </div>
                                                        )}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                                    {customer.total_orders}
                                                </td>
                                                <td className="px-6 py-4 text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {formatCurrency(customer.total_spent, 'USD')}
                                                </td>
                                                <td className="px-6 py-4">
                                                    <Badge className={customer.is_active 
                                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300'
                                                        : 'bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300'
                                                    }>
                                                        {customer.is_active ? 'Actif' : 'Inactif'}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            </div>

            {canCreate && (
                <div className="md:hidden fixed bottom-20 right-4 z-30">
                    <Button
                        onClick={handleOpenCreate}
                        className="h-14 w-14 rounded-full bg-amber-500 hover:bg-amber-600 text-white shadow-lg"
                        size="icon"
                        title="Nouveau client"
                    >
                        <Plus className="h-6 w-6" />
                    </Button>
                </div>
            )}

            {/* Drawer pour créer */}
            {drawerOpen && (
                <CustomerDrawer
                    isOpen={drawerOpen}
                    onClose={handleCloseDrawer}
                    customer={selectedCustomer}
                />
            )}

            <ImportModal
                show={importOpen}
                onClose={() => { setImportOpen(false); setImportFile(null); setImportPreview(null); }}
                title="Importer des clients"
                summaryItems={[
                    'Importez vos clients via un fichier Excel (.xlsx) ou CSV.',
                    'Colonnes obligatoires : prenom, nom, email. Les autres sont optionnelles.',
                    'Ne pas modifier les en-têtes du modèle.',
                ]}
                examples={[
                    { values: { prenom: 'Jean', nom: 'Dupont', email: 'jean@example.com', telephone: '0999999999' } },
                ]}
                templateUrl={route('ecommerce.customers.import.template')}
                accept=".xlsx,.csv,.txt"
                file={importFile}
                onFileChange={setImportFile}
                onGeneratePreview={handleGeneratePreview}
                previewLoading={previewLoading}
                preview={importPreview}
                onConfirmImport={handleConfirmImport}
                confirmingImport={importing}
            />
        </AppLayout>
    );
}
