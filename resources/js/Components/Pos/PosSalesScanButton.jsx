import React from 'react';
import { Button } from '@/Components/ui/button';
import { Camera } from 'lucide-react';

/**
 * Bouton d’ouverture du scanner caméra (pages de vente).
 */
export default function PosSalesScanButton({ onClick, className = '' }) {
    return (
        <Button
            type="button"
            variant="outline"
            size="icon"
            onClick={onClick}
            className={`bg-teal-600 hover:bg-teal-700 text-white border-0 shrink-0 ${className}`}
            title="Scanner un produit (caméra)"
            aria-label="Scanner un produit"
        >
            <Camera className="h-4 w-4" />
        </Button>
    );
}
