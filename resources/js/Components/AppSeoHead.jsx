import { Head } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';

/**
 * Balises SEO pour le site marketing OmniSolution (landing, pages publiques).
 * @param {object} [pageSeo] — surcharge par page
 */
export default function AppSeoHead({ pageSeo = null }) {
    const { appSeo } = usePage().props;
    const defaults = appSeo ?? {};
    const seo = { ...defaults, ...(pageSeo || {}) };

    const title = seo.title || defaults.siteName || 'OmniSolution';
    const description = seo.description || '';
    const keywords = seo.keywords || null;
    const robots = seo.robots || 'index, follow';
    const canonical = seo.canonicalUrl || null;
    const ogImage = seo.ogImage || null;
    const ogType = seo.ogType || 'website';
    const locale = seo.locale || 'fr_FR';
    const jsonLd = seo.jsonLd ?? null;
    const googleVerification = defaults.googleSiteVerification || seo.googleSiteVerification || null;
    const twitterHandle = defaults.twitterHandle || seo.twitterHandle || null;

    return (
        <Head title={title}>
            {googleVerification ? (
                <meta head-key="google-site-verification" name="google-site-verification" content={googleVerification} />
            ) : null}
            {description ? <meta head-key="description" name="description" content={description} /> : null}
            {keywords ? <meta head-key="keywords" name="keywords" content={keywords} /> : null}
            <meta head-key="robots" name="robots" content={robots} />
            {canonical ? <link head-key="canonical" rel="canonical" href={canonical} /> : null}

            <meta head-key="og:locale" property="og:locale" content={locale} />
            <meta head-key="og:type" property="og:type" content={ogType} />
            <meta head-key="og:title" property="og:title" content={title} />
            {description ? <meta head-key="og:description" property="og:description" content={description} /> : null}
            {canonical ? <meta head-key="og:url" property="og:url" content={canonical} /> : null}
            {ogImage ? <meta head-key="og:image" property="og:image" content={ogImage} /> : null}
            {defaults.siteName ? (
                <meta head-key="og:site_name" property="og:site_name" content={defaults.siteName} />
            ) : null}

            <meta head-key="twitter:card" name="twitter:card" content={ogImage ? 'summary_large_image' : 'summary'} />
            <meta head-key="twitter:title" name="twitter:title" content={title} />
            {description ? <meta head-key="twitter:description" name="twitter:description" content={description} /> : null}
            {ogImage ? <meta head-key="twitter:image" name="twitter:image" content={ogImage} /> : null}
            {twitterHandle ? <meta head-key="twitter:site" name="twitter:site" content={twitterHandle} /> : null}

            {jsonLd ? (
                <script
                    head-key="json-ld"
                    type="application/ld+json"
                    dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
                />
            ) : null}
        </Head>
    );
}
