import AppLayout from '@/Layouts/AppLayout';
import { Head, Link, useForm } from '@inertiajs/react';

export default function Faq({ articles, categories, filters }) {
    const { data, setData, get } = useForm({
        q: filters.q || '',
        category_id: filters.category_id || '',
    });

    const submit = (e) => {
        e.preventDefault();
        get(route('support.faq.index'), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        FAQ & Base de connaissance
                    </h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Recherchez dans la base de connaissances OmniPOS avant de créer un ticket.
                    </p>
                </div>
            }
        >
            <Head title="FAQ & Base de connaissance" />

            <div className="py-6">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 space-y-6">
                    {/* Recherche */}
                    <form
                        onSubmit={submit}
                        className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 flex flex-wrap gap-4 items-end"
                    >
                        <div className="flex-1 min-w-[200px]">
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Recherche
                            </label>
                            <input
                                type="text"
                                value={data.q}
                                onChange={(e) => setData('q', e.target.value)}
                                placeholder="Rechercher un article..."
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            />
                        </div>

                        <div>
                            <label className="block text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                                Catégorie
                            </label>
                            <select
                                value={data.category_id}
                                onChange={(e) => setData('category_id', e.target.value)}
                                className="mt-1 block w-48 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            >
                                <option value="">Toutes</option>
                                {categories.map((cat) => (
                                    <option key={cat.id} value={cat.id}>
                                        {cat.name}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <button
                            type="submit"
                            className="ml-auto inline-flex items-center px-4 py-2 rounded-md bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700"
                        >
                            Rechercher
                        </button>
                    </form>

                    {/* Articles */}
                    <div className="grid gap-4 md:grid-cols-2">
                        {articles.data.map((article) => (
                            <div
                                key={article.id}
                                className="bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-5 flex flex-col gap-2"
                            >
                                <div className="flex items-center justify-between gap-2">
                                    <h3 className="text-base font-semibold text-gray-900 dark:text-white">
                                        {article.title}
                                    </h3>
                                    {article.category && (
                                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">
                                            {article.category.name}
                                        </span>
                                    )}
                                </div>
                                <p className="text-sm text-gray-600 dark:text-gray-300">
                                    {article.excerpt}
                                </p>
                            </div>
                        ))}
                        {articles.data.length === 0 && (
                            <div className="col-span-full text-center text-sm text-gray-500 dark:text-gray-400">
                                Aucun article trouvé.
                            </div>
                        )}
                    </div>

                    <div className="text-sm text-gray-500 dark:text-gray-400">
                        Vous ne trouvez pas la réponse ?{' '}
                        <Link
                            href={route('support.tickets.create')}
                            className="text-amber-600 dark:text-amber-400 hover:underline"
                        >
                            Créez un ticket de support
                        </Link>
                        .
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}

