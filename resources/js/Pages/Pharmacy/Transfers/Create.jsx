import React, { useState, useCallback } from 'react';
import { Head, router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Badge } from '@/Components/ui/badge';
import {
    ArrowRightLeft,
    ArrowLeft,
    Plus,
    Trash2,
    Save,
    Search,
    Package,
    Building2,
    ArrowRight,
    AlertCircle,
    Loader2
} from 'lucide-react';
import axios from 'axios';
import { toast } from 'react-hot-toast';

export default function TransferCreate({ shops, products, currentShopId }) {
    const { auth } = usePage().props;

    const [loading, setLoading] = useState(false);
    const [fromShopId, setFromShopId] = useState(currentShopId || '');
    const [toShopId, setToShopId] = useState('');
    const [notes, setNotes] = useState('');
    const [items, setItems] = useState([]);
    const [searchTerm, setSearchTerm] = useState('');

    // Filtrer les produits selon la recherche
    const filteredProducts = products.filter(p =>
        p.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
        (p.code && p.code.toLowerCase().includes(searchTerm.toLowerCase()))
    );

    // Ajouter un produit au transfert
    const addItem = (product) => {
        if (items.find(i => i.product_id === product.id)) {
            toast.error('Ce produit est déjà dans le transfert');
            return;
        }
        setItems([...items, {
            product_id: product.id,
            product_name: product.name,
            product_code: product.code,
            current_stock: product.stock,
            quantity: 1
        }]);
        setSearchTerm('');
    };

    // Mettre à jour la quantité
    const updateQuantity = (productId, quantity) => {
        setItems(items.map(item =>
            item.product_id === productId
                ? { ...item, quantity: Math.max(1, parseInt(quantity) || 1) }
                : item
        ));
    };

    // Supprimer un item
    const removeItem = (productId) => {
        setItems(items.filter(item => item.product_id !== productId));
    };

    // Calculer le total
    const totalQuantity = items.reduce((sum, item) => sum + item.quantity, 0);

    // Soumettre le formulaire
    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!fromShopId) {
            toast.error('Sélectionnez le magasin source');
            return;
        }
        if (!toShopId) {
            toast.error('Sélectionnez le magasin destination');
            return;
        }
        if (fromShopId === toShopId) {
            toast.error('Les magasins source et destination doivent être différents');
            return;
        }
        if (items.length === 0) {
            toast.error('Ajoutez au moins un produit');
            return;
        }

        // Vérifier les stocks
        const stockErrors = items.filter(item => item.quantity > item.current_stock);
        if (stockErrors.length > 0) {
            toast.error(`Stock insuffisant pour: ${stockErrors.map(e => e.product_name).join(', ')}`);
            return;
        }

        setLoading(true);
        try {
            const response = await axios.post(route('pharmacy.transfers.store'), {
                from_shop_id: fromShopId,
                to_shop_id: toShopId,
                notes,
                items: items.map(item => ({
                    product_id: item.product_id,
                    quantity: item.quantity
                }))
            });

            if (response.data.success) {
                toast.success('Transfert créé avec succès');
                router.visit(route('pharmacy.transfers.show', response.data.transfer_id));
            } else {
                toast.error(response.data.message || 'Erreur lors de la création');
            }
        } catch (error) {
            console.error(error);
            toast.error(error.response?.data?.message || 'Erreur lors de la création du transfert');
        } finally {
            setLoading(false);
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex justify-between items-center">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">
                        Nouveau Transfert
                    </h2>
                    <Link href={route('pharmacy.transfers.index')}>
                        <Button variant="outline">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Retour
                        </Button>
                    </Link>
                </div>
            }
        >
            <Head title="Nouveau Transfert" />

            <div className="py-6">
                <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Sélection des magasins */}
                        <Card className="bg-white dark:bg-slate-800">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Building2 className="h-5 w-5 text-blue-600" />
                                    Magasins
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="grid md:grid-cols-3 gap-6 items-end">
                                    {/* Magasin source */}
                                    <div>
                                        <Label className="text-red-600 flex items-center gap-2 mb-2">
                                            <Building2 className="h-4 w-4" />
                                            Magasin Source (Expéditeur)
                                        </Label>
                                        <select
                                            value={fromShopId}
                                            onChange={(e) => setFromShopId(e.target.value)}
                                            className="w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white px-3"
                                            required
                                        >
                                            <option value="">Sélectionner...</option>
                                            {shops.map(shop => (
                                                <option key={shop.id} value={shop.id} disabled={shop.id.toString() === toShopId}>
                                                    {shop.name} ({shop.code})
                                                </option>
                                            ))}
                                        </select>
                                    </div>

                                    {/* Flèche */}
                                    <div className="hidden md:flex justify-center">
                                        <div className="h-10 w-10 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center">
                                            <ArrowRight className="h-5 w-5 text-blue-600" />
                                        </div>
                                    </div>

                                    {/* Magasin destination */}
                                    <div>
                                        <Label className="text-green-600 flex items-center gap-2 mb-2">
                                            <Building2 className="h-4 w-4" />
                                            Magasin Destination (Récepteur)
                                        </Label>
                                        <select
                                            value={toShopId}
                                            onChange={(e) => setToShopId(e.target.value)}
                                            className="w-full h-10 rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white px-3"
                                            required
                                        >
                                            <option value="">Sélectionner...</option>
                                            {shops.map(shop => (
                                                <option key={shop.id} value={shop.id} disabled={shop.id.toString() === fromShopId}>
                                                    {shop.name} ({shop.code})
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Ajout de produits */}
                        <Card className="bg-white dark:bg-slate-800">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <Package className="h-5 w-5 text-blue-600" />
                                    Produits à transférer
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {/* Recherche */}
                                <div className="relative mb-4">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        type="text"
                                        placeholder="Rechercher un produit par nom ou code..."
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                        className="pl-10"
                                    />

                                    {/* Dropdown de résultats */}
                                    {searchTerm && (
                                        <div className="absolute z-10 w-full mt-1 bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-600 rounded-md shadow-lg max-h-60 overflow-y-auto">
                                            {filteredProducts.length === 0 ? (
                                                <div className="p-3 text-center text-gray-500">Aucun produit trouvé</div>
                                            ) : (
                                                filteredProducts.slice(0, 10).map(product => (
                                                    <div
                                                        key={product.id}
                                                        onClick={() => addItem(product)}
                                                        className="p-3 hover:bg-gray-50 dark:hover:bg-slate-700 cursor-pointer border-b border-gray-100 dark:border-slate-700 last:border-0"
                                                    >
                                                        <div className="flex justify-between items-center">
                                                            <div>
                                                                <span className="font-medium text-gray-900 dark:text-white">{product.name}</span>
                                                                {product.code && (
                                                                    <span className="ml-2 text-xs text-gray-500">({product.code})</span>
                                                                )}
                                                            </div>
                                                            <Badge variant={product.stock > 0 ? 'default' : 'destructive'}>
                                                                Stock: {product.stock}
                                                            </Badge>
                                                        </div>
                                                    </div>
                                                ))
                                            )}
                                        </div>
                                    )}
                                </div>

                                {/* Liste des items */}
                                {items.length === 0 ? (
                                    <div className="text-center py-12 border-2 border-dashed border-gray-200 dark:border-slate-700 rounded-lg">
                                        <Package className="h-12 w-12 mx-auto text-gray-300 dark:text-gray-600 mb-3" />
                                        <p className="text-gray-500 dark:text-gray-400">
                                            Recherchez et ajoutez des produits à transférer
                                        </p>
                                    </div>
                                ) : (
                                    <div className="overflow-x-auto">
                                        <table className="w-full">
                                            <thead>
                                                <tr className="border-b border-gray-200 dark:border-slate-700">
                                                    <th className="text-left py-3 px-4 text-xs font-medium text-gray-500 uppercase">Produit</th>
                                                    <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 uppercase">Stock actuel</th>
                                                    <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 uppercase">Quantité</th>
                                                    <th className="text-center py-3 px-4 text-xs font-medium text-gray-500 uppercase">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-gray-200 dark:divide-slate-700">
                                                {items.map((item) => (
                                                    <tr key={item.product_id}>
                                                        <td className="py-3 px-4">
                                                            <div className="font-medium text-gray-900 dark:text-white">{item.product_name}</div>
                                                            {item.product_code && (
                                                                <div className="text-xs text-gray-500">{item.product_code}</div>
                                                            )}
                                                        </td>
                                                        <td className="py-3 px-4 text-center">
                                                            <Badge variant={item.current_stock >= item.quantity ? 'default' : 'destructive'}>
                                                                {item.current_stock}
                                                            </Badge>
                                                        </td>
                                                        <td className="py-3 px-4">
                                                            <div className="flex justify-center">
                                                                <Input
                                                                    type="number"
                                                                    min="1"
                                                                    max={item.current_stock}
                                                                    value={item.quantity}
                                                                    onChange={(e) => updateQuantity(item.product_id, e.target.value)}
                                                                    className="w-20 text-center"
                                                                />
                                                            </div>
                                                            {item.quantity > item.current_stock && (
                                                                <div className="flex items-center justify-center gap-1 mt-1 text-xs text-red-500">
                                                                    <AlertCircle className="h-3 w-3" />
                                                                    Stock insuffisant
                                                                </div>
                                                            )}
                                                        </td>
                                                        <td className="py-3 px-4 text-center">
                                                            <Button
                                                                type="button"
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => removeItem(item.product_id)}
                                                                className="text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                            <tfoot>
                                                <tr className="bg-gray-50 dark:bg-slate-700/50">
                                                    <td colSpan="2" className="py-3 px-4 text-right font-medium">
                                                        Total ({items.length} produit{items.length > 1 ? 's' : ''})
                                                    </td>
                                                    <td className="py-3 px-4 text-center font-bold text-lg">
                                                        {totalQuantity} unités
                                                    </td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Notes */}
                        <Card className="bg-white dark:bg-slate-800">
                            <CardHeader>
                                <CardTitle>Notes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <textarea
                                    value={notes}
                                    onChange={(e) => setNotes(e.target.value)}
                                    placeholder="Notes ou commentaires (optionnel)..."
                                    rows={3}
                                    className="w-full rounded-md border border-gray-300 dark:border-slate-600 bg-white dark:bg-slate-800 text-gray-900 dark:text-white px-3 py-2"
                                />
                            </CardContent>
                        </Card>

                        {/* Actions */}
                        <div className="flex justify-end gap-3">
                            <Link href={route('pharmacy.transfers.index')}>
                                <Button type="button" variant="outline">
                                    Annuler
                                </Button>
                            </Link>
                            <Button
                                type="submit"
                                disabled={loading || items.length === 0}
                                className="bg-blue-600 hover:bg-blue-700"
                            >
                                {loading ? (
                                    <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                ) : (
                                    <Save className="h-4 w-4 mr-2" />
                                )}
                                Créer le Transfert
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
