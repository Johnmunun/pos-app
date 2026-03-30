import { useState } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { BookOpen, Compass, Package, ShoppingCart, Users, Settings, LifeBuoy, CheckCircle2 } from 'lucide-react';

function hasModule(permissions, key) {
    if (!Array.isArray(permissions)) return false;
    if (permissions.includes('*')) return true;
    if (key === 'pharmacy') return permissions.includes('module.pharmacy') || permissions.some((p) => String(p).startsWith('pharmacy.'));
    if (key === 'hardware') return permissions.includes('module.hardware') || permissions.some((p) => String(p).startsWith('hardware.'));
    if (key === 'commerce') return permissions.includes('module.commerce') || permissions.some((p) => String(p).startsWith('commerce.'));
    if (key === 'ecommerce') return permissions.includes('module.ecommerce') || permissions.some((p) => String(p).startsWith('ecommerce.'));
    return false;
}

const firstDayPlan = [
    'Etape 1: ouvrez le module et verifiez le Dashboard.',
    'Etape 2: allez dans Parametres et completez les informations de base.',
    'Etape 3: ajoutez 2 ou 3 produits tests (nom, prix, stock, categorie).',
    'Etape 4: faites une vente test et verifiez le statut de paiement.',
    'Etape 5: creez au moins 1 client et 1 fournisseur.',
    'Etape 6: exportez un rapport pour confirmer vos donnees.',
];

const commonTips = [
    'Toujours verifier le module actif dans la barre laterale avant de creer des donnees.',
    'Utiliser les filtres de date pour eviter de melanger les periodes.',
    'Ne pas supprimer un produit actif sans verifier les ventes liees.',
    'Faire une action test apres chaque changement important.',
];

const onboardingSteps = [
    {
        title: 'Verifier le module actif',
        detail: 'Dans la barre laterale, confirmez que vous etes dans le bon module avant de creer ou modifier des donnees.',
    },
    {
        title: 'Configurer les parametres',
        detail: 'Commencez par la devise, taxes, depots et reglages de base pour eviter les erreurs de calcul ensuite.',
    },
    {
        title: 'Ajouter les donnees de base',
        detail: 'Creez categories, produits, clients et fournisseurs avant de lancer les ventes reelles.',
    },
    {
        title: 'Faire une vente test',
        detail: 'Passez une commande test et verifiez prix, remise, taxe, paiement et mise a jour du stock.',
    },
    {
        title: 'Controler et exporter',
        detail: 'Consultez dashboard + rapports, puis exportez PDF/Excel pour valider la qualite des donnees.',
    },
];

const faqItems = [
    {
        q: 'Je ne vois pas un menu (Produits, Ventes, etc.), pourquoi ?',
        a: 'Le menu depend des permissions et du module actif. Verifiez votre role et les modules actives avec l administrateur.',
    },
    {
        q: 'Un produit n apparait pas en vente ou en vitrine.',
        a: 'Verifiez le stock, le statut actif/publie, la categorie, puis rafraichissez la page apres sauvegarde.',
    },
    {
        q: 'Mes montants semblent incorrects.',
        a: 'Controlez devise, taxes, remises et methode de paiement dans les parametres et dans la commande test.',
    },
    {
        q: 'Je ne peux pas valider une commande.',
        a: 'Verifier d abord le client, le stock, et les champs obligatoires (livraison/paiement selon votre module).',
    },
];

