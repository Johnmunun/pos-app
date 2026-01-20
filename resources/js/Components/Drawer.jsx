import { Fragment } from 'react';
import { X } from 'lucide-react';
import { Transition } from '@headlessui/react';

/**
 * Component: Drawer
 * 
 * Drawer réutilisable qui glisse de droite vers la gauche
 * Utilisé pour les formulaires de création/édition
 * Animation fluide avec Headless UI Transition
 */
export default function Drawer({ isOpen, onClose, title, children, size = 'md' }) {
    const sizeClasses = {
        sm: 'max-w-md',
        md: 'max-w-lg',
        lg: 'max-w-2xl',
        xl: 'max-w-4xl',
    };

    return (
        <Transition show={isOpen} as={Fragment}>
            {/* Overlay */}
            <Transition.Child
                as={Fragment}
                enter="ease-out duration-300"
                enterFrom="opacity-0"
                enterTo="opacity-100"
                leave="ease-in duration-200"
                leaveFrom="opacity-100"
                leaveTo="opacity-0"
            >
                <div
                    className="fixed inset-0 bg-black/50 z-40"
                    onClick={onClose}
                />
            </Transition.Child>

            {/* Drawer */}
            <Transition.Child
                as={Fragment}
                enter="transform transition ease-out duration-300"
                enterFrom="translate-x-full"
                enterTo="translate-x-0"
                leave="transform transition ease-in duration-200"
                leaveFrom="translate-x-0"
                leaveTo="translate-x-full"
            >
                <div
                    className={`fixed right-0 top-0 h-full ${sizeClasses[size]} w-full bg-white dark:bg-gray-800 shadow-xl z-50`}
                    onClick={(e) => e.stopPropagation()}
                >
                    <div className="flex flex-col h-full">
                        {/* Header */}
                        <div className="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                                {title}
                            </h2>
                            <button
                                onClick={onClose}
                                className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                aria-label="Fermer"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Content */}
                        <div className="flex-1 overflow-y-auto px-6 py-6 drawer-content">
                            {children}
                        </div>
                    </div>
                </div>
            </Transition.Child>
        </Transition>
    );
}
