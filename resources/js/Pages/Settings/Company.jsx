import { Head, useForm } from '@inertiajs/react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import FlashMessages from '@/Components/FlashMessages';

export default function Company({ tenant }) {
    const { data, setData, put, processing, errors } = useForm({
        name: tenant?.name || '',
        email: tenant?.email || '',
        address: tenant?.address || '',
        phone: tenant?.phone || '',
        idnat: tenant?.idnat || '',
        rccm: tenant?.rccm || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('settings.company.update'));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Informations Entreprise" />

            <div className="py-6">
                <div className="max-w-3xl mx-auto sm:px-6 lg:px-8">
                    <FlashMessages />

                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <h2 className="text-xl font-bold text-gray-900 dark:text-white mb-6">
                            Informations Entreprise
                        </h2>

                        <form onSubmit={submit} className="space-y-6">
                            <div>
                                <InputLabel value="Nom de l'entreprise" />
                                <TextInput
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Nom de l'entreprise"
                                    className="mt-1"
                                    required
                                />
                                {errors.name && <p className="text-red-500 text-sm mt-1">{errors.name}</p>}
                            </div>

                            <div>
                                <InputLabel value="Email" />
                                <TextInput
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="email@example.com"
                                    className="mt-1"
                                    required
                                />
                                {errors.email && <p className="text-red-500 text-sm mt-1">{errors.email}</p>}
                            </div>

                            <div>
                                <InputLabel value="Adresse" />
                                <TextInput
                                    value={data.address}
                                    onChange={(e) => setData('address', e.target.value)}
                                    placeholder="Adresse complète"
                                    className="mt-1"
                                />
                                {errors.address && <p className="text-red-500 text-sm mt-1">{errors.address}</p>}
                            </div>

                            <div>
                                <InputLabel value="Téléphone" />
                                <TextInput
                                    value={data.phone}
                                    onChange={(e) => setData('phone', e.target.value)}
                                    placeholder="+243 XXX XXX XXX"
                                    className="mt-1"
                                />
                                {errors.phone && <p className="text-red-500 text-sm mt-1">{errors.phone}</p>}
                            </div>

                            <div>
                                <InputLabel value="IDNAT" />
                                <TextInput
                                    value={data.idnat}
                                    onChange={(e) => setData('idnat', e.target.value)}
                                    placeholder="Numéro IDNAT"
                                    className="mt-1"
                                />
                                {errors.idnat && <p className="text-red-500 text-sm mt-1">{errors.idnat}</p>}
                            </div>

                            <div>
                                <InputLabel value="RCCM" />
                                <TextInput
                                    value={data.rccm}
                                    onChange={(e) => setData('rccm', e.target.value)}
                                    placeholder="Numéro RCCM"
                                    className="mt-1"
                                />
                                {errors.rccm && <p className="text-red-500 text-sm mt-1">{errors.rccm}</p>}
                            </div>

                            <div className="flex gap-4">
                                <PrimaryButton type="submit" disabled={processing}>
                                    Enregistrer
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}


