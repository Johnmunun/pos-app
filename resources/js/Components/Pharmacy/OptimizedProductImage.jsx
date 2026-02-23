import { useState, useEffect, useRef } from 'react';
import { Package } from 'lucide-react';

/**
 * Composant optimisé pour afficher les images produits
 * 
 * Fonctionnalités :
 * - Lazy loading pour améliorer les performances
 * - Gestion d'erreur avec fallback
 * - Compression côté serveur déjà appliquée
 * - Support du cache navigateur
 */
export default function OptimizedProductImage({
    src,
    alt,
    className = '',
    size = 'medium', // 'small' (40px), 'medium' (128px), 'large' (256px)
    showPlaceholder = true,
    ...props
}) {
    const [imageSrc, setImageSrc] = useState<string | null>(null);
    const [hasError, setHasError] = useState(false);
    const [isLoading, setIsLoading] = useState(true);
    const imgRef = useRef<HTMLImageElement>(null);

    // Tailles prédéfinies pour optimiser le chargement
    const sizeClasses = {
        small: 'h-10 w-10',
        medium: 'h-32 w-32',
        large: 'h-64 w-64',
    };

    useEffect(() => {
        if (!src) {
            setIsLoading(false);
            return;
        }

        // Réinitialiser l'état lors du changement de src
        setHasError(false);
        setIsLoading(true);
        setImageSrc(src);
    }, [src]);

    const handleError = () => {
        setHasError(true);
        setIsLoading(false);
        setImageSrc(null);
    };

    const handleLoad = () => {
        setIsLoading(false);
    };

    // Si pas d'image ou erreur, afficher le placeholder
    if (!src || hasError) {
        if (!showPlaceholder) return null;
        
        return (
            <div className={`${sizeClasses[size]} ${className} rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center`}>
                <Package className={`${size === 'small' ? 'h-6 w-6' : size === 'medium' ? 'h-16 w-16' : 'h-32 w-32'} text-gray-400 dark:text-gray-500`} />
            </div>
        );
    }

    return (
        <div className={`${sizeClasses[size]} ${className} rounded-lg overflow-hidden bg-gray-100 dark:bg-gray-700 relative`}>
            {isLoading && (
                <div className="absolute inset-0 flex items-center justify-center bg-gray-100 dark:bg-gray-700">
                    <div className="animate-pulse">
                        <Package className={`${size === 'small' ? 'h-6 w-6' : size === 'medium' ? 'h-16 w-16' : 'h-32 w-32'} text-gray-300 dark:text-gray-600`} />
                    </div>
                </div>
            )}
            <img
                ref={imgRef}
                src={imageSrc || undefined}
                alt={alt || 'Product image'}
                className={`absolute inset-0 ${sizeClasses[size]} object-cover ${isLoading ? 'opacity-0' : 'opacity-100'} transition-opacity duration-200`}
                loading="lazy"
                decoding="async"
                onError={handleError}
                onLoad={handleLoad}
                {...props}
            />
        </div>
    );
}
