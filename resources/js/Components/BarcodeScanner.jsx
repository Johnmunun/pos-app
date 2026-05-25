import React, { useState } from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Camera } from 'lucide-react';
import PosBarcodeScannerModal from '@/Components/Pos/PosBarcodeScannerModal';

/**
 * Champ code-barres avec scan caméra (fiches produit) ou bouton seul (POS via onCameraClick).
 */
export default function BarcodeScanner({
    value,
    onChange,
    id,
    placeholder = 'Code-barres',
    buttonOnly = false,
    onCameraClick,
}) {
    const [modalOpen, setModalOpen] = useState(false);

    const openCamera = () => {
        if (typeof onCameraClick === 'function') {
            onCameraClick();
            return;
        }
        setModalOpen(true);
    };

    const handleScan = (code) => {
        onChange(code);
        setModalOpen(false);
    };

    return (
        <>
            <div className={buttonOnly ? '' : 'space-y-2'}>
                <div className={buttonOnly ? '' : 'flex gap-2'}>
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
                    <Button
                        type="button"
                        variant="outline"
                        size={buttonOnly ? 'icon' : 'sm'}
                        onClick={openCamera}
                        className={buttonOnly ? '' : 'whitespace-nowrap'}
                        title="Scanner un code-barres"
                    >
                        <Camera className="h-4 w-4" />
                        {!buttonOnly && <span className="ml-1">Scanner</span>}
                    </Button>
                </div>
            </div>

            {!onCameraClick && (
                <PosBarcodeScannerModal
                    open={modalOpen}
                    onClose={() => setModalOpen(false)}
                    onScan={handleScan}
                    title="Scanner le code-barres"
                />
            )}
        </>
    );
}
