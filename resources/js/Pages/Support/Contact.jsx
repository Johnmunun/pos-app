import AppLayout from '@/Layouts/AppLayout';
import { Head, useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';

export default function Contact({ supportEmail }) {
    const { data, setData, post, processing, errors } = useForm({
        subject: '',
        message: '',
        attachment: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('support.contact.send'), {
            forceFormData: true,
        });
    };

    const handleFileChange = (e) => {
        const file = e.target.files?.[0];
        setData('attachment', file || null);
    };

    return (
        <AppLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Contacter le support
                    </h2>
                    <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Envoyez un message à l&apos;équipe support OmniPOS.
                    </p>
                </div>
            }
        >
            <Head title="Contact support" />

            <div className="py-6">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <div className="mb-4 text-sm text-gray-600 dark:text-gray-300">
                        Adresse email support:&nbsp;
                        <span className="font-mono">{supportEmail}</span>
                    </div>

                    <form
                        onSubmit={submit}
                        className="space-y-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm p-6"
                    >
                        <div>
                            <InputLabel htmlFor="subject" value="Sujet" />
                            <TextInput
                                id="subject"
                                type="text"
                                value={data.subject}
                                onChange={(e) => setData('subject', e.target.value)}
                                className="mt-1 block w-full"
                            />
                            <InputError message={errors.subject} className="mt-2" />
                        </div>

                        <div>
                            <InputLabel htmlFor="message" value="Message" />
                            <textarea
                                id="message"
                                rows={5}
                                value={data.message}
                                onChange={(e) => setData('message', e.target.value)}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm focus:border-amber-500 focus:ring-amber-500"
                            />
                            <InputError message={errors.message} className="mt-2" />
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

                        <div className="flex items-center justify-end">
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center px-4 py-2 bg-amber-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 disabled:opacity-50 transition"
                            >
                                {processing ? 'Envoi...' : 'Envoyer'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}

