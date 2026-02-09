import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';

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
        { key: 'kiosk', name: 'Kiosque', icon: '🏪', description: 'Petits commerces, snacks, boissons' },
        { key: 'supermarket', name: 'Supermarché', icon: '🛒', description: 'Épicerie, produits alimentaires, ménagers' },
        { key: 'butchery', name: 'Boucherie', icon: '🥩', description: 'Viandes, volailles, produits frais' },
        { key: 'hardware', name: 'Quincaillerie', icon: '🔧', description: 'Matériel, outillage, bricolage' },
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
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800">
            <Head title="Secteur d'activité - Étape 2/4" />
            
            {/* Header fixé */}
            <header className="fixed top-0 left-0 right-0 bg-white/80 dark:bg-gray-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-gray-700 z-50">
                <div className="max-w-4xl mx-auto px-4 py-4">
                    <div className="flex items-center justify-between">
                        <div className="flex items-center space-x-3">
                            <div className="w-10 h-10 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg">
                                <span className="text-white font-bold text-sm">POS</span>
                            </div>
                            <div>
                                <h1 className="text-xl font-bold text-gray-900 dark:text-white">POS SaaS</h1>
                                <p className="text-sm text-gray-600 dark:text-gray-400">Inscription marchand</p>
                            </div>
                        </div>
                        
                        <div className="hidden md:block">
                            <div className="flex items-center space-x-2">
                                {[1, 2, 3, 4].map((s) => (
                                    <div
                                        key={s}
                                        className={`w-3 h-3 rounded-full transition-all ${
                                            s <= 2 
                                                ? 'bg-amber-500' 
                                                : 'bg-gray-300 dark:bg-gray-600'
                                        }`}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {/* Progress bar */}
            <div className="fixed top-16 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 z-40">
                <div 
                    className="h-full bg-amber-500 transition-all duration-500 ease-out"
                    style={{ width: '50%' }} // 2/4 = 50%
                />
            </div>

            {/* Contenu scrollable */}
            <main className="pt-20 pb-8">
                <div className="max-w-2xl mx-auto px-4">
                    {/* Stepper */}
                    <OnboardingStepper currentStep={2} totalSteps={4} />
                    
                    {/* Titre */}
                    <div className="text-center mb-8">
                        <h2 className="text-2xl font-bold text-gray-900 dark:text-white mb-2">
                            Votre activité
                        </h2>
                        <p className="text-gray-600 dark:text-gray-400">
                            Ces informations nous aideront à personnaliser votre expérience
                        </p>
                    </div>

                    {/* Formulaire */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-6">
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
                                                p-4 rounded-xl border-2 cursor-pointer transition-all duration-200
                                                ${data.sector === sector.key
                                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20 shadow-lg'
                                                    : 'border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700 hover:bg-gray-50 dark:hover:bg-gray-700'
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
                                                p-3 rounded-lg border cursor-pointer transition-all
                                                ${data.business_type === type.key
                                                    ? 'border-amber-500 bg-amber-50 dark:bg-amber-900/20'
                                                    : 'border-gray-200 dark:border-gray-700 hover:border-amber-300 dark:hover:border-amber-700 hover:bg-gray-50 dark:hover:bg-gray-700'
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
                            <div className="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl p-4">
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
                                            Vous pourrez accéder à tous les modules une fois votre compte activé.
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
            </main>
        </div>
    );
}