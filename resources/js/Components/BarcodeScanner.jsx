import React, { useState, useRef, useEffect } from 'react';
import { Html5Qrcode } from 'html5-qrcode';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Camera, X } from 'lucide-react';
import { toast } from 'react-hot-toast';

/**
 * Composant de scan de code-barres réutilisable
 * @param {Object} props
 * @param {string} props.value - Valeur actuelle du code-barres
 * @param {Function} props.onChange - Callback appelé quand un code est scanné
 * @param {string} props.id - ID pour l'input
 * @param {string} props.placeholder - Placeholder pour l'input
 * @param {boolean} props.buttonOnly - Si true, affiche uniquement le bouton scanner (pas d'input)
 */
export default function BarcodeScanner({ value, onChange, id, placeholder = "Code-barres", buttonOnly = false }) {
    const [isScanning, setIsScanning] = useState(false);
    const [scanner, setScanner] = useState(null);
    const scannerRef = useRef(null);
    const html5QrCodeRef = useRef(null);

    useEffect(() => {
        return () => {
            // Nettoyer le scanner lors du démontage
            if (html5QrCodeRef.current) {
                html5QrCodeRef.current.stop().catch(() => {});
            }
        };
    }, []);

    const startScanning = async () => {
        try {
            const scannerId = `scanner-${id}`;
            const html5QrCode = new Html5Qrcode(scannerId);
            html5QrCodeRef.current = html5QrCode;

            await html5QrCode.start(
                { facingMode: "environment" }, // Utiliser la caméra arrière
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 },
                    aspectRatio: 1.0,
                },
                (decodedText) => {
                    // Code scanné avec succès
                    onChange(decodedText);
                    stopScanning();
                    toast.success('Code-barres scanné avec succès');
                },
                (errorMessage) => {
                    // Ignorer les erreurs de scan continu (normal pendant la recherche)
                }
            );

            setIsScanning(true);
            setScanner(html5QrCode);
        } catch (err) {
            console.error('Erreur lors du démarrage du scanner:', err);
            toast.error('Impossible d\'accéder à la caméra. Vérifiez les permissions.');
        }
    };

    const stopScanning = async () => {
        if (html5QrCodeRef.current) {
            try {
                await html5QrCodeRef.current.stop();
                html5QrCodeRef.current.clear();
            } catch (err) {
                console.error('Erreur lors de l\'arrêt du scanner:', err);
            }
            html5QrCodeRef.current = null;
        }
        setIsScanning(false);
        setScanner(null);
    };

    return (
        <div className={buttonOnly ? "" : "space-y-2"}>
            <div className={buttonOnly ? "" : "flex gap-2"}>
                {!buttonOnly && (
                    <div className="relative flex-1">
                        <Input
                            id={id}
                            type="text"
                            value={value || ''}
                            onChange={(e) => onChange(e.target.value)}
                            placeholder={placeholder}
                            className="w-full"
                        />
                    </div>
                )}
                {!isScanning ? (
                    <Button
                        type="button"
                        variant="outline"
                        size={buttonOnly ? "icon" : "sm"}
                        onClick={startScanning}
                        className={buttonOnly ? "" : "whitespace-nowrap"}
                        title="Scanner un code-barres"
                    >
                        <Camera className="h-4 w-4" />
                        {!buttonOnly && <span className="ml-1">Scanner</span>}
                    </Button>
                ) : (
                    <Button
                        type="button"
                        variant="outline"
                        size={buttonOnly ? "icon" : "sm"}
                        onClick={stopScanning}
                        className={`${buttonOnly ? "" : "whitespace-nowrap"} bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30`}
                        title="Arrêter le scan"
                    >
                        <X className="h-4 w-4" />
                        {!buttonOnly && <span className="ml-1">Arrêter</span>}
                    </Button>
                )}
            </div>
            {isScanning && (
                <div className={`relative ${buttonOnly ? "fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4" : ""}`}>
                    <div className={`${buttonOnly ? "bg-white dark:bg-slate-900 rounded-lg p-4 max-w-md w-full" : ""}`}>
                        <div
                            id={`scanner-${id}`}
                            ref={scannerRef}
                            className={`${buttonOnly ? "w-full" : "w-full max-w-md mx-auto"} rounded-lg overflow-hidden border-2 border-amber-500`}
                        />
                        <p className="text-xs text-center text-gray-600 dark:text-gray-400 mt-2">
                            Pointez la caméra vers le code-barres
                        </p>
                        {buttonOnly && (
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={stopScanning}
                                className="w-full mt-4"
                            >
                                <X className="h-4 w-4 mr-1" />
                                Fermer
                            </Button>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
