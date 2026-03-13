import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { Image as ImageIcon, MonitorSmartphone, Printer, Trash2, UploadCloud } from 'lucide-react';

export default function Branding() {
    const { props } = usePage();
    const initialLogoUrl = props.appLogoUrl || props.appLogoUrl === '' ? props.appLogoUrl : props.appLogoUrl;
    const initialHeroMainUrl = props.heroMainUrl || null;
    const initialHeroDevicesUrl = props.heroDevicesUrl || null;

    const [previewUrl, setPreviewUrl] = useState(initialLogoUrl || null);
    const [heroMainPreviewUrl, setHeroMainPreviewUrl] = useState(initialHeroMainUrl || null);
    const [heroDevicesPreviewUrl, setHeroDevicesPreviewUrl] = useState(initialHeroDevicesUrl || null);

    const fileInputRef = useRef(null);
    const heroMainInputRef = useRef(null);
    const heroDevicesInputRef = useRef(null);

    const { data, setData, post, processing, reset } = useForm({
        app_logo: null,
        remove_logo: false,
        hero_main: null,
        hero_devices: null,
        remove_hero_main: false,
        remove_hero_devices: false,
    });

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setData('app_logo', file);
        setData('remove_logo', false);

        const reader = new FileReader();
        reader.onload = (event) => {
            setPreviewUrl(event.target.result);
        };
        reader.readAsDataURL(file);
    };

    const handleHeroChange = (e, field, setPreview) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setData(field, file);
        if (field === 'hero_main') {
            setData('remove_hero_main', false);
        } else if (field === 'hero_devices') {
            setData('remove_hero_devices', false);
        }

        const reader = new FileReader();
        reader.onload = (event) => {
            setPreview(event.target.result);
        };
        reader.readAsDataURL(file);
    };

    const handleRemove = () => {
        setData('app_logo', null);
        setData('remove_logo', true);
        setPreviewUrl(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    };

    const handleRemoveHero = (field, setPreview, inputRef) => {
        setData(field, null);
        if (field === 'hero_main') {
            setData('remove_hero_main', true);
        } else if (field === 'hero_devices') {
            setData('remove_hero_devices', true);
        }
        setPreview(null);
        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('admin.branding.update'), {
            forceFormData: true,
            onSuccess: () => {
                reset('app_logo');
            },
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                            Branding application
                        </h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Téléchargez le logo global d&apos;OmniPOS utilisé dans l&apos;interface.
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Branding OmniPOS" />

            <div className="py-6">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 space-y-8">
                    <form
                        onSubmit={handleSubmit}
                        className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6 space-y-8"
                    >
                        {/* Logo application */}
                        <section className="space-y-4">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    Logo de l&apos;application
                                </h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Formats acceptés: PNG, JPG, WebP. Taille recommandée: 256×256px, fond transparent si possible.
                                </p>
                            </div>

                            <div className="grid gap-6 md:grid-cols-[2fr,1fr] items-start">
                                {/* Zone d'upload */}
                                <div>
                                    <div
                                        className="relative flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl px-6 py-10 bg-gray-50 dark:bg-gray-900/40 text-center cursor-pointer hover:border-amber-500 hover:bg-amber-50/40 dark:hover:border-amber-500 dark:hover:bg-amber-900/10 transition-colors"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        <UploadCloud className="h-10 w-10 text-amber-500 mb-3" />
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            Cliquez pour choisir une image
                                        </p>
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            ou glissez-déposez un fichier ici
                                        </p>
                                        <p className="mt-3 text-xs text-gray-400 dark:text-gray-500">
                                            Max 2 Mo · PNG, JPG, WebP
                                        </p>
                                        <input
                                            ref={fileInputRef}
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                            className="hidden"
                                            onChange={handleFileChange}
                                        />
                                    </div>
                                </div>

                                {/* Aperçu */}
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2">
                                        <ImageIcon className="h-4 w-4 text-gray-400" />
                                        <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Aperçu
                                        </span>
                                    </div>
                                    <div className="aspect-square rounded-xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 flex items-center justify-center overflow-hidden">
                                        {previewUrl ? (
                                            <img
                                                src={previewUrl}
                                                alt="Logo OmniPOS"
                                                className="max-h-full max-w-full object-contain"
                                            />
                                        ) : (
                                            <div className="flex flex-col items-center text-gray-400 text-xs">
                                                <ImageIcon className="h-8 w-8 mb-2" />
                                                <span>Aucun logo sélectionné</span>
                                            </div>
                                        )}
                                    </div>
                                    {previewUrl && (
                                        <button
                                            type="button"
                                            onClick={handleRemove}
                                            className="inline-flex items-center gap-1.5 text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <Trash2 className="h-3 w-3" />
                                            Supprimer le logo
                                        </button>
                                    )}
                                </div>
                            </div>
                        </section>

                        {/* Images Hero landing page */}
                        <section className="space-y-4 border-t border-dashed border-gray-200 dark:border-gray-700 pt-6">
                            <div>
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                    Images Hero de la landing page
                                </h3>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    Ces visuels apparaissent en arrière-plan de la section Hero sur la landing page (POS + terminaux).
                                </p>
                            </div>

                            <div className="grid gap-6 md:grid-cols-2">
                                {/* Hero principal */}
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2">
                                        <MonitorSmartphone className="h-4 w-4 text-gray-400" />
                                        <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Image principale (écran POS)
                                        </span>
                                    </div>
                                    <div
                                        className="relative flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl px-4 py-8 bg-gray-50 dark:bg-gray-900/40 text-center cursor-pointer hover:border-amber-500 hover:bg-amber-50/40 dark:hover:border-amber-500 dark:hover:bg-amber-900/10 transition-colors"
                                        onClick={() => heroMainInputRef.current?.click()}
                                    >
                                        <UploadCloud className="h-8 w-8 text-amber-500 mb-2" />
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            Choisir une image
                                        </p>
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            1440×900px recommandé · PNG/JPG/WebP
                                        </p>
                                        <input
                                            ref={heroMainInputRef}
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                            className="hidden"
                                            onChange={(e) => handleHeroChange(e, 'hero_main', setHeroMainPreviewUrl)}
                                        />
                                    </div>
                                    <div className="aspect-video rounded-xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 flex items-center justify-center overflow-hidden">
                                        {heroMainPreviewUrl ? (
                                            <img
                                                src={heroMainPreviewUrl}
                                                alt="Hero POS principal"
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex flex-col items-center text-gray-400 text-xs px-3 text-center">
                                                <MonitorSmartphone className="h-8 w-8 mb-2" />
                                                <span>Aucune image hero principale sélectionnée</span>
                                            </div>
                                        )}
                                    </div>
                                    {heroMainPreviewUrl && (
                                        <button
                                            type="button"
                                            onClick={() => handleRemoveHero('hero_main', setHeroMainPreviewUrl, heroMainInputRef)}
                                            className="inline-flex items-center gap-1.5 text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <Trash2 className="h-3 w-3" />
                                            Supprimer l&apos;image hero principale
                                        </button>
                                    )}
                                </div>

                                {/* Devices secondaires */}
                                <div className="space-y-3">
                                    <div className="flex items-center gap-2">
                                        <Printer className="h-4 w-4 text-gray-400" />
                                        <span className="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            Image terminaux & imprimantes
                                        </span>
                                    </div>
                                    <div
                                        className="relative flex flex-col items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-xl px-4 py-8 bg-gray-50 dark:bg-gray-900/40 text-center cursor-pointer hover:border-amber-500 hover:bg-amber-50/40 dark:hover:border-amber-500 dark:hover:bg-amber-900/10 transition-colors"
                                        onClick={() => heroDevicesInputRef.current?.click()}
                                    >
                                        <UploadCloud className="h-8 w-8 text-amber-500 mb-2" />
                                        <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            Choisir une image
                                        </p>
                                        <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                            800×600px recommandé · PNG/JPG/WebP
                                        </p>
                                        <input
                                            ref={heroDevicesInputRef}
                                            type="file"
                                            accept="image/png,image/jpeg,image/jpg,image/webp"
                                            className="hidden"
                                            onChange={(e) => handleHeroChange(e, 'hero_devices', setHeroDevicesPreviewUrl)}
                                        />
                                    </div>
                                    <div className="aspect-video rounded-xl border border-dashed border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-900/40 flex items-center justify-center overflow-hidden">
                                        {heroDevicesPreviewUrl ? (
                                            <img
                                                src={heroDevicesPreviewUrl}
                                                alt="Hero POS terminaux"
                                                className="h-full w-full object-cover"
                                            />
                                        ) : (
                                            <div className="flex flex-col items-center text-gray-400 text-xs px-3 text-center">
                                                <Printer className="h-8 w-8 mb-2" />
                                                <span>Aucune image terminaux sélectionnée</span>
                                            </div>
                                        )}
                                    </div>
                                    {heroDevicesPreviewUrl && (
                                        <button
                                            type="button"
                                            onClick={() =>
                                                handleRemoveHero('hero_devices', setHeroDevicesPreviewUrl, heroDevicesInputRef)
                                            }
                                            className="inline-flex items-center gap-1.5 text-xs text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <Trash2 className="h-3 w-3" />
                                            Supprimer l&apos;image terminaux
                                        </button>
                                    )}
                                </div>
                            </div>
                        </section>

                        <div className="flex items-center justify-end gap-3 pt-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center justify-center px-4 py-2 text-sm font-semibold rounded-lg bg-amber-600 text-white hover:bg-amber-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-colors"
                            >
                                {processing ? 'Enregistrement...' : 'Enregistrer le branding'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}

