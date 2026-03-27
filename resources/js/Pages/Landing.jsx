import Header from '../Components/Header';
import Hero from '../Components/Hero';
import Features from '../Components/Features';
import Testimonials from '../Components/Testimonials';
import PaymentMethods from '../Components/PaymentMethods';
import Pricing from '../Components/Pricing';
import Footer from '../Components/Footer';
import SupportPublicChatWidget from '../Components/Support/SupportPublicChatWidget';

/**
 * Page: Landing
 *
 * Page d'accueil principale avec design professionnel
 * - Header avec navigation
 * - Hero section
 * - Fonctionnalités
 * - Témoignages
 * - Tarifs
 * - Footer avec CTA
 */
export default function Landing() {
    // Gestion du scroll vers les sections
    const handleScrollToSection = (id) => {
        const element = document.getElementById(id);
        if (element) {
            const headerHeight = 64; // Hauteur du header (h-16 = 64px)
            const elementPosition = element.getBoundingClientRect().top + window.scrollY - headerHeight;
            window.scrollTo({
                top: elementPosition,
                behavior: 'smooth',
            });
        }
    };

    return (
        <div className="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 min-h-screen transition-colors duration-200">
            {/* Header */}
            <Header onScrollToSection={handleScrollToSection} />

            {/* Main content */}
            <main>
                {/* Hero */}
                <Hero />

                {/* Features */}
                <Features />

                {/* Testimonials */}
                <Testimonials />

                {/* Payment methods */}
                <PaymentMethods />

                {/* Pricing */}
                <Pricing />
            </main>

            {/* Footer */}
            <Footer />

            {/* Public support chat (no login) */}
            <SupportPublicChatWidget />
        </div>
    );
}
