import { usePage } from '@inertiajs/react';

/**
 * Retourne les URLs du storefront selon le contexte (admin prévisualisation ou vitrine publique sous-domaine).
 * À utiliser dans toutes les pages storefront pour que les liens restent sur le sous-domaine en public.
 * Utilise la globale route() fournie par Ziggy (@routes dans app.blade.php).
 */
export default function useStorefrontLinks() {
    const { storefrontIsPublic, storefrontPublicBaseUrl } = usePage().props;
    const route = typeof window !== 'undefined' ? window.route : null;
    const pathname = typeof window !== 'undefined' ? window.location.pathname : '';
    const isPublicByPath = pathname && !pathname.startsWith('/ecommerce/storefront');

    if (storefrontIsPublic && storefrontPublicBaseUrl) {
        const base = storefrontPublicBaseUrl.replace(/\/$/, '');
        return {
            index: () => base + '/',
            catalog: () => base + '/catalog',
            cart: () => base + '/cart',
            blog: () => base + '/blog',
            product: (id) => base + '/product/' + id,
            page: (slug) => base + '/page/' + slug,
            blogShow: (slug) => base + '/blog/' + slug,
        };
    }

    // Fallback robuste: en vitrine publique (sous-domaine), générer des liens relatifs publics.
    if (isPublicByPath) {
        const base = `${window?.location?.origin || ''}`.replace(/\/$/, '');
        return {
            index: () => base + '/',
            catalog: () => base + '/catalog',
            cart: () => base + '/cart',
            blog: () => base + '/blog',
            product: (id) => base + '/product/' + id,
            page: (slug) => base + '/page/' + slug,
            blogShow: (slug) => base + '/blog/' + slug,
        };
    }

    if (!route) {
        const fallback = (path) => `${window?.location?.origin || ''}/ecommerce/storefront${path}`;
        return {
            index: () => fallback(''),
            catalog: () => fallback('/catalog'),
            cart: () => fallback('/cart'),
            blog: () => fallback('/blog'),
            product: (id) => fallback('/product/' + id),
            page: (slug) => fallback('/page/' + slug),
            blogShow: (slug) => fallback('/blog/' + slug),
        };
    }

    return {
        index: () => route('ecommerce.storefront.index'),
        catalog: () => route('ecommerce.storefront.catalog'),
        cart: () => route('ecommerce.storefront.cart'),
        blog: () => route('ecommerce.storefront.blog'),
        product: (id) => route('ecommerce.storefront.product', id),
        page: (slug) => route('ecommerce.storefront.page', slug),
        blogShow: (slug) => route('ecommerce.storefront.blog.show', slug),
    };
}
