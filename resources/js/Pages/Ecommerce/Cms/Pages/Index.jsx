import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { FileText, Plus, Pencil, Trash2, HelpCircle } from 'lucide-react';
import CmsPageDrawer from '@/Components/Ecommerce/Cms/CmsPageDrawer';
import CmsHelpModal from '@/Components/Ecommerce/Cms/CmsHelpModal';

export default function CmsPagesIndex({ pages = [], media = [] }) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingPage, setEditingPage] = useState(null);
    const [helpOpen, setHelpOpen] = useState(false);

    const formatDate = (d) => {
        if (!d) return '-';
        return new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    };

    const handleCreate = () => {
        setEditingPage(null);
        setDrawerOpen(true);
    };

    const handleEdit = (page) => {
        setEditingPage(page);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setEditingPage(null);
    };

    const pageForDrawer = editingPage ? {
        ...editingPage,
        template: editingPage.template ?? 'standard',
        metadata: editingPage.metadata ?? {},
        published_at: editingPage.published_at ? String(editingPage.published_at).replace(' ', 'T').slice(0, 16) : '',
    } : null;

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-3">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">CMS - Pages</h2>
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                            <HelpCircle className="h-4 w-4 mr-1" />
                            Tutoriel
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.post(route('ecommerce.cms.pages.create-defaults'), {}, { preserveScroll: true })}
                        >
                            <FileText className="h-4 w-4 mr-1" />
                            Créer pages par défaut
                        </Button>
                        <Button size="sm" onClick={handleCreate} className="inline-flex items-center gap-2">
                            <Plus className="h-4 w-4" />
                            Nouvelle page
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Pages CMS - E-commerce" />
            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-slate-700">
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Titre</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Slug</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Publication</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Statut</th>
                                        <th className="text-right px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {pages.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-8 text-center text-gray-500">
                                                <p className="mb-2">Aucune page.</p>
                                                <Button size="sm" onClick={handleCreate}>Créer une page</Button>
                                            </td>
                                        </tr>
                                    ) : (
                                        pages.map((p) => (
                                            <tr key={p.id} className="border-b border-gray-100 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                                <td className="px-4 py-3 font-medium">{p.title}</td>
                                                <td className="px-4 py-3 text-sm text-gray-500"><code>{p.slug}</code></td>
                                                <td className="px-4 py-3 text-sm">{formatDate(p.published_at)}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={p.is_active ? 'default' : 'secondary'}>{p.is_active ? 'Publié' : 'Brouillon'}</Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="sm" onClick={() => handleEdit(p)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Button>
                                                        <Button variant="ghost" size="sm" className="text-red-600" onClick={() => { if (confirm('Supprimer cette page ?')) router.delete(route('ecommerce.cms.pages.destroy', p.id)); }}>
                                                            <Trash2 className="h-4 w-4" />
                                                        </Button>
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

            <CmsPageDrawer key={editingPage?.id ?? 'new'} isOpen={drawerOpen} onClose={handleCloseDrawer} page={pageForDrawer} media={media} />
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="pages" />
        </AppLayout>
    );
}
