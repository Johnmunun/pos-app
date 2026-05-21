import { ShoppingBag, CreditCard, BarChart, Sparkles, TrendingUp } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import LandingReveal from './LandingReveal';

/**
 * Component: Hero
 *
 * Section hero premium : titre fort, CTA, mockup avec éléments flottants
 */
export default function Hero() {
    const { props } = usePage();
    const heroImages = props.heroImages || {};
    const heroMainUrl = heroImages.main || '/images/pos-hero-main.png';
    const heroDevicesUrl = heroImages.devices || '/images/pos-hero-devices.png';

    const equipment = [
        {
            label: 'Imprimante thermique',
            desc: 'Reçus instantanés',
            icon: (
                <svg className="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
            ),
        },
        {
            label: 'Scanner code-barres',
            desc: 'Ajout rapide',
            icon: (
                <svg className="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                </svg>
            ),
        },
        {
            label: 'Terminal de paiement',
            desc: 'Tous modes',
            icon: (
                <svg className="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
            ),
        },
        {
            label: 'Tablette & mobile',
            desc: 'Multi-appareils',
            icon: (
                <svg className="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            ),
        },
    ];

    return (
        <section className="relative overflow-x-hidden pt-28 pb-20 sm:pt-32 sm:pb-28 lg:pt-36 lg:pb-32">
            <div className="pointer-events-none absolute inset-0 bg-gradient-to-b from-amber-50/90 via-white to-white dark:from-gray-950 dark:via-gray-950 dark:to-gray-900 transition-colors duration-200" />
            <div className="pointer-events-none absolute -top-24 right-1/4 h-[28rem] w-[28rem] rounded-full bg-gradient-to-br from-amber-400/25 via-orange-400/15 to-transparent blur-3xl dark:from-amber-500/12 dark:via-orange-500/8" />
            <div className="pointer-events-none absolute bottom-0 left-0 h-80 w-80 rounded-full bg-gradient-to-tr from-orange-200/30 to-transparent blur-3xl dark:from-orange-600/10" />

            <div className="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div className="grid gap-14 lg:gap-16 lg:grid-cols-12 lg:items-center">
                    <LandingReveal className="lg:col-span-6 text-center mx-auto w-full" delay={0}>
                        <div className="max-w-3xl mx-auto">
                        <h1 className="text-4xl sm:text-5xl lg:text-6xl xl:text-[3.5rem] font-bold tracking-tight text-gray-900 dark:text-white leading-[1.08] mb-6">
                            Votre point de vente{' '}
                            <span className="bg-gradient-to-r from-amber-500 via-orange-500 to-orange-600 bg-clip-text text-transparent">
                                en ligne
                            </span>
                        </h1>

                        <p className="text-lg sm:text-xl text-gray-600 dark:text-gray-300 leading-relaxed font-medium mb-10">
                            La solution complète pour gérer vos ventes digitales, paiements et clients. Tout ce qu’il vous faut en une seule plateforme.
                        </p>

                        <div className="flex flex-col sm:flex-row gap-3 sm:gap-4 justify-center items-stretch sm:items-center mb-10 sm:mb-12">
                            <a
                                href={route('register')}
                                className="inline-flex items-center justify-center gap-2 rounded-2xl px-8 py-4 text-base font-semibold text-white bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 shadow-lg shadow-amber-500/25 hover:shadow-xl hover:shadow-amber-500/30 active:scale-[0.98] transition-all duration-200"
                            >
                                <Sparkles className="w-5 h-5 opacity-90" aria-hidden />
                                Démarrer gratuitement
                            </a>
                            <a
                                href="#features"
                                className="inline-flex items-center justify-center rounded-2xl px-8 py-4 text-base font-semibold border border-gray-200/90 dark:border-gray-600/90 bg-white/80 dark:bg-gray-900/50 backdrop-blur-sm text-gray-800 dark:text-gray-100 hover:border-amber-300 dark:hover:border-amber-500/50 hover:bg-white dark:hover:bg-gray-900/80 active:scale-[0.98] transition-all duration-200 shadow-sm"
                            >
                                Découvrir les fonctionnalités
                            </a>
                        </div>

                        <div className="flex flex-col items-center gap-4 mb-10 lg:mb-0">
                            <div className="flex -space-x-2.5">
                                {[
                                    { src: 'https://i.pravatar.cc/80?img=47', initials: 'AK' },
                                    { src: 'https://i.pravatar.cc/80?img=12', initials: 'CN' },
                                    { src: 'https://i.pravatar.cc/80?img=32', initials: 'NM' },
                                    { src: 'https://i.pravatar.cc/80?img=15', initials: 'YE' },
                                    { src: 'https://i.pravatar.cc/80?img=59', initials: 'BN' },
                                    { src: 'https://i.pravatar.cc/80?img=25', initials: 'GN' },
                                ].map((avatar, idx) => (
                                    <div
                                        key={idx}
                                        className="h-10 w-10 rounded-full border-2 border-white dark:border-gray-950 overflow-hidden shadow-md bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/40 dark:to-orange-900/30"
                                        title="Utilisateur"
                                    >
                                        <img
                                            src={avatar.src}
                                            alt=""
                                            loading="lazy"
                                            className="h-full w-full object-cover"
                                            onError={(e) => {
                                                e.currentTarget.style.display = 'none';
                                                const fallback = e.currentTarget.parentElement?.querySelector('.avatar-fallback');
                                                if (fallback) fallback.classList.remove('hidden');
                                            }}
                                        />
                                        <span className="avatar-fallback hidden flex h-full w-full items-center justify-center text-[10px] font-bold text-amber-800 dark:text-amber-200">
                                            {avatar.initials}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            <p className="text-sm sm:text-base text-gray-600 dark:text-gray-400">
                                <span className="font-semibold text-gray-900 dark:text-white">+ de 150 équipes</span>{' '}
                                utilisent déjà la plateforme
                            </p>
                        </div>
                        </div>

                        <div className="mt-8 grid grid-cols-1 sm:grid-cols-3 gap-4 w-full max-w-4xl mx-auto">
                            {[
                                { icon: ShoppingBag, text: 'Caisse rapide commerce & retail' },
                                { icon: CreditCard, text: 'Tous vos paiements au même endroit' },
                                { icon: BarChart, text: 'Statistiques de vente en temps réel' },
                            ].map((item, idx) => (
                                <div
                                    key={idx}
                                    className="flex flex-col items-center justify-center text-center gap-3 rounded-2xl border border-gray-100 dark:border-gray-800/80 bg-white/60 dark:bg-gray-900/40 backdrop-blur-sm px-4 py-3.5 shadow-sm"
                                >
                                    <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-50 dark:bg-emerald-500/10">
                                        <item.icon className="w-4 h-4 text-emerald-600 dark:text-emerald-400" aria-hidden />
                                    </div>
                                    <span className="text-sm font-medium text-gray-700 dark:text-gray-300 leading-snug">{item.text}</span>
                                </div>
                            ))}
                        </div>
                    </LandingReveal>

                    <LandingReveal className="lg:col-span-6 relative mt-4 lg:mt-0" delay={80}>
                        <div className="relative mx-auto max-w-lg lg:max-w-xl">
                            <div className="absolute -left-4 top-1/4 z-20 hidden sm:flex motion-safe:animate-float-gentle flex-col gap-2 rounded-2xl border border-white/60 dark:border-gray-700/80 bg-white/85 dark:bg-gray-900/85 backdrop-blur-xl px-4 py-3 shadow-landing-soft">
                                <div className="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                    <TrendingUp className="w-3.5 h-3.5 text-emerald-500" aria-hidden />
                                    Aujourd’hui
                                </div>
                                <p className="text-lg font-bold text-gray-900 dark:text-white tabular-nums">+18,4%</p>
                                <p className="text-xs text-gray-500 dark:text-gray-400">vs. hier</p>
                            </div>

                            <div className="absolute -right-2 sm:right-0 top-8 z-20 motion-safe:animate-float flex rounded-2xl border border-emerald-200/60 dark:border-emerald-500/20 bg-emerald-50/90 dark:bg-emerald-950/50 backdrop-blur-md px-4 py-2.5 shadow-landing-soft">
                                <span className="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Paiement accepté</span>
                            </div>

                            <div className="relative rounded-[2rem] border border-gray-200/80 dark:border-gray-700/60 bg-gradient-to-b from-white to-gray-50/80 dark:from-gray-900 dark:to-gray-950 p-3 sm:p-4 shadow-landing-soft-lg ring-1 ring-black/[0.03] dark:ring-white/[0.06]">
                                <div className="absolute inset-x-8 -top-px h-px bg-gradient-to-r from-transparent via-amber-400/40 to-transparent" />
                                <div className="overflow-hidden rounded-2xl bg-gray-100 dark:bg-gray-800/80 ring-1 ring-gray-200/60 dark:ring-gray-700/50">
                                    <img
                                        src={heroMainUrl}
                                        alt="Aperçu de l’application OmniPOS"
                                        className="w-full h-auto max-h-[260px] sm:max-h-[360px] lg:max-h-[420px] object-cover object-center"
                                        loading="eager"
                                    />
                                </div>
                                <div className="pointer-events-none absolute -bottom-6 -right-4 w-[42%] max-w-[200px] motion-safe:animate-float-slow drop-shadow-2xl sm:block hidden">
                                    <img
                                        src={heroDevicesUrl}
                                        alt=""
                                        className="w-full h-auto object-contain rounded-xl"
                                        loading="lazy"
                                    />
                                </div>
                                <div className="sm:hidden mt-4 flex justify-center">
                                    <img
                                        src={heroDevicesUrl}
                                        alt=""
                                        className="w-[55%] max-w-[220px] h-auto object-contain"
                                        loading="lazy"
                                    />
                                </div>
                            </div>
                        </div>
                    </LandingReveal>
                </div>

                <LandingReveal className="mt-16 sm:mt-20 lg:mt-24" delay={120}>
                    <p className="text-center text-xs sm:text-sm font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-8">
                        Compatible avec vos équipements
                    </p>
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 max-w-4xl mx-auto">
                        {equipment.map((item, idx) => (
                            <div
                                key={idx}
                                className="group flex flex-col items-center text-center gap-2.5 rounded-2xl border border-gray-100 dark:border-gray-800 bg-white/70 dark:bg-gray-900/40 backdrop-blur-sm p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-amber-200/80 dark:hover:border-amber-500/25 transition-all duration-300 hover:-translate-y-0.5"
                            >
                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-500/15 dark:to-orange-500/10 ring-1 ring-amber-200/50 dark:ring-amber-500/20 transition-transform duration-300 group-hover:scale-105">
                                    {item.icon}
                                </div>
                                <p className="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white leading-tight">{item.label}</p>
                                <p className="text-[11px] sm:text-xs text-gray-500 dark:text-gray-400">{item.desc}</p>
                            </div>
                        ))}
                    </div>
                </LandingReveal>
            </div>
        </section>
    );
}
