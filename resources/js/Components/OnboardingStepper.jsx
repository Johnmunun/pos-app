import React from 'react';

export default function OnboardingStepper({ currentStep, totalSteps = 5 }) {
    const steps = [
        { number: 1, title: 'Compte', description: 'Création du compte' },
        { number: 2, title: 'Entreprise', description: 'Informations boutique' },
        { number: 3, title: 'Secteur', description: 'Choix activité' },
        { number: 4, title: 'Légal', description: 'Documents (optionnel)' },
        { number: 5, title: 'Confirmation', description: 'Validation' },
    ];

    return (
        <div className="mb-8">
            <div className="flex justify-between items-center relative">
                {/* Barre de progression */}
                <div className="absolute top-4 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                    <div 
                        className="h-full bg-amber-500 transition-all duration-500 ease-out"
                        style={{ width: `${(currentStep / totalSteps) * 100}%` }}
                    ></div>
                </div>

                {/* Étapes */}
                {steps.map((step, index) => (
                    <div key={step.number} className="relative flex flex-col items-center z-10">
                        {/* Cercle */}
                        <div className={`
                            w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-all duration-300
                            ${step.number <= currentStep 
                                ? 'bg-amber-500 text-white shadow-lg' 
                                : 'bg-white dark:bg-gray-800 text-gray-400 dark:text-gray-500 border-2 border-gray-200 dark:border-gray-700'
                            }
                        `}>
                            {step.number < currentStep ? (
                                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                                </svg>
                            ) : (
                                step.number
                            )}
                        </div>
                        
                        {/* Texte */}
                        <div className="mt-2 text-center hidden md:block">
                            <div className={`text-xs font-semibold ${
                                step.number <= currentStep 
                                    ? 'text-amber-600 dark:text-amber-400' 
                                    : 'text-gray-500 dark:text-gray-400'
                            }`}>
                                {step.title}
                            </div>
                            <div className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                {step.description}
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Progression texte (mobile) */}
            <div className="mt-6 text-center md:hidden">
                <p className="text-sm text-gray-600 dark:text-gray-400">
                    Étape <span className="font-semibold text-amber-600 dark:text-amber-400">{currentStep}</span> sur {totalSteps}
                </p>
            </div>
        </div>
    );
}