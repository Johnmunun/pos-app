import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import FlashMessages from '@/Components/FlashMessages';
import GuestLayout from '@/Layouts/GuestLayout';
import { AuthMarkLink, AuthVisualBackdrop, authCardClassName, authInputClassName } from '@/Components/AuthPageShell';
import AppSeoHead from '@/Components/AppSeoHead';
import { Link, useForm } from '@inertiajs/react';

/**
 * Page: Connexion — même langage visuel que la landing (ambre / orange, cartes douces)
 */
export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <AppSeoHead pageSeo={{ title: 'Connexion', path: '/login', noindex: true }} />

            <AuthVisualBackdrop>
                <div className="w-full max-w-md">
                    <div className="flex flex-col items-center">
                        <AuthMarkLink />

                        <h1 className="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 dark:text-white text-center mb-3">
                            Bienvenue
                        </h1>
                        <p className="text-gray-600 dark:text-gray-400 text-center text-sm max-w-sm mb-8 leading-relaxed">
                            Connectez-vous pour gérer votre boutique en ligne
                        </p>

                        <div className="flex items-center justify-center gap-5 sm:gap-6 mb-2">
                            {[
                                {
                                    label: 'Imprimante thermique',
                                    path: 'M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z',
                                },
                                {
                                    label: 'Scanner code-barres',
                                    path: 'M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z',
                                },
                                {
                                    label: 'Paiements',
                                    path: 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
                                },
                            ].map((item) => (
                                <div key={item.label} className="flex flex-col items-center gap-2">
                                    <div className="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 dark:from-amber-500/15 dark:to-orange-500/10 ring-1 ring-amber-200/40 dark:ring-amber-500/20 flex items-center justify-center">
                                        <svg className="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden>
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d={item.path} />
                                        </svg>
                                    </div>
                                    <p className="text-[11px] text-gray-500 dark:text-gray-400 text-center max-w-[5.5rem] leading-snug">{item.label}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    {status && (
                        <div className="mb-6 mt-8 p-4 rounded-2xl border border-emerald-200/80 dark:border-emerald-800/60 bg-emerald-50/90 dark:bg-emerald-950/30 text-emerald-800 dark:text-emerald-200 text-sm shadow-sm">
                            {status}
                        </div>
                    )}

                    <div className={`${authCardClassName} p-8 sm:p-8 mt-8 space-y-6`}>
                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel htmlFor="email" value="Adresse email" className="text-gray-900 dark:text-gray-100 font-semibold mb-2 block text-sm" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    className={authInputClassName}
                                    autoComplete="username"
                                    isFocused={true}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="vous@example.com"
                                />
                                <InputError message={errors.email} className="mt-2 text-red-500 dark:text-red-400 text-sm" />
                            </div>

                            <div>
                                <InputLabel htmlFor="password" value="Mot de passe" className="text-gray-900 dark:text-gray-100 font-semibold mb-2 block text-sm" />
                                <TextInput
                                    id="password"
                                    type="password"
                                    name="password"
                                    value={data.password}
                                    className={authInputClassName}
                                    autoComplete="current-password"
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="••••••••"
                                />
                                <InputError message={errors.password} className="mt-2 text-red-500 dark:text-red-400 text-sm" />
                            </div>

                            <div className="flex items-center justify-between gap-3 flex-wrap">
                                <label className="flex items-center cursor-pointer group">
                                    <Checkbox
                                        name="remember"
                                        checked={data.remember}
                                        onChange={(e) => setData('remember', e.target.checked)}
                                        className="rounded border-gray-300 dark:border-gray-600 text-amber-500 focus:ring-amber-500"
                                    />
                                    <span className="ms-2 text-sm text-gray-600 dark:text-gray-400 group-hover:text-gray-900 dark:group-hover:text-gray-200 transition-colors">
                                        Se souvenir de moi
                                    </span>
                                </label>

                                {canResetPassword && (
                                    <Link
                                        href={route('password.request')}
                                        className="text-sm text-amber-600 dark:text-amber-400 hover:text-amber-700 dark:hover:text-amber-300 font-semibold transition-colors"
                                    >
                                        Mot de passe oublié ?
                                    </Link>
                                )}
                            </div>

                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full rounded-2xl bg-gradient-to-r from-amber-500 to-orange-600 hover:from-amber-600 hover:to-orange-700 text-white font-semibold py-3.5 px-4 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed shadow-md shadow-amber-500/20 hover:shadow-lg hover:shadow-amber-500/25 active:scale-[0.99]"
                            >
                                {processing ? (
                                    <span className="flex items-center justify-center gap-2">
                                        <svg className="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden>
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                        </svg>
                                        Connexion en cours…
                                    </span>
                                ) : (
                                    'Se connecter'
                                )}
                            </button>
                        </form>

                        <div className="relative my-2">
                            <div className="absolute inset-0 flex items-center">
                                <div className="w-full border-t border-gray-200/90 dark:border-gray-700/90" />
                            </div>
                            <div className="relative flex justify-center text-sm">
                                <span className="px-4 bg-white/95 dark:bg-gray-900/80 text-gray-500 dark:text-gray-400 rounded-full">
                                    Nouveau sur OmniPOS ?
                                </span>
                            </div>
                        </div>

                        <div className="text-center">
                            <Link
                                href={route('register')}
                                className="inline-flex items-center justify-center w-full border border-gray-200/90 dark:border-gray-700 text-gray-800 dark:text-gray-100 font-semibold py-3.5 px-4 rounded-2xl hover:border-amber-300 dark:hover:border-amber-500/40 hover:bg-amber-50/50 dark:hover:bg-amber-500/5 transition-all duration-200"
                            >
                                Créer un compte gratuit
                            </Link>
                        </div>
                    </div>

                    <div className="mt-8 text-center">
                        <Link
                            href="/"
                            className="text-sm text-gray-600 dark:text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors inline-flex items-center gap-2 font-medium"
                        >
                            <svg className="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden>
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour à l’accueil
                        </Link>
                    </div>
                </div>
            </AuthVisualBackdrop>
            <FlashMessages />
        </GuestLayout>
    );
}
