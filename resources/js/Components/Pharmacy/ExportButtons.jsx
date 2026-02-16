import { useState } from 'react';
import { Button } from '@/Components/ui/button';
import { 
    FileText, 
    FileSpreadsheet, 
    Download, 
    Loader2,
    ChevronDown
} from 'lucide-react';

/**
 * Composant réutilisable pour les boutons d'export PDF et Excel.
 * 
 * @param {Object} props
 * @param {string} props.pdfUrl - URL de l'export PDF
 * @param {string} props.excelUrl - URL de l'export Excel
 * @param {boolean} props.disabled - Désactiver les boutons
 * @param {string} props.variant - 'default' | 'compact' | 'dropdown'
 * @param {string} props.className - Classes CSS additionnelles
 */
export default function ExportButtons({ 
    pdfUrl, 
    excelUrl, 
    disabled = false,
    variant = 'default',
    className = ''
}) {
    const [loading, setLoading] = useState(null); // 'pdf' | 'excel' | null
    const [dropdownOpen, setDropdownOpen] = useState(false);

    const handleExport = async (type, url) => {
        if (!url || disabled) return;
        
        setLoading(type);
        try {
            // Ouvrir dans un nouvel onglet pour déclencher le téléchargement
            window.open(url, '_blank');
        } finally {
            // Petit délai pour UX
            setTimeout(() => setLoading(null), 1000);
        }
    };

    // Variant compact (icônes seulement)
    if (variant === 'compact') {
        return (
            <div className={`flex items-center gap-1 ${className}`}>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleExport('pdf', pdfUrl)}
                    disabled={disabled || !pdfUrl || loading === 'pdf'}
                    className="h-8 w-8 p-0"
                    title="Exporter PDF"
                >
                    {loading === 'pdf' ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <FileText className="h-4 w-4 text-red-500" />
                    )}
                </Button>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => handleExport('excel', excelUrl)}
                    disabled={disabled || !excelUrl || loading === 'excel'}
                    className="h-8 w-8 p-0"
                    title="Exporter Excel"
                >
                    {loading === 'excel' ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                    ) : (
                        <FileSpreadsheet className="h-4 w-4 text-green-600" />
                    )}
                </Button>
            </div>
        );
    }

    // Variant dropdown
    if (variant === 'dropdown') {
        return (
            <div className={`relative ${className}`}>
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setDropdownOpen(!dropdownOpen)}
                    disabled={disabled}
                    className="gap-2"
                >
                    <Download className="h-4 w-4" />
                    <span className="hidden sm:inline">Exporter</span>
                    <ChevronDown className="h-3 w-3" />
                </Button>
                
                {dropdownOpen && (
                    <>
                        <div 
                            className="fixed inset-0 z-10" 
                            onClick={() => setDropdownOpen(false)}
                        />
                        <div className="absolute right-0 mt-1 w-48 bg-white dark:bg-slate-800 rounded-lg shadow-lg border border-gray-200 dark:border-slate-700 z-20 py-1">
                            <button
                                onClick={() => {
                                    handleExport('pdf', pdfUrl);
                                    setDropdownOpen(false);
                                }}
                                disabled={!pdfUrl || loading === 'pdf'}
                                className="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center gap-2 disabled:opacity-50"
                            >
                                {loading === 'pdf' ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <FileText className="h-4 w-4 text-red-500" />
                                )}
                                Exporter PDF
                            </button>
                            <button
                                onClick={() => {
                                    handleExport('excel', excelUrl);
                                    setDropdownOpen(false);
                                }}
                                disabled={!excelUrl || loading === 'excel'}
                                className="w-full px-4 py-2 text-left text-sm hover:bg-gray-100 dark:hover:bg-slate-700 flex items-center gap-2 disabled:opacity-50"
                            >
                                {loading === 'excel' ? (
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                ) : (
                                    <FileSpreadsheet className="h-4 w-4 text-green-600" />
                                )}
                                Exporter Excel
                            </button>
                        </div>
                    </>
                )}
            </div>
        );
    }

    // Variant default (boutons complets)
    return (
        <div className={`flex items-center gap-2 ${className}`}>
            <Button
                variant="outline"
                size="sm"
                onClick={() => handleExport('pdf', pdfUrl)}
                disabled={disabled || !pdfUrl || loading === 'pdf'}
                className="gap-2 border-red-200 hover:bg-red-50 hover:border-red-300 dark:border-red-800 dark:hover:bg-red-900/20"
            >
                {loading === 'pdf' ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <FileText className="h-4 w-4 text-red-500" />
                )}
                <span className="hidden sm:inline">PDF</span>
            </Button>
            <Button
                variant="outline"
                size="sm"
                onClick={() => handleExport('excel', excelUrl)}
                disabled={disabled || !excelUrl || loading === 'excel'}
                className="gap-2 border-green-200 hover:bg-green-50 hover:border-green-300 dark:border-green-800 dark:hover:bg-green-900/20"
            >
                {loading === 'excel' ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                ) : (
                    <FileSpreadsheet className="h-4 w-4 text-green-600" />
                )}
                <span className="hidden sm:inline">Excel</span>
            </Button>
        </div>
    );
}
