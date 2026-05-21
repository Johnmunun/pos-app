import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';
import OnboardingPageChrome from '@/Components/OnboardingPageChrome';
import { authCardClassName } from '@/Components/AuthPageShell';

export default function Step2({ sectors, businessTypes, sessionData }) {
    const { data, setData, post, processing, errors } = useForm({
        sector: sessionData?.sector || '',
        business_type: sessionData?.business_type || '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.step2.process'));
    };

    // Options de secteurs avec icônes
    const sectorOptions = [
        { key: 'pharmacy', name: 'Pharmacie', icon: '💊', description: 'Médicaments, parapharmacie, produits de santé' },
        { key: 'global_commerce', name: 'Commerce général', icon: '🏬', description: 'Alimentaire, épicerie, produits courants (POS commerce)' },
        { key: 'kiosk', name: 'Kiosque', icon: '🏪', description: 'Petits commerces, snacks, boissons' },
        { key: 'supermarket', name: 'Supermarché', icon: '🛒', description: 'Épicerie, produits alimentaires, ménagers' },
        { key: 'butchery', name: 'Boucherie', icon: '🥩', description: 'Viandes, volailles, produits frais' },
        { key: 'hardware', name: 'Quincaillerie', icon: '🔧', description: 'Matériel, outillage, bricolage' },
        { key: 'ecommerce', name: 'E-commerce', icon: '🛍️', description: 'Vente en ligne, marketplace, boutique digitale' },
        { key: 'other', name: 'Autre', icon: '🏢', description: 'Autres types de commerce' },
    ];

    // Options de types de commerce
    const businessTypeOptions = [
        { key: 'individual', name: 'Commerçant individuel' },
        { key: 'sarl', name: 'SARL' },
        { key: 'sa', name: 'SA' },
        { key: 'sas', name: 'SAS' },
        { key: 'sasu', name: 'SASU' },
        { key: 'association', name: 'Association' },
        { key: 'other', name: 'Autre' },
    ];

    return (
        <>
            <Head title="Secteur d'activité" />
            <OnboardingPageChrome currentStep={2}>
                <div className="max-w-2xl mx-auto px-4">
                    {/* Stepper */}
                    <OnboardingStepper currentStep={2} totalSteps={5} />
                    
                    {/* Titre */}
                    <div className="text-center mb-8">
                        <h2 className="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">
                            Votre activité
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400 text-sm sm:text-base leading-relaxed">
                            Ces informations nous aideront à personnaliser votre expérience
                        </p>
                    </div>

                    <div className={`${authCardClassName} p-6 sm:p-8`}>
                        <form onSubmit={submit} className="space-y-8">

                            {/* Sélection du secteur */}
                            <div>
                                <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    Secteur d'activité *
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {sectorOptions.map((sector) => (
                                        <div
                                            key={sector.key}
                                            onClick={() => setData('sector', sector.key)}
                                            className={`
                                                p-4 rounded-2xl border-2 cursor-pointer transition-all duration-200
                                                ${data.sector === sector.key
                                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 shadow-md ring-1 ring-amber-500/15'
                                                    : 'border-gray-100 dark:border-gray-800 hover:border-amber-300 dark:hover:border-amber-600/50 hover:bg-gray-50/80 dark:hover:bg-gray-800/50'
                                                }
                                            `}
                                        >
                                            <div className="flex items-start gap-3">
                                                <div className="text-xl flex-shrink-0">
                                                    {sector.icon}
                                                </div>
                                                <div className="flex-1">
                                                    <h3 className={`
                                                        font-semibold mb-1
                                                        ${data.sector === sector.key
                                                            ? 'text-amber-700 dark:text-amber-300'
                                                            : 'text-gray-900 dark:text-gray-100'
                                                        }
                                                    `}>
                                                        {sector.name}
                                                    </h3>
                                                    <p className={`
                                                        text-xs
                                                        ${data.sector === sector.key
                                                            ? 'text-amber-600 dark:text-amber-400'
                                                            : 'text-gray-600 dark:text-gray-400'
                                                        }
                                                    `}>
                                                        {sector.description}
                                                    </p>
                                                </div>
                                                {data.sector === sector.key && (
                                                    <div className="flex-shrink-0">
                                                        <svg className="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                                        </svg>
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                {errors.sector && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.sector}</p>
                                )}
                            </div>

                            {/* Sélection du type de commerce */}
                            <div>
                                <label className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-4">
                                    Type de commerce *
                                </label>
                                <div className="grid grid-cols-1 gap-2">
                                    {businessTypeOptions.map((type) => (
                                        <div
                                            key={type.key}
                                            onClick={() => setData('business_type', type.key)}
                                            className={`
                                                p-3.5 rounded-xl border cursor-pointer transition-all duration-200
                                                ${data.business_type === type.key
                                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 shadow-sm ring-1 ring-amber-500/10'
                                                    : 'border-gray-100 dark:border-gray-800 hover:border-amber-300 dark:hover:border-amber-600/50 hover:bg-gray-50/80 dark:hover:bg-gray-800/50'
                                                }
                                            `}
                                        >
                                            <div className="flex items-center justify-between">
                                                <span className={`
                                                    font-medium
                                                    ${data.business_type === type.key
                                                        ? 'text-amber-700 dark:text-amber-300'
                                                        : 'text-gray-900 dark:text-gray-100'
                                                    }
                                                `}>
                                                    {type.name}
                                                </span>
                                                {data.business_type === type.key && (
                                                    <svg className="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                                    </svg>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                {errors.business_type && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.business_type}</p>
                                )}
                            </div>

                            {/* Message d'information */}
                            <div className="bg-blue-50/90 dark:bg-blue-950/30 border border-blue-200/80 dark:border-blue-800/60 rounded-2xl p-4 shadow-sm">
                                <div className="flex items-start gap-3">
                                    <svg className="w-5 h-5 text-blue-500 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                                    </svg>
                                    <div>
                                        <h4 className="text-sm font-semibold text-blue-800 dark:text-blue-200 mb-1">
                                            Information importante
                                        </h4>
                                        <p className="text-sm text-blue-700 dark:text-blue-300">
                                            Ces choix nous aident à personnaliser votre interface mais ne limitent pas vos fonctionnalités.
                                            Le mode de démarrage de la boutique (vide ou préconfigurée) sera choisi à la dernière étape, juste avant la création du compte.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {/* Boutons d'action */}
                            <OnboardingNavigationButtons
                                previousRoute={route('onboarding.step1')}
                                nextRoute={route('onboarding.step2.process')}
                                nextLabel="Continuer →"
                                processing={processing}
                                disabled={!data.sector || !data.business_type}
                            />
                        </form>
                    </div>
                </div>
            </OnboardingPageChrome>
        </>
    );
}