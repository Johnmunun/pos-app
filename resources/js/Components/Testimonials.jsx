import { useState } from 'react';
import { Star } from 'lucide-react';
import LandingReveal from './LandingReveal';

/**
 * Component: Testimonials
 *
 * Section de témoignages clients
 */
function Avatar({ src, alt, fallback }) {
    const [failed, setFailed] = useState(false);

    if (!src || failed) {
        return (
            <div className="w-12 h-12 bg-gradient-to-br from-amber-400 to-orange-600 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-md ring-2 ring-white dark:ring-gray-800">
                {fallback}
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            onError={() => setFailed(true)}
            className="w-12 h-12 rounded-full object-cover shadow-md ring-2 ring-amber-100/80 dark:ring-amber-900/40"
        />
    );
}

function TestimonialCard({ name, role, company, content, avatar, avatarUrl }) {
    return (
        <div className="group h-full flex flex-col rounded-3xl border border-gray-100/90 dark:border-gray-700/80 bg-white/90 dark:bg-gray-900/60 backdrop-blur-sm p-6 sm:p-7 shadow-sm hover:shadow-landing-soft transition-all duration-300 hover:-translate-y-0.5 hover:border-amber-200/70 dark:hover:border-amber-500/20">
            <div className="flex gap-1 mb-4">
                {[...Array(5)].map((_, i) => (
                    <Star key={i} className="w-4 h-4 sm:w-5 sm:h-5 text-amber-400 fill-amber-400" aria-hidden />
                ))}
            </div>

            <div className="mb-3">
                <svg className="w-7 h-7 text-amber-500/40 dark:text-amber-400/30" fill="currentColor" viewBox="0 0 24 24" aria-hidden>
                    <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.996 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.984zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                </svg>
            </div>

            <p className="text-gray-600 dark:text-gray-300 mb-6 leading-relaxed text-sm sm:text-base grow">« {content} »</p>

            <div className="flex items-center gap-3 pt-2 border-t border-gray-100 dark:border-gray-800/80 mt-auto">
                <Avatar src={avatarUrl} alt={name} fallback={avatar} />
                <div>
                    <p className="font-semibold text-gray-900 dark:text-white text-sm">{name}</p>
                    <p className="text-gray-500 dark:text-gray-400 text-xs">
                        {role} · {company}
                    </p>
                </div>
            </div>
        </div>
    );
}

export default function Testimonials() {
    const testimonials = [
        {
            name: 'Aline Kabeya',
            role: 'Fondatrice',
            company: 'Marché Nzambe',
            content: 'OmniPOS a transformé ma façon de vendre. Interface intuitive et support formidable!',
            avatar: 'AK',
            avatarUrl: 'https://api.dicebear.com/9.x/personas/svg?seed=AlineKabeya&backgroundColor=f59e0b',
        },
        {
            name: 'Cédric Nzambe',
            role: 'Manager',
            company: 'Congo Express',
            content: 'Les tableaux de bord sont incroyables. Nos revenus ont augmenté de 40% en 3 mois.',
            avatar: 'CN',
            avatarUrl: 'https://api.dicebear.com/9.x/personas/svg?seed=CedricNzambe&backgroundColor=ea580c',
        },
        {
            name: 'Nadine Mbuyi',
            role: 'Directrice',
            company: 'Mbote Boutique',
            content: "Excellent service client et fonctionnalités que d'autres n'ont pas. Recommandé!",
            avatar: 'NM',
            avatarUrl: 'https://api.dicebear.com/9.x/personas/svg?seed=NadineMbuyi&backgroundColor=d97706',
        },
        {
            name: 'Yannick Ekambi',
            role: 'Propriétaire',
            company: 'Douala Mode',
            content: 'La meilleure décision pour mon business. Paiements sécurisés et rapides, clients satisfaits.',
            avatar: 'YE',
            avatarUrl: 'https://api.dicebear.com/9.x/personas/svg?seed=YannickEkambi&backgroundColor=b45309',
        },
        {
            name: 'Brice Ndzié',
            role: "Chef d'équipe",
            company: 'Yaoundé Tech',
            content: 'Parfait pour gérer nos ventes en ligne. Intégration facile et ROI rapide!',
            avatar: 'BN',
            avatarUrl: 'https://api.dicebear.com/9.x/personas/svg?seed=BriceNdzie&backgroundColor=c2410c',
        },
        {
            name: 'Grace Nkem',
            role: 'Directeur Commercial',
            company: 'Camer Market',
            content: "Solution complète et fiable. Notre équipe l'adore et les clients aussi!",
            avatar: 'GN',
            avatarUrl: 'https://api.dicebear.com/9.x/personas/svg?seed=GraceNkem&backgroundColor=9a3412',
        },
    ];

    return (
        <section
            id="testimonials"
            className="py-24 sm:py-28 lg:py-32 px-4 sm:px-6 lg:px-8 bg-gray-50/90 dark:bg-gray-950 transition-colors duration-200"
        >
            <div className="max-w-7xl mx-auto">
                <LandingReveal className="text-center max-w-3xl mx-auto mb-16 sm:mb-20">
                    <div className="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white dark:bg-gray-900 ring-1 ring-gray-200/80 dark:ring-gray-700 mb-8 shadow-sm">
                        <svg className="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <h2 className="text-3xl sm:text-4xl lg:text-5xl font-bold tracking-tight text-gray-900 dark:text-white mb-5">
                        Ce que nos clients disent
                    </h2>
                    <p className="text-lg sm:text-xl text-gray-600 dark:text-gray-400 leading-relaxed">
                        Des milliers de commerçants font confiance à OmniPOS.
                    </p>
                </LandingReveal>

                <div className="grid sm:grid-cols-2 lg:grid-cols-3 gap-5 sm:gap-6 lg:gap-8 mb-16 sm:mb-20">
                    {testimonials.map((testimonial, idx) => (
                        <LandingReveal key={idx} delay={Math.min(idx * 55, 400)}>
                            <TestimonialCard
                                name={testimonial.name}
                                role={testimonial.role}
                                company={testimonial.company}
                                content={testimonial.content}
                                avatar={testimonial.avatar}
                                avatarUrl={testimonial.avatarUrl}
                            />
                        </LandingReveal>
                    ))}
                </div>

                <LandingReveal delay={100}>
                    <div className="rounded-[2rem] border border-gray-200/80 dark:border-gray-700/80 bg-white/90 dark:bg-gray-900/50 backdrop-blur-sm p-8 sm:p-10 lg:p-12 grid sm:grid-cols-3 gap-10 sm:gap-8 text-center shadow-landing-soft">
                        {[
                            { number: '10K+', label: 'Utilisateurs actifs', icon: '👥' },
                            { number: '99.9%', label: 'Disponibilité', icon: '⚡' },
                            { number: '$50M+', label: 'Transactions traitées', icon: '💰' },
                        ].map((stat, idx) => (
                            <div
                                key={idx}
                                className="flex flex-col items-center group rounded-2xl px-4 py-2 transition-transform duration-300 hover:scale-[1.02]"
                            >
                                <div className="text-3xl mb-2 grayscale group-hover:grayscale-0 transition-all">{stat.icon}</div>
                                <p className="text-3xl sm:text-4xl font-bold bg-gradient-to-r from-amber-500 to-orange-600 bg-clip-text text-transparent tabular-nums">
                                    {stat.number}
                                </p>
                                <p className="text-gray-600 dark:text-gray-400 mt-2 text-sm font-medium">{stat.label}</p>
                            </div>
                        ))}
                    </div>
                </LandingReveal>
            </div>
        </section>
    );
}
