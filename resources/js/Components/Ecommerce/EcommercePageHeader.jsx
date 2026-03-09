/**
 * En-tête responsive pour les pages du module E-commerce.
 * - Titre plus petit sur mobile
 * - Boutons en icônes uniquement sur mobile, alignés sur une ligne
 */
export default function EcommercePageHeader({ title, icon: Icon, children }) {
    return (
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 className="text-base sm:text-lg md:text-xl font-semibold text-gray-800 dark:text-gray-100 leading-tight truncate min-w-0">
                {Icon && <Icon className="h-4 w-4 sm:h-5 sm:w-5 inline-block mr-2 text-gray-500 dark:text-gray-400 shrink-0" />}
                <span className="align-middle">{title}</span>
            </h2>
            <div className="flex items-center gap-1.5 sm:gap-2 flex-shrink-0 flex-wrap">
                {children}
            </div>
        </div>
    );
}
