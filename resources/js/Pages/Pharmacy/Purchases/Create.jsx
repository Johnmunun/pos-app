import React, { useState, useEffect } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Package, Plus, Trash2, RefreshCw } from 'lucide-react';
import axios from 'axios';
import { useToast } from '@/Components/ui/use-toast';

export default function PurchasesCreate({ auth, products = [], suppliers = [], routePrefix = 'pharmacy' }) {
    const { toast } = useToast();
    const { shop } = usePage().props;
    const defaultCurrency = shop?.currency || 'CDF';
    
    // Debug: Log des données reçues
    console.log('🔍 PurchasesCreate - Debug:', {
        suppliers_count: suppliers?.length || 0,
        suppliers: suppliers,
        products_count: products?.length || 0,
        routePrefix,
    });
    
    const [supplierId, setSupplierId] = useState('');
    const [expectedAt, setExpectedAt] = useState('');
    const [currency, setCurrency] = useState(defaultCurrency);
    const [lines, setLines] = useState([{ product_id: '', product_name: '', ordered_quantity: 1, unit_cost: 0 }]);
    const [submitting, setSubmitting] = useState(false);
    const [reloadingSuppliers, setReloadingSuppliers] = useState(false);
    const [currentSuppliers, setCurrentSuppliers] = useState(suppliers);

    // Mettre à jour la liste des fournisseurs quand elle change
    useEffect(() => {
        console.log('🔍 useEffect - suppliers changed:', {
            suppliers_count: suppliers?.length || 0,
            suppliers: suppliers,
        });
        setCurrentSuppliers(suppliers);
    }, [suppliers]);

    // Recharger la liste des fournisseurs
    const handleReloadSuppliers = async () => {
        setReloadingSuppliers(true);
        try {
            await router.reload({ only: ['suppliers'], preserveState: true });
            toast({ title: 'Liste des fournisseurs mise à jour' });
        } catch (error) {
            toast({ title: 'Erreur', description: 'Impossible de recharger la liste', variant: 'destructive' });
        } finally {
            setReloadingSuppliers(false);
        }
    };

    const addLine = () => {
        setLines([...lines, { product_id: '', product_name: '', ordered_quantity: 1, unit_cost: 0 }]);
    };

    const setLineProduct = (index, product) => {
        const next = [...lines];
        // S'assurer que ordered_quantity est toujours défini et >= 1
        const currentQuantity = next[index]?.ordered_quantity ?? 1;
        next[index] = { 
            ...next[index], 
            product_id: product.id, 
            product_name: product.name, 
            ordered_quantity: currentQuantity >= 1 ? currentQuantity : 1,
            unit_cost: product.cost_amount ?? 0 
        };
        setLines(next);
    };

    const updateLine = (index, field, value) => {
        const next = [...lines];
        // S'assurer que ordered_quantity est toujours un nombre valide >= 1
        if (field === 'ordered_quantity') {
            const numValue = Number(value);
            if (isNaN(numValue) || numValue < 1) {
                // Si la valeur est invalide, utiliser 1 comme valeur par défaut
                next[index] = { ...next[index], [field]: 1 };
            } else {
                next[index] = { ...next[index], [field]: numValue };
            }
        } else if (field === 'unit_cost') {
            // S'assurer que unit_cost est toujours un nombre valide >= 0
            const numValue = Number(value);
            if (isNaN(numValue) || numValue < 0) {
                next[index] = { ...next[index], [field]: 0 };
            } else {
                next[index] = { ...next[index], [field]: numValue };
            }
        } else {
            next[index] = { ...next[index], [field]: value };
        }
        setLines(next);
    };

    const removeLine = (index) => {
        setLines(lines.filter((_, i) => i !== index));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!supplierId) {
            toast({ title: 'Sélectionnez un fournisseur', variant: 'destructive' });
            return;
        }
        // Valider et nettoyer les lignes avant l'envoi
        const validLines = lines
            .map((l, index) => {
                // Vérifier que l'objet ligne existe
                if (!l || typeof l !== 'object') {
                    console.warn(`Ligne ${index} - n'est pas un objet valide:`, l);
                    return null;
                }
                
                // Vérifier que tous les champs requis sont présents et valides
                if (!l.product_id || l.product_id === '' || l.product_id === null || l.product_id === undefined) {
                    console.warn(`Ligne ${index} - product_id manquant ou vide:`, l);
                    return null;
                }
                
                // S'assurer que ordered_quantity existe, sinon utiliser 1
                const rawQuantity = l.ordered_quantity ?? l.quantity ?? 1;
                const quantity = Number(rawQuantity);
                if (isNaN(quantity) || quantity <= 0) {
                    console.warn(`Ligne ${index} - ordered_quantity invalide:`, {
                        ordered_quantity: l.ordered_quantity,
                        quantity: l.quantity,
                        rawQuantity: rawQuantity,
                        quantityNumber: quantity,
                        line: l
                    });
                    return null;
                }
                
                // S'assurer que unit_cost existe, sinon utiliser 0
                const rawUnitCost = l.unit_cost ?? l.cost ?? 0;
                const unitCost = Number(rawUnitCost);
                if (isNaN(unitCost) || unitCost < 0) {
                    console.warn(`Ligne ${index} - unit_cost invalide:`, {
                        unit_cost: l.unit_cost,
                        cost: l.cost,
                        rawUnitCost: rawUnitCost,
                        unitCostNumber: unitCost,
                        line: l
                    });
                    return null;
                }
                
                return {
                    product_id: String(l.product_id),
                    ordered_quantity: quantity,
                    unit_cost: unitCost,
                };
            })
            .filter(l => l !== null);
            
        if (validLines.length === 0) {
            toast({ title: 'Ajoutez au moins une ligne avec produit et quantité', variant: 'destructive' });
            return;
        }
        setSubmitting(true);
        try {
            // Mapper les lignes avec validation stricte pour s'assurer que quantity est toujours présent
            const payloadLines = validLines.map((l, index) => {
                // Vérifier que l'objet ligne existe
                if (!l || typeof l !== 'object') {
                    console.error(`Ligne ${index} - n'est pas un objet:`, l);
                    return null;
                }
                
                // Vérifier que product_id existe
                const productId = l.product_id;
                if (!productId || productId === '' || productId === null || productId === undefined) {
                    console.error(`Ligne ${index} - product_id manquant:`, l);
                    return null;
                }
                
                // Extraire quantity depuis ordered_quantity (ou quantity si présent)
                const rawQuantity = l.ordered_quantity ?? l.quantity ?? null;
                if (rawQuantity === null || rawQuantity === undefined || rawQuantity === '') {
                    console.error(`Ligne ${index} - ordered_quantity/quantity manquant:`, {
                        ordered_quantity: l.ordered_quantity,
                        quantity: l.quantity,
                        line: l
                    });
                    return null;
                }
                
                const quantity = Number(rawQuantity);
                if (isNaN(quantity) || quantity <= 0) {
                    console.error(`Ligne ${index} - quantity invalide:`, {
                        ordered_quantity: l.ordered_quantity,
                        quantity: l.quantity,
                        rawQuantity: rawQuantity,
                        quantityNumber: quantity,
                        line: l
                    });
                    return null;
                }
                
                // Extraire unit_cost
                const rawUnitCost = l.unit_cost ?? l.cost ?? null;
                if (rawUnitCost === null || rawUnitCost === undefined || rawUnitCost === '') {
                    console.error(`Ligne ${index} - unit_cost manquant:`, {
                        unit_cost: l.unit_cost,
                        cost: l.cost,
                        line: l
                    });
                    return null;
                }
                
                const unitCost = Number(rawUnitCost);
                if (isNaN(unitCost) || unitCost < 0) {
                    console.error(`Ligne ${index} - unit_cost invalide:`, {
                        unit_cost: l.unit_cost,
                        cost: l.cost,
                        rawUnitCost: rawUnitCost,
                        unitCostNumber: unitCost,
                        line: l
                    });
                    return null;
                }
                
                // Créer un objet propre avec les champs attendus par le backend
                const cleanLine = {
                    product_id: String(productId),
                    quantity: quantity, // Le backend attend 'quantity', pas 'ordered_quantity'
                    unit_cost: unitCost,
                };
                
                // Vérification finale que tous les champs sont présents
                if (!cleanLine.product_id || !cleanLine.quantity || !cleanLine.unit_cost) {
                    console.error(`Ligne ${index} - ligne incomplète après nettoyage:`, cleanLine);
                    return null;
                }
                
                return cleanLine;
            }).filter(line => {
                // Filtrage final
                if (!line) return false;
                if (!line.product_id || !line.quantity || line.quantity <= 0 || !line.unit_cost || line.unit_cost < 0) {
                    console.error('Ligne filtrée car invalide:', line);
                    return false;
                }
                return true;
            });
            
            if (payloadLines.length === 0) {
                toast({ 
                    title: 'Erreur de validation', 
                    description: 'Aucune ligne valide après validation. Vérifiez que toutes les lignes ont un produit, une quantité > 0 et un prix >= 0.', 
                    variant: 'destructive' 
                });
                setSubmitting(false);
                return;
            }
            
            // Vérification finale stricte avant envoi
            const finalPayloadLines = payloadLines.map((line, idx) => {
                // Créer un nouvel objet avec seulement les propriétés nécessaires
                const cleanLine = {
                    product_id: String(line.product_id),
                    quantity: Number(line.quantity),
                    unit_cost: Number(line.unit_cost),
                };
                
                // Vérifier que quantity est bien un nombre valide
                if (isNaN(cleanLine.quantity) || cleanLine.quantity <= 0) {
                    console.error(`Ligne ${idx} a une quantité invalide après nettoyage:`, cleanLine);
                    return null;
                }
                
                // Vérifier que unit_cost est bien un nombre valide
                if (isNaN(cleanLine.unit_cost) || cleanLine.unit_cost < 0) {
                    console.error(`Ligne ${idx} a un unit_cost invalide après nettoyage:`, cleanLine);
                    return null;
                }
                
                return cleanLine;
            }).filter(line => line !== null && line.quantity > 0 && line.unit_cost >= 0);

            if (finalPayloadLines.length === 0) {
                toast({ 
                    title: 'Erreur de validation', 
                    description: 'Aucune ligne valide après nettoyage final. Veuillez vérifier que toutes les lignes ont un produit, une quantité > 0 et un prix >= 0.', 
                    variant: 'destructive' 
                });
                setSubmitting(false);
                return;
            }
            
            // Log pour déboguer
            console.log('Envoi des lignes d\'achat:', {
                linesCount: finalPayloadLines.length,
                lines: finalPayloadLines,
                firstLine: finalPayloadLines[0],
                firstLineKeys: finalPayloadLines[0] ? Object.keys(finalPayloadLines[0]) : [],
                firstLineQuantity: finalPayloadLines[0]?.quantity,
                firstLineQuantityType: typeof finalPayloadLines[0]?.quantity,
                payloadStringified: JSON.stringify({
                    supplier_id: supplierId,
                    currency,
                    expected_at: expectedAt || null,
                    lines: finalPayloadLines,
                }),
            });
            
            const payload = {
                supplier_id: supplierId,
                currency,
                expected_at: expectedAt || null,
                lines: finalPayloadLines,
            };
            
            await axios.post(route(`${routePrefix}.purchases.store`), payload, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
            });
            toast({ title: 'Bon de commande créé' });
            router.visit(route(`${routePrefix}.purchases.index`));
        } catch (err) {
            console.error('Erreur lors de la création du bon de commande:', err);
            const errorMessage = err.response?.data?.message || 
                                (err.response?.data?.errors ? JSON.stringify(err.response.data.errors) : null) ||
                                'Erreur lors de la création du bon de commande';
            toast({ title: 'Erreur', description: errorMessage, variant: 'destructive' });
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-white leading-tight">Nouveau bon de commande</h2>
                    <Button variant="outline" asChild>
                        <Link href={route(`${routePrefix}.purchases.index`)}>Retour</Link>
                    </Button>
                </div>
            }
        >
            <Head title="Nouveau bon de commande" />
            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <form onSubmit={handleSubmit}>
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle>Fournisseur & dates</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <div className="flex items-center justify-between mb-1">
                                        <label className="block text-sm font-medium">Fournisseur *</label>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={handleReloadSuppliers}
                                            disabled={reloadingSuppliers}
                                            className="h-7 px-2 text-xs"
                                        >
                                            <RefreshCw className={`h-3 w-3 mr-1 ${reloadingSuppliers ? 'animate-spin' : ''}`} />
                                            Actualiser
                                        </Button>
                                    </div>
                                    <select
                                        className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                        value={supplierId}
                                        onChange={(e) => setSupplierId(e.target.value)}
                                        required
                                    >
                                        <option value="">Choisir... ({currentSuppliers?.length || 0} fournisseurs disponibles)</option>
                                        {currentSuppliers && currentSuppliers.length > 0 ? (
                                            currentSuppliers.map((s) => (
                                                <option key={s.id} value={s.id}>{s.name}</option>
                                            ))
                                        ) : (
                                            <option value="" disabled>Aucun fournisseur disponible</option>
                                        )}
                                    </select>
                                </div>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Date livraison prévue</label>
                                        <Input type="date" value={expectedAt} onChange={(e) => setExpectedAt(e.target.value)} />
                                    </div>
                                    <div>
                                        <label className="block text-sm font-medium mb-1">Devise</label>
                                        <select className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white" value={currency} onChange={(e) => setCurrency(e.target.value)}>
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
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <Card className="mb-6">
                            <CardHeader>
                                <div className="flex justify-between items-center">
                                    <CardTitle className="flex items-center">
                                        <Package className="h-5 w-5 mr-2" />
                                        Lignes
                                    </CardTitle>
                                    <Button type="button" variant="outline" size="sm" onClick={addLine}><Plus className="h-4 w-4 mr-1" /> Ligne</Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {lines.map((line, index) => (
                                        <div key={index} className="flex flex-wrap items-end gap-4 p-4 border dark:border-gray-700 rounded">
                                            <div className="flex-1 min-w-[200px]">
                                                <label className="block text-sm font-medium mb-1">Produit</label>
                                                <select
                                                    className="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                                                    value={line.product_id}
                                                    onChange={(e) => {
                                                        const p = products.find(pr => pr.id === e.target.value);
                                                        if (p) setLineProduct(index, p);
                                                    }}
                                                >
                                                    <option value="">Choisir...</option>
                                                    {products.map((p) => (
                                                        <option key={p.id} value={p.id}>{p.name} ({p.code})</option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div className="w-24">
                                                <label className="block text-sm font-medium mb-1">Qté *</label>
                                                <Input 
                                                    type="number" 
                                                    min={1} 
                                                    step={1}
                                                    required
                                                    value={line.ordered_quantity || 1} 
                                                    onChange={(e) => {
                                                        const value = e.target.value;
                                                        // S'assurer que la valeur est toujours un nombre valide >= 1
                                                        const numValue = parseInt(value, 10);
                                                        if (!isNaN(numValue) && numValue >= 1) {
                                                            updateLine(index, 'ordered_quantity', numValue);
                                                        } else if (value === '' || value === '0') {
                                                            // Si vide ou 0, définir à 1
                                                            updateLine(index, 'ordered_quantity', 1);
                                                        }
                                                    }}
                                                    onBlur={(e) => {
                                                        // S'assurer que la valeur est au moins 1 quand on quitte le champ
                                                        const numValue = parseInt(e.target.value, 10);
                                                        if (isNaN(numValue) || numValue < 1) {
                                                            updateLine(index, 'ordered_quantity', 1);
                                                        }
                                                    }}
                                                />
                                            </div>
                                            <div className="w-28">
                                                <label className="block text-sm font-medium mb-1">Prix d'achat</label>
                                                <Input type="number" step="0.01" min={0} value={line.unit_cost} onChange={(e) => updateLine(index, 'unit_cost', parseFloat(e.target.value) || 0)} />
                                            </div>
                                            <Button type="button" variant="ghost" size="sm" onClick={() => removeLine(index)} disabled={lines.length <= 1}>
                                                <Trash2 className="h-4 w-4 text-red-500" />
                                            </Button>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" asChild>
                                <Link href={route(`${routePrefix}.purchases.index`)}>Annuler</Link>
                            </Button>
                            <Button type="submit" disabled={submitting}>Enregistrer le bon de commande</Button>
                        </div>
                    </form>
                </div>
            </div>
        </AppLayout>
    );
}
