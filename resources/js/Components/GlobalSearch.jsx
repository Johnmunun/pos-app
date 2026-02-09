import { useState, useEffect, useRef } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import * as LucideIcons from 'lucide-react';

/**
 * Component: GlobalSearch
 * 
 * Recherche globale intelligente avec :
 * - Recherche en temps réel
 * - Résultats groupés par module
 * - Filtrage par permissions
 * - Navigation vers les routes
 * - Support mobile et desktop
 */
export default function GlobalSearch({ isRoot = false }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [results, setResults] = useState({});
    const [isOpen, setIsOpen] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const searchRef = useRef(null);
    const resultsRef = useRef(null);

    // Fermer le dropdown si on clique en dehors
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (
                searchRef.current &&
                !searchRef.current.contains(event.target) &&
                resultsRef.current &&
                !resultsRef.current.contains(event.target)
            ) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Recherche avec debounce
    useEffect(() => {
        if (!searchQuery.trim()) {
            setResults({});
            setIsOpen(false);
            return;
        }

        const timeoutId = setTimeout(() => {
            performSearch(searchQuery);
        }, 300); // Debounce de 300ms

        return () => clearTimeout(timeoutId);
    }, [searchQuery]);

    const performSearch = async (query) => {
        if (!query.trim()) {
            setResults({});
            setIsOpen(false);
            return;
        }

        setIsLoading(true);
        setIsOpen(true);

        try {
            const response = await axios.get('/api/search', {
                params: { q: query },
            });

            setResults(response.data.results || {});
        } catch (error) {
            console.error('Erreur lors de la recherche:', error);
            setResults({});
        } finally {
            setIsLoading(false);
        }
    };

    const handleItemClick = (routeName) => {
        // Utiliser router.visit avec le nom de route directement
        // route() est disponible globalement via Ziggy (@routes dans app.blade.php)
        if (typeof route === 'function') {
            try {
                const url = route(routeName);
                router.visit(url);
            } catch (error) {
                console.error('Route not found:', routeName, error);
                // Fallback: construire l'URL manuellement si la route n'existe pas
                router.visit(`/${routeName.replace(/\./g, '/')}`);
            }
        } else {
            // Fallback si route() n'est pas disponible
            console.warn('route() function not available, using fallback');
            router.visit(`/${routeName.replace(/\./g, '/')}`);
        }
        setIsOpen(false);
        setSearchQuery('');
    };

    const getIcon = (iconName) => {
        if (!iconName) return null;
        const IconComponent = LucideIcons[iconName] || LucideIcons['File'];
        return IconComponent;
    };

    const hasResults = Object.keys(results).length > 0;

    return (
        <div className="relative flex flex-1" ref={searchRef}>
            <form
                className="relative flex flex-1"
                onSubmit={(e) => {
                    e.preventDefault();
                    if (hasResults && Object.values(results)[0]?.length > 0) {
                        const firstResult = Object.values(results)[0][0];
                        handleItemClick(firstResult.route_name);
                    }
                }}
            >
                <label htmlFor="search-field" className="sr-only">
                    Rechercher
                </label>
                <svg
                    className="pointer-events-none absolute inset-y-0 left-0 h-full w-5 text-gray-400 pl-3"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                >
                    <path
                        fillRule="evenodd"
                        d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z"
                        clipRule="evenodd"
                    />
                </svg>
                <input
                    id="search-field"
                    className="block h-full w-full border-0 py-0 pl-10 pr-0 text-gray-900 dark:text-gray-100 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:ring-0 sm:text-sm bg-transparent"
                    placeholder="Rechercher..."
                    type="search"
                    name="search"
                    value={searchQuery}
                    onChange={(e) => setSearchQuery(e.target.value)}
                    onFocus={() => {
                        if (hasResults) {
                            setIsOpen(true);
                        }
                    }}
                    autoComplete="off"
                />
            </form>

            {/* Dropdown des résultats */}
            {isOpen && (searchQuery.trim() || isLoading) && (
                <div
                    ref={resultsRef}
                    className="absolute left-0 right-0 top-full z-50 mt-2 max-h-96 overflow-y-auto rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-lg"
                >
                    {isLoading ? (
                        <div className="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            Recherche en cours...
                        </div>
                    ) : hasResults ? (
                        <div className="py-2">
                            {Object.entries(results).map(([module, items]) => (
                                <div key={module} className="mb-2 last:mb-0">
                                    <div className="px-4 py-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/50">
                                        {module}
                                    </div>
                                    {items.map((item, index) => {
                                        const IconComponent = getIcon(item.icon);
                                        return (
                                            <button
                                                key={index}
                                                type="button"
                                                onClick={() => handleItemClick(item.route_name)}
                                                className="w-full flex items-center gap-3 px-4 py-2 text-left text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                                            >
                                                {IconComponent && (
                                                    <IconComponent className="h-4 w-4 text-gray-400 dark:text-gray-500 flex-shrink-0" />
                                                )}
                                                <div className="flex-1 min-w-0">
                                                    <div className="font-medium text-gray-900 dark:text-white truncate">
                                                        {item.label}
                                                    </div>
                                                    {item.description && (
                                                        <div className="text-xs text-gray-500 dark:text-gray-400 truncate">
                                                            {item.description}
                                                        </div>
                                                    )}
                                                </div>
                                            </button>
                                        );
                                    })}
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="p-4 text-center text-sm text-gray-500 dark:text-gray-400">
                            Aucun résultat trouvé
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
