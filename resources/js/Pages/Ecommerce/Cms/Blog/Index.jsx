import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Plus, Pencil, Trash2, FolderOpen, HelpCircle } from 'lucide-react';
import CmsBlogDrawer from '@/Components/Ecommerce/Cms/CmsBlogDrawer';
import CmsHelpModal from '@/Components/Ecommerce/Cms/CmsHelpModal';

export default function CmsBlogIndex({ articles = [], categories = [] }) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingArticle, setEditingArticle] = useState(null);
    const [helpOpen, setHelpOpen] = useState(false);

    const handleCreate = () => {
        setEditingArticle(null);
        setDrawerOpen(true);
    };

    const handleEdit = (a) => {
        setEditingArticle({ ...a, published_at: a.published_at ? String(a.published_at).replace(' ', 'T').slice(0, 16) : '' });
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setEditingArticle(null);
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-3">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">CMS - Blog / Articles</h2>
                    <div className="flex gap-2">
                        <Button variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                            <HelpCircle className="h-4 w-4 mr-1" /> Tutoriel
                        </Button>
                        <Button variant="outline" size="sm" asChild>
                            <Link href={route('ecommerce.cms.blog.categories.index')} className="inline-flex items-center gap-2">
                                <FolderOpen className="h-4 w-4" /> Catégories
                            </Link>
                        </Button>
                        <Button size="sm" onClick={handleCreate} className="inline-flex items-center gap-2">
                            <Plus className="h-4 w-4" /> Nouvel article
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Blog CMS - E-commerce" />
            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-slate-700">
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Titre</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Catégorie</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Publication</th>
                                        <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Statut</th>
                                        <th className="text-right px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {articles.length === 0 ? (
                                        <tr>
                                            <td colSpan={5} className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                                <p className="mb-2">Aucun article.</p>
                                                <Button size="sm" onClick={handleCreate}>Créer un article</Button>
                                            </td>
                                        </tr>
                                    ) : (
                                        articles.map((a) => (
                                            <tr key={a.id} className="border-b border-gray-100 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                                <td className="px-4 py-3 font-medium text-gray-900 dark:text-gray-100">{a.title}</td>
                                                <td className="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{a.category_name || '-'}</td>
                                                <td className="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{a.published_at || '-'}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={a.is_active ? 'default' : 'secondary'}>{a.is_active ? 'Actif' : 'Inactif'}</Badge>
                                                </td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="sm" onClick={() => handleEdit(a)}><Pencil className="h-4 w-4" /></Button>
                                                        <Button variant="ghost" size="sm" className="text-red-600" onClick={() => { if (confirm('Supprimer ?')) router.delete(route('ecommerce.cms.blog.destroy', a.id)); }}><Trash2 className="h-4 w-4" /></Button>
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

            <CmsBlogDrawer isOpen={drawerOpen} onClose={handleCloseDrawer} article={editingArticle} categories={categories} />
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="blog" />
        </AppLayout>
    );
}
