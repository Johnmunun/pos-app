import { useEffect, useRef } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import { initFacebookPixel, trackFacebookPageView } from '../../lib/metaPixel';

function initTikTok(pixelId) {
    if (window.ttq) {
        window.ttq.page();
        return;
    }
    const w = window;
    const ttq = w.TiktokAnalyticsObject = 'ttq';
    const q = w.ttq = w.ttq || [];
    q.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie', 'holdConsent', 'revokeConsent', 'grantConsent'];
    q.setAndDefer = function setAndDefer(obj, method) {
        obj[method] = function ttqMethod() {
            obj.push([method].concat(Array.prototype.slice.call(arguments, 0)));
        };
    };
    for (let i = 0; i < q.methods.length; i++) {
        q.setAndDefer(q, q.methods[i]);
    }
    q.instance = function instance(id) {
        const inst = q._i[id] || [];
        for (let n = 0; n < q.methods.length; n++) {
            q.setAndDefer(inst, q.methods[n]);
        }
        return inst;
    };
    q.load = function load(id, opt) {
        const s = 'https://analytics.tiktok.com/i18n/pixel/events.js';
        q._i = q._i || {};
        q._i[id] = [];
        q._i[id]._u = s;
        q._t = q._t || {};
        q._t[id] = +new Date();
        q._o = q._o || {};
        q._o[id] = opt || {};
        const sc = document.createElement('script');
        sc.type = 'text/javascript';
        sc.async = true;
        sc.src = `${s}?sdkid=${id}&lib=ttq`;
        const x = document.getElementsByTagName('script')[0];
        x.parentNode.insertBefore(sc, x);
    };
    w.ttq.load(pixelId);
    w.ttq.page();
}

function initGtag(measurementId) {
    if (window.gtag) {
        window.gtag('config', measurementId, { page_path: window.location.pathname + window.location.search });
        return;
    }
    window.dataLayer = window.dataLayer || [];
    window.gtag = function gtag() {
        window.dataLayer.push(arguments);
    };
    window.gtag('js', new Date());
    window.gtag('config', measurementId, { send_page_view: true });
    const s = document.createElement('script');
    s.async = true;
    s.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(measurementId)}`;
    document.head.appendChild(s);
}

function initGtm(containerId) {
    if (document.querySelector(`script[data-gtm="${containerId}"]`)) return;
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ 'gtm.start': Date.now(), event: 'gtm.js' });
    const s = document.createElement('script');
    s.async = true;
    s.dataset.gtm = containerId;
    s.src = `https://www.googletagmanager.com/gtm.js?id=${encodeURIComponent(containerId)}`;
    document.head.appendChild(s);
}

/**
 * Pixels / tags (plan Pro) + ping audience géo (analytics avancé), vitrine publique uniquement pour le ping.
 */
export default function StorefrontClientBootstrap() {
    const page = usePage();
    const { storefrontClient, storefrontTheme } = page.props;
    const audience = storefrontClient?.audience;
    const tags = storefrontClient?.marketingTags;
    const url = page.url;

    const pixelsInit = useRef(false);

    // Même thème que dans resources/views/app.blade.php — nécessaire après navigation Inertia
    // depuis une page hors vitrine (sinon --sf-* absents → panier / boutons « invisibles »).
    useEffect(() => {
        const root = document.documentElement;
        const theme = storefrontTheme;
        if (theme && typeof theme === 'object' && theme.primary) {
            root.style.setProperty('--sf-primary', theme.primary);
            const secondary = theme.secondary ?? '#d97706';
            root.style.setProperty('--sf-secondary', secondary);
            root.style.setProperty('--sf-primary-hover', secondary);
        } else {
            root.style.removeProperty('--sf-primary');
            root.style.removeProperty('--sf-secondary');
            root.style.removeProperty('--sf-primary-hover');
        }
        return () => {
            root.style.removeProperty('--sf-primary');
            root.style.removeProperty('--sf-secondary');
            root.style.removeProperty('--sf-primary-hover');
        };
    }, [storefrontTheme]);

    useEffect(() => {
        if (!audience?.enabled || !audience.pingPath) return undefined;
        const path = window.location.pathname + window.location.search;
        const payload = JSON.stringify({ path, title: document.title });
        const blob = new Blob([payload], { type: 'application/json' });
        try {
            if (navigator.sendBeacon) {
                navigator.sendBeacon(audience.pingPath, blob);
            } else {
                fetch(audience.pingPath, {
                    method: 'POST',
                    body: payload,
                    headers: { 'Content-Type': 'application/json' },
                    keepalive: true,
                    credentials: 'same-origin',
                }).catch(() => {});
            }
        } catch {
            /* ignore */
        }
        return undefined;
    }, [audience?.enabled, audience?.pingPath, url]);

    useEffect(() => {
        if (!tags || pixelsInit.current) return undefined;
        const hasGtm = !!tags.googleTagManagerId;
        const hasGa = !!tags.googleAnalyticsId && !hasGtm;

        (async () => {
            try {
                if (hasGtm) {
                    initGtm(tags.googleTagManagerId);
                } else if (hasGa) {
                    initGtag(tags.googleAnalyticsId);
                }
                if (tags.facebookPixelId) {
                    initFacebookPixel(tags.facebookPixelId);
                }
                if (tags.tiktokPixelId) {
                    initTikTok(tags.tiktokPixelId);
                }
                pixelsInit.current = true;
            } catch {
                /* ignore third-party */
            }
        })();

        return undefined;
    }, [tags]);

    useEffect(() => {
        if (!tags?.facebookPixelId && !tags?.tiktokPixelId && !(tags?.googleAnalyticsId && !tags?.googleTagManagerId)) {
            return undefined;
        }
        const remove = router.on('success', () => {
            try {
                trackFacebookPageView();
                if (window.ttq) window.ttq.page();
                if (window.gtag && tags.googleAnalyticsId && !tags.googleTagManagerId) {
                    window.gtag('event', 'page_view', {
                        page_path: window.location.pathname + window.location.search,
                    });
                }
            } catch {
                /* ignore */
            }
        });
        return () => remove();
    }, [tags]);

    const metaVerification = tags?.metaVerification;

    return (
        <>
            {metaVerification ? (
                <Head>
                    <meta name="google-site-verification" content={metaVerification} />
                </Head>
            ) : null}
        </>
    );
}
