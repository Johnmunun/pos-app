import { useState, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import Drawer from '@/Components/Drawer';
import { Button } from '@/Components/ui/button';
import { Label } from '@/Components/ui/label';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { Upload, HelpCircle, Sparkles, Image as ImageIcon } from 'lucide-react';
import CmsHelpModal from './CmsHelpModal';
import axios from 'axios';
import { toast } from 'react-hot-toast';

export default function CmsMediaDrawer({ isOpen, onClose }) {
    const { props } = usePage();
    const canAiGenerate = props.auth?.planFeatures?.ai_media_image_generate === true;
    const aiMediaUsage = props.auth?.planUsage?.ai_media_image_generate || { used: 0, limit: null, remaining: null };
    const [file, setFile] = useState(null);
    const [aiTitle, setAiTitle] = useState('');
    const [aiDescription, setAiDescription] = useState('');
    const [aiPreview, setAiPreview] = useState(null);
    const [aiCandidates, setAiCandidates] = useState([]);
    const [aiGenerating, setAiGenerating] = useState(false);
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
        setAiPreview(null);
        setAiCandidates([]);
        setAiTitle('');
        setAiDescription('');
        fileInputRef.current && (fileInputRef.current.value = '');
        onClose();
    };

    const fileFromDataUrl = async (item, idx) => {
        const blob = await (await fetch(item.image_data_url)).blob();
        return new File([blob], item.file_name || `cms-ai-image-${idx + 1}.png`, { type: blob.type || 'image/png' });
    };

    const selectAiCandidate = async (item, idx = 0) => {
        if (!item?.image_data_url) return;
        const generated = await fileFromDataUrl(item, idx);
        setFile(generated);
        setAiPreview(item.image_data_url);
        fileInputRef.current && (fileInputRef.current.value = '');
    };

    const handleGenerateAiImage = async () => {
        if (!canAiGenerate) return;
        if (!String(aiTitle || '').trim()) {
            toast.error('Saisissez un titre pour générer une image IA.');
            return;
        }
        setAiGenerating(true);
        try {
            const res = await axios.post(route('ecommerce.cms.media.ai.generate-image'), {
                title: aiTitle,
                description: aiDescription || '',
                count: 4,
                async: true,
            });
            const requestId = res.data?.request_id;
            if (!requestId) {
                throw new Error('Requête IA invalide');
            }
            const pollStatus = async () => {
                for (let i = 0; i < 60; i += 1) {
                    const statusRes = await axios.get(route('ecommerce.cms.media.ai.generate-image.status', requestId));
                    const st = String(statusRes.data?.status || '').toLowerCase();
                    if (st === 'completed') return statusRes.data;
                    if (st === 'failed') throw new Error(statusRes.data?.error_message || 'Génération IA échouée.');
                    await new Promise((resolve) => setTimeout(resolve, 1000));
                }
                throw new Error('Génération IA trop longue, veuillez réessayer.');
            };
            const done = await pollStatus();
            const images = Array.isArray(done?.images) && done.images.length > 0
                ? done.images
                : [{ image_data_url: done?.image_data_url, file_name: done?.file_name || 'cms-ai-image.png' }];
            const validImages = images.filter((img) => !!img?.image_data_url).slice(0, 4);
            if (validImages.length === 0) {
                throw new Error('Réponse IA invalide');
            }
            setAiCandidates(validImages);
            await selectAiCandidate(validImages[0], 0);
            toast.success(`${validImages.length} image(s) IA générée(s). Choisissez celle à uploader.`);
        } catch (err) {
            toast.error(err.response?.data?.message || err.message || 'Génération IA impossible.');
        } finally {
            setAiGenerating(false);
        }
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
                    {canAiGenerate && (
                        <div className="rounded-md border border-amber-200 bg-amber-50/60 p-3 space-y-2">
                            <p className="text-sm font-medium text-amber-900">Génération image IA (Pro/Enterprise)</p>
                            <Input
                                value={aiTitle}
                                onChange={(e) => setAiTitle(e.target.value)}
                                placeholder="Titre de l'image (ex: bannière promo savon bio)"
                            />
                            <Textarea
                                value={aiDescription}
                                onChange={(e) => setAiDescription(e.target.value)}
                                rows={2}
                                placeholder="Description optionnelle (style, couleurs, ambiance...)"
                            />
                            <Button type="button" variant="secondary" size="sm" onClick={handleGenerateAiImage} disabled={aiGenerating}>
                                <Sparkles className="h-4 w-4 mr-2" />
                                {aiGenerating ? 'Génération...' : 'Générer 4 images IA'}
                            </Button>
                            <p className="text-[11px] text-gray-600">
                                Quota image Media IA: {aiMediaUsage.limit == null
                                    ? `utilisé ${aiMediaUsage.used} (illimité)`
                                    : `${aiMediaUsage.used}/${aiMediaUsage.limit} (reste ${Math.max(0, aiMediaUsage.remaining ?? 0)})`}
                            </p>
                            {aiCandidates.length > 1 && (
                                <div>
                                    <p className="text-xs text-gray-600 mb-2">Choisir une variante :</p>
                                    <div className="flex gap-2 flex-wrap">
                                        {aiCandidates.map((img, idx) => (
                                            <button
                                                key={`${img.file_name || 'ai'}-${idx}`}
                                                type="button"
                                                onClick={() => selectAiCandidate(img, idx)}
                                                className="h-14 w-14 rounded border overflow-hidden hover:border-amber-500"
                                                title={`Variante ${idx + 1}`}
                                            >
                                                <img src={img.image_data_url} alt={`Variante IA ${idx + 1}`} className="h-full w-full object-cover" />
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            )}
                            {aiPreview && (
                                <div className="h-24 w-24 rounded border overflow-hidden bg-white">
                                    <img src={aiPreview} alt="Aperçu IA" className="h-full w-full object-cover" />
                                </div>
                            )}
                        </div>
                    )}
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
                        {file && (
                            <p className="text-sm text-gray-600 mt-1 inline-flex items-center gap-1">
                                <ImageIcon className="h-4 w-4" /> Sélectionné : {file.name}
                            </p>
                        )}
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
