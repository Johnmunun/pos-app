import React, { useState } from 'react';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { 
  ArrowLeft, 
  Save,
  Package,
  Hash,
  FileText,
  Tag,
  DollarSign,
  Archive,
  Pill,
  Info
} from 'lucide-react';
import { useToast } from '@/Components/ui/use-toast';

export default function ProductCreate({ auth, categories, routePrefix = 'pharmacy' }) {
    const { toast } = useToast();
    const { shop } = usePage().props;
    const defaultCurrency = shop?.currency || 'CDF';
    
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        product_code: '',
        description: '',
        category_id: '',
        price: '',
        currency: defaultCurrency,
        cost: '',
        minimum_stock: '',
        unit: '',
        medicine_type: '',
        dosage: '',
        prescription_required: false,
        manufacturer: '',
        supplier_id: '',
        type_unite: 'UNITE',
        quantite_par_unite: 1,
        est_divisible: true
    });

    const [isMedicine, setIsMedicine] = useState(false);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route(`${routePrefix}.products.store`), {
            onSuccess: () => {
                toast({
                    title: "Success",
                    description: "Product created successfully",
                });
                router.visit(route(`${routePrefix}.products`));
            },
            onError: (errors) => {
                toast({
                    title: "Error",
                    description: "Please check the form for errors",
                    variant: "destructive",
                });
            }
        });
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center">
                    <Button variant="ghost" asChild className="mr-4">
                        <Link href={route(`${routePrefix}.products`)}>
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Créer un nouveau produit
                    </h2>
                </div>
            }
        >
            <Head title="Create Product" />

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit}>
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center">
                                    <Package className="h-5 w-5 mr-2" />
                                    Product Information
                                </CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-6">
                                {/* Basic Information */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Product Name *</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="Enter product name"
                                        />
                                        {errors.name && <p className="text-sm text-red-600">{errors.name}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="product_code">Product Code *</Label>
                                        <div className="relative">
                                            <Hash className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                            <Input
                                                id="product_code"
                                                value={data.product_code}
                                                onChange={(e) => setData('product_code', e.target.value)}
                                                placeholder="PROD-001"
                                                className="pl-10"
                                            />
                                        </div>
                                        {errors.product_code && <p className="text-sm text-red-600">{errors.product_code}</p>}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                        placeholder="Product description..."
                                        rows={3}
                                    />
                                    {errors.description && <p className="text-sm text-red-600">{errors.description}</p>}
                                </div>

                                {/* Category and Type */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="category_id">Category *</Label>
                                        <div className="relative">
                                            <Tag className="absolute left-3 top-3 h-4 w-4 text-gray-400" />
                                            <select
                                                id="category_id"
                                                value={data.category_id}
                                                onChange={(e) => setData('category_id', e.target.value)}
                                                className="w-full rounded-md border-gray-300 pl-10 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                <option value="">Select category</option>
                                                {categories.map(category => (
                                                    <option key={category.id} value={category.id}>
                                                        {category.name}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        {errors.category_id && <p className="text-sm text-red-600">{errors.category_id}</p>}
                                    </div>

                                    <div className="space-y-2">
                                        <Label htmlFor="unit">Unit *</Label>
                                        <Input
                                            id="unit"
                                            value={data.unit}
                                            onChange={(e) => setData('unit', e.target.value)}
                                            placeholder="e.g., bottle, box, tablet"
                                        />
                                        {errors.unit && <p className="text-sm text-red-600">{errors.unit}</p>}
                                    </div>
                                </div>

                                {/* Unité de vente (divisible / non divisible) */}
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className="space-y-2">
                                        <Label htmlFor="type_unite">Type d'unité *</Label>
                                        <select
                                            id="type_unite"
                                            value={data.type_unite}
                                            onChange={(e) => setData('type_unite', e.target.value)}
                                            className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        >
                                            <option value="PLAQUETTE">Plaquette</option>
                                            <option value="BOITE">Boîte</option>
                                            <option value="FLACON">Flacon</option>
                                            <option value="TUBE">Tube</option>
                                            <option value="SACHET">Sachet</option>
                                            <option value="UNITE">Unité</option>
                                        </select>
                                        {errors.type_unite && <p className="text-sm text-red-600">{errors.type_unite}</p>}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="quantite_par_unite">Quantité par unité *</Label>
                                        <Input
                                            id="quantite_par_unite"
                                            type="number"
                                            min={1}
                                            value={data.quantite_par_unite}
                                            onChange={(e) => setData('quantite_par_unite', parseInt(e.target.value, 10) || 1)}
                                            placeholder="ex: 10 comprimés"
                                        />
                                        <p className="text-xs text-gray-500">ex: 10 comprimés par plaquette</p>
                                        {errors.quantite_par_unite && <p className="text-sm text-red-600">{errors.quantite_par_unite}</p>}
                                    </div>
                                    <div className="space-y-2 flex flex-col justify-end">
                                        <div className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                id="est_divisible"
                                                checked={data.est_divisible}
                                                onChange={(e) => setData('est_divisible', e.target.checked)}
                                                className="rounded border-gray-300"
                                            />
                                            <Label htmlFor="est_divisible" className="cursor-pointer">Vente en fraction autorisée</Label>
                                        </div>
                                        <p className="text-xs text-gray-500">Désactiver pour boîte/flacon (quantité entière uniquement)</p>
                                        {errors.est_divisible && <p className="text-sm text-red-600">{errors.est_divisible}</p>}
                                    </div>
                                </div>

                                {/* Pricing */}
                                <div className="border-t pt-6">
                                    <h3 className="text-lg font-medium mb-4 flex items-center">
                                        <DollarSign className="h-5 w-5 mr-2" />
                                        Pricing Information
                                    </h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="price">Selling Price *</Label>
                                            <Input
                                                id="price"
                                                type="number"
                                                step="0.01"
                                                value={data.price}
                                                onChange={(e) => setData('price', e.target.value)}
                                                placeholder="0.00"
                                            />
                                            {errors.price && <p className="text-sm text-red-600">{errors.price}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="cost">Cost Price</Label>
                                            <Input
                                                id="cost"
                                                type="number"
                                                step="0.01"
                                                value={data.cost}
                                                onChange={(e) => setData('cost', e.target.value)}
                                                placeholder="0.00"
                                            />
                                            {errors.cost && <p className="text-sm text-red-600">{errors.cost}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="currency">Devise *</Label>
                                            <select
                                                id="currency"
                                                value={data.currency}
                                                onChange={(e) => setData('currency', e.target.value)}
                                                className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            >
                                                {shop?.currencies && shop.currencies.length > 0 ? (
                                                    shop.currencies.map(c => (
                                                        <option key={c.code} value={c.code}>{c.code} - {c.name}</option>
                                                    ))
                                                ) : (
                                                    <>
                                                        <option value="CDF">CDF - Franc Congolais</option>
                                                        <option value="USD">USD - Dollar US</option>
                                                        <option value="EUR">EUR - Euro</option>
                                                    </>
                                                )}
                                            </select>
                                            {errors.currency && <p className="text-sm text-red-600">{errors.currency}</p>}
                                        </div>
                                    </div>
                                </div>

                                {/* Inventory */}
                                <div className="border-t pt-6">
                                    <h3 className="text-lg font-medium mb-4 flex items-center">
                                        <Archive className="h-5 w-5 mr-2" />
                                        Inventory Settings
                                    </h3>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div className="space-y-2">
                                            <Label htmlFor="minimum_stock">Minimum Stock Level *</Label>
                                            <Input
                                                id="minimum_stock"
                                                type="number"
                                                value={data.minimum_stock}
                                                onChange={(e) => setData('minimum_stock', e.target.value)}
                                                placeholder="10"
                                            />
                                            {errors.minimum_stock && <p className="text-sm text-red-600">{errors.minimum_stock}</p>}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="manufacturer">Manufacturer</Label>
                                            <Input
                                                id="manufacturer"
                                                value={data.manufacturer}
                                                onChange={(e) => setData('manufacturer', e.target.value)}
                                                placeholder="Manufacturer name"
                                            />
                                            {errors.manufacturer && <p className="text-sm text-red-600">{errors.manufacturer}</p>}
                                        </div>
                                    </div>
                                </div>

                                {/* Medicine Specific Fields */}
                                <div className="border-t pt-6">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-medium flex items-center">
                                            <Pill className="h-5 w-5 mr-2" />
                                            Medicine Information
                                        </h3>
                                        <label className="flex items-center">
                                            <input
                                                type="checkbox"
                                                checked={isMedicine}
                                                onChange={(e) => setIsMedicine(e.target.checked)}
                                                className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            />
                                            <span className="ml-2 text-sm text-gray-600">This is a medicine product</span>
                                        </label>
                                    </div>

                                    {isMedicine && (
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 bg-blue-50 p-4 rounded-lg">
                                            <div className="space-y-2">
                                                <Label htmlFor="medicine_type">Medicine Type</Label>
                                                <select
                                                    id="medicine_type"
                                                    value={data.medicine_type}
                                                    onChange={(e) => setData('medicine_type', e.target.value)}
                                                    className="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                >
                                                    <option value="">Select type</option>
                                                    <option value="tablet">Tablet</option>
                                                    <option value="capsule">Capsule</option>
                                                    <option value="syrup">Syrup</option>
                                                    <option value="injection">Injection</option>
                                                    <option value="cream">Cream/Ointment</option>
                                                    <option value="other">Other</option>
                                                </select>
                                                {errors.medicine_type && <p className="text-sm text-red-600">{errors.medicine_type}</p>}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="dosage">Dosage</Label>
                                                <Input
                                                    id="dosage"
                                                    value={data.dosage}
                                                    onChange={(e) => setData('dosage', e.target.value)}
                                                    placeholder="e.g., 500mg, 10ml"
                                                />
                                                {errors.dosage && <p className="text-sm text-red-600">{errors.dosage}</p>}
                                            </div>

                                            <div className="space-y-2 md:col-span-2">
                                                <div className="flex items-center">
                                                    <input
                                                        type="checkbox"
                                                        id="prescription_required"
                                                        checked={data.prescription_required}
                                                        onChange={(e) => setData('prescription_required', e.target.checked)}
                                                        className="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                    />
                                                    <Label htmlFor="prescription_required" className="ml-2">
                                                        Prescription Required
                                                    </Label>
                                                </div>
                                                {errors.prescription_required && <p className="text-sm text-red-600">{errors.prescription_required}</p>}
                                            </div>
                                        </div>
                                    )}
                                </div>

                                {/* Form Actions */}
                                <div className="flex justify-end space-x-4 pt-6 border-t">
                                    <Button
                                        variant="outline"
                                        asChild
                                        disabled={processing}
                                    >
                                        <Link href={route(`${routePrefix}.products`)}>
                                            Cancel
                                        </Link>
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        <Save className="h-4 w-4 mr-2" />
                                        {processing ? 'Creating...' : 'Create Product'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}