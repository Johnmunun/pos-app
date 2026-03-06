import React, { useEffect } from 'react';
import { X } from 'lucide-react';

const sizeClasses = {
    md: 'max-w-2xl',
    lg: 'max-w-4xl',
    xl: 'max-w-6xl',
    '2xl': 'max-w-7xl',
};

export default function MacWindowModal({
    isOpen,
    onClose,
    title,
    subtitle,
    size = 'xl',
    children,
}) {
    useEffect(() => {
        if (!isOpen) return;
        const onKeyDown = (e) => {
            if (e.key === 'Escape') onClose?.();
        };
        window.addEventListener('keydown', onKeyDown);
        return () => window.removeEventListener('keydown', onKeyDown);
    }, [isOpen, onClose]);

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-[80]">
            <div
                className="absolute inset-0 bg-black/50 backdrop-blur-[2px]"
                onClick={onClose}
            />

            <div className="absolute inset-0 flex items-center justify-center p-4">
                <div
                    className={`w-full ${sizeClasses[size] ?? sizeClasses.xl} rounded-2xl overflow-hidden shadow-2xl border border-gray-200/70 dark:border-slate-700/70 bg-white dark:bg-slate-900`}
                    onClick={(e) => e.stopPropagation()}
                    role="dialog"
                    aria-modal="true"
                >
                    {/* Barre style macOS */}
                    <div className="flex items-center justify-between px-4 py-3 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-900/60">
                        <div className="flex items-center gap-2">
                            <span className="h-3 w-3 rounded-full bg-red-500 shadow-inner ring-1 ring-black/10" />
                            <span className="h-3 w-3 rounded-full bg-amber-400 shadow-inner ring-1 ring-black/10" />
                            <span className="h-3 w-3 rounded-full bg-green-500 shadow-inner ring-1 ring-black/10" />
                        </div>

                        <div className="min-w-0 flex-1 px-4 text-center">
                            <div className="truncate text-sm font-semibold text-gray-900 dark:text-gray-100">
                                {title}
                            </div>
                            {subtitle ? (
                                <div className="truncate text-xs text-gray-500 dark:text-gray-400">
                                    {subtitle}
                                </div>
                            ) : null}
                        </div>

                        <button
                            type="button"
                            onClick={onClose}
                            className="inline-flex items-center justify-center rounded-lg h-9 w-9 hover:bg-gray-100 dark:hover:bg-slate-800 text-gray-600 dark:text-gray-300"
                            title="Fermer"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    <div className="max-h-[calc(100vh-140px)] overflow-y-auto">
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}

