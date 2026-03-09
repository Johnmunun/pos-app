import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import { Card, CardContent } from '@/Components/ui/card';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Star, Plus, Pencil, Trash2 } from 'lucide-react';
import { Pagination } from '@/Components/ui/pagination';

export default function EcommerceReviewsIndex({ reviews }) {
    const renderStars = (rating) => {
        return (
            <div className="flex gap-0.5">
                {[1, 2, 3, 4, 5].map((s) => (
                    <Star
                        key={s}
                        className={`h-4 w-4 ${s <= rating ? 'text-amber-500 fill-amber-500' : 'text-gray-300 dark:text-gray-600'}`}
                    />
                ))}
            </div>
        );
    };

    return (
        <AppLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="font-semibold text-xl text-gray-800 dark:text-gray-100">Avis clients</h2>
                    <Button asChild>
                        <Link href={route('ecommerce.reviews.create')} className="inline-flex items-center gap-2">
                            <Plus className="h-4 w-4 shrink-0" />
                            Nouvel avis
                        </Link>
                    </Button>
                </div>
            }
        >
            <Head title="Avis - E-commerce" />
            <div className="py-6">
                <Card className="bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700">
                    <CardContent className="p-0">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-gray-200 dark:border-slate-700">
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Produit</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Client</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Note</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Commentaire</th>
                                    <th className="text-left px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Statut</th>
                                    <th className="text-right px-4 py-3 font-medium text-gray-700 dark:text-gray-300">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {!reviews.data?.length ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-8 text-center text-gray-500">
                                            Aucun avis. <Link href={route('ecommerce.reviews.create')} className="text-blue-600 hover:underline">Créer un avis</Link>
                                        </td>
                                    </tr>
                                ) : (
                                    reviews.data.map((r) => (
                                        <tr key={r.id} className="border-b border-gray-100 dark:border-slate-700/50 hover:bg-gray-50 dark:hover:bg-slate-700/30">
                                            <td className="px-4 py-3 font-medium">{r.product_name}</td>
                                            <td className="px-4 py-3">{r.customer_name}</td>
                                            <td className="px-4 py-3">{renderStars(r.rating)}</td>
                                            <td className="px-4 py-3 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                                                {r.title && <strong>{r.title} </strong>}
                                                {r.comment}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex gap-1">
                                                    <Badge variant={r.is_approved ? 'default' : 'secondary'}>
                                                        {r.is_approved ? 'Approuvé' : 'En attente'}
                                                    </Badge>
                                                    {r.is_featured && <Badge variant="outline">Vedette</Badge>}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex justify-end gap-1">
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={route('ecommerce.reviews.edit', r.id)}>
                                                            <Pencil className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-red-600 hover:text-red-700"
                                                        onClick={() => {
                                                            if (confirm("Supprimer cet avis ?")) {
                                                                router.delete(route('ecommerce.reviews.destroy', r.id));
                                                            }
                                                        }}
                                                    >
                                                        <Trash2 className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                        {reviews.data?.length > 0 && reviews.last_page > 1 && (
                            <div className="border-t border-gray-200 dark:border-slate-700">
                                <Pagination
                                    pagination={{
                                        current_page: reviews.current_page,
                                        last_page: reviews.last_page,
                                        per_page: reviews.per_page,
                                        total: reviews.total,
                                        from: reviews.from,
                                        to: reviews.to,
                                    }}
                                    routeName="ecommerce.reviews.index"
                                />
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
