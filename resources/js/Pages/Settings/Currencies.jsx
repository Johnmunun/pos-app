import { Head, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import Drawer from '@/Components/Drawer';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import FlashMessages from '@/Components/FlashMessages';
import Swal from 'sweetalert2';
import { Plus, Edit, Trash2, DollarSign } from 'lucide-react';

export default function Currencies({ currencies, exchangeRates }) {
    const [currencyDrawerOpen, setCurrencyDrawerOpen] = useState(false);
    const [rateDrawerOpen, setRateDrawerOpen] = useState(false);
    const [selectedCurrency, setSelectedCurrency] = useState(null);
    const [selectedRate, setSelectedRate] = useState(null);

    // Form for currency
    const currencyForm = useForm({
        code: '',
        name: '',
        symbol: '',
        is_default: false,
    });

    // Form for exchange rate
    const rateForm = useForm({
        from_currency_id: '',
        to_currency_id: '',
        rate: '',
        effective_date: new Date().toISOString().split('T')[0],
    });

    const openCurrencyDrawer = (currency = null) => {
        setSelectedCurrency(currency);
        if (currency) {
            currencyForm.setData({
                code: currency.code,
                name: currency.name,
                symbol: currency.symbol,
                is_default: currency.is_default,
            });
        } else {
            currencyForm.reset();
        }
        setCurrencyDrawerOpen(true);
    };

    const openRateDrawer = (rate = null) => {
        setSelectedRate(rate);
        if (rate) {
            rateForm.setData({
                from_currency_id: rate.from_currency_id || '',
                to_currency_id: rate.to_currency_id || '',
                rate: rate.rate,
                effective_date: rate.effective_date,
            });
        } else {
            rateForm.reset();
            rateForm.setData('effective_date', new Date().toISOString().split('T')[0]);
        }
        setRateDrawerOpen(true);
    };

    const handleCurrencySubmit = (e) => {
        e.preventDefault();
        if (selectedCurrency) {
            currencyForm.put(route('settings.currencies.update', selectedCurrency.id), {
                onSuccess: () => {
                    setCurrencyDrawerOpen(false);
                    currencyForm.reset();
                    setSelectedCurrency(null);
                },
            });
        } else {
            currencyForm.post(route('settings.currencies.store'), {
                onSuccess: () => {
                    setCurrencyDrawerOpen(false);
                    currencyForm.reset();
                },
            });
        }
    };

    const handleRateSubmit = (e) => {
        e.preventDefault();
        if (selectedRate) {
            rateForm.put(route('settings.exchange-rates.update', selectedRate.id), {
                onSuccess: () => {
                    setRateDrawerOpen(false);
                    rateForm.reset();
                    setSelectedRate(null);
                },
            });
        } else {
            rateForm.post(route('settings.exchange-rates.store'), {
                onSuccess: () => {
                    setRateDrawerOpen(false);
                    rateForm.reset();
                    rateForm.setData('effective_date', new Date().toISOString().split('T')[0]);
                },
            });
        }
    };

    const handleDeleteCurrency = (id) => {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: 'Cette action est irréversible !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('settings.currencies.destroy', id));
            }
        });
    };

    const handleDeleteRate = (id) => {
        Swal.fire({
            title: 'Êtes-vous sûr ?',
            text: 'Cette action est irréversible !',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Oui, supprimer',
            cancelButtonText: 'Annuler',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('settings.exchange-rates.destroy', id));
            }
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-row justify-between items-center gap-4">
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        Gestion des Devises
                    </h2>
                    <button
                        onClick={() => openCurrencyDrawer()}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors shadow-sm hover:shadow-md whitespace-nowrap"
                    >
                        <Plus className="h-4 w-4" />
                        <span className="hidden sm:inline">Ajouter une devise</span>
                        <span className="sm:hidden">Ajouter</span>
                    </button>
                </div>
            }
        >
            <Head title="Gestion des Devises" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <FlashMessages />

                    {/* Currencies List */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white flex items-center gap-2">
                                <DollarSign className="h-5 w-5" />
                                Devises
                            </h2>
                        </div>
                        <div className="p-6">
                            {currencies.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Code
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Nom
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Symbole
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Par défaut
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {currencies.map((currency) => (
                                                <tr key={currency.id}>
                                                    <td className="px-4 py-3 text-sm text-gray-900 dark:text-white font-semibold">
                                                        {currency.code}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                        {currency.name}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                        {currency.symbol}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        {currency.is_default ? (
                                                            <span className="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-400 rounded text-xs">
                                                                Oui
                                                            </span>
                                                        ) : (
                                                            <span className="text-gray-400">Non</span>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        <div className="flex gap-2">
                                                            <button
                                                                onClick={() => openCurrencyDrawer(currency)}
                                                                className="text-amber-600 hover:text-amber-700"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </button>
                                                            {!currency.is_default && (
                                                                <button
                                                                    onClick={() => handleDeleteCurrency(currency.id)}
                                                                    className="text-red-600 hover:text-red-700"
                                                                >
                                                                    <Trash2 className="h-4 w-4" />
                                                                </button>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-gray-600 dark:text-gray-400">
                                    Aucune devise configurée.
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Exchange Rates List */}
                    <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
                        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                            <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                                Taux de Change
                            </h2>
                            <button
                                onClick={() => openRateDrawer()}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg transition-colors text-sm"
                            >
                                <Plus className="h-4 w-4" />
                                Ajouter un taux
                            </button>
                        </div>
                        <div className="p-6">
                            {exchangeRates.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead>
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    De
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Vers
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Taux
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Date effective
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 dark:divide-gray-700">
                                            {exchangeRates.map((rate) => (
                                                <tr key={rate.id}>
                                                    <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                        {rate.from_currency_code}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                        {rate.to_currency_code}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 font-semibold">
                                                        {rate.rate}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">
                                                        {rate.effective_date}
                                                    </td>
                                                    <td className="px-4 py-3 text-sm">
                                                        <div className="flex gap-2">
                                                            <button
                                                                onClick={() => openRateDrawer(rate)}
                                                                className="text-amber-600 hover:text-amber-700"
                                                            >
                                                                <Edit className="h-4 w-4" />
                                                            </button>
                                                            <button
                                                                onClick={() => handleDeleteRate(rate.id)}
                                                                className="text-red-600 hover:text-red-700"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <p className="text-gray-600 dark:text-gray-400">
                                    Aucun taux de change configuré.
                                </p>
                            )}
                        </div>
                    </div>
                </div>
            </div>

            {/* Currency Drawer */}
            <Drawer
                isOpen={currencyDrawerOpen}
                onClose={() => {
                    setCurrencyDrawerOpen(false);
                    currencyForm.reset();
                    setSelectedCurrency(null);
                }}
                title={selectedCurrency ? 'Modifier la devise' : 'Ajouter une devise'}
                size="md"
            >
                <form onSubmit={handleCurrencySubmit} className="space-y-4">
                    {/* Code */}
                    <div>
                        <InputLabel htmlFor="code" value="Code (ISO 4217)" />
                        <TextInput
                            id="code"
                            type="text"
                            value={currencyForm.data.code}
                            onChange={(e) => currencyForm.setData('code', e.target.value.toUpperCase())}
                            className="mt-1 block w-full"
                            placeholder="USD, EUR, XOF..."
                            maxLength={3}
                            required
                        />
                        <InputError message={currencyForm.errors.code} className="mt-2" />
                    </div>

                    {/* Name */}
                    <div>
                        <InputLabel htmlFor="name" value="Nom" />
                        <TextInput
                            id="name"
                            type="text"
                            value={currencyForm.data.name}
                            onChange={(e) => currencyForm.setData('name', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="Dollar américain, Euro..."
                            required
                        />
                        <InputError message={currencyForm.errors.name} className="mt-2" />
                    </div>

                    {/* Symbol */}
                    <div>
                        <InputLabel htmlFor="symbol" value="Symbole" />
                        <TextInput
                            id="symbol"
                            type="text"
                            value={currencyForm.data.symbol}
                            onChange={(e) => currencyForm.setData('symbol', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="$, €, FCFA..."
                            required
                        />
                        <InputError message={currencyForm.errors.symbol} className="mt-2" />
                    </div>

                    {/* Is Default */}
                    <div className="flex items-center">
                        <input
                            id="is_default"
                            type="checkbox"
                            checked={currencyForm.data.is_default}
                            onChange={(e) => currencyForm.setData('is_default', e.target.checked)}
                            className="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded"
                        />
                        <InputLabel htmlFor="is_default" value="Définir comme devise par défaut" className="ml-2" />
                    </div>
                    <InputError message={currencyForm.errors.is_default} className="mt-2" />

                    {/* Submit Button */}
                    <div className="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            onClick={() => {
                                setCurrencyDrawerOpen(false);
                                currencyForm.reset();
                                setSelectedCurrency(null);
                            }}
                            className="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                        >
                            Annuler
                        </button>
                        <PrimaryButton disabled={currencyForm.processing}>
                            {selectedCurrency ? 'Modifier' : 'Ajouter'}
                        </PrimaryButton>
                    </div>
                </form>
            </Drawer>

            {/* Rate Drawer */}
            <Drawer
                isOpen={rateDrawerOpen}
                onClose={() => {
                    setRateDrawerOpen(false);
                    rateForm.reset();
                    setSelectedRate(null);
                }}
                title={selectedRate ? 'Modifier le taux' : 'Ajouter un taux de change'}
                size="md"
            >
                <form onSubmit={handleRateSubmit} className="space-y-4">
                    {/* From Currency */}
                    <div>
                        <InputLabel htmlFor="from_currency_id" value="De (Devise source)" />
                        <select
                            id="from_currency_id"
                            value={rateForm.data.from_currency_id}
                            onChange={(e) => rateForm.setData('from_currency_id', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            required
                        >
                            <option value="">Sélectionner une devise</option>
                            {currencies.map((currency) => (
                                <option key={currency.id} value={currency.id}>
                                    {currency.code} - {currency.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={rateForm.errors.from_currency_id} className="mt-2" />
                    </div>

                    {/* To Currency */}
                    <div>
                        <InputLabel htmlFor="to_currency_id" value="Vers (Devise cible)" />
                        <select
                            id="to_currency_id"
                            value={rateForm.data.to_currency_id}
                            onChange={(e) => rateForm.setData('to_currency_id', e.target.value)}
                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                            required
                        >
                            <option value="">Sélectionner une devise</option>
                            {currencies.map((currency) => (
                                <option key={currency.id} value={currency.id}>
                                    {currency.code} - {currency.name}
                                </option>
                            ))}
                        </select>
                        <InputError message={rateForm.errors.to_currency_id} className="mt-2" />
                    </div>

                    {/* Rate */}
                    <div>
                        <InputLabel htmlFor="rate" value="Taux de change" />
                        <TextInput
                            id="rate"
                            type="number"
                            step="0.0001"
                            min="0"
                            value={rateForm.data.rate}
                            onChange={(e) => rateForm.setData('rate', e.target.value)}
                            className="mt-1 block w-full"
                            placeholder="1.00"
                            required
                        />
                        <InputError message={rateForm.errors.rate} className="mt-2" />
                    </div>

                    {/* Effective Date */}
                    <div>
                        <InputLabel htmlFor="effective_date" value="Date effective" />
                        <TextInput
                            id="effective_date"
                            type="date"
                            value={rateForm.data.effective_date}
                            onChange={(e) => rateForm.setData('effective_date', e.target.value)}
                            className="mt-1 block w-full"
                            required
                        />
                        <InputError message={rateForm.errors.effective_date} className="mt-2" />
                    </div>

                    {/* Submit Button */}
                    <div className="flex justify-end gap-3 pt-4">
                        <button
                            type="button"
                            onClick={() => {
                                setRateDrawerOpen(false);
                                rateForm.reset();
                                setSelectedRate(null);
                            }}
                            className="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                        >
                            Annuler
                        </button>
                        <PrimaryButton disabled={rateForm.processing}>
                            {selectedRate ? 'Modifier' : 'Ajouter'}
                        </PrimaryButton>
                    </div>
                </form>
            </Drawer>
        </AppLayout>
    );
}


