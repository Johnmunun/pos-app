import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Upload, Trash2, FileText, HelpCircle } from 'lucide-react';
import CmsMediaDrawer from '@/Components/Ecommerce/Cms/CmsMediaDrawer';

function formatSize(bytes) {
    if (!bytes) return '-';
    if (bytes < 1024) return bytes + ' o';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' Ko';
    return (bytes / (1024 * 1024)).toFixed(1) + ' Mo';
}

export default function CmsMediaIndex({ media = [] }) {
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [helpOpen, setHelpOpen] = useState(false);

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">CMS - Bibliothèque média</h2>
                    <div className="flex items-center gap-2">
                        <Button variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                            <HelpCircle className="h-4 w-4 mr-1" /> Tutoriel
                        </Button>
                        <Button size="sm" onClick={() => setDrawerOpen(true)}>
                            <Upload className="h-4 w-4 mr-2" /> Uploader un fichier
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title="Médias CMS - E-commerce" />
            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-4">
                        {media.length === 0 ? (
                            <div className="text-center py-12 text-gray-500">
                                <Upload className="h-12 w-12 mx-auto mb-3 opacity-50" />
                                <p className="mb-2">Aucun média. Uploadez des images ou documents.</p>
                                <p className="text-sm text-gray-400 mb-3">Utilisez les chemins dans Pages, Bannières et Blog.</p>
                                <Button size="sm" onClick={() => setDrawerOpen(true)}>
                                    <Upload className="h-4 w-4 mr-2" /> Ajouter un fichier
                                </Button>
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-4">
                                {media.map((m) => (
                                    <div key={m.id} className="group relative rounded-lg border border-gray-200 dark:border-slate-600 overflow-hidden bg-gray-50 dark:bg-slate-700/50">
                                        <div className="aspect-square flex items-center justify-center p-2">
                                            {m.file_type === 'image' && m.url ? (
                                                <img src={m.url} alt={m.name} className="max-w-full max-h-full object-contain" />
                                            ) : (
                                                <FileText className="h-12 w-12 text-gray-400" />
                                            )}
                                        </div>
                                        <div className="p-2 text-xs truncate" title={m.name}>{m.name}</div>
                                        <div className="px-2 pb-2 text-xs text-gray-500">{formatSize(m.file_size)}</div>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="absolute top-1 right-1 opacity-0 group-hover:opacity-100 bg-white/90 dark:bg-slate-800/90 text-red-600 h-7 w-7 p-0"
                                            onClick={() => { if (confirm('Supprimer ce fichier ?')) router.delete(route('ecommerce.cms.media.destroy', m.id)); }}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <CmsMediaDrawer isOpen={drawerOpen} onClose={() => setDrawerOpen(false)} />
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="media" />
        </AppLayout>
    );
}
