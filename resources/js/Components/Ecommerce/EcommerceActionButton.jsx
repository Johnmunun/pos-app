/**
 * Bouton d'action pour l'en-tête E-commerce.
 * Sur mobile : icône uniquement (avec title pour l'accessibilité)
 * Sur desktop : icône + texte
 */
import { forwardRef } from 'react';
import { Button } from '@/Components/ui/button';

const EcommerceActionButton = forwardRef(({ icon: Icon, label, className = '', ...props }, ref) => (
    <Button
        ref={ref}
        size="sm"
        className={`inline-flex items-center justify-center gap-2 p-2 sm:px-3 sm:py-2 min-w-[36px] sm:min-w-0 ${className}`}
        title={label}
        {...props}
    >
        {Icon && <Icon className="h-4 w-4 shrink-0" />}
        <span className="hidden sm:inline">{label}</span>
    </Button>
));

EcommerceActionButton.displayName = 'EcommerceActionButton';

export default EcommerceActionButton;
