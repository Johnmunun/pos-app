import { Head, useForm } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';
import { useState } from 'react';

export default function Step1() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.step1'));
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8">
            <Head title="Création du compte - Étape 1/5" />
            
            <div className="w-full max-w-md">
                {/* Header */}
                <div className="text-center mb-8">
                    <h1 className="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                        Bienvenue sur POS SaaS
                    </h1>
                    <p className="text-gray-600 dark:text-gray-400">
                        Créez votre compte en quelques étapes
                    </p>
                </div>

                {/* Stepper */}
                <OnboardingStepper currentStep={1} totalSteps={4} />

                {/* Formulaire */}
                <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
                    <form onSubmit={submit} className="space-y-6">
                        {/* Nom complet */}
                        <div>
                            <label 
                                htmlFor="name" 
                                className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                            >
                                Nom complet
                            </label>
                            <input
                                id="name"
                                name="name"
                                type="text"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                required
                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                placeholder="John Doe"
                            />
                            {errors.name && (
                                <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.name}</p>
                            )}
                        </div>

                        {/* Email */}
                        <div>
                            <label 
                                htmlFor="email" 
                                className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                            >
                                Adresse email
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                required
                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                placeholder="vous@exemple.com"
                            />
                            {errors.email && (
                                <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.email}</p>
                            )}
                        </div>

                        {/* Mot de passe */}
                        <div>
                            <label 
                                htmlFor="password" 
                                className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                            >
                                Mot de passe
                            </label>
                            <div className="relative">
                                <input
                                    id="password"
                                    name="password"
                                    type={showPassword ? "text" : "password"}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                    className="w-full px-4 py-3 pr-12 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                    placeholder="••••••••"
                                />
                                <button
                                    type="button"
                                    onClick={() => setShowPassword(!showPassword)}
                                    className="absolute inset-y-0 right-0 pr-3 flex items-center"
                                >
                                    <svg 
                                        className="h-5 w-5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" 
                                        fill="none" 
                                        viewBox="0 0 24 24" 
                                        stroke="currentColor"
                                    >
                                        {showPassword ? (
                                            <>
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                            </>
                                        ) : (
                                            <>
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </>
                                        )}
                                    </svg>
                                </button>
                            </div>
                            {errors.password && (
                                <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.password}</p>
                            )}
                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Minimum 8 caractères avec majuscules, minuscules et chiffres
                            </p>
                        </div>

                        {/* Confirmation mot de passe */}
                        <div>
                            <label 
                                htmlFor="password_confirmation" 
                                className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2"
                            >
                                Confirmer le mot de passe
                            </label>
                            <input
                                id="password_confirmation"
                                name="password_confirmation"
                                type="password"
                                value={data.password_confirmation}
                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                required
                                className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all"
                                placeholder="••••••••"
                            />
                            {errors.password_confirmation && (
                                <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.password_confirmation}</p>
                            )}
                        </div>

                        {/* Bouton submit */}
                        <OnboardingNavigationButtons
                            nextRoute={route('onboarding.step1')}
                            nextLabel="Continuer →"
                            processing={processing}
                        />
                    </form>

                    {/* Déjà un compte */}
                    <div className="mt-6 text-center">
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                            Déjà inscrit ?{}
                            <a 
                                href={route('login')} 
                                className="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-semibold"
                            >
                                Se connecter
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}