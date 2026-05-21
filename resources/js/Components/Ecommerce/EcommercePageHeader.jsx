/**
 * En-tête responsive pour les pages du module E-commerce (aligné Pharmacie / Commerce).
 * - Titre fort, sous-titre optionnel desktop
 * - Boutons en icônes uniquement sur mobile
 */
export default function EcommercePageHeader({ title, description, icon: Icon, children }) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between w-full min-w-0">
            <div className="min-w-0">
                <h2 className="font-bold text-xl sm:text-2xl text-gray-900 dark:text-white tracking-tight leading-tight truncate">
                    {Icon && (
                        <Icon className="h-5 w-5 sm:h-6 sm:w-6 inline-block mr-2 text-amber-500 align-middle shrink-0" />
                    )}
                    <span className="align-middle">{title}</span>
                </h2>
                {description ? (
                    <p className="text-sm text-gray-500 dark:text-gray-400 mt-1.5 max-w-2xl hidden sm:block">
                        {description}
                    </p>
                ) : null}
            </div>
            <div className="flex items-center gap-1.5 sm:gap-2 shrink-0 flex-wrap sm:justify-end">
                {children}
            </div>
        </div>
    );
}
