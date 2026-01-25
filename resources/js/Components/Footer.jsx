import { Share2, Linkedin, MessageCircle, Camera } from 'lucide-react';

/**
 * Component: Footer
 *
 * Pied de page professionnel avec liens et CTA
 */
export default function Footer() {
    const currentYear = new Date().getFullYear();

    const sections = [
        {
            title: 'Produit',
            links: ['Fonctionnalités', 'Tarifs', 'Documentation', 'Support'],
        },
        {
            title: 'Entreprise',
            links: ['À propos', 'Blog', 'Carrières', 'Newsroom'],
        },
        {
            title: 'Légal',
            links: ['Mentions légales', 'Politique de confidentialité', 'Conditions d\'utilisation', 'RGPD'],
        },
    ];

    const socials = [
        { name: 'Twitter', icon: Share2, url: '#' },
        { name: 'LinkedIn', icon: Linkedin, url: '#' },
        { name: 'Facebook', icon: MessageCircle, url: '#' },
        { name: 'Instagram', icon: Camera, url: '#' },
    ];

    return (
        <>
            {/* CTA Banner */}
            <div className="bg-gradient-to-r from-amber-500 to-orange-600 text-white">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div className="flex flex-col md:flex-row items-center justify-between gap-8">
                        <div>
                            <h3 className="text-3xl font-bold mb-2">Prêt à commencer ?</h3>
                            <p className="text-amber-100">Rejoignez des milliers de commerçants satisfaits.</p>
                        </div>
                        <button className="bg-white hover:bg-gray-100 text-amber-600 px-8 py-3 rounded-lg font-semibold transition-colors duration-200 whitespace-nowrap">
                            Essayer gratuitement
                        </button>
                    </div>
                </div>
            </div>

            {/* Footer */}
            <footer className="bg-white text-gray-900 border-t border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Main Footer Content */}
                    <div className="py-16">
                        <div className="grid md:grid-cols-4 gap-12 mb-12">
                            {/* Brand */}
                            <div>
                                <div className="flex items-center space-x-2 mb-4">
                                    <div className="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-600 rounded-lg flex items-center justify-center">
                                        <span className="text-white font-bold text-sm">POS</span>
                                    </div>
                                    <span className="text-xl font-bold text-gray-900">POS SaaS</span>
                                </div>
                                <p className="text-sm text-gray-600 mb-6">
                                    La plateforme complète pour gérer vos ventes digitales et faire croître votre activité.
                                </p>
                                <div className="flex gap-4">
                                    {socials.map((social, idx) => {
                                        const IconComponent = social.icon;
                                        return (
                                            <a
                                                key={idx}
                                                href={social.url}
                                                title={social.name}
                                                className="w-10 h-10 bg-gray-100 hover:bg-amber-500 text-gray-600 hover:text-white rounded-lg flex items-center justify-center transition-colors duration-200"
                                            >
                                                <IconComponent className="w-5 h-5" />
                                            </a>
                                        );
                                    })}
                                </div>
                            </div>

                            {/* Links Sections */}
                            {sections.map((section, idx) => (
                                <div key={idx}>
                                    <h3 className="font-semibold text-gray-900 mb-4">{section.title}</h3>
                                    <ul className="space-y-2">
                                        {section.links.map((link, linkIdx) => (
                                            <li key={linkIdx}>
                                                <a
                                                    href="#"
                                                    className="text-gray-600 hover:text-amber-500 transition-colors duration-200 text-sm"
                                                >
                                                    {link}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>

                        {/* Divider */}
                        <div className="h-px bg-gray-200 my-8"></div>

                        {/* Bottom */}
                        <div className="flex flex-col md:flex-row justify-between items-center gap-4">
                            <p className="text-sm text-gray-600">
                                © {currentYear} POS SaaS. Tous droits réservés.
                            </p>
                            <div className="flex items-center gap-2 text-sm text-gray-600">
                                <span className="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                <span>Tous les systèmes opérationnels</span>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </>
    );
}
