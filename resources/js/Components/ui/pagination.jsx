import React from 'react';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import { router } from '@inertiajs/react';

export function Pagination({ pagination, filters = {} }) {
    if (!pagination || pagination.last_page <= 1) {
        return null;
    }

    const { current_page, last_page, per_page, total, from, to } = pagination;

    const handlePageChange = (page) => {
        router.get(route('pharmacy.categories.index'), {
            ...filters,
            page
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handlePerPageChange = (newPerPage) => {
        router.get(route('pharmacy.categories.index'), {
            ...filters,
            per_page: newPerPage,
            page: 1
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    // Générer les numéros de page à afficher
    const getPageNumbers = () => {
        const pages = [];
        const maxPages = 7;
        
        if (last_page <= maxPages) {
            for (let i = 1; i <= last_page; i++) {
                pages.push(i);
            }
        } else {
            if (current_page <= 3) {
                for (let i = 1; i <= 5; i++) {
                    pages.push(i);
                }
                pages.push('...');
                pages.push(last_page);
            } else if (current_page >= last_page - 2) {
                pages.push(1);
                pages.push('...');
                for (let i = last_page - 4; i <= last_page; i++) {
                    pages.push(i);
                }
            } else {
                pages.push(1);
                pages.push('...');
                for (let i = current_page - 1; i <= current_page + 1; i++) {
                    pages.push(i);
                }
                pages.push('...');
                pages.push(last_page);
            }
        }
        
        return pages;
    };

    return (
        <div className="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 bg-white dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
            {/* Info */}
            <div className="text-sm text-gray-700 dark:text-gray-300">
                Affichage de <span className="font-medium">{from}</span> à <span className="font-medium">{to}</span> sur{' '}
                <span className="font-medium">{total}</span> résultat(s)
            </div>

            {/* Pagination */}
            <div className="flex items-center gap-2">
                {/* Per page selector */}
                <select
                    value={per_page}
                    onChange={(e) => handlePerPageChange(e.target.value)}
                    className="rounded-md border-gray-300 dark:border-gray-600 shadow-sm focus:border-amber-500 focus:ring-amber-500 dark:bg-gray-700 dark:text-white text-sm px-2 py-1"
                >
                    <option value={10}>10 / page</option>
                    <option value={15}>15 / page</option>
                    <option value={25}>25 / page</option>
                    <option value={50}>50 / page</option>
                    <option value={100}>100 / page</option>
                </select>

                {/* Navigation buttons */}
                <div className="flex items-center gap-1">
                    <button
                        onClick={() => handlePageChange(1)}
                        disabled={current_page === 1}
                        className="p-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Première page"
                    >
                        <ChevronsLeft className="h-4 w-4" />
                    </button>
                    <button
                        onClick={() => handlePageChange(current_page - 1)}
                        disabled={current_page === 1}
                        className="p-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Page précédente"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </button>

                    {/* Page numbers */}
                    <div className="flex items-center gap-1">
                        {getPageNumbers().map((page, index) => (
                            page === '...' ? (
                                <span key={`ellipsis-${index}`} className="px-2 text-gray-500 dark:text-gray-400">
                                    ...
                                </span>
                            ) : (
                                <button
                                    key={page}
                                    onClick={() => handlePageChange(page)}
                                    className={`px-3 py-1 rounded-md text-sm font-medium transition-colors ${
                                        current_page === page
                                            ? 'bg-amber-500 text-white dark:bg-amber-600'
                                            : 'border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600'
                                    }`}
                                >
                                    {page}
                                </button>
                            )
                        ))}
                    </div>

                    <button
                        onClick={() => handlePageChange(current_page + 1)}
                        disabled={current_page === last_page}
                        className="p-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Page suivante"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </button>
                    <button
                        onClick={() => handlePageChange(last_page)}
                        disabled={current_page === last_page}
                        className="p-2 rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        title="Dernière page"
                    >
                        <ChevronsRight className="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>
    );
}
