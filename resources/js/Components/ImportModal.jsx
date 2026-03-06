import React, { useRef } from 'react';
import Modal from '@/Components/Modal';
import { Button } from '@/Components/ui/button';
import { Download, FileSpreadsheet, Cloud, Upload, X } from 'lucide-react';

/**
 * Modal d'import réutilisable, conforme au design standard.
 *
 * @param {Object} props
 * @param {boolean} props.show - Afficher le modal
 * @param {Function} props.onClose - Fermer le modal
 * @param {string} props.title - Titre du modal
 * @param {string[]} props.summaryItems - Règles du Résumé rapide (liste à puces)
 * @param {Array<{label: string, values: Record<string, string>}>} props.examples - Exemples de lignes valides
 * @param {string} props.templateUrl - URL du modèle à télécharger
 * @param {string} props.accept - Types de fichiers acceptés (ex: ".xlsx,.csv,.txt")
 * @param {File|null} props.file - Fichier sélectionné
 * @param {Function} props.onFileChange - Callback quand un fichier est sélectionné
 * @param {Function} props.onGeneratePreview - Callback pour générer l'aperçu (ignoré si directImport)
 * @param {boolean} props.previewLoading - Chargement en cours
 * @param {Object|null} props.preview - Données d'aperçu (total, valid, invalid, sample, errors)
 * @param {Function} props.onConfirmImport - Callback pour confirmer l'import
 * @param {boolean} props.confirmingImport - Import en cours
 * @param {React.ReactNode} [props.previewContent] - Contenu personnalisé pour la zone d'aperçu
 * @param {boolean} [props.directImport] - Si true, import direct sans aperçu (bouton "Importer" au lieu de "Générer l'aperçu")
 */
