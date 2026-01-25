import { Head } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { BarChart } from 'lucide-react';

export default function Reports() {
    return (
        <AppLayout
            header={
                <div className="flex items-center gap-2">
                    <BarChart className="h-6 w-6" />
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Rapports Pharmacy
                    </h2>
                </div>
            }
        >
            <Head title="Rapports - Pharmacy" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">

                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                        <p className="text-gray-600 dark:text-gray-400">
                            Les rapports seront disponibles prochainement.
                        </p>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}


