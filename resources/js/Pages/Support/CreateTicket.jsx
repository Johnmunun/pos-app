import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import RichTextEditor from '@/Components/RichTextEditor';
import { useState } from 'react';

export default function CreateTicket({ enums }) {
    const { data, setData, post, processing, errors } = useForm({
        title: '',
        description: '',
        priority: 'medium',
        category: 'bug',
        module: 'system',
        attachment: null,
    });

    const [editorValue, setEditorValue] = useState('');

    const submit = (e) => {
        e.preventDefault();
        post(route('support.tickets.store'), {
            forceFormData: true,
        });
    };

    const handleDescriptionChange = (value) => {
        setEditorValue(value);
        setData('description', value);
    };

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        setData('attachment', file || null);
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                            Créer un ticket de support
                        </h2>
                        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Décrivez votre problème pour que l&apos;équipe OmniPOS puisse vous aider rapidement.
                        </p>
                    </div>
                </div>
            }
        >
            <Head title="Créer un ticket" />

            <div className="py-6">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="md:col-span-2">
                                <InputLabel htmlFor="title" value="Titre du ticket" />
                                <TextInput
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(e) => setData('title', e.target.value)}
                                    className="mt-1 block w-full"
                                />
                                <InputError message={errors.title} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="priority" value="Priorité" />
                                <select
                                    id="priority"
                                    value={data.priority}
                                    onChange={(e) => setData('priority', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="low">Faible</option>
                                    <option value="medium">Moyenne</option>
                                    <option value="high">Élevée</option>
                                    <option value="critical">Critique</option>
                                </select>
                                <InputError message={errors.priority} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="category" value="Catégorie" />
                                <select
                                    id="category"
                                    value={data.category}
                                    onChange={(e) => setData('category', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="bug">Bug</option>
                                    <option value="request">Demande</option>
                                    <option value="incident">Incident</option>
                                    <option value="support">Support</option>
                                </select>
                                <InputError message={errors.category} className="mt-2" />
                            </div>

                            <div>
                                <InputLabel htmlFor="module" value="Module concerné" />
                                <select
                                    id="module"
                                    value={data.module}
                                    onChange={(e) => setData('module', e.target.value)}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                >
                                    <option value="hardware">Hardware</option>
                                    <option value="pharmacy">Pharmacy</option>
                                    <option value="commerce">Global Commerce</option>
                                    <option value="ecommerce">E-commerce</option>
                                    <option value="system">Système</option>
                                </select>
                                <InputError message={errors.module} className="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Description détaillée" />
                            <div className="mt-1">
                                <RichTextEditor
                                    value={editorValue}
                                    onChange={handleDescriptionChange}
                                    placeholder="Expliquez le problème, les étapes pour le reproduire, les messages d'erreur, etc."
                                />
                            </div>
                            <InputError message={errors.description} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="attachment" value="Pièce jointe (optionnel)" />
                            <input
                                id="attachment"
                                type="file"
                                onChange={handleFileChange}
                                className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 dark:text-gray-300 dark:file:bg-gray-700 dark:file:text-gray-100"
                            />
                            <InputError message={errors.attachment} className="mt-2" />
                        </div>

                        <div className="flex items-center justify-end gap-3">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-2 py-1.5 sm:px-4 sm:py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 transition"
                            >
                                {processing ? 'Envoi en cours...' : 'Envoyer le ticket'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}