function ModuleGuideCard({ module }) {
    return (
        <article className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5">
            <div className="flex items-center justify-between gap-3">
                <h3 className="font-semibold text-slate-900 dark:text-white">{module.title}</h3>
                <span className="text-xs px-2 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                    Module
                </span>
            </div>

            <p className="mt-3 text-sm text-slate-700 dark:text-slate-200">
                <strong>Objectif:</strong> {module.goal}
            </p>

            <div className="mt-4">
                <h4 className="text-sm font-semibold text-slate-900 dark:text-white">Parcours rapide</h4>
                <ol className="mt-2 space-y-1.5 text-sm text-slate-700 dark:text-slate-200 list-decimal pl-5">
                    {module.flow.map((step) => (
                        <li key={step}>{step}</li>
                    ))}
                </ol>
            </div>

            <div className="mt-4 rounded-lg border border-amber-200 dark:border-amber-900/50 bg-amber-50/60 dark:bg-amber-900/20 p-3">
                <p className="text-sm text-slate-700 dark:text-slate-200">
                    <strong>Exemple (ajouter un produit):</strong> {module.addProduct}
                </p>
            </div>

            <div className="mt-4 flex flex-wrap gap-2">
                {module.links.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className="inline-flex items-center rounded-lg border border-slate-200 dark:border-slate-700 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800"
                    >
                        {item.label}
                    </Link>
                ))}
            </div>
        </article>
    );
}

