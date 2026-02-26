import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { 
    Truck, 
    Plus, 
    Eye, 
    Edit, 
    Filter, 
    Search, 
    Users, 
    CheckCircle, 
    XCircle,
    Phone,
    Mail,
    Building
} from 'lucide-react';
import axios from 'axios';
import toast from 'react-hot-toast';
import SupplierDrawer from '@/Components/Pharmacy/SupplierDrawer';
import ExportButtons from '@/Components/Pharmacy/ExportButtons';

export default function SuppliersIndex({ suppliers, filters = {}, routePrefix = 'pharmacy' }) {
    const { auth } = usePage().props;
    const permissions = auth?.permissions || [];
    
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [perPage, setPerPage] = useState(filters.per_page || 15);
    const [loading, setLoading] = useState({});

    // Drawer state
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [selectedSupplier, setSelectedSupplier] = useState(null);

    const hasPermission = (perm) => {
        if (permissions.includes('*')) return true;
        return perm.split('|').some(p => permissions.includes(p));
    };

    const canCreate = hasPermission(`${routePrefix}.supplier.create`);
    const canEdit = hasPermission(`${routePrefix}.supplier.edit`);
    const canView = hasPermission(`${routePrefix}.supplier.view`);

    const handleFilter = (e) => {
        e.preventDefault();
        router.get(route(`${routePrefix}.suppliers.index`), {
            search: search || undefined,
            status: status || undefined,
            per_page: perPage || undefined,
        }, { preserveState: true });
    };

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

    const handleToggleStatus = async (supplier) => {
        const action = supplier.status === 'active' ? 'deactivate' : 'activate';
        const permission = supplier.status === 'active' 
            ? `${routePrefix}.supplier.deactivate` 
            : `${routePrefix}.supplier.activate`;

        if (!hasPermission(permission)) {
            toast.error('Vous n\'avez pas la permission pour cette action.');
            return;
        }

        setLoading(prev => ({ ...prev, [supplier.id]: true }));

        try {
            const response = await axios.post(route(`${routePrefix}.suppliers.${action}`, supplier.id));
            if (response.data.success) {
                toast.success(response.data.message);
                router.reload({ only: ['suppliers'] });
            } else {
                toast.error(response.data.message);
            }
        } catch (error) {
            toast.error('Une erreur est survenue.');
        } finally {
            setLoading(prev => ({ ...prev, [supplier.id]: false }));
        }
    };

    const getStatusBadge = (s) => {
        if (s === 'active') {
            return <Badge className="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">Actif</Badge>;
        }
        return <Badge className="bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">Inactif</Badge>;
    };

    const activeCount = suppliers.data?.filter(s => s.status === 'active').length || 0;
    const inactiveCount = suppliers.data?.filter(s => s.status === 'inactive').length || 0;

    return (
        <AppLayout
            header={
                <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                    Gestion des Fournisseurs
                </h2>
            }
        >
            <Head title="Fournisseurs" />
            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">

                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Total Fournisseurs</CardTitle>
                                <Truck className="h-4 w-4 text-blue-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-900 dark:text-white">{suppliers.total || 0}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    enregistrés
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Actifs</CardTitle>
                                <CheckCircle className="h-4 w-4 text-green-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-green-600 dark:text-green-400">{activeCount}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    fournisseurs actifs
                                </p>
                            </CardContent>
                        </Card>

                        <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Inactifs</CardTitle>
                                <XCircle className="h-4 w-4 text-gray-500" />
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold text-gray-600 dark:text-gray-400">{inactiveCount}</div>
                                <p className="text-xs text-gray-500 dark:text-gray-400">
                                    fournisseurs inactifs
                                </p>
                            </CardContent>
                        </Card>

                        {canCreate && (
                            <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                    <CardTitle className="text-sm font-medium text-gray-700 dark:text-gray-200">Action rapide</CardTitle>
                                    <Plus className="h-4 w-4 text-purple-500" />
                                </CardHeader>
                                <CardContent>
                                    <Button 
                                        onClick={handleOpenCreate}
                                        className="w-full bg-amber-500 hover:bg-amber-600 text-white inline-flex items-center justify-center gap-2"
                                    >
                                        <Plus className="h-4 w-4" />
                                        <span>Nouveau fournisseur</span>
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Filtres */}
                    <Card className="mb-6 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Filter className="h-5 w-5 mr-2 text-amber-500" />
                                Filtres
                            </CardTitle>
                            <ExportButtons
                                pdfUrl={route(`${routePrefix}.exports.suppliers.pdf`)}
                                excelUrl={route(`${routePrefix}.exports.suppliers.excel`)}
                                disabled={!suppliers.data?.length}
                            />
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleFilter} className="flex flex-wrap gap-4 items-end">
                                <div className="flex-1 min-w-[200px]">
                                    <label className="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Recherche</label>
                                    <Input 
                                        type="text" 
                                        value={search} 
                                        onChange={(e) => setSearch(e.target.value)}
                                        placeholder="Nom, contact, email, téléphone..."
                                        className="bg-white dark:bg-slate-800 border-gray-300 dark:border-slate-600 text-gray-900 dark:text-gray-100"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Statut</label>
                                    <select
                                        className="h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                        value={status}
                                        onChange={(e) => setStatus(e.target.value)}
                                    >
                                        <option value="">Tous</option>
                                        <option value="active">Actifs</option>
                                        <option value="inactive">Inactifs</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1 text-gray-700 dark:text-gray-300">Par page</label>
                                    <select
                                        className="h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-gray-100 px-3 focus:outline-none focus:ring-2 focus:ring-amber-500"
                                        value={perPage}
                                        onChange={(e) => setPerPage(e.target.value)}
                                    >
                                        <option value="10">10</option>
                                        <option value="15">15</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                                <Button type="submit" className="bg-amber-500 hover:bg-amber-600 text-white inline-flex items-center gap-2">
                                    <Search className="h-4 w-4" />
                                    <span>Filtrer</span>
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Liste des fournisseurs */}
                    <Card className="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-700">
                        <CardHeader>
                            <CardTitle className="flex items-center text-gray-900 dark:text-white">
                                <Users className="h-5 w-5 mr-2 text-blue-500" />
                                Liste des fournisseurs ({suppliers.total || 0})
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {!suppliers.data || suppliers.data.length === 0 ? (
                                <div className="py-12 text-center">
                                    <Truck className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" />
                                    <p className="text-gray-500 dark:text-gray-400 mb-4">
                                        Aucun fournisseur trouvé.
                                    </p>
                                    {canCreate && (
                                        <Button 
                                            onClick={handleOpenCreate}
                                            className="bg-amber-500 hover:bg-amber-600 text-white inline-flex items-center gap-2"
                                        >
                                            <Plus className="h-4 w-4" />
                                            <span>Créer un fournisseur</span>
                                        </Button>
                                    )}
                                </div>
                            ) : (
                                <>
                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                            <thead className="bg-gray-50 dark:bg-slate-800">
                                                <tr>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Nom</th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Contact</th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Téléphone</th>
                                                    <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Email</th>
                                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Commandes</th>
                                                    <th className="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Statut</th>
                                                    <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-slate-700">
                                                {suppliers.data.map((supplier) => (
                                                    <tr key={supplier.id} className="hover:bg-gray-50 dark:hover:bg-slate-800">
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            <div className="flex items-center">
                                                                <div className="h-10 w-10 flex-shrink-0 bg-amber-100 dark:bg-amber-900/30 rounded-full flex items-center justify-center">
                                                                    <Building className="h-5 w-5 text-amber-600 dark:text-amber-400" />
                                                                </div>
                                                                <div className="ml-4">
                                                                    <div className="text-sm font-medium text-gray-900 dark:text-white">{supplier.name}</div>
                                                                    <div className="text-xs text-gray-500 dark:text-gray-400">Créé le {supplier.created_at}</div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                                            {supplier.contact_person || '—'}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            {supplier.phone ? (
                                                                <span className="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                                                                    <Phone className="h-3 w-3 mr-1 text-gray-400" />
                                                                    {supplier.phone}
                                                                </span>
                                                            ) : '—'}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap">
                                                            {supplier.email ? (
                                                                <span className="inline-flex items-center text-sm text-gray-700 dark:text-gray-300">
                                                                    <Mail className="h-3 w-3 mr-1 text-gray-400" />
                                                                    {supplier.email}
                                                                </span>
                                                            ) : '—'}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                                                                {supplier.total_orders}
                                                            </span>
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-center">
                                                            {getStatusBadge(supplier.status)}
                                                        </td>
                                                        <td className="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                                            {canView && (
                                                                <Button variant="outline" size="sm" asChild className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700">
                                                                    <Link href={route(`${routePrefix}.suppliers.show`, supplier.id)}>
                                                                        <Eye className="h-4 w-4" />
                                                                    </Link>
                                                                </Button>
                                                            )}
                                                            {canEdit && (
                                                                <Button 
                                                                    variant="outline" 
                                                                    size="sm" 
                                                                    onClick={() => handleOpenEdit(supplier)}
                                                                    className="border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-700"
                                                                >
                                                                    <Edit className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            {supplier.status === 'active' && hasPermission(`${routePrefix}.supplier.deactivate`) && (
                                                                <Button 
                                                                    variant="outline" 
                                                                    size="sm" 
                                                                    onClick={() => handleToggleStatus(supplier)}
                                                                    disabled={loading[supplier.id]}
                                                                    className="border-orange-300 dark:border-orange-600 text-orange-700 dark:text-orange-400 hover:bg-orange-50 dark:hover:bg-orange-900/20"
                                                                >
                                                                    <XCircle className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                            {supplier.status === 'inactive' && hasPermission(`${routePrefix}.supplier.activate`) && (
                                                                <Button 
                                                                    variant="outline" 
                                                                    size="sm" 
                                                                    onClick={() => handleToggleStatus(supplier)}
                                                                    disabled={loading[supplier.id]}
                                                                    className="border-green-300 dark:border-green-600 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20"
                                                                >
                                                                    <CheckCircle className="h-4 w-4" />
                                                                </Button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* Pagination */}
                                    {suppliers.links && suppliers.links.length > 3 && (
                                        <div className="flex items-center justify-between mt-6 pt-4 border-t border-gray-200 dark:border-slate-700">
                                            <div className="text-sm text-gray-500 dark:text-gray-400">
                                                Affichage de {suppliers.from} à {suppliers.to} sur {suppliers.total} résultats
                                            </div>
                                            <div className="flex gap-1">
                                                {suppliers.links.map((link, index) => (
                                                    <Button
                                                        key={index}
                                                        variant={link.active ? "default" : "outline"}
                                                        size="sm"
                                                        disabled={!link.url}
                                                        onClick={() => link.url && router.get(link.url)}
                                                        className={link.active 
                                                            ? "bg-amber-500 hover:bg-amber-600 text-white" 
                                                            : "border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200"
                                                        }
                                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                                    />
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Supplier Drawer */}
            <SupplierDrawer
                isOpen={drawerOpen}
                onClose={handleCloseDrawer}
                supplier={selectedSupplier}
                onSuccess={handleDrawerSuccess}
                canCreate={canCreate}
                canUpdate={canEdit}
                routePrefix={routePrefix}
            />
        </AppLayout>
    );
}