export default function ImportModal({
    show,
    onClose,
    title = "Importer",
    summaryItems = [],
    examples = [],
    templateUrl,
    accept = ".xlsx,.csv,.txt",
    file,
    onFileChange,
    onGeneratePreview,
    previewLoading = false,
    preview = null,
    onConfirmImport,
    confirmingImport = false,
    previewContent,
    directImport = false,
}) {
    const fileInputRef = useRef(null);

    const handleClose = () => {
        onClose();
    };

    const handleFileClick = () => {
        fileInputRef.current?.click();
    };

    const handleFileInputChange = (e) => {
        const f = e.target.files?.[0] || null;
        onFileChange(f);
        e.target.value = '';
    };

    const handleGeneratePreviewSubmit = (e) => {
        e.preventDefault();
        if (directImport && file && onConfirmImport) {
            onConfirmImport();
        } else if (file && onGeneratePreview) {
            onGeneratePreview(e);
        }
    };

    const canConfirm = directImport ? (!!file && !confirmingImport) : (preview && preview.valid > 0 && !confirmingImport);

    return (
        <Modal show={show} onClose={handleClose} maxWidth="2xl">
            <div className="p-6">
                {/* Header */}
                <div className="flex justify-between items-start mb-6">
                    <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                        {title}
                    </h3>
                    <button
                        type="button"
                        onClick={handleClose}
                        className="rounded-md text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                {/* Résumé rapide */}
                {summaryItems.length > 0 && (
                    <div className="mb-6">
                        <h4 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Résumé rapide
                        </h4>
                        <ul className="text-sm text-gray-600 dark:text-gray-400 space-y-1 list-disc list-inside">
                            {summaryItems.map((item, idx) => (
                                <li key={idx}>{item}</li>
                            ))}
                        </ul>
                    </div>
                )}

                {/* Exemples de lignes valides */}
                {examples.length > 0 && (
                    <div className="mb-6">
                        <h4 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                            Exemples de lignes valides
                        </h4>
                        <div className="space-y-2">
                            {examples.map((ex, idx) => (
                                <div
                                    key={idx}
                                    className="text-sm text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-slate-800/50 rounded-md px-3 py-2 font-mono"
                                >
                                    {Object.entries(ex.values || ex).map(([k, v]) => (
                                        <span key={k}>
                                            <span className="text-amber-600 dark:text-amber-400">{k}</span>
                                            <span className="text-gray-400 dark:text-gray-500"> = </span>
                                            <span className="text-emerald-600 dark:text-emerald-400">&quot;{v}&quot;</span>
                                            {' '}
                                        </span>
                                    ))}
                                </div>
                            ))}
                        </div>
                    </div>
                )}

                {/* Boutons d'action */}
                <div className="space-y-4">
                    <div className="flex flex-wrap items-center gap-3">
                        {templateUrl && (
                            <Button
                                type="button"
                                onClick={() => window.open(templateUrl, '_blank')}
                                className="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white"
                            >
                                <Download className="h-4 w-4 shrink-0" />
                                Télécharger le modèle
                            </Button>
                        )}
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleFileClick}
                            className="inline-flex items-center gap-2 border-blue-300 text-blue-700 hover:bg-blue-50 dark:border-blue-700 dark:text-blue-300 dark:hover:bg-blue-900/20"
                        >
                            <FileSpreadsheet className="h-4 w-4 shrink-0" />
                            Choisir un fichier Excel
                        </Button>
                        <input
                            ref={fileInputRef}
                            type="file"
                            accept={accept}
                            onChange={handleFileInputChange}
                            className="hidden"
                        />
                    </div>

                    <form onSubmit={handleGeneratePreviewSubmit} className="flex justify-center">
                        <Button
                            type="submit"
                            variant="outline"
                            size="lg"
                            disabled={!file || previewLoading || confirmingImport}
                            className="inline-flex items-center gap-2 px-6"
                        >
                            {previewLoading || confirmingImport ? (
                                <>
                                    <span className="h-4 w-4 shrink-0 animate-spin rounded-full border-2 border-gray-300 border-t-blue-600" />
                                    {directImport ? 'Import en cours...' : 'Analyse en cours...'}
                                </>
                            ) : directImport ? (
                                <>
                                    <Upload className="h-4 w-4 shrink-0" />
                                    Importer
                                </>
                            ) : (
                                <>
                                    <Cloud className="h-4 w-4 shrink-0" />
                                    Générer l&apos;aperçu
                                </>
                            )}
                        </Button>
                    </form>
                </div>

                {/* Zone d'aperçu (masquée en mode directImport) */}
                {!directImport && preview && (
                    <div className="mt-6 space-y-4 pt-4 border-t border-gray-200 dark:border-slate-700">
                        {previewContent ? (
                            previewContent
                        ) : (
                            <>
                                <div className="flex flex-wrap items-center gap-4 text-sm">
                                    <span className="flex items-center gap-1 text-gray-700 dark:text-gray-200">
                                        Total lignes : <strong>{preview.total}</strong>
                                    </span>
                                    <span className="flex items-center gap-1 text-green-600 dark:text-green-400">
                                        Valides : <strong>{preview.valid}</strong>
                                    </span>
                                    <span className="flex items-center gap-1 text-red-600 dark:text-red-400">
                                        En erreur : <strong>{preview.invalid}</strong>
                                    </span>
                                </div>
                                {preview.sample?.header?.length > 0 && (
                                    <div className="border border-gray-200 dark:border-slate-700 rounded-lg overflow-x-auto max-h-64">
                                        <table className="min-w-full text-xs">
                                            <thead className="bg-gray-50 dark:bg-slate-800">
                                                <tr>
                                                    {preview.sample.header.map((h, idx) => (
                                                        <th key={idx} className="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">
                                                            {h || `Col ${idx + 1}`}
                                                        </th>
                                                    ))}
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-slate-700">
                                                {preview.sample.rows?.map((row, rIdx) => (
                                                    <tr key={rIdx} className="bg-white dark:bg-slate-900">
                                                        {row.map((cell, cIdx) => (
                                                            <td key={cIdx} className="px-3 py-1 text-gray-700 dark:text-gray-200">
                                                                {cell}
                                                            </td>
                                                        ))}
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                )}
                                {preview.errors?.length > 0 && (
                                    <div className="border border-red-200 dark:border-red-700 rounded-lg p-3 max-h-40 overflow-y-auto bg-red-50 dark:bg-red-900/20">
                                        <div className="flex items-center gap-2 mb-2 text-sm font-semibold text-red-700 dark:text-red-300">
                                            Lignes en erreur (non importées)
                                        </div>
                                        <ul className="text-xs text-red-700 dark:text-red-300 space-y-1">
                                            {preview.errors.map((err, idx) => (
                                                <li key={idx}>
                                                    {err.line && <strong>Ligne {err.line} :</strong>}{' '}
                                                    {err.field && <span>[{err.field}] </span>}
                                                    {err.message}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                )}

                {/* Footer */}
                <div className="mt-6 pt-4 flex justify-end items-center gap-3 border-t border-gray-200 dark:border-slate-700">
                    <Button type="button" variant="ghost" onClick={handleClose} className="text-blue-600 hover:text-blue-700 dark:text-blue-400">
                        Fermer
                    </Button>
                    {!directImport && (
                        <Button
                            type="button"
                            disabled={!canConfirm}
                            onClick={onConfirmImport}
                            className={canConfirm ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'opacity-50 cursor-not-allowed'}
                        >
                            {confirmingImport ? "Import en cours..." : "Confirmer l'import"}
                        </Button>
                    )}
                </div>
            </div>
        </Modal>
    );
}
