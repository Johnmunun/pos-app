import { Warehouse } from 'lucide-react';

/**
 * Bandeau invitant à sélectionner un dépôt quand le tenant a plusieurs dépôts et qu'aucun n'est choisi.
 */
export default function DepotAlert({ depots = [], currentDepot }) {
    if (!depots?.length || depots.length <= 1 || currentDepot) return null;

    return (
        <div className="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800 px-4 py-2">
            <div className="max-w-7xl mx-auto flex items-center gap-2 text-sm text-amber-800 dark:text-amber-200">
                <Warehouse className="h-4 w-4 shrink-0" />
                <span>
                    Veuillez sélectionner un dépôt dans la barre de navigation pour accéder aux produits et au stock.
                </span>
            </div>
        </div>
    );
}
