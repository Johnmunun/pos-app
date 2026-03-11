import { Star } from 'lucide-react';

/**
 * Component: Testimonials
 *
 * Section de témoignages clients
 */
function TestimonialCard({ name, role, company, content, avatar }) {
    return (
        <div className="bg-white dark:bg-gray-700 rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-200 dark:border-gray-600">
            {/* Étoiles */}
            <div className="flex gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                    <Star key={i} className="w-5 h-5 text-yellow-400 fill-yellow-400" />
                ))}
            </div>

            {/* Quote icon */}
            <div className="mb-3">
                <svg className="w-8 h-8 text-amber-500 dark:text-amber-400 opacity-50" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.996 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.984zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/>
                </svg>
            </div>

            {/* Contenu */}
            <p className="text-gray-600 dark:text-gray-300 mb-6 italic">"{content}"</p>

            {/* Auteur */}
            <div className="flex items-center gap-3">
                <div className="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-lg">
                    {avatar}
                </div>
                <div>
                    <p className="font-semibold text-gray-900 dark:text-white text-sm">{name}</p>
                    <p className="text-gray-500 dark:text-gray-400 text-xs">{role} · {company}</p>
                </div>
            </div>
        </div>
    );
}



export default function Testimonials() {
    const testimonials = [
        {
            name: 'Sophie Martin',
            role: 'Fondatrice',
            company: 'Digital Shop',
            content: 'OmniPOS a transformé ma façon de vendre. Interface intuitive et support formidable!',
            avatar: 'SM',
        },
        {
            name: 'Jean Dubois',
            role: 'Manager',
            company: 'E-Commerce Plus',
            content: 'Les tableaux de bord sont incroyables. Nos revenus ont augmenté de 40% en 3 mois.',
            avatar: 'JD',
        },
        {
            name: 'Marie Laurent',
            role: 'Directrice',
            company: 'Boutique Online',
            content: 'Excellent service client et fonctionnalités que d\'autres n\'ont pas. Recommandé!',
            avatar: 'ML',
        },
        {
            name: 'Thomas Lefevre',
            role: 'Propriétaire',
            company: 'Mode Express',
            content: 'La meilleure décision pour mon business. Paiements sécurisés et rapides, clients satisfaits.',
            avatar: 'TL',
        },
        {
            name: 'Isabelle Rousseau',
            role: 'Chef d\'équipe',
            company: 'Tech Boutique',
            content: 'Parfait pour gérer nos ventes en ligne. Intégration facile et ROI rapide!',
            avatar: 'IR',
        },
        {
            name: 'Pierre Menard',
            role: 'Directeur Commercial',
            company: 'Ventes Directes',
            content: 'Solution complète et fiable. Notre équipe l\'adore et les clients aussi!',
            avatar: 'PM',
        },
    ];

    return (
        <section id="testimonials" className="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50 dark:bg-gray-800 transition-colors duration-200">
            <div className="max-w-7xl mx-auto">
                {/* En-tête */}
                <div className="text-center mb-16">
                    <div className="inline-flex items-center justify-center mb-6">
                        <svg className="w-16 h-16 text-amber-500 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h2 className="text-4xl md:text-5xl font-bold text-gray-900 dark:text-white mb-4">
                        Ce que nos clients disent
                    </h2>
                    <p className="text-lg text-gray-600 dark:text-gray-400 max-w-2xl mx-auto">
                        Des milliers de commerçants font confiance à OmniPOS.
                    </p>
                </div>

                {/* Grille de témoignages */}
                <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-16">
                    {testimonials.map((testimonial, idx) => (
                        <TestimonialCard
                            key={idx}
                            name={testimonial.name}
                            role={testimonial.role}
                            company={testimonial.company}
                            content={testimonial.content}
                            avatar={testimonial.avatar}
                        />
                    ))}
                </div>

                {/* Statistiques */}
                <div className="bg-white dark:bg-gray-700 rounded-xl p-8 grid md:grid-cols-3 gap-8 text-center border border-gray-200 dark:border-gray-600 shadow-lg">
                    {[
                        { number: '10K+', label: 'Utilisateurs actifs', icon: '👥' },
                        { number: '99.9%', label: 'Disponibilité', icon: '⚡' },
                        { number: '$50M+', label: 'Transactions traitées', icon: '💰' },
                    ].map((stat, idx) => (
                        <div key={idx} className="transform hover:scale-105 transition-transform duration-300">
                            <div className="text-4xl mb-2">{stat.icon}</div>
                            <p className="text-4xl font-bold bg-gradient-to-r from-amber-500 to-orange-600 bg-clip-text text-transparent">
                                {stat.number}
                            </p>
                            <p className="text-gray-600 dark:text-gray-300 mt-2">{stat.label}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
