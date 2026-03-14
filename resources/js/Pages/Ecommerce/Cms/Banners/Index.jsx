import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Image, Plus, Pencil, Trash2, HelpCircle } from 'lucide-react';
import CmsBannerDrawer from '@/Components/Ecommerce/Cms/CmsBannerDrawer';
import CmsHelpModal from '@/Components/Ecommerce/Cms/CmsHelpModal';

export default function CmsBannersIndex({ banners = [], positions = [] }) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingBanner, setEditingBanner] = useState(null);
    const [helpOpen, setHelpOpen] = useState(false);
    const positionLabel = (v) => positions.find((p) => p.value === v)?.label ?? v;

    const handleCreate = () => {
        setEditingBanner(null);
        setDrawerOpen(true);
    };

    const handleEdit = (b) => {
        setEditingBanner(b);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setEditingBanner(null);
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-3">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">CMS - Bannières</h2>
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                            <HelpCircle className="h-4 w-4 mr-1" /> Tutoriel
                        </Button>
                        <Button size="sm" onClick={handleCreate} className="inline-flex items-center gap-2">
                            <Plus className="h-4 w-4" />
                            Nouvelle bannière
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Bannières CMS - E-commerce" />
            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-slate-700">
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Titre</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Position</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Lien</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Statut</th>
                                        <th className="text-right px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {banners.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                                <p className="mb-2">Aucune bannière.</p>
                                                <Button size="sm" onClick={handleCreate}>Créer une bannière</Button>
                                            </td>
                                        </tr>
                                    ) : (
                                        banners.map((b) => (
                                            <tr key={b.id} className="border-b border-gray-100 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                                <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{b.title}</td>
                                                <td className="px-4 py-3 text-gray-700 dark:text-gray-300">{positionLabel(b.position)}</td>
                                                <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 truncate max-w-[150px]">{b.link || '-'}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={b.is_active ? 'default' : 'secondary'}>{b.is_active ? 'Actif' : 'Inactif'}</Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="sm" onClick={() => handleEdit(b)}><Pencil className="h-4 w-4" /></Button>
                                                        <Button variant="ghost" size="sm" className="text-red-600" onClick={() => { if (confirm('Supprimer ?')) router.delete(route('ecommerce.cms.banners.destroy', b.id)); }}><Trash2 className="h-4 w-4" /></Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <CmsBannerDrawer isOpen={drawerOpen} onClose={handleCloseDrawer} banner={editingBanner} positions={positions} />
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="banners" />
        </AppLayout>
    );
}
