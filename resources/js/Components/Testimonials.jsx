import { Star } from 'lucide-react';

/**
 * Component: Testimonials
 *
 * Section de témoignages clients
 */
function TestimonialCard({ name, role, company, content, avatar }) {
    return (
        <div className="bg-white rounded-xl p-6 shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-200">
            {/* Étoiles */}
            <div className="flex gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                    <Star key={i} className="w-5 h-5 text-yellow-400 fill-yellow-400" />
                ))}
            </div>

            {/* Contenu */}
            <p className="text-gray-600 mb-6 italic">"{content}"</p>

            {/* Auteur */}
            <div className="flex items-center gap-3">
                <div className="w-10 h-10 bg-gradient-to-br from-amber-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-sm">
                    {avatar}
                </div>
                <div>
                    <p className="font-semibold text-gray-900 text-sm">{name}</p>
                    <p className="text-gray-500 text-xs">{role} · {company}</p>
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
            content: 'POS SaaS a transformé ma façon de vendre. Interface intuitive et support formidable!',
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
        <section id="testimonials" className="py-20 px-4 sm:px-6 lg:px-8 bg-gray-50">
            <div className="max-w-7xl mx-auto">
                {/* En-tête */}
                <div className="text-center mb-16">
                    <h2 className="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                        Ce que nos clients disent
                    </h2>
                    <p className="text-lg text-gray-600 max-w-2xl mx-auto">
                        Des milliers de commerçants font confiance à POS SaaS.
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
                <div className="bg-white rounded-xl p-8 grid md:grid-cols-3 gap-8 text-center border border-gray-200">
                    {[
                        { number: '10K+', label: 'Utilisateurs actifs' },
                        { number: '99.9%', label: 'Disponibilité' },
                        { number: '$50M+', label: 'Transactions traitées' },
                    ].map((stat, idx) => (
                        <div key={idx}>
                            <p className="text-4xl font-bold bg-gradient-to-r from-amber-500 to-orange-600 bg-clip-text text-transparent">
                                {stat.number}
                            </p>
                            <p className="text-gray-600 mt-2">{stat.label}</p>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
