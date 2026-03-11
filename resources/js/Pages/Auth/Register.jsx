import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import FlashMessages from '@/Components/FlashMessages';
import GuestLayout from '@/Layouts/GuestLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect } from 'react';

/**
 * Page: Register
 * 
 * Page d'inscription avec design SaaS moderne et professionnel
 * Support dark mode basé sur la préférence du navigateur
 */
export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        company_name: '',
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
        referral_code: '',
    });

    // Pré-remplir le code de parrainage à partir de l'URL (?ref=CODE)
    useEffect(() => {
        if (typeof window === 'undefined') return;
        try {
            const params = new URLSearchParams(window.location.search);
            const ref = params.get('ref');
            if (ref && !data.referral_code) {
                setData('referral_code', ref);
            }
        } catch {
            // Ignorer en cas d'erreur parsing URL
        }
    }, [data.referral_code, setData]);

    const submit = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Inscription" />

            <div className="min-h-screen bg-gradient-to-br from-gray-50 via-white to-amber-50 dark:from-gray-900 dark:via-gray-900 dark:to-gray-800 flex items-center justify-center px-4 py-12 sm:px-6 lg:px-8 transition-colors duration-200 relative overflow-hidden">
                {/* Animated Background Elements */}
                <div className="absolute inset-0 overflow-hidden pointer-events-none">
                    {/* Floating circles */}
                    <div className="absolute top-20 left-10 w-72 h-72 bg-amber-200/30 dark:bg-amber-900/20 rounded-full blur-3xl animate-pulse"></div>
                    <div className="absolute top-40 right-20 w-96 h-96 bg-orange-200/30 dark:bg-orange-900/20 rounded-full blur-3xl animate-pulse" style={{ animationDelay: '1s', animationDuration: '4s' }}></div>
                    <div className="absolute bottom-20 left-1/4 w-80 h-80 bg-amber-300/20 dark:bg-amber-800/20 rounded-full blur-3xl animate-pulse" style={{ animationDelay: '2s', animationDuration: '5s' }}></div>
                    
                    {/* Floating shapes */}
                    <div className="absolute top-1/4 right-1/4 w-32 h-32 bg-gradient-to-br from-amber-400/20 to-orange-400/20 dark:from-amber-600/10 dark:to-orange-600/10 rounded-2xl rotate-45 animate-float"></div>
                    <div className="absolute bottom-1/3 left-1/3 w-24 h-24 bg-gradient-to-br from-orange-400/20 to-amber-400/20 dark:from-orange-600/10 dark:to-amber-600/10 rounded-full animate-float-slow"></div>
                    <div className="absolute top-1/2 right-1/3 w-20 h-20 bg-gradient-to-br from-amber-300/20 to-orange-300/20 dark:from-amber-500/10 dark:to-orange-500/10 rounded-lg rotate-12 animate-float" style={{ animationDelay: '2.5s' }}></div>
                    <div className="absolute bottom-1/4 left-1/2 w-16 h-16 bg-gradient-to-br from-orange-300/15 to-amber-300/15 dark:from-orange-500/8 dark:to-amber-500/8 rounded-full animate-float-slow" style={{ animationDelay: '3s' }}></div>
                    
                    {/* Grid pattern */}
                    <div className="absolute inset-0 bg-[linear-gradient(to_right,#80808012_1px,transparent_1px),linear-gradient(to_bottom,#80808012_1px,transparent_1px)] bg-[size:24px_24px] opacity-40 dark:opacity-20"></div>
                </div>

                {/* Content */}
                <div className="w-full max-w-md relative z-10">
                    {/* Logo et branding */}
                    <div className="flex flex-col items-center mb-10">
                        <Link 
                            href="/" 
                            className="flex items-center space-x-3 mb-8 group hover:opacity-90 transition-opacity"
                        >
                            <div className="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center shadow-lg group-hover:shadow-xl transition-shadow">
                                <span className="text-white font-bold text-lg">OP</span>
                            </div>
                            <span className="text-2xl font-bold text-gray-900 dark:text-white">OmniPOS</span>
                        </Link>
                        
                        <h1 className="text-3xl md:text-4xl font-bold text-gray-900 dark:text-white text-center mb-3">
                            Créez votre boutique
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400 text-center text-sm max-w-sm mb-6">
                            Démarrez votre activité en ligne en quelques minutes
                        </p>
                        
                        {/* Images et explications */}
                        <div className="flex items-center justify-center gap-4 mb-6">
                            <div className="flex flex-col items-center gap-2">
                                <div className="w-12 h-12 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-lg flex items-center justify-center">
                                    <svg className="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 text-center">Imprimante thermique</p>
                            </div>
                            <div className="flex flex-col items-center gap-2">
                                <div className="w-12 h-12 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-lg flex items-center justify-center">
                                    <svg className="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                                    </svg>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 text-center">Scanner code-barres</p>
                            </div>
                            <div className="flex flex-col items-center gap-2">
                                <div className="w-12 h-12 bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-900/30 dark:to-orange-900/30 rounded-lg flex items-center justify-center">
                                    <svg className="w-6 h-6 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                    </svg>
                                </div>
                                <p className="text-xs text-gray-500 dark:text-gray-400 text-center">Paiements</p>
                            </div>
                        </div>
                    </div>

                    {/* Formulaire */}
                    <div className="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8 space-y-6 transition-all duration-200">
                        <form onSubmit={submit} className="space-y-5">
                            {/* Company Name */}
                            <div>
                                <InputLabel 
                                    htmlFor="company_name" 
                                    value="Nom de votre boutique" 
                                    className="text-gray-900 dark:text-gray-100 font-semibold mb-2 block text-sm" 
                                />
                                <TextInput
                                    id="company_name"
                                    name="company_name"
                                    value={data.company_name}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm"
                                    autoComplete="organization"
                                    isFocused={true}
                                    onChange={(e) => setData('company_name', e.target.value)}
                                    required
                                    placeholder="Ma Boutique En Ligne"
                                />
                                <InputError message={errors.company_name} className="mt-2 text-red-500 dark:text-red-400 text-sm" />
                            </div>

                            {/* Name */}
                            <div>
                                <InputLabel 
                                    htmlFor="name" 
                                    value="Votre nom complet" 
                                    className="text-gray-900 dark:text-gray-100 font-semibold mb-2 block text-sm" 
                                />
                                <TextInput
                                    id="name"
                                    name="name"
                                    value={data.name}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm"
                                    autoComplete="name"
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    placeholder="John Doe"
                                />
                                <InputError message={errors.name} className="mt-2 text-red-500 dark:text-red-400 text-sm" />
                            </div>

                            {/* Email */}
                            <div>
                                <InputLabel 
                                    htmlFor="email" 
                                    value="Adresse email" 
                                    className="text-gray-900 dark:text-gray-100 font-semibold mb-2 block text-sm" 
                                />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm"
                                    autoComplete="email"
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    placeholder="vous@example.com"
                                />
                                <InputError message={errors.email} className="mt-2 text-red-500 dark:text-red-400 text-sm" />
                            </div>

                            {/* Password */}
                            <div>
                                <InputLabel 
                                    htmlFor="password" 
                                    value="Mot de passe" 
                                    className="text-gray-900 dark:text-gray-100 font-semibold mb-2 block text-sm" 
                                />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm"
                                    autoComplete="new-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
                                    placeholder="••••••••"
                                />
                                <InputError message={errors.password} className="mt-2 text-red-500 dark:text-red-400 text-sm" />
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-2">
                                    Minimum 8 caractères avec majuscules, minuscules et chiffres
                                </p>
                            </div>

                            {/* Confirm Password */}
                            <div>
                                <InputLabel
                                    htmlFor="password_confirmation"
                                    value="Confirmer le mot de passe"
                                    className="text-gray-900 dark:text-gray-100 font-semibold mb-2 block text-sm"
                                />
                                <TextInput
                                    id="password_confirmation"
                                    type="password"
                                    name="password_confirmation"
                                    value={data.password_confirmation}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm"
                                    autoComplete="new-password"
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    required
                                    placeholder="••••••••"
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                    className="mt-2 text-red-500 dark:text-red-400 text-sm"
                                />
                            </div>

                            {/* Referral Code (optional) */}
                            <div>
                                <InputLabel
                                    htmlFor="referral_code"
                                    value="Code de parrainage (optionnel)"
                                    className="text-gray-900 dark:text-gray-100 font-semibold mb-2 block text-sm"
                                />
                                <TextInput
                                    id="referral_code"
                                    name="referral_code"
                                    value={data.referral_code}
                                    className="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-xl bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all shadow-sm"
                                    onChange={(e) => setData('referral_code', e.target.value)}
                                    placeholder="Ex: ABCD1234"
                                />
                                <InputError
                                    message={errors.referral_code}
                                    className="mt-2 text-red-500 dark:text-red-400 text-sm"
                                />
                                <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Si vous avez été invité, entrez ici le code de votre parrain.
                                </p>
                            </div>

                            {/* Submit button */}
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold py-3 px-4 rounded-xl transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-lg hover:shadow-xl transform hover:scale-[1.02] active:scale-[0.98] mt-6"
                            >
                                {processing ? (
                                    <span className="flex items-center justify-center">
                                        <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Création en cours...
                                    </span>
                                ) : (
                                    'Créer mon compte'
                                )}
                            </button>
                        </form>

                        {/* Conditions d'utilisation */}
                        <div className="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl text-xs text-gray-700 dark:text-gray-300 text-center">
                            <p className="mb-2">
                                En créant un compte, vous acceptez nos{' '}
                                <Link href="#" className="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium underline">
                                    Conditions d'utilisation
                                </Link>
                                {' '}et notre{' '}
                                <Link href="#" className="text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-medium underline">
                                    Politique de confidentialité
                                </Link>
                            </p>
                            <p className="text-gray-600 dark:text-gray-400 mt-2 flex items-center justify-center gap-1">
                                <svg className="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                                Essai gratuit de 14 jours • Aucune carte bancaire requise
                            </p>
                        </div>

                        {/* Divider */}
                        <div className="relative my-6">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-gray-200 dark:border-gray-700"></div>
                            </div>
                            <div className="relative flex justify-center text-sm">
                                <span className="px-4 bg-white dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                                    Vous avez déjà un compte ?
                                </span>
                            </div>
                        </div>

                        {/* Lien vers connexion */}
                        <div className="text-center">
                            <Link
                                href={route('login')}
                                className="inline-flex items-center justify-center w-full border-2 border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-300 hover:border-amber-500 dark:hover:border-amber-500 hover:text-amber-600 dark:hover:text-amber-400 font-semibold py-3 px-4 rounded-xl transition-all duration-200"
                            >
                                Se connecter
                            </Link>
                        </div>
                    </div>

                    {/* Footer avec lien accueil */}
                    <div className="mt-8 text-center">
                        <Link
                            href="/"
                            className="text-sm text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors inline-flex items-center gap-2"
                        >
                            <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour à l'accueil
                        </Link>
                    </div>
                </div>
            </div>
            <FlashMessages />
        </GuestLayout>
    );
}
