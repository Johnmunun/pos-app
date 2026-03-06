import React from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import { ArrowLeft, Truck } from 'lucide-react';

export default function CommerceSuppliersCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        phone: '',
        address: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('commerce.suppliers.store'));
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center">
                    <Button variant="ghost" asChild className="mr-4">
                        <Link href={route('commerce.suppliers.index')}>
                            <ArrowLeft className="h-4 w-4 mr-2" /> Retour
                        </Link>
                    </Button>
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
                        Nouveau fournisseur — Global Commerce
                    </h2>
                </div>
            }
        >
            <Head title="Nouveau fournisseur - Commerce" />
            <div className="py-6">
                <div className="max-w-xl mx-auto sm:px-6 lg:px-8">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center">
                                <Truck className="h-5 w-5 mr-2" /> Créer un fournisseur
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="grid grid-cols-1 gap-4">
                                    <div>
                                        <Label htmlFor="name">Nom *</Label>
                                        <Input
                                            id="name"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            className="mt-1 w-full"
                                            placeholder="Nom du fournisseur"
                                            required
                                        />
                                        {errors.name && <p className="text-sm text-red-600 mt-1">{errors.name}</p>}
                                    </div>
                                    <div>
                                        <Label htmlFor="email">Email</Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            value={data.email}
                                            onChange={(e) => setData('email', e.target.value)}
                                            className="mt-1 w-full"
                                            placeholder="email@exemple.com"
                                        />
                                        {errors.email && <p className="text-sm text-red-600 mt-1">{errors.email}</p>}
                                    </div>
                                    <div>
                                        <Label htmlFor="phone">Téléphone</Label>
                                        <Input
                                            id="phone"
                                            value={data.phone}
                                            onChange={(e) => setData('phone', e.target.value)}
                                            className="mt-1 w-full"
                                            placeholder="+33 6 12 34 56 78"
                                        />
                                        {errors.phone && <p className="text-sm text-red-600 mt-1">{errors.phone}</p>}
                                    </div>
                                    <div>
                                        <Label htmlFor="address">Adresse</Label>
                                        <Textarea
                                            id="address"
                                            value={data.address}
                                            onChange={(e) => setData('address', e.target.value)}
                                            className="mt-1 w-full min-h-[80px]"
                                            placeholder="Adresse complète"
                                        />
                                        {errors.address && <p className="text-sm text-red-600 mt-1">{errors.address}</p>}
                                    </div>
                                </div>
                                <div className="flex flex-col-reverse sm:flex-row gap-2 sm:gap-4 pt-4">
                                    <Button type="button" variant="outline" asChild className="w-full sm:w-auto">
                                        <Link href={route('commerce.suppliers.index')}>Annuler</Link>
                                    </Button>
                                    <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                                        Enregistrer
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
