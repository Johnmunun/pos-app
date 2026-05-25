import React, { useCallback, useEffect, useId, useRef, useState } from 'react';
import { X, Camera, Loader2 } from 'lucide-react';
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';
import { shouldIgnoreRapidScan } from '@/lib/posProductScan';

const SCAN_DEBOUNCE_MS = 500;

const FORMATS = [
    Html5QrcodeSupportedFormats.QR_CODE,
    Html5QrcodeSupportedFormats.EAN_13,
    Html5QrcodeSupportedFormats.EAN_8,
    Html5QrcodeSupportedFormats.UPC_A,
    Html5QrcodeSupportedFormats.UPC_E,
    Html5QrcodeSupportedFormats.CODE_128,
    Html5QrcodeSupportedFormats.CODE_39,
    Html5QrcodeSupportedFormats.ITF,
];

/**
 * Modal scanner caméra (mobile + desktop avec webcam).
 */
export default function PosBarcodeScannerModal({ open, onClose, onScan, title = 'Scanner un produit' }) {
    const reactId = useId();
    const readerId = `pos-qr-reader-${reactId.replace(/:/g, '')}`;
    const scannerRef = useRef(null);
    const lastScanRef = useRef(null);
    const handledRef = useRef(false);
    const [starting, setStarting] = useState(false);
    const [error, setError] = useState(null);

    const stopScanner = useCallback(async () => {
        const instance = scannerRef.current;
        scannerRef.current = null;
        if (!instance) return;
        try {
            if (instance.isScanning) {
                await instance.stop();
            }
            await instance.clear();
        } catch {
            // ignore cleanup errors
        }
    }, []);

    useEffect(() => {
        if (!open) {
            stopScanner();
            setError(null);
            setStarting(false);
            handledRef.current = false;
            return undefined;
        }

        let cancelled = false;

        const start = async () => {
            setStarting(true);
            setError(null);
            await stopScanner();

            try {
                const html5 = new Html5Qrcode(readerId, { verbose: false });
                scannerRef.current = html5;

                await html5.start(
                    { facingMode: 'environment' },
                    {
                        fps: 10,
                        qrbox: { width: 280, height: 160 },
                        aspectRatio: 1.5,
                        formatsToSupport: FORMATS,
                    },
                    (decodedText) => {
                        if (cancelled || handledRef.current) return;
                        const code = String(decodedText ?? '').trim();
                        if (!code) return;
                        if (shouldIgnoreRapidScan(lastScanRef, code, SCAN_DEBOUNCE_MS)) return;

                        handledRef.current = true;
                        onScan(code);
                        onClose();
                    },
                    () => {},
                );

                if (!cancelled) {
                    setStarting(false);
                }
            } catch (err) {
                if (!cancelled) {
                    setError(
                        err?.message?.includes('NotAllowed')
                            ? 'Accès à la caméra refusé. Autorisez la caméra ou utilisez une douchette USB.'
                            : 'Impossible de démarrer la caméra. Utilisez la recherche ou une douchette.',
                    );
                    setStarting(false);
                }
            }
        };

        const timer = setTimeout(start, 150);

        return () => {
            cancelled = true;
            clearTimeout(timer);
            stopScanner();
        };
    }, [open, readerId, onScan, onClose, stopScanner]);

    if (!open) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-[70] flex items-end sm:items-center justify-center bg-black/60 p-0 sm:p-4"
            role="dialog"
            aria-modal="true"
            aria-label={title}
        >
            <div className="bg-white dark:bg-slate-900 w-full sm:max-w-md rounded-t-2xl sm:rounded-2xl shadow-xl overflow-hidden max-h-[92dvh] flex flex-col">
                <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-slate-700">
                    <div className="flex items-center gap-2 text-gray-900 dark:text-white font-semibold">
                        <Camera className="h-5 w-5 text-teal-600" />
                        {title}
                    </div>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:hover:bg-slate-800"
                        aria-label="Fermer"
                    >
                        <X className="h-5 w-5" />
                    </button>
                </div>

                <div className="p-4 flex-1 min-h-0 overflow-y-auto">
                    {error ? (
                        <p className="text-sm text-red-600 dark:text-red-400 text-center py-6">{error}</p>
                    ) : (
                        <>
                            <p className="text-xs text-gray-500 dark:text-gray-400 text-center mb-3">
                                Placez le code-barres ou le QR Code dans le cadre
                            </p>
                            <div
                                id={readerId}
                                className="w-full overflow-hidden rounded-xl bg-black min-h-[220px] [&>video]:rounded-xl"
                            />
                            {starting && (
                                <div className="flex items-center justify-center gap-2 mt-3 text-sm text-gray-500">
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Démarrage de la caméra…
                                </div>
                            )}
                        </>
                    )}
                </div>

                <div className="p-4 border-t border-gray-200 dark:border-slate-700 pb-[max(1rem,env(safe-area-inset-bottom))]">
                    <button
                        type="button"
                        onClick={onClose}
                        className="w-full py-3 rounded-xl border border-gray-300 dark:border-slate-600 text-gray-700 dark:text-gray-200 font-medium"
                    >
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    );
}
