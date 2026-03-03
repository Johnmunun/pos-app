import React from 'react';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Camera } from 'lucide-react';
import { toast } from 'react-hot-toast';

/**
 * Version fallback du scanner de code-barres pour la production
 * (sans dépendance externe html5-qrcode).
 *
 * On garde l'API du composant (value, onChange, id, placeholder, buttonOnly),
 * mais le bouton affiche simplement un message indiquant que
 * le scan caméra n'est pas encore disponible.
 */
export default function BarcodeScanner({ value, onChange, id, placeholder = 'Code-barres', buttonOnly = false }) {
    const handleClick = () => {
        toast('Le scan par caméra n’est pas encore disponible dans cette version.', {
            icon: '📷',
        });
    };

    return (
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
                    onClick={handleClick}
                    className={buttonOnly ? '' : 'whitespace-nowrap'}
                    title="Scanner un code-barres"
                >
                    <Camera className="h-4 w-4" />
                    {!buttonOnly && <span className="ml-1">Scanner</span>}
                </Button>
            </div>
        </div>
    );
}
