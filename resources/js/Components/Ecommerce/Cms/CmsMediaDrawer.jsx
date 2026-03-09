import { useState, useRef } from 'react';
import { router } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Upload, HelpCircle } from 'lucide-react';
import CmsHelpModal from './CmsHelpModal';

export default function CmsMediaDrawer({ isOpen, onClose }) {
    const [file, setFile] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [helpOpen, setHelpOpen] = useState(false);
    const fileInputRef = useRef(null);

    const handleFileChange = (e) => {
        setFile(e.target.files?.[0] || null);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        if (!file) return;
        setProcessing(true);
        router.post(route('ecommerce.cms.media.store'), { file }, {
            onFinish: () => setProcessing(false),
            onSuccess: () => {
                setFile(null);
                fileInputRef.current && (fileInputRef.current.value = '');
                onClose();
            },
        });
    };

    const handleClose = () => {
        setFile(null);
        fileInputRef.current && (fileInputRef.current.value = '');
        onClose();
    };

    return (
        <>
            <Drawer isOpen={isOpen} onClose={handleClose} title="Ajouter un fichier" size="md">
                <div className="flex justify-end mb-2">
                    <Button type="button" variant="ghost" size="sm" onClick={() => setHelpOpen(true)} className="text-amber-600">
                        <HelpCircle className="h-4 w-4 mr-1" /> Tutoriel
                    </Button>
                </div>
                <form onSubmit={handleSubmit} className="space-y-4">
                    <div>
                        <Label>Fichier *</Label>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept="image/*,.pdf,.doc,.docx,.txt"
                            onChange={handleFileChange}
                            className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200"
                        />
                        <p className="text-xs text-gray-500 mt-1">Images, PDF, documents. Max 10 Mo.</p>
                        {file && <p className="text-sm text-gray-600 mt-1">Sélectionné : {file.name}</p>}
                    </div>
                    <div className="flex gap-2 pt-4 border-t">
                        <Button type="button" variant="outline" onClick={handleClose}>Annuler</Button>
                        <Button type="submit" disabled={!file || processing}>{processing ? 'Upload...' : 'Uploader'}</Button>
                    </div>
                </form>
            </Drawer>
            <CmsHelpModal show={helpOpen} onClose={() => setHelpOpen(false)} module="media" />
        </>
    );
}
