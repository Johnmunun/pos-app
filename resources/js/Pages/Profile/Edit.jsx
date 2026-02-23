import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import DeleteUserForm from './Partials/DeleteUserForm';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';
import { User } from 'lucide-react';

export default function Edit({ mustVerifyEmail, status }) {
    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2">
                    <User className="h-5 w-5 text-gray-500 dark:text-gray-400" />
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Mon profil
                    </h2>
                </div>
            }
        >
            <Head title="Mon profil" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                    <div className="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8 border border-gray-200 dark:border-gray-700">
                        <UpdateProfileInformationForm
                            mustVerifyEmail={mustVerifyEmail}
                            status={status}
                            className="max-w-xl"
                        />
                    </div>

                    <div className="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8 border border-gray-200 dark:border-gray-700">
                        <UpdatePasswordForm className="max-w-xl" />
                    </div>

                    <div className="bg-white dark:bg-gray-800 p-4 shadow sm:rounded-lg sm:p-8 border border-gray-200 dark:border-gray-700">
                        <DeleteUserForm className="max-w-xl" />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
