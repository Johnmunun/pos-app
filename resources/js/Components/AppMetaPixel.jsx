import { useEffect, useRef } from 'react';
import { router, usePage } from '@inertiajs/react';
import { initFacebookPixel, trackFacebookPageView } from '../lib/metaPixel';

/**
 * Pixel Meta pour l’application Inertia (back-office, landing, etc.) — distinct du pixel vitrine boutique.
 */
export default function AppMetaPixel() {
    const page = usePage();
    const onPublicStorefront = page.props.storefrontIsPublic === true;
    const metaPixelId = onPublicStorefront
        ? null
        : (page.props.appMarketing?.metaPixelId ?? null);
    const pixelsInit = useRef(false);

    useEffect(() => {
        if (!metaPixelId || pixelsInit.current) return undefined;
        pixelsInit.current = true;
        try {
            initFacebookPixel(metaPixelId);
        } catch {
            /* ignore third-party */
        }
        return undefined;
    }, [metaPixelId]);

    useEffect(() => {
        if (!metaPixelId) return undefined;
        const remove = router.on('success', () => {
            trackFacebookPageView();
        });
        return () => remove();
    }, [metaPixelId]);

    return null;
}
