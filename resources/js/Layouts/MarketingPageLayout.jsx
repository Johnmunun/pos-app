import Header from '@/Components/Header';
import Footer from '@/Components/Footer';
import SupportPublicChatWidget from '@/Components/Support/SupportPublicChatWidget';

/**
 * Layout pages marketing publiques (légal, à propos) — même chrome que la landing.
 */
export default function MarketingPageLayout({ children }) {
    const scrollToLandingSection = (id) => {
        window.location.href = `/#${id}`;
    };

    return (
        <div className="min-h-screen bg-white dark:bg-gray-950 text-gray-900 dark:text-gray-100 antialiased selection:bg-amber-500/25 selection:text-gray-900 dark:selection:text-white transition-colors duration-200">
            <Header onScrollToSection={scrollToLandingSection} linkMode />
            <main className="pt-16">{children}</main>
            <Footer />
            <SupportPublicChatWidget />
        </div>
    );
}
