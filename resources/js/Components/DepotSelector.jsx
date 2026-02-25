import { router } from '@inertiajs/react';
import { Warehouse } from 'lucide-react';

/**
 * Sélecteur de dépôt - affiché quand le tenant a plusieurs dépôts.
 * Obligatoire avant d'accéder aux produits/stock.
 */
export default function DepotSelector({ depots = [], currentDepot }) {
    if (!depots || depots.length === 0) return null;

    const handleSwitch = (depotId) => {
        const id = depotId === '' || depotId == null ? null : Number(depotId);
        router.post(route('depot.switch'), { depot_id: id }, {
            preserveState: false,
            preserveScroll: false,
        });
    };

    const currentId = currentDepot?.id ?? (depots.length === 1 ? depots[0]?.id : null);
    const value = currentId !== undefined && currentId !== null ? String(currentId) : '';

    return (
        <div className="flex items-center gap-2">
            <Warehouse className="h-4 w-4 text-gray-500 dark:text-gray-400 shrink-0" />
            <select
                value={value}
                onChange={(e) => handleSwitch(e.target.value)}
                className="rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm py-1.5 px-3 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 dark:focus:ring-amber-400 min-w-[140px]"
            >
                {depots.length > 1 && <option value="">— Choisir un dépôt —</option>}
                {depots.map((d) => (
                    <option key={d.id} value={String(d.id)}>
                        {d.name}
                    </option>
                ))}
            </select>
        </div>
    );
}
