import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'react-hot-toast';
import Drawer from '@/Components/Drawer';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { 
    DollarSign, 
    Percent, 
    Calendar, 
    Package, 
    Save, 
    Loader2,
    Calculator,
    Search
} from 'lucide-react';

export default function SupplierPricingDrawer({ 
    isOpen, 
    onClose, 
    supplierId, 
    supplierName,
    existingPrice = null, 
    onSuccess = null,
    canManage = false,
    routePrefix = 'pharmacy'
}) {
    const { shop } = usePage().props;
    const currency = shop?.currency || 'CDF';
    
    const [products, setProducts] = useState([]);
    const [loadingProducts, setLoadingProducts] = useState(false);
    const [productSearch, setProductSearch] = useState('');
    
    const [data, setData] = useState({
        supplier_id: '',
        product_id: '',
        normal_price: '',
        agreed_price: '',
        tax_rate: '0',
        effective_from: new Date().toISOString().split('T')[0],
    });
    const [errors, setErrors] = useState({});
    const [processing, setProcessing] = useState(false);

    const [selectedProduct, setSelectedProduct] = useState(null);

    useEffect(() => {
        if (isOpen) {
            loadProducts();
        }
    }, [isOpen]);

    useEffect(() => {
        if (supplierId) {
            setData(prev => ({ ...prev, supplier_id: supplierId }));
        }
    }, [supplierId]);

    useEffect(() => {
        if (existingPrice) {
            setData({
                supplier_id: existingPrice.supplier_id || supplierId,
                product_id: existingPrice.product_id || '',
                normal_price: existingPrice.normal_price?.toString() || '',
                agreed_price: existingPrice.agreed_price?.toString() || '',
                tax_rate: existingPrice.tax_rate?.toString() || '0',
                effective_from: existingPrice.effective_from || new Date().toISOString().split('T')[0],
            });
            if (existingPrice.product_id) {
                setSelectedProduct({
                    id: existingPrice.product_id,
                    name: existingPrice.product_name,
                    code: existingPrice.product_code,
                });
            }
        } else {
            setData({
                supplier_id: supplierId || '',
                product_id: '',
                normal_price: '',
                agreed_price: '',
                tax_rate: '0',
                effective_from: new Date().toISOString().split('T')[0],
            });
            setSelectedProduct(null);
        }
        setErrors({});
    }, [existingPrice, supplierId, isOpen]);

    const loadProducts = async () => {
        setLoadingProducts(true);
        try {
            const response = await axios.get(route(`${routePrefix}.products`), {
                params: { per_page: 1000 }
            });
            // Extract products from paginated response
            const productsData = response.data?.products?.data || response.data?.products || [];
            setProducts(Array.isArray(productsData) ? productsData : []);
        } catch (error) {
            console.error('Error loading products:', error);
            setProducts([]);
        } finally {
            setLoadingProducts(false);
        }
    };

    const handleChange = (field, value) => {
        setData(prev => ({ ...prev, [field]: value }));
        if (errors[field]) {
            setErrors(prev => ({ ...prev, [field]: null }));
        }
    };

    const handleProductSelect = (product) => {
        setSelectedProduct(product);
        handleChange('product_id', product.id);
    };

    const calculatePriceWithTax = () => {
        const price = parseFloat(data.agreed_price || data.normal_price) || 0;
        const tax = parseFloat(data.tax_rate) || 0;
        return (price * (1 + tax / 100)).toFixed(2);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        if (!canManage) {
            toast.error('Vous n\'avez pas la permission de gérer les prix fournisseur.');
            return;
        }

        setProcessing(true);
        setErrors({});

        try {
            const payload = {
                supplier_id: data.supplier_id,
                product_id: data.product_id,
                normal_price: parseFloat(data.normal_price) || 0,
                agreed_price: data.agreed_price ? parseFloat(data.agreed_price) : null,
                tax_rate: parseFloat(data.tax_rate) || 0,
                effective_from: data.effective_from,
            };

            const response = await axios.post(route(`${routePrefix}.suppliers.prices.store`), payload);

            if (response.data.success) {
                toast.success(response.data.message);
                onClose();
                if (onSuccess) {
                    onSuccess();
                }
            } else {
                toast.error(response.data.message || 'Une erreur est survenue');
            }
        } catch (error) {
            if (error.response?.status === 422 && error.response?.data?.errors) {
                setErrors(error.response.data.errors);
            } else {
                toast.error(error.response?.data?.message || 'Une erreur est survenue');
            }
        } finally {
            setProcessing(false);
        }
    };

    const filteredProducts = products.filter(p => 
        p.name?.toLowerCase().includes(productSearch.toLowerCase()) ||
        p.code?.toLowerCase().includes(productSearch.toLowerCase())
    );

    return (
        <Drawer
            isOpen={isOpen}
            onClose={onClose}
            title={`Définir un prix - ${supplierName || 'Fournisseur'}`}
            size="lg"
        >
            <form onSubmit={handleSubmit} className="flex flex-col h-full">
                <div className="flex-1 space-y-4 p-4 overflow-y-auto">
                    {/* Sélection du produit */}
                    <div className="space-y-2">
                        <Label className="flex items-center gap-2">
                            <Package className="h-4 w-4" />
                            Produit <span className="text-red-500">*</span>
                        </Label>
                        
                        {selectedProduct ? (
                            <div className="p-3 bg-primary/10 rounded-lg flex items-center justify-between">
                                <div>
                                    <p className="font-medium">{selectedProduct.name}</p>
                                    {selectedProduct.code && (
                                        <p className="text-sm text-gray-500">{selectedProduct.code}</p>
                                    )}
                                </div>
                                {!existingPrice && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => {
                                            setSelectedProduct(null);
                                            handleChange('product_id', '');
                                        }}
                                    >
                                        Changer
                                    </Button>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-2">
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        placeholder="Rechercher un produit..."
                                        value={productSearch}
                                        onChange={(e) => setProductSearch(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                                <div className="max-h-48 overflow-y-auto border rounded-lg divide-y">
                                    {loadingProducts ? (
                                        <div className="p-4 text-center text-gray-500">
                                            <Loader2 className="h-5 w-5 animate-spin mx-auto" />
                                            Chargement...
                                        </div>
                                    ) : filteredProducts.length > 0 ? (
                                        filteredProducts.slice(0, 10).map(product => (
                                            <button
                                                key={product.id}
                                                type="button"
                                                onClick={() => handleProductSelect(product)}
                                                className="w-full px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-800"
                                            >
                                                <p className="font-medium">{product.name}</p>
                                                {product.code && (
                                                    <p className="text-sm text-gray-500">{product.code}</p>
                                                )}
                                            </button>
                                        ))
                                    ) : (
                                        <div className="p-4 text-center text-gray-500">
                                            Aucun produit trouvé
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                        {errors.product_id && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.product_id}</p>
                        )}
                    </div>

                    {/* Prix normal */}
                    <div className="space-y-2">
                        <Label htmlFor="normal_price" className="flex items-center gap-2">
                            <DollarSign className="h-4 w-4" />
                            Prix d'achat normal ({currency}) <span className="text-red-500">*</span>
                        </Label>
                        <Input
                            id="normal_price"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.normal_price}
                            onChange={(e) => handleChange('normal_price', e.target.value)}
                            placeholder="0.00"
                            className={errors.normal_price ? 'border-red-500' : ''}
                        />
                        {errors.normal_price && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.normal_price}</p>
                        )}
                    </div>

                    {/* Prix convenu */}
                    <div className="space-y-2">
                        <Label htmlFor="agreed_price" className="flex items-center gap-2">
                            <DollarSign className="h-4 w-4" />
                            Prix convenu ({currency})
                        </Label>
                        <Input
                            id="agreed_price"
                            type="number"
                            min="0"
                            step="0.01"
                            value={data.agreed_price}
                            onChange={(e) => handleChange('agreed_price', e.target.value)}
                            placeholder="0.00"
                            className={errors.agreed_price ? 'border-red-500' : ''}
                        />
                        <p className="text-xs text-gray-500 dark:text-gray-400">
                            Prix négocié avec ce fournisseur (prioritaire si défini)
                        </p>
                        {errors.agreed_price && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.agreed_price}</p>
                        )}
                    </div>

                    {/* Taux de taxe */}
                    <div className="space-y-2">
                        <Label htmlFor="tax_rate" className="flex items-center gap-2">
                            <Percent className="h-4 w-4" />
                            Taux de taxe (%)
                        </Label>
                        <Input
                            id="tax_rate"
                            type="number"
                            min="0"
                            max="100"
                            step="0.01"
                            value={data.tax_rate}
                            onChange={(e) => handleChange('tax_rate', e.target.value)}
                            placeholder="0"
                            className={errors.tax_rate ? 'border-red-500' : ''}
                        />
                        {errors.tax_rate && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.tax_rate}</p>
                        )}
                    </div>

                    {/* Date d'effet */}
                    <div className="space-y-2">
                        <Label htmlFor="effective_from" className="flex items-center gap-2">
                            <Calendar className="h-4 w-4" />
                            Date d'effet
                        </Label>
                        <Input
                            id="effective_from"
                            type="date"
                            value={data.effective_from}
                            onChange={(e) => handleChange('effective_from', e.target.value)}
                            className={errors.effective_from ? 'border-red-500' : ''}
                        />
                        {errors.effective_from && (
                            <p className="text-sm text-red-600 dark:text-red-400">{errors.effective_from}</p>
                        )}
                    </div>

                    {/* Résumé du calcul */}
                    {(data.normal_price || data.agreed_price) && (
                        <div className="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg space-y-2">
                            <div className="flex items-center gap-2 text-sm font-medium">
                                <Calculator className="h-4 w-4" />
                                Résumé
                            </div>
                            <div className="grid grid-cols-2 gap-2 text-sm">
                                <span className="text-gray-500">Prix effectif HT:</span>
                                <span className="font-medium">
                                    {currency} {parseFloat(data.agreed_price || data.normal_price || 0).toFixed(2)}
                                </span>
                                <span className="text-gray-500">Taxe ({data.tax_rate}%):</span>
                                <span className="font-medium">
                                    {currency} {((parseFloat(data.agreed_price || data.normal_price || 0) * parseFloat(data.tax_rate || 0)) / 100).toFixed(2)}
                                </span>
                                <span className="text-gray-500 font-medium">Prix TTC:</span>
                                <span className="font-bold text-primary">
                                    {currency} {calculatePriceWithTax()}
                                </span>
                            </div>
                        </div>
                    )}
                </div>

                {/* Footer avec boutons */}
                <div className="border-t p-4 flex gap-3">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        className="flex-1"
                        disabled={processing}
                    >
                        Annuler
                    </Button>
                    <Button
                        type="submit"
                        className="flex-1"
                        disabled={processing || !canManage || !data.product_id}
                    >
                        {processing ? (
                            <>
                                <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                                Traitement...
                            </>
                        ) : (
                            <>
                                <Save className="h-4 w-4 mr-2" />
                                Enregistrer
                            </>
                        )}
                    </Button>
                </div>
            </form>
        </Drawer>
    );
}
