import { Share2, Linkedin, MessageCircle, Camera } from 'lucide-react';
import LandingReveal from './LandingReveal';

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
            links: ['Mentions légales', 'Politique de confidentialité', "Conditions d'utilisation", 'RGPD'],
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
            <div id="contact" className="scroll-mt-20 px-4 sm:px-6 lg:px-8 pt-6 sm:pt-10">
                <LandingReveal className="max-w-7xl mx-auto">
                    <div className="relative overflow-hidden rounded-[2rem] sm:rounded-[2.25rem] bg-gradient-to-br from-amber-500 via-orange-500 to-orange-600 text-white shadow-landing-soft-lg ring-1 ring-white/20">
                        <div className="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-white/10 blur-3xl" />
                        <div className="pointer-events-none absolute -left-16 bottom-0 h-48 w-48 rounded-full bg-orange-900/20 blur-2xl" />
                        <div className="relative px-6 sm:px-10 lg:px-14 py-12 sm:py-14 lg:py-16 flex flex-col lg:flex-row items-center justify-between gap-10 text-center lg:text-left">
                            <div className="max-w-xl">
                                <h3 className="text-2xl sm:text-3xl lg:text-4xl font-bold tracking-tight mb-3">
                                    Prêt à commencer ?
                                </h3>
                                <p className="text-base sm:text-lg text-white/90 leading-relaxed">
                                    Rejoignez des milliers de commerçants satisfaits.
                                </p>
                            </div>
                            <a
                                href={route('register')}
                                className="inline-flex shrink-0 items-center justify-center rounded-2xl bg-white px-8 py-4 text-base font-semibold text-amber-700 shadow-lg shadow-black/10 hover:bg-amber-50 active:scale-[0.98] transition-all duration-200"
                            >
                                Essayer gratuitement
                            </a>
                        </div>
                    </div>
                </LandingReveal>
            </div>

            <footer className="mt-12 sm:mt-16 bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 border-t border-gray-200/80 dark:border-gray-800/80 transition-colors duration-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="py-14 sm:py-16">
                        <div className="grid md:grid-cols-4 gap-12 lg:gap-14 mb-12">
                            <div>
                                <div className="flex items-center gap-2.5 mb-5">
                                    <div className="w-9 h-9 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-md shadow-amber-500/20">
                                        <span className="text-white font-bold text-sm">OP</span>
                                    </div>
                                    <span className="text-xl font-bold tracking-tight">OmniPOS</span>
                                </div>
                                <p className="text-sm text-gray-600 dark:text-gray-400 leading-relaxed mb-6">
                                    La plateforme complète pour gérer vos ventes digitales et faire croître votre activité.
                                </p>
                                <div className="flex gap-3">
                                    {socials.map((social, idx) => {
                                        const IconComponent = social.icon;
                                        return (
                                            <a
                                                key={idx}
                                                href={social.url}
                                                title={social.name}
                                                className="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-900 text-gray-600 dark:text-gray-400 hover:bg-gradient-to-br hover:from-amber-500 hover:to-orange-600 hover:text-white transition-all duration-200 flex items-center justify-center ring-1 ring-transparent hover:ring-amber-400/30"
                                            >
                                                <IconComponent className="w-5 h-5" />
                                            </a>
                                        );
                                    })}
                                </div>
                            </div>

                            {sections.map((section, idx) => (
                                <div key={idx}>
                                    <h3 className="font-semibold text-gray-900 dark:text-white mb-4 tracking-tight">{section.title}</h3>
                                    <ul className="space-y-2.5">
                                        {section.links.map((link, linkIdx) => (
                                            <li key={linkIdx}>
                                                <a
                                                    href="#"
                                                    className="text-sm text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors duration-200"
                                                >
                                                    {link}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            ))}
                        </div>

                        <div className="h-px bg-gradient-to-r from-transparent via-gray-200 dark:via-gray-800 to-transparent my-8" />

                        <div className="flex flex-col md:flex-row justify-between items-center gap-4">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                © {currentYear} OmniPOS. Tous droits réservés.
                            </p>
                            <div className="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <span className="w-2 h-2 bg-emerald-500 rounded-full motion-safe:animate-pulse" />
                                <span>Tous les systèmes opérationnels</span>
                            </div>
                        </div>
                    </div>
                </div>
            </footer>
        </>
    );
}
