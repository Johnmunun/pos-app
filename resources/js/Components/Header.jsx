import { useEffect, useState } from 'react';
import { Menu, X } from 'lucide-react';
import clsx from 'clsx';

/**
 * Component: Header
 *
 * En-tête principal avec logo, navigation et CTA
 * Responsive et supporte le dark mode
 */
export default function Header({ onScrollToSection }) {
    const [isOpen, setIsOpen] = useState(false);
    const [scrolled, setScrolled] = useState(false);

    const menuItems = [
        { label: 'Fonctionnalités', id: 'features' },
        { label: 'Tarifs', id: 'pricing' },
        { label: 'Témoignages', id: 'testimonials' },
        { label: 'Contact', id: 'contact' },
    ];

    useEffect(() => {
        const onScroll = () => setScrolled(window.scrollY > 16);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    useEffect(() => {
        document.body.style.overflow = isOpen ? 'hidden' : '';
        return () => {
            document.body.style.overflow = '';
        };
    }, [isOpen]);

    const handleMenuClick = (id) => {
        onScrollToSection(id);
        setIsOpen(false);
    };

    return (
        <header
            className={clsx(
                'fixed top-0 left-0 right-0 z-50 transition-all duration-300 ease-out border-b',
                scrolled
                    ? 'bg-white/75 dark:bg-gray-950/80 backdrop-blur-xl border-gray-200/80 dark:border-gray-800/80 shadow-landing-soft'
                    : 'bg-white/40 dark:bg-gray-950/50 backdrop-blur-md border-transparent dark:border-transparent'
            )}
        >
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center h-16">
                    <div className="flex-shrink-0">
                        <a href="/" className="flex items-center gap-2.5 group rounded-xl pr-2 -ml-1 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500/60 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-gray-950">
                            <div className="w-9 h-9 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-md shadow-amber-500/25 group-hover:shadow-lg group-hover:shadow-amber-500/30 transition-shadow duration-300">
                                <span className="text-white font-bold text-sm tracking-tight">OP</span>
                            </div>
                            <span className="text-lg sm:text-xl font-bold tracking-tight text-gray-900 dark:text-white">
                                OmniPOS
                            </span>
                        </a>
                    </div>

                    <nav className="hidden md:flex items-center gap-1 lg:gap-2">
                        {menuItems.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => handleMenuClick(item.id)}
                                className="relative px-3.5 py-2 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white transition-colors duration-200 group"
                            >
                                <span className="relative z-10">{item.label}</span>
                                <span className="absolute inset-0 rounded-xl bg-gray-100/0 dark:bg-white/0 group-hover:bg-gray-100/90 dark:group-hover:bg-white/5 transition-colors duration-200" />
                                <span className="absolute left-1/2 -translate-x-1/2 bottom-1 h-0.5 w-0 bg-gradient-to-r from-amber-500 to-orange-500 rounded-full group-hover:w-4/5 transition-all duration-300 ease-out" />
                            </button>
                        ))}
                    </nav>

                    <div className="hidden md:flex items-center gap-2">
                        <a
                            href={route('login')}
                            className="px-4 py-2.5 rounded-xl text-sm font-semibold text-gray-700 dark:text-gray-200 hover:text-amber-700 dark:hover:text-amber-300 transition-colors duration-200"
                        >
                            Connexion
                        </a>
                        <a
                            href={route('register')}
                            className="inline-flex items-center justify-center px-5 py-2.5 rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 shadow-md shadow-amber-500/25 hover:shadow-lg hover:shadow-amber-500/30 active:scale-[0.98] transition-all duration-200"
                        >
                            Démarrer gratuitement
                        </a>
                    </div>

                    <div className="md:hidden flex items-center">
                        <button
                            type="button"
                            onClick={() => setIsOpen(!isOpen)}
                            aria-expanded={isOpen}
                            aria-label={isOpen ? 'Fermer le menu' : 'Ouvrir le menu'}
                            className="inline-flex items-center justify-center p-2.5 rounded-xl text-gray-700 dark:text-gray-200 hover:bg-gray-100/90 dark:hover:bg-white/10 active:scale-95 transition-all duration-200"
                        >
                            {isOpen ? <X className="w-6 h-6" /> : <Menu className="w-6 h-6" />}
                        </button>
                    </div>
                </div>
            </div>

            <div
                className={clsx(
                    'md:hidden fixed inset-0 z-40 transition-opacity duration-300 ease-out',
                    isOpen ? 'opacity-100 pointer-events-auto' : 'opacity-0 pointer-events-none'
                )}
                aria-hidden={!isOpen}
            >
                <button
                    type="button"
                    className="absolute inset-0 bg-gray-900/30 dark:bg-black/50 backdrop-blur-sm"
                    onClick={() => setIsOpen(false)}
                    tabIndex={isOpen ? 0 : -1}
                />
                <div
                    className={clsx(
                        'absolute top-16 right-0 bottom-0 w-full max-w-sm bg-white/95 dark:bg-gray-950/95 backdrop-blur-2xl border-l border-gray-200/80 dark:border-gray-800/80 shadow-landing-soft-lg flex flex-col transition-transform duration-300 ease-[cubic-bezier(0.22,1,0.36,1)]',
                        isOpen ? 'translate-x-0' : 'translate-x-full'
                    )}
                >
                    <nav className="flex-1 flex flex-col p-5 pt-6 gap-1 overflow-y-auto">
                        {menuItems.map((item) => (
                            <button
                                key={item.id}
                                type="button"
                                onClick={() => handleMenuClick(item.id)}
                                className="w-full text-left px-4 py-3.5 rounded-2xl text-base font-medium text-gray-800 dark:text-gray-100 hover:bg-amber-50 dark:hover:bg-amber-500/10 active:scale-[0.99] transition-all duration-200"
                            >
                                {item.label}
                            </button>
                        ))}
                    </nav>
                    <div className="p-5 pt-0 pb-8 border-t border-gray-100 dark:border-gray-800/80 space-y-3 shrink-0">
                        <a
                            href={route('login')}
                            className="flex w-full items-center justify-center rounded-2xl border border-gray-200 dark:border-gray-700 px-4 py-3.5 text-sm font-semibold text-gray-800 dark:text-gray-100 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors duration-200"
                        >
                            Connexion
                        </a>
                        <a
                            href={route('register')}
                            className="flex w-full items-center justify-center rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 text-white px-4 py-3.5 text-sm font-semibold shadow-md shadow-amber-500/25 active:scale-[0.99] transition-all duration-200"
                        >
                            Démarrer gratuitement
                        </a>
                    </div>
                </div>
            </div>
        </header>
    );
}
