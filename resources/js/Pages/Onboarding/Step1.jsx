import AppSeoHead from '@/Components/AppSeoHead';
import { useForm, Link } from '@inertiajs/react';
import OnboardingStepper from '@/Components/OnboardingStepper';
import OnboardingNavigationButtons from '@/Components/OnboardingNavigationButtons';
import { AuthMarkLink, AuthVisualBackdrop, authCardClassName, authInputClassName } from '@/Components/AuthPageShell';
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

    const inputClass = authInputClassName;
    const inputPasswordClass = `${authInputClassName} pr-12`;

    return (
        <>
            <AppSeoHead pageSeo={{ title: 'Création du compte', noindex: true }} />
            <AuthVisualBackdrop>
                <div className="w-full max-w-md">
                    <div className="flex flex-col items-center text-center">
                        <AuthMarkLink />
                        <h1 className="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 dark:text-white mb-2">
                            Bienvenue sur OmniPOS
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400 text-sm sm:text-base max-w-sm mb-8 leading-relaxed">
                            Créez votre compte en quelques étapes
                        </p>
                    </div>

                    <OnboardingStepper currentStep={1} totalSteps={5} />

                    <div className={`${authCardClassName} p-8 mt-2`}>
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <label htmlFor="name" className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Nom complet
                                </label>
                                <input
                                    id="name"
                                    name="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    className={inputClass}
                                    placeholder="John Doe"
                                />
                                {errors.name && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.name}</p>}
                            </div>

                            <div>
                                <label htmlFor="email" className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Adresse email
                                </label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    className={inputClass}
                                    placeholder="vous@exemple.com"
                                />
                                {errors.email && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.email}</p>}
                            </div>

                            <div>
                                <label htmlFor="password" className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Mot de passe
                                </label>
                                <div className="relative">
                                    <input
                                        id="password"
                                        name="password"
                                        type={showPassword ? 'text' : 'password'}
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                        required
                                        className={inputPasswordClass}
                                        placeholder="••••••••"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute inset-y-0 right-0 pr-3 flex items-center rounded-r-2xl text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                        aria-label={showPassword ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                                    >
                                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                                            {showPassword ? (
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21" />
                                            ) : (
                                                <>
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                </>
                                            )}
                                        </svg>
                                    </button>
                                </div>
                                {errors.password && <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.password}</p>}
                                <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                    Minimum 8 caractères avec majuscules, minuscules et chiffres
                                </p>
                            </div>

                            <div>
                                <label htmlFor="password_confirmation" className="block text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                    Confirmer le mot de passe
                                </label>
                                <input
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    required
                                    className={inputClass}
                                    placeholder="••••••••"
                                />
                                {errors.password_confirmation && (
                                    <p className="mt-2 text-sm text-red-600 dark:text-red-400">{errors.password_confirmation}</p>
                                )}
                            </div>

                            <OnboardingNavigationButtons
                                nextRoute={route('onboarding.step1')}
                                nextLabel="Continuer →"
                                processing={processing}
                            />
                        </form>

                        <div className="mt-6 text-center border-t border-gray-100 dark:border-gray-800 pt-6">
                            <p className="text-sm text-gray-600 dark:text-gray-400">
                                Déjà inscrit ?{' '}
                                <Link href={route('login')} className="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-semibold">
                                    Se connecter
                                </Link>
                            </p>
                        </div>
                    </div>
                </div>
            </AuthVisualBackdrop>
        </>
    );
}