export default function TutorialIndex() {
    const { auth } = usePage().props;
    const permissions = Array.isArray(auth?.permissions) ? auth.permissions : [];
    const [currentStep, setCurrentStep] = useState(0);
    const [openFaq, setOpenFaq] = useState(0);

    const modules = [
        {
            key: 'pharmacy',
            title: 'Pharmacy',
            goal: 'Gerer produits medicaux, stock, expirations, ventes et achats.',
            links: [
                { label: 'Dashboard', href: '/pharmacy/dashboard' },
                { label: 'Produits', href: '/pharmacy/products' },
                { label: 'Ventes', href: '/pharmacy/sales' },
                { label: 'Achats', href: '/pharmacy/purchases' },
            ],
            flow: [
                'Ouvrez Produits pour creer vos references.',
                'Mettez les categories et depots.',
                'Controlez le stock et les expirations.',
                'Passez par Ventes pour la sortie quotidienne.',
            ],
            addProduct: 'Pharmacy > Produits > Ajouter produit > nom, categorie, prix, stock initial > Enregistrer.',
        },
        {
            key: 'hardware',
            title: 'Quincaillerie',
            goal: 'Piloter references, stock, mouvements, caisse et bons de commande.',
            links: [
                { label: 'Dashboard', href: '/hardware/dashboard' },
                { label: 'Produits', href: '/hardware/products' },
                { label: 'Stock', href: '/hardware/stock' },
                { label: 'Ventes', href: '/hardware/sales' },
            ],
            flow: [
                'Creez les categories et produits.',
                'Controlez stock et mouvements.',
                'Utilisez Caisse pour les ventes rapides.',
                'Suivez les rapports pour ajuster les achats.',
            ],
            addProduct: 'Quincaillerie > Produits > Nouveau produit > prix, quantite, unite, categorie > Enregistrer.',
        },
        {
            key: 'commerce',
            title: 'Global Commerce',
            goal: 'Gerer produits de detail, ventes, transferts, inventaires et depots.',
            links: [
                { label: 'Dashboard', href: '/commerce/dashboard' },
                { label: 'Produits', href: '/commerce/products' },
                { label: 'Ventes', href: '/commerce/sales' },
                { label: 'Transferts', href: '/commerce/transfers' },
            ],
            flow: [
                'Ajoutez vos produits et categories.',
                'Configurez les depots si necessaire.',
                'Faites les ventes et suivez le stock.',
                'Utilisez inventaires pour corriger les ecarts.',
            ],
            addProduct: 'Global Commerce > Produits > Ajouter > nom, prix vente, stock, categorie > Enregistrer.',
        },
        {
            key: 'ecommerce',
            title: 'E-commerce',
            goal: 'Vendre en ligne: catalogue, commandes, paiements, livraison, marketing.',
            links: [
                { label: 'Dashboard', href: '/ecommerce/dashboard' },
                { label: 'Produits', href: '/ecommerce/products' },
                { label: 'Catalogue', href: '/ecommerce/catalog' },
                { label: 'Commandes', href: '/ecommerce/orders' },
            ],
            flow: [
                'Ajoutez les produits et images.',
                'Publiez les produits dans la vitrine.',
                'Configurez livraison et paiements.',
                'Suivez commandes et marketing.',
            ],
            addProduct: 'E-commerce > Produits > Nouveau produit > infos, image, stock > Publier.',
        },
    ].filter((m) => hasModule(permissions, m.key));

    const startSetupHref = (() => {
        if (hasModule(permissions, 'ecommerce')) return '/ecommerce/settings';
        if (hasModule(permissions, 'pharmacy')) return '/pharmacy/products';
        if (hasModule(permissions, 'commerce')) return '/commerce/products';
        if (hasModule(permissions, 'hardware')) return '/hardware/products';
        return '/dashboard';
    })();

    return (
        <AppLayout>
            <Head title="Tutoriel complet" />

            <div className="py-6 space-y-6">
                <section className="rounded-2xl border border-amber-200 dark:border-amber-900/50 bg-gradient-to-br from-amber-50 to-white dark:from-amber-950/30 dark:to-slate-900 p-5 sm:p-6">
                    <div className="flex items-start justify-between gap-4 flex-wrap">
                        <span className="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300">
                            <BookOpen className="h-5 w-5" />
                        </span>
                        <div className="flex-1 min-w-[220px]">
                            <h1 className="text-xl sm:text-2xl font-bold text-slate-900 dark:text-white">Tutoriel complet de la plateforme</h1>
                            <p className="mt-1 text-sm text-slate-600 dark:text-slate-300">
                                Ce guide est ecrit pour etre simple: quoi faire en premier, comment naviguer, comment ajouter vos donnees et quoi verifier pour eviter les erreurs.
                            </p>
                        </div>
                        <Link
                            href={startSetupHref}
                            className="inline-flex items-center rounded-lg bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 text-sm font-semibold shrink-0"
                        >
                            Commencer la configuration
                        </Link>
                    </div>
                </section>

                <section className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5">
                    <div className="flex items-center gap-2 mb-3">
                        <Compass className="h-4 w-4 text-indigo-500" />
                        <h2 className="font-semibold text-slate-900 dark:text-white">Premiere journee: plan pas-a-pas</h2>
                    </div>
                    <ul className="space-y-2 text-sm text-slate-700 dark:text-slate-200">
                        {firstDayPlan.map((s) => (
                            <li key={s} className="flex items-start gap-2">
                                <CheckCircle2 className="h-4 w-4 mt-0.5 text-emerald-500 shrink-0" />
                                <span>{s}</span>
                            </li>
                        ))}
                    </ul>
                </section>

                <section className="rounded-2xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5">
                    <div className="flex items-center justify-between gap-3">
                        <h2 className="font-semibold text-slate-900 dark:text-white">Onboarding interactif</h2>
                        <span className="text-xs px-2 py-1 rounded-full bg-indigo-100 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300">
                            Etape {currentStep + 1}/{onboardingSteps.length}
                        </span>
                    </div>
                    <div className="mt-4 rounded-xl border border-indigo-200 dark:border-indigo-900/40 bg-indigo-50/50 dark:bg-indigo-900/20 p-4">
                        <h3 className="font-semibold text-slate-900 dark:text-white">{onboardingSteps[currentStep].title}</h3>
                        <p className="mt-2 text-sm text-slate-700 dark:text-slate-200">{onboardingSteps[currentStep].detail}</p>
                    </div>
                    <div className="mt-4 flex items-center justify-between">
                        <button
                            type="button"
                            onClick={() => setCurrentStep((s) => Math.max(0, s - 1))}
                            disabled={currentStep === 0}
                            className="inline-flex items-center rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-2 text-sm font-medium text-slate-700 dark:text-slate-200 disabled:opacity-50"
                        >
                            Etape precedente
                        </button>
                        <button
                            type="button"
                            onClick={() => setCurrentStep((s) => Math.min(onboardingSteps.length - 1, s + 1))}
                            disabled={currentStep === onboardingSteps.length - 1}
                            className="inline-flex items-center rounded-lg bg-indigo-600 hover:bg-indigo-700 px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
                        >
                            Etape suivante
                        </button>
                    </div>
                </section>

                <section className="grid grid-cols-1 xl:grid-cols-2 gap-4">
                    {modules.length > 0 ? (
                        modules.map((module) => <ModuleGuideCard key={module.key} module={module} />)
                    ) : (
                        <article className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-5">
                            <h3 className="font-semibold text-slate-900 dark:text-white">Aucun module detecte</h3>
                            <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">
                                Vous n avez pas encore de module actif sur ce profil. Contactez un administrateur pour activer vos permissions.
                            </p>
                        </article>
                    )}
                </section>

                <section className="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <article className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
                        <div className="flex items-center gap-2 mb-2">
                            <ShoppingCart className="h-4 w-4 text-amber-500" />
                            <h4 className="font-semibold text-slate-900 dark:text-white">Ventes</h4>
                        </div>
                        <p className="text-sm text-slate-600 dark:text-slate-300">
                            Faites d abord une vente test puis verifiez les statuts (commande, paiement, livraison) avant de travailler en production.
                        </p>
                    </article>
                    <article className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
                        <div className="flex items-center gap-2 mb-2">
                            <Users className="h-4 w-4 text-blue-500" />
                            <h4 className="font-semibold text-slate-900 dark:text-white">Clients et fournisseurs</h4>
                        </div>
                        <p className="text-sm text-slate-600 dark:text-slate-300">
                            Creez des fiches propres (nom, telephone, email) pour faciliter l historique, les relances et les rapports.
                        </p>
                    </article>
                    <article className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
                        <div className="flex items-center gap-2 mb-2">
                            <Settings className="h-4 w-4 text-fuchsia-500" />
                            <h4 className="font-semibold text-slate-900 dark:text-white">Parametres critiques</h4>
                        </div>
                        <p className="text-sm text-slate-600 dark:text-slate-300">
                            Avant toute saisie massive, confirmez la devise, les taxes, les depots et les regles de paiement/livraison.
                        </p>
                    </article>
                </section>

                <section className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
                    <div className="flex items-center gap-2 mb-2">
                        <Package className="h-4 w-4 text-emerald-500" />
                        <h4 className="font-semibold text-slate-900 dark:text-white">Erreurs frequentes a eviter</h4>
                    </div>
                    <ul className="space-y-1.5 text-sm text-slate-700 dark:text-slate-200 list-disc pl-5">
                        {commonTips.map((tip) => (
                            <li key={tip}>{tip}</li>
                        ))}
                    </ul>
                </section>

                <section className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
                    <h4 className="font-semibold text-slate-900 dark:text-white">FAQ rapide (probleme -&gt; solution)</h4>
                    <div className="mt-3 space-y-2">
                        {faqItems.map((item, index) => {
                            const isOpen = openFaq === index;
                            return (
                                <div key={item.q} className="rounded-lg border border-slate-200 dark:border-slate-700">
                                    <button
                                        type="button"
                                        onClick={() => setOpenFaq(isOpen ? -1 : index)}
                                        className="w-full text-left px-3 py-2 text-sm font-medium text-slate-800 dark:text-slate-200"
                                    >
                                        {item.q}
                                    </button>
                                    {isOpen ? (
                                        <div className="px-3 pb-3 text-sm text-slate-600 dark:text-slate-300">
                                            {item.a}
                                        </div>
                                    ) : null}
                                </div>
                            );
                        })}
                    </div>
                </section>

                <section className="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-4">
                    <div className="flex items-center gap-2 mb-2">
                        <LifeBuoy className="h-4 w-4 text-red-500" />
                        <h4 className="font-semibold text-slate-900 dark:text-white">Besoin d aide</h4>
                    </div>
                    <p className="text-sm text-slate-600 dark:text-slate-300">
                        Si un ecran ne reagit pas comme prevu, faites une capture et contactez le support depuis le bouton d aide de l application.
                    </p>
                </section>
            </div>
        </AppLayout>
    );
}

