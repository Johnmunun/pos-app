import { useState } from 'react';
import { router } from '@inertiajs/react';
import axios from 'axios';

/**
 * Page: Welcome/Landing
 *
 * Page publique d'accueil et de connexion.
 *
 * Affiche:
 * - Présentation du système OmniPOS
 * - Formulaire de connexion
 *
 * Utilisée comme point d'entrée pour les utilisateurs non authentifiés.
 */
export default function Welcome() {
    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);

    /**
     * Gérer la connexion utilisateur
     */
    const handleLogin = async (e) => {
        e.preventDefault();
        setError('');
        setLoading(true);

        try {
            // ⚠️ TODO: Implémenter l'endpoint API de connexion
            // POST /api/auth/login
            const response = await axios.post('/api/auth/login', {
                email,
                password,
            });

            if (response.data.success) {
                // Stocker le token
                localStorage.setItem('token', response.data.token);
                // Rediriger vers le dashboard
                router.visit('/dashboard');
            } else {
                setError(
                    response.data.message || 'Connexion échouée'
                );
            }
        } catch (err) {
            setError(
                err.response?.data?.message ||
                'Erreur de connexion. Veuillez vérifier vos identifiants.'
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="min-h-screen bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center px-4">
            <div className="max-w-md w-full">
                {/* Header */}
                <div className="text-center mb-8">
                    <h1 className="text-4xl font-bold text-white mb-2">
                        OmniPOS
                    </h1>
                    <p className="text-blue-100">
                        Smart POS &amp; Business Management
                    </p>
                </div>

                {/* Formulaire de connexion */}
                <div className="bg-white rounded-lg shadow-2xl p-8">
                    <h2 className="text-2xl font-bold text-gray-900 mb-6">
                        Connexion
                    </h2>

                    {error && (
                        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            {error}
                        </div>
                    )}

                    <form onSubmit={handleLogin} className="space-y-4">
                        {/* Email */}
                        <div>
                            <label
                                htmlFor="email"
                                className="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={email}
                                onChange={(e) => setEmail(e.target.value)}
                                placeholder="admin@pos-saas.local"
                                disabled={loading}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                                required
                            />
                        </div>

                        {/* Password */}
                        <div>
                            <label
                                htmlFor="password"
                                className="block text-sm font-medium text-gray-700 mb-2"
                            >
                                Mot de passe
                            </label>
                            <input
                                id="password"
                                type="password"
                                value={password}
                                onChange={(e) => setPassword(e.target.value)}
                                placeholder="••••••••"
                                disabled={loading}
                                className="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition"
                                required
                            />
                        </div>

                        {/* Submit */}
                        <button
                            type="submit"
                            disabled={loading}
                            className="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed mt-6"
                        >
                            {loading ? 'Connexion...' : 'Se connecter'}
                        </button>
                    </form>

                    {/* Info développement */}
                    <div className="mt-6 p-4 bg-gray-50 rounded-lg text-sm text-gray-600 border border-gray-200">
                        <p className="font-semibold mb-2">
                            🔐 Identifiants de développement:
                        </p>
                        <p>
                            <strong>Email:</strong> admin@omnipos.test
                        </p>
                        <p>
                            <strong>Mot de passe:</strong>{' '}
                            <code className="bg-white px-2 py-1 rounded">
                                SecurePassword123
                            </code>
                        </p>
                        <p className="text-xs text-red-600 mt-2">
                            ⚠️ À changer en production!
                        </p>
                    </div>
                </div>

                {/* Footer */}
                <div className="text-center mt-8 text-blue-100 text-sm">
                    <p>© 2026 OmniPOS. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    );
}
