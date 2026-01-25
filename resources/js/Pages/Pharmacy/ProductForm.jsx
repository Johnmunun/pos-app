import { Head, useForm, Link } from '@inertiajs/react';
import { useState } from 'react';
import AppLayout from '@/Layouts/AppLayout';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import FlashMessages from '@/Components/FlashMessages';
import { Image as ImageIcon, Upload, Link as LinkIcon, Tag } from 'lucide-react';

export default function ProductForm({ product, currencies, categories }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: product?.name || '',
        sku: product?.sku || '',
        description: product?.description || '',
        category_id: product?.category_id || '',
        selling_price: product?.selling_price || 0,
        purchase_price: product?.purchase_price || 0,
        currency: product?.currency || currencies[0]?.code || 'USD',
        manufacturer: product?.manufacturer || '',
        prescription_required: product?.prescription_required || false,
        stock_alert_level: product?.stock_alert_level || 0,
        barcode: product?.barcode || '',
        image: product?.image || '',
        image_type: product?.image_type || 'url',
        image_file: null,
    });

    const [imagePreview, setImagePreview] = useState(product?.image_url || null);

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData('image_file', file);
            setData('image_type', 'upload');
            const reader = new FileReader();
            reader.onloadend = () => {
                setImagePreview(reader.result);
            };
            reader.readAsDataURL(file);
        }
    };

    const handleImageUrlChange = (url) => {
        setData('image', url);
        setData('image_type', 'url');
        setImagePreview(url);
    };

    const submit = (e) => {
        e.preventDefault();
        if (product) {
            put(route('pharmacy.products.update', product.id));
        } else {
            post(route('pharmacy.products.store'));
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex flex-row justify-between items-center gap-4">
                    <h2 className="text-2xl font-bold leading-tight text-gray-900 dark:text-white">
                        {product ? 'Modifier le produit' : 'Créer un produit'}
                    </h2>
                    <Link
                        href={route('pharmacy.products')}
                        className="px-4 py-2 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors"
                    >
                        Annuler
                    </Link>
                </div>
            }
        >
            <Head title={product ? 'Modifier Produit' : 'Créer Produit'} />

            <div className="py-6">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <FlashMessages />

                    <form onSubmit={submit} className="space-y-6">
                        {/* Main Card */}
                        <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6 space-y-6">
                            {/* Image Section */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Image du produit
                                </h3>

                                {/* Image Preview */}
                                {imagePreview && (
                                    <div className="flex justify-center">
                                        <div className="relative">
                                            <img
                                                src={imagePreview}
                                                alt="Preview"
                                                className="h-48 w-48 object-cover rounded-lg border-2 border-gray-200 dark:border-gray-700 shadow-md"
                                                onError={(e) => {
                                                    e.target.style.display = 'none';
                                                    const fallback = e.target.nextSibling;
                                                    if (fallback) fallback.style.display = 'flex';
                                                }}
                                            />
                                            <div className="hidden h-48 w-48 items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-gray-700 dark:to-gray-800 rounded-lg border-2 border-gray-200 dark:border-gray-700">
                                                <ImageIcon className="w-16 h-16 text-gray-400 dark:text-gray-500" />
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {/* Image Type Selection */}
                                <div>
                                    <InputLabel value="Type d'image" />
                                    <div className="flex gap-4 mt-2">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="radio"
                                                value="url"
                                                checked={data.image_type === 'url'}
                                                onChange={(e) => {
                                                    setData('image_type', 'url');
                                                    if (data.image) handleImageUrlChange(data.image);
                                                }}
                                                className="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                            />
                                            <LinkIcon className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">URL</span>
                                        </label>
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="radio"
                                                value="upload"
                                                checked={data.image_type === 'upload'}
                                                onChange={(e) => setData('image_type', 'upload')}
                                                className="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700"
                                            />
                                            <Upload className="h-4 w-4 text-gray-600 dark:text-gray-400" />
                                            <span className="text-sm text-gray-700 dark:text-gray-300">Upload</span>
                                        </label>
                                    </div>
                                </div>

                                {/* Image Input */}
                                {data.image_type === 'url' ? (
                                    <div>
                                        <InputLabel htmlFor="image_url" value="URL de l'image" />
                                        <TextInput
                                            id="image_url"
                                            type="text"
                                            value={data.image}
                                            onChange={(e) => handleImageUrlChange(e.target.value)}
                                            placeholder="https://example.com/image.jpg"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.image} className="mt-2" />
                                    </div>
                                ) : (
                                    <div>
                                        <InputLabel htmlFor="image_file" value="Fichier image (max 2MB)" />
                                        <input
                                            id="image_file"
                                            type="file"
                                            accept="image/*"
                                            onChange={handleImageChange}
                                            className="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-50 dark:file:bg-amber-900/20 file:text-amber-700 dark:file:text-amber-400 hover:file:bg-amber-100 dark:hover:file:bg-amber-900/30"
                                        />
                                        <InputError message={errors.image_file} className="mt-2" />
                                    </div>
                                )}
                            </div>

                            <div className="border-t border-gray-200 dark:border-gray-700"></div>

                            {/* Basic Information */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Informations de base
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {/* Name */}
                                    <div className="md:col-span-2">
                                        <InputLabel htmlFor="name" value="Nom du produit *" />
                                        <TextInput
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="Nom du produit"
                                            className="mt-1 block w-full"
                                            required
                                        />
                                        <InputError message={errors.name} className="mt-2" />
                                    </div>

                                    {/* Category */}
                                    <div className="md:col-span-2">
                                        <InputLabel htmlFor="category_id" value="Catégorie *" />
                                        <select
                                            id="category_id"
                                            value={data.category_id}
                                            onChange={(e) => setData('category_id', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            required
                                        >
                                            <option value="">Sélectionner une catégorie</option>
                                            {categories.map((category) => (
                                                <option key={category.id} value={category.id}>
                                                    {category.name}
                                                </option>
                                            ))}
                                        </select>
                                        {categories.length === 0 && (
                                            <p className="mt-2 text-sm text-amber-600 dark:text-amber-400">
                                                Aucune catégorie disponible. <Link href="/categories" className="underline">Créer une catégorie</Link>
                                            </p>
                                        )}
                                        <InputError message={errors.category_id} className="mt-2" />
                                    </div>

                                    {/* SKU */}
                                    <div>
                                        <InputLabel htmlFor="sku" value="SKU" />
                                        <TextInput
                                            id="sku"
                                            value={data.sku}
                                            onChange={(e) => setData('sku', e.target.value)}
                                            placeholder="Code SKU"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.sku} className="mt-2" />
                                    </div>

                                    {/* Barcode */}
                                    <div>
                                        <InputLabel htmlFor="barcode" value="Code-barres" />
                                        <TextInput
                                            id="barcode"
                                            value={data.barcode}
                                            onChange={(e) => setData('barcode', e.target.value)}
                                            placeholder="Code-barres"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.barcode} className="mt-2" />
                                    </div>

                                    {/* Description */}
                                    <div className="md:col-span-2">
                                        <InputLabel htmlFor="description" value="Description" />
                                        <textarea
                                            id="description"
                                            value={data.description}
                                            onChange={(e) => setData('description', e.target.value)}
                                            rows="3"
                                            placeholder="Description du produit"
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                        />
                                        <InputError message={errors.description} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <div className="border-t border-gray-200 dark:border-gray-700"></div>

                            {/* Pricing */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Tarification
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {/* Selling Price */}
                                    <div>
                                        <InputLabel htmlFor="selling_price" value="Prix de vente *" />
                                        <TextInput
                                            id="selling_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.selling_price}
                                            onChange={(e) => setData('selling_price', parseFloat(e.target.value) || 0)}
                                            placeholder="0.00"
                                            className="mt-1 block w-full"
                                            required
                                        />
                                        <InputError message={errors.selling_price} className="mt-2" />
                                    </div>

                                    {/* Purchase Price */}
                                    <div>
                                        <InputLabel htmlFor="purchase_price" value="Prix d'achat" />
                                        <TextInput
                                            id="purchase_price"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.purchase_price}
                                            onChange={(e) => setData('purchase_price', parseFloat(e.target.value) || 0)}
                                            placeholder="0.00"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.purchase_price} className="mt-2" />
                                    </div>

                                    {/* Currency */}
                                    <div>
                                        <InputLabel htmlFor="currency" value="Devise *" />
                                        <select
                                            id="currency"
                                            value={data.currency}
                                            onChange={(e) => setData('currency', e.target.value)}
                                            className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-amber-500 focus:ring-amber-500"
                                            required
                                        >
                                            {currencies.map((currency) => (
                                                <option key={currency.id} value={currency.code}>
                                                    {currency.code} - {currency.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError message={errors.currency} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            <div className="border-t border-gray-200 dark:border-gray-700"></div>

                            {/* Additional Information */}
                            <div className="space-y-4">
                                <h3 className="text-lg font-semibold text-gray-900 dark:text-white">
                                    Informations supplémentaires
                                </h3>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {/* Manufacturer */}
                                    <div>
                                        <InputLabel htmlFor="manufacturer" value="Fabricant" />
                                        <TextInput
                                            id="manufacturer"
                                            value={data.manufacturer}
                                            onChange={(e) => setData('manufacturer', e.target.value)}
                                            placeholder="Nom du fabricant"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.manufacturer} className="mt-2" />
                                    </div>

                                    {/* Stock Alert Level */}
                                    <div>
                                        <InputLabel htmlFor="stock_alert_level" value="Seuil d'alerte de stock" />
                                        <TextInput
                                            id="stock_alert_level"
                                            type="number"
                                            min="0"
                                            value={data.stock_alert_level}
                                            onChange={(e) => setData('stock_alert_level', parseInt(e.target.value) || 0)}
                                            placeholder="0"
                                            className="mt-1 block w-full"
                                        />
                                        <InputError message={errors.stock_alert_level} className="mt-2" />
                                    </div>

                                    {/* Prescription Required */}
                                    <div className="md:col-span-2">
                                        <div className="flex items-center">
                                            <input
                                                id="prescription_required"
                                                type="checkbox"
                                                checked={data.prescription_required}
                                                onChange={(e) => setData('prescription_required', e.target.checked)}
                                                className="h-4 w-4 text-amber-600 focus:ring-amber-500 border-gray-300 rounded dark:bg-gray-700 dark:border-gray-600"
                                            />
                                            <InputLabel htmlFor="prescription_required" value="Prescription requise" className="ml-2" />
                                        </div>
                                        <InputError message={errors.prescription_required} className="mt-2" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="flex flex-col sm:flex-row justify-end gap-3">
                            <Link
                                href={route('pharmacy.products')}
                                className="px-6 py-3 text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 rounded-lg transition-colors text-center font-medium"
                            >
                                Annuler
                            </Link>
                            <PrimaryButton type="submit" disabled={processing || categories.length === 0}>
                                {processing ? 'Enregistrement...' : product ? 'Mettre à jour' : 'Créer le produit'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
