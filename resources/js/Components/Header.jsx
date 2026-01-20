import { useState } from 'react';

/**
 * Component: Header
 *
 * En-tête principal avec logo, navigation et CTA
 * Responsive et supporte le dark mode
 */
export default function Header({ onScrollToSection }) {
    const [isOpen, setIsOpen] = useState(false);

    const menuItems = [
        { label: 'Fonctionnalités', id: 'features' },
        { label: 'Tarifs', id: 'pricing' },
        { label: 'Témoignages', id: 'testimonials' },
        { label: 'Contact', id: 'contact' },
    ];

    const handleMenuClick = (id) => {
        onScrollToSection(id);
        setIsOpen(false);
    };

    return (
        <header className="fixed top-0 left-0 right-0 z-50 bg-white/80 dark:bg-gray-900/80 backdrop-blur-md border-b border-gray-200 dark:border-gray-800 transition-all duration-200">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="flex justify-between items-center h-16">
                    {/* Logo - Gauche */}
                    <div className="flex-shrink-0">
                        <a href="/" className="flex items-center space-x-2 group">
                            <div className="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                                <span className="text-white font-bold text-sm">POS</span>
                            </div>
                            <span className="text-xl font-bold text-gray-900 dark:text-white">POS SaaS</span>
                        </a>
                    </div>

                    {/* Navigation Centre */}
                    <nav className="hidden md:flex space-x-8">
                        {menuItems.map((item) => (
                            <button
                                key={item.id}
                                onClick={() => handleMenuClick(item.id)}
                                className="text-gray-600 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 transition-colors text-sm font-medium"
                            >
                                {item.label}
                            </button>
                        ))}
                    </nav>

                    {/* Boutons Droite */}
                    <div className="hidden md:flex items-center space-x-3">
                        <a 
                            href={route('login')}
                            className="text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 px-4 py-2 font-medium text-sm transition-colors"
                        >
                            Connexion
                        </a>
                        <a 
                            href={route('register')}
                            className="bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white px-6 py-2 rounded-lg font-semibold transition-all duration-200 shadow-md hover:shadow-lg"
                        >
                            Démarrer gratuitement
                        </a>
                    </div>

                    {/* Mobile Menu Button */}
                    <div className="md:hidden flex items-center space-x-2">
                        <button
                            onClick={() => setIsOpen(!isOpen)}
                            className="inline-flex items-center justify-center p-2 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        >
                            <svg
                                className={`w-6 h-6 transition-transform duration-200 ${isOpen ? 'rotate-90' : ''}`}
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                    </div>
                </div>

                {/* Mobile Navigation */}
                {isOpen && (
                    <div className="md:hidden pb-4 border-t border-gray-200 dark:border-gray-800">
                        <nav className="flex flex-col space-y-2 pt-4">
                            {menuItems.map((item) => (
                                <button
                                    key={item.id}
                                    onClick={() => handleMenuClick(item.id)}
                                    className="text-gray-600 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 transition-colors text-sm font-medium py-2 text-left"
                                >
                                    {item.label}
                                </button>
                            ))}
                            <div className="flex gap-2 pt-4">
                                <a 
                                    href={route('login')}
                                    className="flex-1 text-gray-700 dark:text-gray-300 hover:text-amber-600 dark:hover:text-amber-400 px-4 py-2 font-medium text-sm transition-colors border border-gray-200 dark:border-gray-700 rounded-lg text-center"
                                >
                                    Connexion
                                </a>
                                <a 
                                    href={route('register')}
                                    className="flex-1 bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white px-4 py-2 rounded-lg font-semibold transition-all duration-200 text-center"
                                >
                                    Démarrer
                                </a>
                            </div>
                        </nav>
                    </div>
                )}
            </div>
        </header>
    );
}
