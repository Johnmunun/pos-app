import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Users, Plus, Search } from 'lucide-react';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import ImportModal from '@/Components/ImportModal';

export default function CommerceCustomersIndex({ customers = [], filters = {} }) {
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
                        <Button asChild className="inline-flex items-center">
                            <Link href={route('commerce.customers.create')}>
                                <span className="flex items-center gap-2">
                                    <Plus className="h-4 w-4" />
                                    <span>Nouveau client</span>
                                </span>
                            </Link>
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Clients - Commerce" />
            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    <input
                        ref={fileInputRef}
                        type="file"
                        className="hidden"
                        accept=".xlsx,.csv,.txt"
                        onChange={handleImportFileChange}
                    />
                    <form onSubmit={handleSearch} className="flex flex-wrap gap-4">
                        <div className="relative flex-1 min-w-[200px]">
                            <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <Input
                                placeholder="Rechercher (nom, email, téléphone)..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="pl-10"
                            />
                        </div>
                        <Button type="submit">Filtrer</Button>
                    </form>

                    <Card className="bg-white dark:bg-slate-900">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-gray-900 dark:text-white">
                                <Users className="h-5 w-5" /> Liste des clients
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="overflow-x-auto -mx-2 sm:mx-0">
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
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                            {customers.length === 0 && (
                                <div className="text-center py-12 text-gray-500">
                                    <Users className="h-12 w-12 mx-auto mb-3 opacity-50" />
                                    <p>Aucun client. Créez-en un pour commencer.</p>
                                    <Button asChild className="mt-4">
                                        <Link href={route('commerce.customers.create')}>Nouveau client</Link>
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>
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
        </AppLayout>
    );
}

