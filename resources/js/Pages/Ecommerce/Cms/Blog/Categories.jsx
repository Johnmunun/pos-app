import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { ArrowLeft, Plus, Pencil, Trash2, HelpCircle } from 'lucide-react';
import CmsBlogCategoryDrawer from '@/Components/Ecommerce/Cms/CmsBlogCategoryDrawer';
import CmsHelpModal from '@/Components/Ecommerce/Cms/CmsHelpModal';

export default function CmsBlogCategories({ categories = [] }) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [helpOpen, setHelpOpen] = useState(false);

    const handleCreate = () => {
        setEditingCategory(null);
        setDrawerOpen(true);
    };

    const handleEdit = (c) => {
        setEditingCategory(c);
        setDrawerOpen(true);
    };

    const handleCloseDrawer = () => {
        setDrawerOpen(false);
        setEditingCategory(null);
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between gap-3">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={route('ecommerce.cms.blog.index')} className="inline-flex items-center gap-2">
                                <ArrowLeft className="h-4 w-4" /> Blog
                            </Link>
                        </Button>
                        <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">CMS - Catégories du blog</h2>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                            <HelpCircle className="h-4 w-4 mr-1" /> Tutoriel
                        </Button>
                        <Button size="sm" onClick={handleCreate}>
                            <Plus className="h-4 w-4 mr-2" /> Nouvelle catégorie
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Catégories blog CMS - E-commerce" />
            <div className="py-6 max-w-2xl">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-gray-200 dark:border-slate-700">
                                        <th className="text-left px-4 py-3 font-medium">Nom</th>
                                        <th className="text-left px-4 py-3 font-medium">Slug</th>
                                        <th className="text-right px-4 py-3 font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {categories.length === 0 ? (
                                        <tr>
                                            <td colSpan={3} className="px-4 py-8 text-center text-gray-500">
                                                <p className="mb-2">Aucune catégorie.</p>
                                                <Button size="sm" onClick={handleCreate}>Créer une catégorie</Button>
                                            </td>
                                        </tr>
                                    ) : (
                                        categories.map((c) => (
                                            <tr key={c.id} className="border-b border-gray-100 dark:border-slate-700/50">
                                                <td className="px-4 py-3 font-medium">{c.name}</td>
                                                <td className="px-4 py-3 text-sm text-gray-500">{c.slug}</td>
                                                <td className="px-4 py-3 text-right">
                                                    <div className="flex justify-end gap-1">
                                                        <Button variant="ghost" size="sm" onClick={() => handleEdit(c)}><Pencil className="h-4 w-4" /></Button>
                                                        <Button variant="ghost" size="sm" className="text-red-600" onClick={() => { if (confirm('Supprimer ?')) router.delete(route('ecommerce.cms.blog.categories.destroy', c.id)); }}><Trash2 className="h-4 w-4" /></Button>
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

            <CmsBlogCategoryDrawer isOpen={drawerOpen} onClose={handleCloseDrawer} category={editingCategory} />
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="blog" />
        </AppLayout>
    );
}
